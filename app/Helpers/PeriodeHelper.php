<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Exception;

class PeriodeHelper
{
    public static function cekPeriodeOpen($tanggal)
    {
        $periode = date('Y-m', strtotime($tanggal));

        $cek = DB::table('periode_akuntansi')
            ->where('periode', $periode)
            ->first();

        if (!$cek) {
            throw new Exception("Periode $periode belum terdaftar di sistem.");
        }

        if ($cek->status === 'closed') {
            throw new Exception("Periode $periode sudah ditutup. Transaksi tidak bisa dilakukan!");
        }

        return true;
    }
}
