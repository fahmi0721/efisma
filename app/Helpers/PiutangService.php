<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class PiutangService
{
    /**
     * Ambil tanggal JP (jurnal pendapatan)
     */
    public static function getData($req)
    {
            $partner_id = $req->input('partner_id');
            $entitas_id = $req->input('entitas_id');
            $cabang_id = $req->input('cabang_id');
            /*
            |----------------------------------------------------------
            | FILTER PARTNER
            |----------------------------------------------------------
            */
            $query = DB::table('view_monitoring_piutang');
            if (!empty($partner_id)) {
                $query->where('partner_id', $partner_id);
            }

            if (!empty($entitas_id)) {
                $query->where('entitas_id', $entitas_id);
            }

            if (!empty($cabang_id)) {
                $query->where('cabang_id', $cabang_id);
            }

            /*
            |--------------------------------------------------------------------------
            | 1. FILTER WAJIB UNTUK USER LEVEL ENTITAS
            |--------------------------------------------------------------------------
            */
            if ($req->entitas_scope) {
                $query->where('entitas_id', $req->entitas_scope);
            }
        return $query;
    }
    
}
