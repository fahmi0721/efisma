<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Repositoris\UangMukaRepositori;
use App\Repositoris\JurnalRepositori;
use App\Repositoris\GLRepositori;
use Exception;

class UangMukaService
{

    public static function getDataTableUangMuka($entitas){
        return UangMukaRepositori::getDataUangMuka($entitas);
    }

    private static function get_jurnal_id_jkk($jurnal){
        if(!$jurnal){
            return false;
        }else{
            if($jurnal->is_multi_cabang == "1"){
                $detail_jurnal = JurnalRepositori::getJurnalDetailByJurnalId($jurnal->id);
                $respons = array();
                foreach($detail_jurnal as $d){
                    $respons[] = $d->jurnal_id_secound;
                }
                return $respons;
            }else{
                return $jurnal->jurnal_id_jkk;
            }
        }
    }
    /**
     * Saat POSTING JN → Insert pelunasan uang muka
     */
    public static function postingPelunasan($jurnalId)
    {
        DB::transaction(function () use ($jurnalId) {

            $jurnal = JurnalRepositori::getJurnalHeaderById($jurnalId);

            // ambil detail
            $details = JurnalRepositori::getJurnalDetailByJurnalId($jurnalId);

            // hapus pelunasan lama (jika reposting)
            UangMukaRepositori::deletePelunasanUangMukaByJurnalId($jurnalId);

            $jkkId = self::get_jurnal_id_jkk($jurnal);

                if (is_array($jkkId)) {
                    foreach ($details as $d) {

                        $akun = GLRepositori::findById($d->akun_id);
                        if (!$akun || $akun->kategori !== 'uang_muka') continue;
                        self::validateTanggal($d->jurnal_id_secound, $jurnal->tanggal);
                        if (!in_array($d->jurnal_id_secound, $jkkId)) continue;
                        $jumlah = $d->kredit;
                        if ($jumlah <= 0) continue;
                        if (!self::cekKelebihanPelunasan($d->jurnal_id_secound, $akun->id, $jumlah)) {
                            throw new Exception(
                                "Pelunasan uang muka melebihi sisa untuk akun {$akun->no_akun} - {$akun->nama}."
                            );
                        }
                        UangMukaRepositori::create([
                            'entitas_id'          => $jurnal->entitas_id,
                            'partner_id'          => $jurnal->partner_id,
                            'jurnal_uang_muka_id' => $d->jurnal_id_secound,
                            'jurnal_biaya_id'     => $jurnalId,
                            'akun_biaya_id'       => $akun->id,
                            'jumlah'              => $jumlah,
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ]);
                        
                    }
                } else {
                    foreach ($details as $d) {
                        $akun = GLRepositori::findById($d->akun_id);
                        if (!$akun || $akun->kategori !== 'uang_muka') continue;
                        self::validateTanggal($jurnal->jurnal_id_jkk, $jurnal->tanggal);
                        $jumlah = $d->debit > 0 ? $d->debit : $d->kredit;
                        if ($jumlah <= 0) continue;
                        $sisa = self::getSisaUangMuka($jurnal->jurnal_id_jkk, $akun->id,);
                        if (!self::cekKelebihanPelunasan($jurnal->jurnal_id_jkk, $akun->id, $jumlah)) {
                            throw new Exception(
                                "Pelunasan uang muka melebihi sisa untuk akun {$akun->no_akun} - {$akun->nama}."
                            );
                        }
                        UangMukaRepositori::create([
                            'entitas_id'          => $jurnal->entitas_id,
                            'partner_id'          => $jurnal->partner_id,
                            'jurnal_uang_muka_id' => $jurnal->jurnal_id_jkk,
                            'jurnal_biaya_id'     => $jurnalId,
                            'akun_biaya_id'       => $akun->id,
                            'jumlah'              => $jumlah,
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ]);
                    }
                }

        });
    }

    

    /**
     * Unposting JN → Hapus pelunasan
     */
    public static function unpostingPelunasan($jurnalId)
    {
        UangMukaRepositori::deleteByJurnalBiaya($jurnalId);
    }

    /** cek apakah pelunasan tidak melebihi sisa */
    public static function cekKelebihanPelunasan($jurnal_id, $akunUangMukaId, $jumlah)
    {
        $sisa = self::getSisaUangMuka($jurnal_id, $akunUangMukaId);
        if($jumlah < $sisa){
            return false;
        }else{
            return true;
        }
        // return $jumlah < $sisa;
    }

    /** hitung sisa UM */
    public static function getSisaUangMuka($jurnal_id, $akunId)
    {
        $totalUangMuka = UangMukaRepositori::totalUangMuka($jurnal_id);
        $totalUsed = UangMukaRepositori::totalUsed($jurnal_id,$akunId);
        return $totalUangMuka - $totalUsed;
    }

    /** Validasi tanggal JN >= tanggal JKK */
    public static function validateTanggal($jkkId, $tanggalJN)
    {
        $jkk = JurnalRepositori::getJurnalHeaderById($jkkId);
        if (!$jkk) throw new Exception("Jurnal uang muka (JKK) tidak ditemukan.");
        if ($tanggalJN < $jkk->tanggal) {
            throw new Exception("Tanggal JN ($tanggalJN) tidak boleh lebih kecil dari tanggal JKK ($jkk->tanggal).");
        }
    }

    /** Validasi draft JN */
    public static function validateDraftPelunasan($request)
    {
        if (!$request->filled('jurnal_id_jkk')) return true;

        self::validateTanggal($request->jurnal_id_jkk, $request->tanggal);

        foreach ($request->detail as $d) {
            $akun = GLRepositori::findById($d['akun_id']);
            if (!$akun || $akun->kategori !== 'uang_muka') continue;
            $jumlah = floatval(str_replace('.', '', $d['debit'] ?? $d['kredit'] ?? 0));
            if ($jumlah <= 0) continue;
            if (!self::cekKelebihanPelunasan($request->jurnal_id_jkk, $akun->id, $jumlah)) {
                $sisa = self::getSisaUangMuka($request->jurnal_id_jkk, $akun->id);
                throw new Exception("Pelunasan uang muka melebihi sisa untuk akun {$akun->no_akun} - {$akun->nama}. Sisa: " . number_format($sisa, 0, ',', '.'));
            }
        }

        return true;
    }
}
