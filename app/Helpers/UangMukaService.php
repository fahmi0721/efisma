<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Repositoris\UangMukaRepositori;
use App\Repositoris\JurnalRepositori;
use App\Repositoris\GLRepositori;
use Exception;
use Carbon\Carbon;

class UangMukaService
{
     private static function getSaldoAwal(
        $entitas_id,
        $partner_id,
        $akun_gl_id,
        $periode
    ) {
        return (float) DB::table('m_saldo_awal as sa')
            ->join(
                'm_akun_gl as a',
                'a.id',
                '=',
                'sa.akun_gl_id'
            )
            ->where('a.kategori', 'uang_muka')

            ->when($entitas_id !== null, function ($q) use ($entitas_id) {
                $q->where('sa.entitas_id', $entitas_id);
            })

            ->when($partner_id !== null, function ($q) use ($partner_id) {
                $q->where('sa.partner_id', $partner_id);
            })

            ->when($akun_gl_id !== null, function ($q) use ($akun_gl_id) {
                $q->where('sa.akun_gl_id', $akun_gl_id);
            })

            ->where('sa.periode', $periode)
            ->sum('sa.saldo');
    }


    public static function getMonitoring(
        $entitas_id = null,
        $partner_id = null,
        $akun_gl_id = null,
        $periode_awal = null,
        $periode_akhir = null
    ) {
        $awal = Carbon::parse($periode_awal)->startOfDay();
        $akhir = Carbon::parse($periode_akhir)->startOfDay();

        if ($awal->gt($akhir)) {
            throw new \InvalidArgumentException(
                'Periode awal tidak boleh melebihi periode akhir.'
            );
        }

        // Saldo awal diambil berdasarkan bulan periode awal.
        $periode = $awal->format('Y-m');

        $saldoAwal = self::getSaldoAwal(
            $entitas_id,
            $partner_id,
            $akun_gl_id,
            $periode
        );

        // Jika periode awal bukan tanggal 1, hitung mutasi
        // dari awal bulan sampai sehari sebelum periode awal.
        $saldo = $saldoAwal;

        if ($awal->day !== 1) {
            $mutasiSebelum = self::getMutasi(
                $entitas_id,
                $partner_id,
                $akun_gl_id,
                $awal->copy()->startOfMonth(),
                $awal->copy()->subDay()
            );

            foreach ($mutasiSebelum as $item) {
                $saldo += self::hitungMutasi($item);
            }
        }

        $saldoAwalLaporan = $saldo;

        // Mutasi transaksi periode terpilih.
        $mutasi = self::getMutasi(
            $entitas_id,
            $partner_id,
            $akun_gl_id,
            $awal,
            $akhir
        );

        $rows = collect();

        // Baris pertama wajib saldo awal.
        $rows->push([
            'id' => null,
            'kode_jurnal' => null,
            'tanggal' => $awal->format('Y-m-d'),
            'jenis' => 'saldo_awal',
            'akun_gl' => '-',
            'entitas' => '-',
            'partner' => '-',
            'keterangan' => 'SALDO AWAL',
            'debit' => 0,
            'kredit' => 0,
            'saldo' => round($saldoAwalLaporan, 2),
        ]);

        $totalDebit = 0;
        $totalKredit = 0;

        foreach ($mutasi as $item) {

            $debit = (float) $item->debit;
            $kredit = (float) $item->kredit;

            $totalDebit += $debit;
            $totalKredit += $kredit;

            // Menghitung saldo berjalan.
            $saldo += self::hitungMutasi($item);

            $rows->push([
                'id' => $item->id,
                'kode_jurnal' => $item->kode_jurnal,
                'tanggal' => Carbon::parse(
                    $item->tanggal
                )->format('Y-m-d'),

                'jenis' => 'mutasi',

                'akun_gl' => $item->no_akun
                    . ' - ' . $item->akun_nama,

                'entitas' => $item->entitas_nama,

                'partner' => $item->partner_nama,

                'keterangan' => $item->keterangan,

                'debit' => $debit,
                'kredit' => $kredit,
                'saldo' => round($saldo, 2),
            ]);
        }

        return [
            'data' => $rows,
            'summary' => [
                'saldo_awal' => round($saldoAwalLaporan, 2),
                'total_debit' => round($totalDebit, 2),
                'total_kredit' => round($totalKredit, 2),
                'saldo_akhir' => round($saldo, 2),
            ],
        ];
    }


