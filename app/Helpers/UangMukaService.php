<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Exception;

class UangMukaService
{
    /**
     * Saat POSTING JN → Insert pelunasan uang muka
     */
    public static function postingPelunasan($jurnalId)
    {
        $jurnal = DB::table('jurnal_header')->where('id', $jurnalId)->first();
        if (!$jurnal || !$jurnal->jurnal_id_jkk) return;

        $jkkId = $jurnal->jurnal_id_jkk;

        // Validasi tanggal JN >= tanggal JKK
        self::validateTanggal($jkkId, $jurnal->tanggal);

        // ambil detail
        $details = DB::table('jurnal_detail')
            ->where('jurnal_id', $jurnalId)
            ->get();

        // hapus pelunasan lama (jika reposting)
        DB::table('pelunasan_uang_muka')
            ->where('jurnal_biaya_id', $jurnalId)
            ->delete();

        foreach ($details as $d) {

            $akun = DB::table('m_akun_gl')->where('id', $d->akun_id)->first();
            if (!$akun || $akun->kategori !== 'uang_muka') continue;

            $jumlah = $d->debit > 0 ? $d->debit : $d->kredit;
            if ($jumlah <= 0) continue;

            // validasi sisa
            if (!self::cekKelebihanPelunasan($jkkId, $akun->id, $jumlah)) {
                throw new Exception("Pelunasan uang muka melebihi sisa untuk akun {$akun->no_akun} - {$akun->nama}.");
            }

            DB::table('pelunasan_uang_muka')->insert([
                'entitas_id'          => $jurnal->entitas_id,
                'partner_id'          => $jurnal->partner_id,
                'jurnal_uang_muka_id' => $jkkId,
                'jurnal_biaya_id'     => $jurnalId,
                'akun_biaya_id'       => $akun->id,
                'jumlah'              => $jumlah,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }
    }

    /**
     * Unposting JN → Hapus pelunasan
     */
    public static function unpostingPelunasan($jurnalId)
    {
        DB::table('pelunasan_uang_muka')
            ->where('jurnal_biaya_id', $jurnalId)
            ->delete();
    }

    /** cek apakah pelunasan tidak melebihi sisa */
    public static function cekKelebihanPelunasan($jkkId, $akunUangMukaId, $jumlah)
    {
        $sisa = self::getSisaUangMuka($jkkId, $akunUangMukaId);
        return $jumlah <= $sisa;
    }

    /** hitung sisa UM */
    public static function getSisaUangMuka($jkkId, $akunId)
    {
        $totalIn = DB::table('jurnal_detail')
            ->where('jurnal_id', $jkkId)
            ->where('akun_id', $akunId)
            ->sum('debit');

        $totalUsed = DB::table('pelunasan_uang_muka')
            ->where('jurnal_uang_muka_id', $jkkId)
            ->where('akun_biaya_id', $akunId)
            ->sum('jumlah');

        return $totalIn - $totalUsed;
    }

    /** Validasi tanggal JN >= tanggal JKK */
    public static function validateTanggal($jkkId, $tanggalJN)
    {
        $jkk = DB::table('jurnal_header')->where('id', $jkkId)->first();
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

            $akun = DB::table('m_akun_gl')->where('id', $d['akun_id'])->first();
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
