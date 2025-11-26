<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class PeriodeHelper
{
    public static function cekPeriodeOpen($tanggal)
    {
        $periode = date('Y-m', strtotime($tanggal));
        $periodeIndo = Carbon::parse($periode)
        ->locale('id')
        ->translatedFormat('F Y');

        $cek = DB::table('periode_akuntansi')
            ->where('periode', $periode)
            ->first();

        if (!$cek) {
            throw new Exception("Periode $periodeIndo belum terdaftar di sistem.");
        }

        if ($cek->status === 'closed') {
            throw new Exception("Periode $periodeIndo sudah ditutup. Transaksi tidak bisa dilakukan!");
        }

        return true;
    }
}
