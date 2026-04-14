<?php

namespace App\Repositoris;

use Illuminate\Support\Facades\DB;
use Exception;

class GLRepositori
{
    /**
     * Saat POSTING JN → Insert pelunasan uang muka
     */
    public static function getGlTransaksi($search = null)
    {
        $query = DB::table('view_akun_transaksi_only');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('no_akun', 'like', "%{$search}%")
                ->orWhere('nama', 'like', "%{$search}%");
            });
        }
        return $query->get();
    }

    
}
