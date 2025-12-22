<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class JurnalService
{
    /* =========================================================
       HELPER: CEK KATEGORI AKUN
    ==========================================================*/
    public static function getAkun($akunId)
    {
        return DB::table('m_akun_gl')->where('id', $akunId)->first();
    }

    /* =========================================================
    VALIDASI: JKK TIDAK BOLEH DIPOSTING JIKA SUDAH DIPAKAI PJ
    ==========================================================*/
    public static function validateJKKNotUsed($jkkId)
    {
        // 1. Ada pelunasan yang sudah tercatat (JN posted)
        $dipakaiPosted = DB::table('pelunasan_uang_muka')
            ->where('jurnal_uang_muka_id', $jkkId)
            ->exists();

        // 2. Ada JN draft / belum posting yang sudah menunjuk JKK ini
        $dipakaiDraft = DB::table('jurnal_header')
            ->where('jenis', 'JN')
            ->where('jurnal_id_jkk', $jkkId)
            ->exists();

        if ($dipakaiPosted || $dipakaiDraft) {
            throw new Exception(
                "Jurnal Uang Muka ini tidak dapat diposting karena sudah digunakan sebagai pertanggungjawaban."
            );
        }

        return true;
    }

    public static function isDepositAkun($akun)
    {
        return $akun && $akun->kategori === 'deposito_customer';
    }

    public static function isUangMukaAkun($akun)
    {
        return $akun && $akun->kategori === 'uang_muka';
    }

    public static function isPiutangAkun($akun)
    {
        return $akun && $akun->kategori === 'piutang';
    }

    /* =========================================================
       SALDO & VALIDASI DEPOSIT
    ==========================================================*/
    public static function getSaldoDeposit($akunId, $partnerId, $entitasId)
    {
        $totalIn = DB::table('buku_besar')
            ->where('akun_id', $akunId)
            ->where('partner_id', $partnerId)
            ->where('entitas_id', $entitasId)
            ->sum(DB::raw('kredit'));

        $totalUsed = DB::table('pelunasan_deposit')
            ->where('akun_deposit_id', $akunId)
            ->where('partner_id', $partnerId)
            ->where('entitas_id', $entitasId)
            ->sum('jumlah') ;

        return $totalIn - $totalUsed;
    }


    /* =========================================================
       PARSE RUPIAH
    ==========================================================*/
    public static function parseRupiah($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        // Hapus simbol dan spasi
        $value = str_replace(['Rp', ' ', "\u{A0}"], '', $value);

        // Jika format Indonesia (ada koma)
        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);   // hapus ribuan
            $value = str_replace(',', '.', $value);  // koma → titik
        }

        return (float) $value;
    }

    


    /* =========================================================
       VALIDASI SALDO NORMAL (JP)
    ==========================================================*/
    public static function validateSaldoNormal($akun, $debit, $kredit)
    {
        if ($akun->saldo_normal === 'debet' && $kredit > 0) {
            return "Akun {$akun->no_akun} - {$akun->nama} memiliki saldo normal Debet, tidak boleh di Kredit.";
        }

        if ($akun->saldo_normal === 'kredit' && $debit > 0) {
            return "Akun {$akun->no_akun} - {$akun->nama} memiliki saldo normal Kredit, tidak boleh di Debit.";
        }

        return null;
    }

    /* =========================================================
       DETEKSI UANG MUKA DALAM DETAIL
    ==========================================================*/
    public static function detectUangMukaDetail($row)
    {
        $akun = self::getAkun($row['akun_id']);

        if ($akun && $akun->kategori === 'uang_muka') {
            return [
                'is'      => true,
                'akun_id' => $akun->id,
                'nominal' => floatval(str_replace('.', '', $row['debit'] ?? 0)),
            ];
        }

        return ['is' => false, 'akun_id' => null, 'nominal' => 0];
    }
}
