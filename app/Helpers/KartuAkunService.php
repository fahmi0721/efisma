<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;

class KartuAkunService
{

    private static function getSaldoAwal(
        $entitas_id,
        $partner_id,
        $akun_gl_id,
        $periode
    ) {
        return (float) DB::table('m_saldo_awal as sa')
            ->join(
                'm_akun_gl as a',
                'a.id',
                '=',
                'sa.akun_gl_id'
            )
            ->where('a.kategori', 'uang_muka')

            ->when($entitas_id !== null, function ($q) use ($entitas_id) {
                $q->where('sa.entitas_id', $entitas_id);
            })

            ->when($partner_id !== null, function ($q) use ($partner_id) {
                $q->where('sa.partner_id', $partner_id);
            })

            ->when($akun_gl_id !== null, function ($q) use ($akun_gl_id) {
                $q->where('sa.akun_gl_id', $akun_gl_id);
            })

            ->where('sa.periode', $periode)
            ->sum('sa.saldo');
    }

    public static function getMonitoring(
        $entitas_id = null,
        $partner_id = null,
        $akun_gl_id = null,
        $periode_awal = null,
        $periode_akhir = null
    ) {
        $awal = Carbon::parse($periode_awal)->startOfDay();
        $akhir = Carbon::parse($periode_akhir)->startOfDay();

        if ($awal->gt($akhir)) {
            throw new \InvalidArgumentException(
                'Periode awal tidak boleh melebihi periode akhir.'
            );
        }

        // Saldo awal diambil berdasarkan bulan periode awal.
        $periode = $awal->format('Y-m');

        $saldoAwal = self::getSaldoAwal(
            $entitas_id,
            $partner_id,
            $akun_gl_id,
            $periode
        );

        // Jika periode awal bukan tanggal 1, hitung mutasi
        // dari awal bulan sampai sehari sebelum periode awal.
        $saldo = $saldoAwal;

        if ($awal->day !== 1) {
            $mutasiSebelum = self::getMutasi(
                $entitas_id,
                $partner_id,
                $akun_gl_id,
                $awal->copy()->startOfMonth(),
                $awal->copy()->subDay()
            );

            foreach ($mutasiSebelum as $item) {
                $saldo += self::hitungMutasi($item);
            }
        }

        $saldoAwalLaporan = $saldo;

        // Mutasi transaksi periode terpilih.
        $mutasi = self::getMutasi(
            $entitas_id,
            $partner_id,
            $akun_gl_id,
            $awal,
            $akhir
        );

        $rows = collect();

        // Baris pertama wajib saldo awal.
        $rows->push([
            'id' => null,
            'kode_jurnal' => null,
            'tanggal' => $awal->format('Y-m-d'),
            'jenis' => 'saldo_awal',
            'akun_gl' => '-',
            'entitas' => '-',
            'partner' => '-',
            'keterangan' => 'SALDO AWAL',
            'debit' => 0,
            'kredit' => 0,
            'saldo' => round($saldoAwalLaporan, 2),
        ]);

        $totalDebit = 0;
        $totalKredit = 0;

        foreach ($mutasi as $item) {

            $debit = (float) $item->debit;
            $kredit = (float) $item->kredit;

            $totalDebit += $debit;
            $totalKredit += $kredit;

            // Menghitung saldo berjalan.
            $saldo += self::hitungMutasi($item);

            $rows->push([
                'id' => $item->id,
                'kode_jurnal' => $item->kode_jurnal,
                'tanggal' => Carbon::parse(
                    $item->tanggal
                )->format('Y-m-d'),

                'jenis' => 'mutasi',

                'akun_gl' => $item->no_akun
                    . ' - ' . $item->akun_nama,

                'entitas' => $item->entitas_nama,

                'partner' => $item->partner_nama,

                'keterangan' => $item->keterangan,

                'debit' => $debit,
                'kredit' => $kredit,
                'saldo' => round($saldo, 2),
            ]);
        }

        return [
            'data' => $rows,
            'summary' => [
                'saldo_awal' => round($saldoAwalLaporan, 2),
                'total_debit' => round($totalDebit, 2),
                'total_kredit' => round($totalKredit, 2),
                'saldo_akhir' => round($saldo, 2),
            ],
        ];
        
    }

    private static function getMutasi(
        $entitas_id,
        $partner_id,
        $akun_gl_id,
        Carbon $awal,
        Carbon $akhir
    ) {
        return DB::table('buku_besar as bb')
            ->join(
                'm_akun_gl as a',
                'a.id',
                '=',
                'bb.akun_id'
            )
            ->leftJoin(
                'm_entitas as e',
                'e.id',
                '=',
                'bb.entitas_id'
            )
            ->leftJoin(
                'm_partner as p',
                'p.id',
                '=',
                'bb.partner_id'
            )

            ->where('a.kategori', 'uang_muka')

            ->when($entitas_id !== null, function ($q) use ($entitas_id) {
                $q->where('bb.entitas_id', $entitas_id);
            })

            ->when($partner_id !== null, function ($q) use ($partner_id) {
                $q->where('bb.partner_id', $partner_id);
            })

            ->when($akun_gl_id !== null, function ($q) use ($akun_gl_id) {
                $q->where('bb.akun_id', $akun_gl_id);
            })

            ->where('bb.tanggal', '>=', $awal->toDateString())
            ->where(
                'bb.tanggal',
                '<',
                $akhir->copy()->addDay()->toDateString()
            )

            ->select(
                'bb.id',
                'bb.kode_jurnal',
                'bb.tanggal',
                'bb.keterangan',
                'bb.debit',
                'bb.kredit',

                'a.no_akun',
                'a.nama as akun_nama',
                'a.saldo_normal',

                'e.nama as entitas_nama',
                'p.nama as partner_nama'
            )

            ->orderBy('bb.tanggal', 'asc')
            ->orderBy('bb.id', 'asc')
            ->get();
    }

    private static function hitungMutasi($item)
    {
        $debit = (float) $item->debit;
        $kredit = (float) $item->kredit;

        $normal = strtolower(trim($item->saldo_normal));

        if (in_array($normal, ['debit', 'debet'])) {
            return $debit - $kredit;
        }

        if ($normal === 'kredit') {
            return $kredit - $debit;
        }

        throw new \UnexpectedValueException(
            'Saldo normal akun GL tidak valid.'
        );
    }
}
