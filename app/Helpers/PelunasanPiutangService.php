<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class PelunasanPiutangService
{
    /**
     * Ambil tanggal JP (jurnal pendapatan)
     */
    public static function getTanggalPiutang($jurnalPiutangId)
    {
        return DB::table('jurnal_header')
            ->where('id', $jurnalPiutangId)
            ->value('tanggal');
    }
    /**
     * Ambil total piutang sebuah JP (jurnal pendapatan)
     */
    public static function getTotalPiutang($jurnalPiutangId)
    {
        return DB::table('jurnal_header')
            ->where('id', $jurnalPiutangId)
            ->value('total_debit'); // debit JP = total piutang
    }

    /**
     * Ambil total pelunasan piutang yang sudah pernah dilakukan
     */
    public static function getTotalPelunasan($jurnalPiutangId)
    {
        return DB::table('pelunasan_piutang')
            ->where('jurnal_piutang_id', $jurnalPiutangId)
            ->sum('jumlah');
    }

    /**
     * Hitung sisa piutang
     */
    public static function getSisaPiutang($jurnalPiutangId)
    {
        $total = self::getTotalPiutang($jurnalPiutangId);
        $pelunasan = self::getTotalPelunasan($jurnalPiutangId);

        return $total - $pelunasan;
    }

    /**
     * Validasi pelunasan piutang : 
     * - Nominal tidak boleh melebihi sisa
     * - Tanggal pelunasan tidak boleh < tanggal JP
     */
    public static function validatePelunasanWithDate($jurnalPiutangId, $nominalBaru, $tanggalPelunasan)
    {
        // 1) Hitung sisa piutang
        $total     = self::getTotalPiutang($jurnalPiutangId);
        $paid      = self::getTotalPelunasan($jurnalPiutangId);
        $sisa      = $total - $paid;

        if ($nominalBaru > $sisa) {
            return [
                'status'  => false,
                'message' => "Pelunasan melebihi sisa piutang. Sisa: " . number_format($sisa, 0, ',', '.')
            ];
        }

        // 2) Validasi tanggal JP
        $tanggalJP = self::getTanggalPiutang($jurnalPiutangId);

        if ($tanggalPelunasan < $tanggalJP) {
            return [
                'status'  => false,
                'message' => "Tanggal pelunasan tidak boleh lebih kecil dari tanggal invoice. 
                            Tanggal JP: {$tanggalJP}, Pelunasan: {$tanggalPelunasan}"
            ];
        }

        return ['status' => true];
    }

    /**
     * Insert pelunasan piutang (JKM atau JN)
     */
    public static function insertPelunasan($jurnalKasId, $jurnalPiutangId, $nominal)
    {
        return DB::table('pelunasan_piutang')->insert([
            'jurnal_kas_id'     => $jurnalKasId,
            'jurnal_piutang_id' => $jurnalPiutangId,
            'jumlah'            => $nominal,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}
