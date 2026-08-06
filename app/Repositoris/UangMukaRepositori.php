<?php

namespace App\Repositoris;

use Illuminate\Support\Facades\DB;
use Exception;

class UangMukaRepositori
{
    

    public static function getDataUangMuka($entitas)
    {
        $query = DB::table('view_uang_muka_per_akun')
                ->where('sisa', '>', 0);

        if ($entitas) {
            $query->where('entitas_id', $entitas);
        }
        return $query;
    }

    public static function deletePelunasanUangMukaByJurnalId($jurnal_id){
        return DB::table('pelunasan_uang_muka')
                ->where('jurnal_biaya_id', $jurnal_id)
                ->delete();
    }

    public static function create($data){
        return DB::table('pelunasan_uang_muka')->insert($data);
    }

    public static function deleteByJurnalBiaya($jurnalId){
        return DB::table('pelunasan_uang_muka')
            ->where('jurnal_biaya_id', $jurnalId)
            ->delete();
    }

    public static function totalUangMuka($jurnal_id){
        return DB::table('jurnal_header')
            ->where('id', $jurnal_id)
            ->value('total_debit');
    }

    public static function totalUsed($jurnal_id,$akunId){
        return DB::table('pelunasan_uang_muka')
            ->where('jurnal_uang_muka_id', $jurnal_id)
            ->where('akun_biaya_id', $akunId)
            ->sum('jumlah');
    }

    
}
