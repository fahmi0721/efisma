<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Repositoris\HutangRepositori;
use Exception;

class HutangService
{
    /* =========================================================
       HELPER: CEK KATEGORI AKUN
    ==========================================================*/
    public static function getAkun($akunId)
    {
        return DB::table('m_akun_gl')->where('id', $akunId)->first();
    }

    public static function getDataTableHutang($entitas){
        $query = HutangRepositori::getDataHutang($entitas); 
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('aksi', function($row){
                return "<button class='btn btn-sm btn-primary pilihHutang'
                            data-id='{$row->jurnal_id}'
                            data-total_tagihan='{$row->total_tagihan}'
                            data-kode='{$row->kode_jurnal}'
                            data-akun_nama='{$row->akun_hutang}'
                            data-akun_hutang_id='{$row->akun_hutang_id}'
                            data-total_pelunasan='{$row->total_pelunasan}'
                            data-umur_hutang='{$row->umur_hutang}'
                            data-sisa_hutang='{$row->sisa_hutang}'
                            data-partner='{$row->partner_nama}'
                            data-partner_id='{$row->partner_id}'
                            data-entitas='{$row->entitas_id}'>
                            Pilih
                        </button>";
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    /* =========================================================
       DETEKSI UANG MUKA DALAM DETAIL
    ==========================================================*/
    public static function detectHutang($row)
    {
        $akun = self::getAkun($row['akun_id']);

        if ($akun && $akun->kategori === 'hutang') {
            return [
                'is'      => true,
                'akun_id' => $akun->id,
                'jurnal_id' => $row['jurnal_id'],
            ];
        }

        return ['is' => false, 'akun_id' => null, 'jurnal_id' => null];
    }

    
}
