<?php

namespace App\Repositoris;

use Illuminate\Support\Facades\DB;
use Exception;

class HutangRepositori
{
    

    public static function getDataHutang($entitas)
    {
        $query = DB::table('view_daftar_hutang')
                ->join("m_akun_gl","m_akun_gl.id","=","view_daftar_hutang.akun_hutang_id")
                ->join("m_entitas","m_entitas.id","=","view_daftar_hutang.entitas_id")
                ->select("view_daftar_hutang.*",DB::raw("CONCAT(m_akun_gl.no_akun, ' - ', m_akun_gl.nama) as akun_hutang"),"m_entitas.nama as entitas_nama")
                ->where('sisa_hutang', '>', 0);

        if ($entitas) {
            $query->where('entitas_id', $entitas);
        }
        return $query;
    }

    
}
