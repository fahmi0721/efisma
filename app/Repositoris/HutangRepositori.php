<?php

namespace App\Repositoris;

use Illuminate\Support\Facades\DB;
use Exception;

class HutangRepositori
{
    

    public static function getDataHutang($entitas,$partner)
    {
        $query = DB::table('view_daftar_hutang')
                ->join("m_akun_gl","m_akun_gl.id","=","view_daftar_hutang.akun_hutang_id")
                ->join("m_entitas","m_entitas.id","=","view_daftar_hutang.entitas_id")
                ->select("view_daftar_hutang.*",DB::raw("CONCAT(m_akun_gl.no_akun, ' - ', m_akun_gl.nama) as akun_hutang"),"m_entitas.nama as entitas_nama")
                ->where('sisa_hutang', '>', 0);

        if ($entitas) {
            $query->where('entitas_id', $entitas);
        }

        if ($partner) {
            $query->where('partner_id', $partner);
        }
        return $query;
    }

    public static function create($data){
        return DB::table('pelunasan_hutang')->insert($data);
    }

    public static function deleteByJurnalHutang($jurnalId){
        return DB::table('pelunasan_hutang')
            ->where('jurnal_hutang_id', $jurnalId)
            ->delete();
    }

    public static function totalHutang($jurnal_id){
        return DB::table('jurnal_header')
            ->where('id', $jurnal_id)
            ->value('total_kredit');
    }

    public static function deletePelunasanHutangByJurnalId($jurnal_id){
        return DB::table("pelunasan_hutang")
                ->where("jurnal_kas_id",$jurnal_id)
                ->delete();
    }

    public static function totalUsed($jurnal_id){
        return DB::table('pelunasan_hutang')
            ->where('jurnal_hutang_id', $jurnal_id)
            ->sum('jumlah');
    }

    
}