    private static function getMutasi(
        $entitas_id,
        $partner_id,
        $akun_gl_id,
        Carbon $awal,
        Carbon $akhir
    ) {
        return DB::table('buku_besar as bb')
            ->join(
                'm_akun_gl as a',
                'a.id',
                '=',
                'bb.akun_id'
            )
            ->leftJoin(
                'm_entitas as e',
                'e.id',
                '=',
                'bb.entitas_id'
            )
            ->leftJoin(
                'm_partner as p',
                'p.id',
                '=',
                'bb.partner_id'
            )

            ->where('a.kategori', 'uang_muka')

            ->when($entitas_id !== null, function ($q) use ($entitas_id) {
                $q->where('bb.entitas_id', $entitas_id);
            })

            ->when($partner_id !== null, function ($q) use ($partner_id) {
                $q->where('bb.partner_id', $partner_id);
            })

            ->when($akun_gl_id !== null, function ($q) use ($akun_gl_id) {
                $q->where('bb.akun_id', $akun_gl_id);
            })

            ->where('bb.tanggal', '>=', $awal->toDateString())
            ->where(
                'bb.tanggal',
                '<',
                $akhir->copy()->addDay()->toDateString()
            )

            ->select(
                'bb.id',
                'bb.kode_jurnal',
                'bb.tanggal',
                'bb.keterangan',
                'bb.debit',
                'bb.kredit',

                'a.no_akun',
                'a.nama as akun_nama',
                'a.saldo_normal',

                'e.nama as entitas_nama',
                'p.nama as partner_nama'
            )

            ->orderBy('bb.tanggal', 'asc')
            ->orderBy('bb.id', 'asc')
            ->get();
    }

