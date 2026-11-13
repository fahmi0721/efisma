<?php
namespace App\Helpers;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaldoAwalGeneratorService
{
    private function tanggalPeriode(string $periode): DateTimeImmutable
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode)) {
            throw ValidationException::withMessages([
                'periode' => 'Format periode harus YYYY-MM.',
            ]);
        }

        $tanggal = DateTimeImmutable::createFromFormat(
            '!Y-m',
            $periode
        );

        if (!$tanggal || $tanggal->format('Y-m') !== $periode) {
            throw ValidationException::withMessages([
                'periode' => 'Periode tidak valid.',
            ]);
        }

        return $tanggal;
    }

    private function decimal($value): string
    {
        return bcadd((string) ($value ?? '0'), '0', 2);
    }

    private function saldoAkhir(
        string $saldo,
        string $debit,
        string $kredit,
        string $saldoNormal
    ): string {
        $normal = strtolower(trim($saldoNormal));

        if (in_array($normal, ['debet', 'debit'], true)) {
            return bcsub(bcadd($saldo, $debit, 2), $kredit, 2);
        }

        if (in_array($normal, ['kredit', 'credit'], true)) {
            return bcsub(bcadd($saldo, $kredit, 2), $debit, 2);
        }

        throw ValidationException::withMessages([
            'akun_gl_id' => 'Saldo normal akun tidak dikenal.',
        ]);
    }

    public function preview(
        int $entitasId,
        int $akunId,
        string $periode
    ): array {
        if (!extension_loaded('bcmath')) {
            throw new \RuntimeException(
                'Ekstensi BCMath diperlukan untuk kalkulasi saldo.'
            );
        }

        $tanggal = $this->tanggalPeriode($periode);
        $awal = $tanggal->format('Y-m-d');
        $akhir = $tanggal->modify('+1 month')->format('Y-m-d');
        $periodeSebelumnya = $tanggal
            ->modify('-1 month')
            ->format('Y-m');

        // Hanya akun transaksi yang boleh diproses.
        $akun = DB::table('view_akun_transaksi_only as v')
            ->join('m_akun_gl as a', 'a.id', '=', 'v.id')
            ->where('v.id', $akunId)
            ->select(
                'v.id',
                'v.no_akun',
                'v.nama',
                'a.saldo_normal'
            )
            ->first();

        if (!$akun) {
            throw ValidationException::withMessages([
                'akun_gl_id' => 'Akun transaksi tidak ditemukan.',
            ]);
        }

        $setting = DB::table('m_akun_saldo_setting')
            ->where('entitas_id', $entitasId)
            ->where('akun_gl_id', $akunId)
            ->where('is_active', true)
            ->first();

        if (!$setting) {
            throw ValidationException::withMessages([
                'akun_gl_id' =>
                    'Akun belum memiliki pengaturan saldo aktif.',
            ]);
        }

        // Reset hanya berlaku pada awal tahun buku.
        // Asumsi tahun buku Januari - Desember.
        $resetTahunan = $tanggal->format('m') === '01'
            && $setting->perlakuan_tahun_buku === 'reset_tahunan';

        /*
         * Reset tahunan harus disahkan proses closing.
         * Sampai modul closing dibuat, preview reset diblokir.
         */
        if ($resetTahunan) {
            throw ValidationException::withMessages([
                'periode' =>
                    'Reset tahunan memerlukan closing tahun buku '
                    . 'yang tervalidasi.',
            ]);
        }

        // Snapshot bulan sebelumnya.
        $sebelumnya = DB::table('m_saldo_awal')
            ->where('periode', $periodeSebelumnya)
            ->where('entitas_id', $entitasId)
            ->where('akun_gl_id', $akunId)
            ->get();

        // Snapshot yang sudah ada pada bulan target.
        $current = DB::table('m_saldo_awal')
            ->where('periode', $periode)
            ->where('entitas_id', $entitasId)
            ->where('akun_gl_id', $akunId)
            ->get();

        // Mutasi bulan target dari buku besar.
        $mutasiQuery = DB::table('buku_besar')
            ->where('entitas_id', $entitasId)
            ->where('akun_id', $akunId)
            ->where('tanggal', '>=', $awal)
            ->where('tanggal', '<', $akhir);

        if ($setting->metode_saldo === 'akun') {

            $mutasi = $mutasiQuery
                ->selectRaw(
                    'CAST(COALESCE(SUM(debit),0) AS DECIMAL(30,2)) AS debit'
                )
                ->selectRaw(
                    'CAST(COALESCE(SUM(kredit),0) AS DECIMAL(30,2)) AS kredit'
                )
                ->first();

            $rows = [[
                'partner_id' => null,
                'debit' => $mutasi->debit,
                'kredit' => $mutasi->kredit,
            ]];

        } elseif ($setting->metode_saldo === 'partner') {

            // Transaksi tanpa partner tidak boleh hilang.
            $tanpaPartner = (clone $mutasiQuery)
                ->whereNull('partner_id')
                ->exists();

            if ($tanpaPartner) {
                throw ValidationException::withMessages([
                    'akun_gl_id' =>
                        'Terdapat mutasi tanpa partner. '
                        . 'Lengkapi atau rekonsiliasi partner '
                        . 'sebelum generate.',
                ]);
            }

            $rows = $mutasiQuery
                ->select('partner_id')
                ->selectRaw(
                    'CAST(COALESCE(SUM(debit),0) AS DECIMAL(30,2)) AS debit'
                )
                ->selectRaw(
                    'CAST(COALESCE(SUM(kredit),0) AS DECIMAL(30,2)) AS kredit'
                )
                ->groupBy('partner_id')
                ->get()
                ->map(fn ($item) => [
                    'partner_id' => (int) $item->partner_id,
                    'debit' => $item->debit,
                    'kredit' => $item->kredit,
                ])
                ->all();

        } else {
            throw ValidationException::withMessages([
                'akun_gl_id' => 'Metode saldo tidak valid.',
            ]);
        }

        /*
         * Pastikan struktur snapshot lama sesuai
         * dengan metode konfigurasi.
         */
        if ($setting->metode_saldo === 'akun') {
            foreach ([$sebelumnya, $current] as $collection) {
                if ($collection->count() > 1
                    || $collection->contains(
                        fn ($r) => $r->partner_id !== null
                    )) {
                    throw ValidationException::withMessages([
                        'akun_gl_id' =>
                            'Snapshot saldo memiliki rincian partner, '
                            . 'tetapi konfigurasi menggunakan per akun.',
                    ]);
                }
            }
        } else {
            foreach ([$sebelumnya, $current] as $collection) {
                if ($collection->contains(
                    fn ($r) => $r->partner_id === null
                )) {
                    throw ValidationException::withMessages([
                        'akun_gl_id' =>
                            'Terdapat saldo agregat tanpa partner '
                            . 'pada akun yang menggunakan metode partner.',
                    ]);
                }
            }
        }

        // Deteksi kemungkinan saldo_akhir legacy belum dihitung.
        foreach ($sebelumnya as $row) {
            if (
                bccomp($this->decimal($row->saldo), '0', 2) !== 0
                && bccomp($this->decimal($row->total_debit), '0', 2) === 0
                && bccomp($this->decimal($row->total_kredit), '0', 2) === 0
                && bccomp($this->decimal($row->saldo_akhir), '0', 2) === 0
            ) {
                throw ValidationException::withMessages([
                    'periode' =>
                        'Saldo akhir periode sebelumnya diduga '
                        . 'belum dihitung. Rekonsiliasi diperlukan.',
                ]);
            }
        }

        /*
         * Jika snapshot sebelumnya tidak tersedia,
         * gunakan saldo opening bulan target yang sudah ada.
         * Jangan otomatis menganggap saldo opening nol.
         */
        if ($sebelumnya->isEmpty() && $current->isEmpty()) {
            throw ValidationException::withMessages([
                'periode' =>
                    'Saldo periode sebelumnya atau opening balance '
                    . 'periode ini belum tersedia.',
            ]);
        }

        $sebelumnyaByPartner = $sebelumnya->keyBy(
            fn ($r) => $r->partner_id === null
                ? 'AKUN'
                : 'P-' . $r->partner_id
        );

        $currentByPartner = $current->keyBy(
            fn ($r) => $r->partner_id === null
                ? 'AKUN'
                : 'P-' . $r->partner_id
        );

        $mutasiByPartner = collect($rows)->keyBy(
            fn ($r) => $r['partner_id'] === null
                ? 'AKUN'
                : 'P-' . $r['partner_id']
        );

        // Gabungkan partner dari opening, mutasi dan snapshot existing.
        $keys = $sebelumnyaByPartner->keys()
            ->merge($currentByPartner->keys())
            ->merge($mutasiByPartner->keys())
            ->unique()
            ->values();

        $hasil = [];

        foreach ($keys as $key) {
            $prev = $sebelumnyaByPartner->get($key);
            $existing = $currentByPartner->get($key);
            $mutasi = $mutasiByPartner->get($key);

            $partnerId = $key === 'AKUN'
                ? null
                : (int) substr($key, 2);

            if ($prev) {
                $saldo = $this->decimal($prev->saldo_akhir);
            } elseif ($sebelumnya->isEmpty() && $existing) {
                // Opening balance periode dasar / data migrasi.
                $saldo = $this->decimal($existing->saldo);
            } elseif ($mutasi && !$existing) {
                // Partner baru yang pertama kali bertransaksi
                // pada periode berjalan
                $saldo = '0.00';
            } elseif ($sebelumnya->isNotEmpty()) {
                // Partner baru yang belum memiliki saldo bulan lalu.
                $saldo = '0.00';
            } else {
                throw ValidationException::withMessages([
                    'periode' =>
                        'Opening balance belum lengkap untuk partner '
                        . ($partnerId ?? 'tanpa partner') . '.',
                ]);
            }

            $debit = $this->decimal($mutasi['debit'] ?? '0');
            $kredit = $this->decimal($mutasi['kredit'] ?? '0');

            $hasil[] = [
                'periode' => $periode,
                'entitas_id' => $entitasId,
                'akun_gl_id' => $akunId,
                'no_akun' => $akun->no_akun,
                'nama_akun' => $akun->nama,
                'partner_id' => $partnerId,
                'saldo' => $saldo,
                'total_debit' => $debit,
                'total_kredit' => $kredit,
                'saldo_akhir' => $this->saldoAkhir(
                    $saldo,
                    $debit,
                    $kredit,
                    $akun->saldo_normal
                ),
            ];
        }

        return [
            'periode' => $periode,
            'entitas_id' => $entitasId,
            'akun_gl_id' => $akunId,
            'metode_saldo' => $setting->metode_saldo,
            'perlakuan_tahun_buku' =>
                $setting->perlakuan_tahun_buku,
            'rows' => $hasil,
        ];
    }
}
