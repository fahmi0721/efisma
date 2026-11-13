<?php

namespace App\Helpers\Akuntansi;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaldoBulananService
{
    public function generate(
        int $entitasId,
        int $akunId,
        string $periode
    ): array {
        if (!extension_loaded('bcmath')) {
            throw new \RuntimeException(
                'Ekstensi BCMath wajib tersedia.'
            );
        }

        $tanggal = \DateTimeImmutable::createFromFormat(
            '!Y-m',
            $periode
        );

        if (!$tanggal || $tanggal->format('Y-m') !== $periode) {
            throw ValidationException::withMessages([
                'periode' => 'Periode harus YYYY-MM.',
            ]);
        }

        $awal = $tanggal->format('Y-m-d');
        $akhir = $tanggal->modify('+1 month')
            ->format('Y-m-d');

        $periodeNext = $tanggal->modify('+1 month')
            ->format('Y-m');

        return DB::transaction(function () use (
            $entitasId,
            $akunId,
            $periode,
            $periodeNext,
            $awal,
            $akhir
        ) {
            // 1. Pengaturan saldo
            $setting = DB::table('m_akun_saldo_setting')
                ->where('entitas_id', $entitasId)
                ->where('akun_gl_id', $akunId)
                ->where('is_active', 1)
                ->lockForUpdate()
                ->first();

            if (!$setting) {
                throw ValidationException::withMessages([
                    'akun_gl_id' =>
                        'Pengaturan saldo belum aktif.',
                ]);
            }

            $metode = $setting->metode_saldo;

            if (!in_array($metode, ['akun', 'partner'], true)) {
                throw ValidationException::withMessages([
                    'akun_gl_id' => 'Metode saldo tidak valid.',
                ]);
            }

            // Versi pertama hanya mendukung carry forward.
            if ($setting->perlakuan_tahun_buku !== 'carry_forward') {
                throw ValidationException::withMessages([
                    'akun_gl_id' =>
                        'Generate untuk reset tahunan belum diaktifkan.',
                ]);
            }

            // 2. Akun GL
            $akun = DB::table('view_akun_transaksi_only as v')
                ->join('m_akun_gl as a', 'a.id', '=', 'v.id')
                ->where('v.id', $akunId)
                ->select('v.id', 'a.saldo_normal')
                ->first();

            if (!$akun || !in_array(
                $akun->saldo_normal,
                ['debet', 'kredit'],
                true
            )) {
                throw ValidationException::withMessages([
                    'akun_gl_id' =>
                        'Akun transaksi atau saldo normal tidak valid.',
                ]);
            }

            // 3. Snapshot bulan target
            $current = DB::table('m_saldo_awal')
                ->where('periode', $periode)
                ->where('entitas_id', $entitasId)
                ->where('akun_gl_id', $akunId)
                ->lockForUpdate()
                ->get();

            // 4. Snapshot bulan berikutnya
            $next = DB::table('m_saldo_awal')
                ->where('periode', $periodeNext)
                ->where('entitas_id', $entitasId)
                ->where('akun_gl_id', $akunId)
                ->lockForUpdate()
                ->get();

            // 5. Pengaman generate ulang
            if ($current->contains(
                fn ($r) => $r->generated_at !== null
            )) {
                throw ValidationException::withMessages([
                    'periode' =>
                        'Saldo periode ini sudah digenerate.',
                ]);
            }

            // Tidak menimpa opening bulan berikutnya.
            if ($next->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'periode' =>
                        'Saldo bulan berikutnya sudah tersedia. '
                        . 'Periksa sebelum generate.',
                ]);
            }

            // 6. Validasi struktur snapshot
            if ($metode === 'akun') {
                if (
                    $current->count() > 1 ||
                    $current->contains(
                        fn ($r) => $r->partner_id !== null
                    )
                ) {
                    throw ValidationException::withMessages([
                        'akun_gl_id' =>
                            'Struktur saldo per akun tidak valid.',
                    ]);
                }
            } else {
                if ($current->contains(
                    fn ($r) => $r->partner_id === null
                )) {
                    throw ValidationException::withMessages([
                        'akun_gl_id' =>
                            'Ada saldo tanpa partner.',
                    ]);
                }

                if (
                    $current->pluck('partner_id')->unique()->count()
                    !== $current->count()
                ) {
                    throw ValidationException::withMessages([
                        'akun_gl_id' =>
                            'Terdapat saldo partner duplikat.',
                    ]);
                }
            }

            // 7. Query mutasi buku besar
            $mutasiQuery = DB::table('buku_besar')
                ->where('entitas_id', $entitasId)
                ->where('akun_id', $akunId)
                ->where('tanggal', '>=', $awal)
                ->where('tanggal', '<', $akhir);

            if ($metode === 'partner') {
                if ((clone $mutasiQuery)
                    ->whereNull('partner_id')
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'akun_gl_id' =>
                            'Ada mutasi tanpa partner.',
                    ]);
                }