    private static function hitungMutasi($item)
    {
        $debit = (float) $item->debit;
        $kredit = (float) $item->kredit;

        $normal = strtolower(trim($item->saldo_normal));

        if (in_array($normal, ['debit', 'debet'])) {
            return $debit - $kredit;
        }

        if ($normal === 'kredit') {
            return $kredit - $debit;
        }

        throw new \UnexpectedValueException(
            'Saldo normal akun GL tidak valid.'
        );
    }
    public static function getSaldoAwalPeriode(
        $entitas_id = null,
        $partner_id = null,
        $akun_gl_id = null,
        $periode
    ) {
        $periode = Carbon::parse($periode)->format('Y-m');
        return DB::table('m_saldo_awal')
            ->when($entitas_id !== null, function ($query) use ($entitas_id) {
                $query->where('entitas_id', $entitas_id);
            })
            ->when($partner_id !== null, function ($query) use ($partner_id) {
                $query->where('partner_id', $partner_id);
            })
            ->when($akun_gl_id !== null, function ($query) use ($akun_gl_id) {
                $query->where('akun_gl_id', $akun_gl_id);
            })
            ->where('periode', $periode)
            ->select(
                'entitas_id',
                'partner_id',
                'akun_gl_id AS akun_id'
            )
            ->selectRaw('
                COALESCE(SUM(saldo), 0) AS saldo_awal
            ')
            ->groupBy(
                'entitas_id',
                'partner_id',
                'akun_gl_id'
            )
            ->get();
    }

    public static function getMutasiPeriode(
        $entitas_id = null,
        $partner_id = null,
        $akun_gl_id = null,
        $tgl_mulai = null,
        $tgl_akhir = null
    ) {
        return DB::table('buku_besar')
            ->when($entitas_id !== null, function ($query) use ($entitas_id) {
                $query->where('entitas_id', $entitas_id);
            })
            ->when($partner_id !== null, function ($query) use ($partner_id) {
                $query->where('partner_id', $partner_id);
            })
            ->when($akun_gl_id !== null, function ($query) use ($akun_gl_id) {
                $query->where('akun_id', $akun_gl_id);
            })
            ->when($tgl_mulai !== null, function ($query) use ($tgl_mulai) {
                $query->where('tanggal', '>=', $tgl_mulai);
            })
            ->when($tgl_akhir !== null, function ($query) use ($tgl_akhir) {
                $query->where('tanggal', '<=', $tgl_akhir);
            })
            ->select(
                'entitas_id',
                'partner_id',
                'akun_id'
            )
            ->selectRaw('
                COALESCE(SUM(debit), 0) AS total_debit,
                COALESCE(SUM(kredit), 0) AS total_kredit
            ')
            ->groupBy(
                'entitas_id',
                'partner_id',
                'akun_id'
            )
            ->get();
    }

    public static function getDaftarUangMuka(
        $entitas_id = null,
        $partner_id = null,
        $akun_gl_id = null,
        $tgl_mulai = null,
        $tgl_akhir = null
    ) {
        if (!$tgl_mulai || !$tgl_akhir) {
            throw new \InvalidArgumentException(
                'Tanggal mulai dan tanggal akhir wajib diisi.'
            );
        }

        if ($tgl_mulai > $tgl_akhir) {
            throw new \InvalidArgumentException(
                'Tanggal mulai tidak boleh melebihi tanggal akhir.'
            );
        }

        // Ambil saldo awal dari fungsi yang sudah dibuat
        $saldoAwal = self::getSaldoAwalPeriode(
            $entitas_id,
            $partner_id,
            $akun_gl_id,
            $tgl_mulai
        );

        // Ambil mutasi buku besar
        $mutasi = self::getMutasiPeriode(
            $entitas_id,
            $partner_id,
            $akun_gl_id,
            $tgl_mulai,
            $tgl_akhir
        );

        // Gabungkan sumber data
        $rows = collect();

        foreach ($saldoAwal as $item) {
            $rows->push([
                'entitas_id' => $item->entitas_id,
                'partner_id' => $item->partner_id,
                'akun_gl_id' => $item->akun_id,
                'saldo_awal' => (float) $item->saldo_awal,
                'total_debit' => 0,
                'total_kredit' => 0,
            ]);
        }

        foreach ($mutasi as $item) {
            $rows->push([
                'entitas_id' => $item->entitas_id,
                'partner_id' => $item->partner_id,
                'akun_gl_id' => $item->akun_id,
                'saldo_awal' => 0,
                'total_debit' => (float) $item->total_debit,
                'total_kredit' => (float) $item->total_kredit,
            ]);
        }

        // Gabungkan berdasarkan entitas + partner + akun
        $grouped = $rows->groupBy(function ($item) {
            return implode('|', [
                $item['entitas_id'],
                $item['partner_id'] ?? 'NULL',
                $item['akun_gl_id'],
            ]);
        });

        $akunIds = $rows->pluck('akun_gl_id')->unique();
        $entitasIds = $rows->pluck('entitas_id')->unique();
        $partnerIds = $rows->pluck('partner_id')
            ->filter(fn ($id) => $id !== null)
            ->unique();

        // Master data hanya diambil sekali
        $akun = DB::table('m_akun_gl')
            ->whereIn('id', $akunIds)
            ->where('kategori', 'uang_muka')
            ->get()
            ->keyBy('id');

        $entitas = DB::table('m_entitas')
            ->whereIn('id', $entitasIds)
            ->get()
            ->keyBy('id');

        $partners = DB::table('m_partner')
            ->whereIn('id', $partnerIds)
            ->get()
            ->keyBy('id');

        return $grouped->map(function ($items) use (
            $akun,
            $entitas,
            $partners
        ) {
            $first = $items->first();

            $akunGl = $akun->get($first['akun_gl_id']);

            // Hanya akun kategori uang_muka
            if (!$akunGl) {
                return null;
            }

            $saldoAwal = $items->sum('saldo_awal');
            $totalDebit = $items->sum('total_debit');
            $totalKredit = $items->sum('total_kredit');

            $normal = strtolower(trim($akunGl->saldo_normal));

            if (in_array($normal, ['debit', 'debet'])) {
                $mutasiSaldo = $totalDebit - $totalKredit;
            } elseif ($normal === 'kredit') {
                $mutasiSaldo = $totalKredit - $totalDebit;
            } else {
                throw new \UnexpectedValueException(
                    'Saldo normal akun GL tidak valid: ' . $akunGl->id
                );
            }

            $saldo = $saldoAwal + $mutasiSaldo;

            $entitasData = $entitas->get($first['entitas_id']);
            $partnerData = $partners->get($first['partner_id']);

            return [
                'akun_gl_id' => $first['akun_gl_id'],
                'akun_gl' => $akunGl->no_akun . ' - ' . $akunGl->nama,

                'entitas_id' => $first['entitas_id'],
                'entitas' => $entitasData?->nama,

                'partner_id' => $first['partner_id'],
                'partner' => $partnerData?->nama,

                'saldo_awal' => $saldoAwal,
                'total_debit' => $totalDebit,
                'total_kredit' => $totalKredit,
                'saldo' => $saldo,
            ];
        })
        ->filter()
        ->filter(fn ($item) => round($item['saldo'], 2) != 0)
        ->sortBy([
            ['akun_gl', 'asc'],
            ['entitas', 'asc'],
            ['partner', 'asc'],
        ])
        ->values();
    }

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
                        if(in_array($akun->id,array(49,167))) continue;
                        self::validateTanggal($d->jurnal_id_secound, $jurnal->tanggal);
                        if (!in_array($d->jurnal_id_secound, $jkkId)) continue;
                        $jumlah = $d->kredit;
                        if ($jumlah <= 0) continue;
                        $sisa = self::getSisaUangMuka(
                            $d->jurnal_id_secound,
                            $akun->id
                        );
                        if (!self::cekKelebihanPelunasan($d->jurnal_id_secound, $akun->id, $jumlah)) {
                            throw new Exception(
                                "Pelunasan uang muka melebihi sisa untuk akun "
                                . "{$akun->no_akun} - {$akun->nama}. "
                                . "Jumlah pelunasan: " . number_format($jumlah, 2, ',', '.') . ", "
                                . "Sisa uang muka: " . number_format($sisa, 2, ',', '.'),
                                422
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
                        if(in_array($akun->id,array(49,167))) continue;
                        self::validateTanggal($jurnal->jurnal_id_jkk, $jurnal->tanggal);
                        $jumlah = $d->debit > 0 ? $d->debit : $d->kredit;
                        if ($jumlah <= 0) continue;
                        $sisa = self::getSisaUangMuka(
                            $d->jurnal_id_secound,
                            $akun->id
                        );
                        if (!self::cekKelebihanPelunasan($jurnal->jurnal_id_jkk, $akun->id, $jumlah)) {
                            throw new Exception(
                                "Pelunasan uang muka melebihi sisa untuk akun "
                                . "{$akun->no_akun} - {$akun->nama}. "
                                . "Jumlah pelunasan: " . number_format($jumlah, 2, ',', '.') . ", "
                                . "Sisa uang muka: " . number_format($sisa, 2, ',', '.'),
                                422
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
        $sisa_uang_muka = self::getSisaUangMuka($jurnal_id, $akunUangMukaId);
        $uang_muka_exis = $sisa_uang_muka - $jumlah; 
        if($uang_muka_exis < 0){
            return false;
        }else{
            return true;
        }
        
        
    }

    /** hitung sisa UM */
    public static function getSisaUangMuka($jurnal_id, $akunId)
    {
        $totalUangMuka = UangMukaRepositori::totalUangMuka($jurnal_id,$akunId);
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
            if(in_array($akun->id,array(49,167))) continue;
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
