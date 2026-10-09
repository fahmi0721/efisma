<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class PiutangService
{
    public static function getAging($request){
        $periode_from = $request->periode_from ?? date('Y-m'); // contoh: "2025-11"
        $periode_to = $request->periode_to ?? date('Y-m'); // contoh: "2025-11"
        $periode_awal = $periode_from . '-01';
        $periode_toto = $periode_to . '-01';
        $periode_akhir = date('Y-m-t', strtotime($periode_toto));
        $tanggalAwal = $periode_awal ?? null;
        $tanggalAkhir = $periode_akhir ?? now()->toDateString();

        $filter = $request['filter'] ?? null;
        $entitasId = $request['entitas_id'] ?? null;

        $query = DB::table('view_daftar_piutang')
            ->select(
                'partner_id',
                'entitas_id'
            )
            ->selectRaw('MAX(partner_nama) AS partner_nama')
            ->selectRaw('MAX(is_vendor) AS is_vendor')
            ->selectRaw('MAX(is_customer) AS is_customer');

        /*
        |--------------------------------------------------------------------------
        | 1. PERHITUNGAN AGING BERDASARKAN TANGGAL AKHIR
        |--------------------------------------------------------------------------
        */

        $aging = [
            'aging_0_14'    => 'BETWEEN 0 AND 14',
            'aging_15_30'   => 'BETWEEN 15 AND 30',
            'aging_31_45'   => 'BETWEEN 31 AND 45',
            'aging_46_60'   => 'BETWEEN 46 AND 60',
            'aging_60_plus' => '> 60',
        ];

        foreach ($aging as $alias => $condition) {
            $query->selectRaw("
                SUM(
                    CASE
                        WHEN DATEDIFF(?, tanggal) {$condition}
                        THEN sisa_piutang
                        ELSE 0
                    END
                ) AS {$alias}
            ", [$tanggalAkhir]);
        }

        $query->selectRaw(
            'SUM(sisa_piutang) AS total_piutang'
        );

        /*
        |--------------------------------------------------------------------------
        | 2. FILTER PERIODE
        |--------------------------------------------------------------------------
        */

        if (!empty($tanggalAwal)) {
            $query->whereDate('tanggal', '>=', $tanggalAwal);
        }

        $query->whereDate('tanggal', '<=', $tanggalAkhir);

        /*
        |--------------------------------------------------------------------------
        | 3. FILTER CUSTOMER / VENDOR
        |--------------------------------------------------------------------------
        */

        if ($filter === 'customer') {
            $query->where('is_customer', 'active');
        } elseif ($filter === 'vendor') {
            $query->where('is_vendor', 'active');
        }

        /*
        |--------------------------------------------------------------------------
        | 4. FILTER ENTITAS
        |--------------------------------------------------------------------------
        */

        if (!empty($entitasId)) {
            $query->where('entitas_id', $entitasId);
        }

        /*
        |--------------------------------------------------------------------------
        | 5. FILTER WAJIB USER LEVEL ENTITAS
        |--------------------------------------------------------------------------
        */

        if ($request->entitas_scope) {
            $query->where(
                'entitas_id',
                $request->entitas_scope
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 6. GROUPING
        |--------------------------------------------------------------------------
        */

        $query->groupBy(
            'partner_id',
            'entitas_id'
        );
        return $query->get();

    }
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