                $mutasi = $mutasiQuery
                    ->select('partner_id')
                    ->selectRaw(
                        'CAST(COALESCE(SUM(debit), 0) AS DECIMAL(30,2)) AS debit'
                    )
                    ->selectRaw(
                        'CAST(COALESCE(SUM(kredit), 0) AS DECIMAL(30,2)) AS kredit'
                    )
                    ->groupBy('partner_id')
                    ->get()
                    ->keyBy(fn ($r) => 'P-' . $r->partner_id);
            } else {
                $total = $mutasiQuery
                    ->selectRaw(
                        'CAST(COALESCE(SUM(debit), 0) AS DECIMAL(30,2)) AS debit'
                    )
                    ->selectRaw(
                        'CAST(COALESCE(SUM(kredit), 0) AS DECIMAL(30,2)) AS kredit'
                    )
                    ->first();

                $mutasi = collect(['AKUN' => $total]);
            }

            // 8. Gabungkan opening dan mutasi
            $currentByKey = $current->keyBy(
                fn ($r) => $metode === 'akun'
                    ? 'AKUN'
                    : 'P-' . $r->partner_id
            );

            $keys = $currentByKey->keys()
                ->merge($mutasi->keys())
                ->unique()
                ->values();

            if ($keys->isEmpty()) {
                // Jangan lewati jika histori lama menunjukkan
                // seharusnya terdapat saldo carry forward.
               // 1. Cari saldo periode sebelumnya terlebih dahulu
                $saldoSebelumnya = DB::table('m_saldo_awal')
                    ->where('entitas_id', $entitasId)
                    ->where('akun_gl_id', $akunId)
                    ->where('periode', '<', $periode)
                    ->orderByDesc('periode')
                    ->first();

                // 2. Jika tidak ditemukan, baru periksa histori buku besar
                $adaHistoriBukuBesar = false;

                if (!$saldoSebelumnya) {
                    $adaHistoriBukuBesar = DB::table('buku_besar')
                        ->where('entitas_id', $entitasId)
                        ->where('akun_id', $akunId)
                        ->where('tanggal', '<', $awal)
                        ->exists();
                }

                // 3. Tentukan apakah ada histori sebelumnya
                $adaHistori = $saldoSebelumnya !== null
                    || $adaHistoriBukuBesar;

                if ($adaHistori) {
                    throw ValidationException::withMessages([
                        'periode' =>
                            'Saldo awal tidak ditemukan, padahal akun '
                            . 'memiliki histori. Periksa carry forward.',
                    ]);
                }

                return [
                    'success' => true,
                    'status' => 'skipped',
                    'jumlah_baris' => 0,
                    'message' =>
                        'Tidak ada saldo awal maupun mutasi. Akun dilewati.',
                ];
            }

            $jumlah = 0;

            foreach ($keys as $key) {
                $existing = $currentByKey->get($key);
                $m = $mutasi->get($key);

                $partnerId = $metode === 'akun'
                    ? null
                    : (int) substr($key, 2);

                // 9. Validasi opening baru
                if (!$existing) {
                    $adaHistori = DB::table('buku_besar')
                        ->where('entitas_id', $entitasId)
                        ->where('akun_id', $akunId)
                        ->when(
                            $metode === 'partner',
                            fn ($q) => $q->where(
                                'partner_id',
                                $partnerId
                            )
                        )
                        ->where('tanggal', '<', $awal)
                        ->exists();

                    $adaSaldoHistori = DB::table('m_saldo_awal')
                        ->where('entitas_id', $entitasId)
                        ->where('akun_gl_id', $akunId)
                        ->when(
                            $metode === 'partner',
                            fn ($q) => $q->where(
                                'partner_id',
                                $partnerId
                            ),
                            fn ($q) => $q->whereNull(
                                'partner_id'
                            )
                        )
                        ->where('periode', '<', $periode)
                        ->exists();

                    if ($adaHistori || $adaSaldoHistori) {
                        throw ValidationException::withMessages([
                            'periode' =>
                                'Opening saldo belum tersedia untuk '
                                . ($partnerId ?? 'akun')
                                . '. Periksa histori.',
                        ]);
                    }
                }

                $saldo = (string) ($existing->saldo ?? '0.00');
                $debit = (string) ($m->debit ?? '0.00');
                $kredit = (string) ($m->kredit ?? '0.00');

                // 10. Perhitungan saldo akhir
                $perubahan = $akun->saldo_normal === 'debet'
                    ? bcsub($debit, $kredit, 2)
                    : bcsub($kredit, $debit, 2);

                $saldoAkhir = bcadd(
                    $saldo,
                    $perubahan,
                    2
                );

                $now = now();

                // 11. Update / insert periode target
                $values = [
                    'saldo' => $saldo,
                    'total_debit' => $debit,
                    'total_kredit' => $kredit,
                    'saldo_akhir' => $saldoAkhir,
                    'generated_at' => $now,
                    'updated_at' => $now,
                ];

                if ($existing) {
                    DB::table('m_saldo_awal')
                        ->where('id', $existing->id)
                        ->update($values);
                } else {
                    DB::table('m_saldo_awal')->insert(
                        array_merge($values, [
                            'periode' => $periode,
                            'entitas_id' => $entitasId,
                            'akun_gl_id' => $akunId,
                            'partner_id' => $partnerId,
                            'created_at' => $now,
                        ])
                    );
                }

                // 12. Buat opening periode berikutnya
                DB::table('m_saldo_awal')->insert([
                    'periode' => $periodeNext,
                    'entitas_id' => $entitasId,
                    'akun_gl_id' => $akunId,
                    'partner_id' => $partnerId,
                    'saldo' => $saldoAkhir,
                    'total_debit' => '0.00',
                    'total_kredit' => '0.00',
                    'saldo_akhir' => '0.00',
                    'generated_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $jumlah++;
            }

            return [
                'success' => true,
                'status' => 'completed',
                'periode' => $periode,
                'periode_berikutnya' => $periodeNext,
                'jumlah_baris' => $jumlah,
                'message' =>
                    'Generate saldo berhasil.',
            ];
        }, 3);
    }
}
