<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Repositoris\HutangRepositori;
use App\Repositoris\JurnalRepositori;
use App\Repositoris\GLRepositori;
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

    public static function cekKelebihanPelunasan($jurnal_id_secound,$jumlah){
        $sisahutang = self::getSisaHutang($jurnal_id_secound);
        if($jumlah > $sisahutang){
            throw new Exception("Jumlah pelunasan hutang lebih. Sisa Hutang : " . number_format($sisahutang, 0, ',', '.'));
        }
        return true;
    }

    public static function unpostingPelunasan($jurnalId)
    {
        HutangRepositori::deleteByJurnalHutang($jurnalId);
    }
    

    public static function postingPelunasan($jurnalId)
    {
        DB::transaction(function () use ($jurnalId) {
            $jurnal = JurnalRepositori::getJurnalHeaderById($jurnalId);
            // ambil detail
            $details = JurnalRepositori::getJurnalDetailByJurnalId($jurnalId);
            HutangRepositori::deletePelunasanHutangByJurnalId($jurnalId);
            foreach($details as $d){
                if(!empty($d->jurnal_id_secound)){
                    self::validateTanggal($d->jurnal_id_secound,$jurnal->tanggal);
                    $akun = GLRepositori::findById($d->akun_id);
                    if (!$akun || $akun->kategori !== 'hutang') continue;
                    $jumlah = $d->debit;
                    if ($jumlah <= 0) continue;
                    if(self::cekKelebihanPelunasan($d->jurnal_id_secound,$jumlah)){
                        HutangRepositori::create([
                            'jurnal_kas_id' => $jurnalId,
                            'jurnal_hutang_id'     => $d->jurnal_id_secound,
                            'jumlah'              => $jumlah,
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ]);
                    }
                }
            }
        });

    }

    public static function getDataTableHutang($entitas,$partner){
        $query = HutangRepositori::getDataHutang($entitas,$partner); 
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

    /** Validasi tanggal JKM >= tanggal JKK */
    public static function validateTanggal($jkmId, $tanggalJKK)
    {
        $jkm = JurnalRepositori::getJurnalHeaderById($jkmId);
        if (!$jkm) throw new Exception("Jurnal Hutang (JKM) tidak ditemukan.");
        if ($tanggalJKK < $jkm->tanggal) {
            throw new Exception("Tanggal JKK ($tanggalJKK) tidak boleh lebih kecil dari tanggal JKM ($jkm->tanggal).");
        }
    }

    /** hitung sisa Hutang */
    public static function getSisaHutang($jurnal_id)
    {
        $totalHutang = HutangRepositori::totalHutang($jurnal_id);
        $totalUsed = HutangRepositori::totalUsed($jurnal_id);
        return $totalHutang - $totalUsed;
    }

    /** Validasi Jumlah pelunasan hutang*/
    public static function validatePelunasan($row)
    {
        $akun = GLRepositori::findById($row['akun_id']); 
        if (!$akun || $akun->kategori !== 'hutang') return true;
        $jumlah = floatval(str_replace('.','',$row['debit']));
        if ($jumlah <= 0) return true;
        $sisahutang = self::getSisaHutang($row['jurnal_id']);
        if($jumlah > $sisahutang){
            throw new Exception("Jumlah pelunasan hutang lebih. Sisa Hutang : " . number_format($sisahutang, 0, ',', '.'));
        }
        
        return true;
    }



    public static function validateDraftHutang($row,$tanggal)
    {
        self::validateTanggal($row['jurnal_id'], $tanggal);
        self::validatePelunasan($row);
    }

    
}
