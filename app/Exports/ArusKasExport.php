<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Facades\DB;

class ArusKasExport implements FromView
{
    protected $entitas_id;
    protected $periode;

    public function __construct($entitas_id = null, $periode = null)
    {
        $this->entitas_id = $entitas_id;
        $this->periode = $periode;
    }

    public function view(): View
    {
        $tglAwal = $periode . '-01';
        $tglAkhir = date('Y-m-t', strtotime($periode));
       

        // 1️⃣ Saldo awal khusus kas/bank per entitas
        $saldoAwal = DB::table('m_saldo_awal as sa')
            ->join('m_akun_gl as ak', 'ak.id', '=', 'sa.akun_gl_id')
            ->select('sa.entitas_id', DB::raw('SUM(sa.saldo) as saldo_awal'))
            ->where('ak.kategori', 'kas_bank')
            ->where('sa.periode', $periode)
            ->when($entitas, fn($q) => $q->where('sa.entitas_id', $entitas))
            ->groupBy('sa.entitas_id')
            ->get()
            ->keyBy('entitas_id');

        // 2️⃣ Arus kas per kelompok per entitas (dengan masuk & keluar)
        $arusKas = DB::table('buku_besar as kas')
        ->join('m_akun_gl as ak', 'ak.id', '=', 'kas.akun_id')
        ->join('buku_besar as lawan', function ($join) {
            $join->on('lawan.jurnal_id', '=', 'kas.jurnal_id')
                ->whereColumn('lawan.id', '!=', 'kas.id');
        })
        ->join('m_akun_gl as al', 'al.id', '=', 'lawan.akun_id')
        ->join('m_entitas as e', 'e.id', '=', 'kas.entitas_id')
        ->select(
            'kas.entitas_id',
            'e.nama as nama_entitas',
            DB::raw("
                CASE 
                    WHEN al.kategori = 'piutang' THEN 'operasional'
                    WHEN al.kategori IN ('pendapatan_operasional','beban_operasional') THEN 'operasional'
                    WHEN al.kategori = 'investasi' THEN 'investasi'
                    WHEN al.kategori = 'pendanaan' THEN 'pendanaan'
                    ELSE 'operasional'
                END AS kelompok
            "),
            DB::raw("SUM(CASE WHEN kas.debit > 0 THEN kas.debit ELSE 0 END) as masuk"),
            DB::raw("SUM(CASE WHEN kas.kredit > 0 THEN kas.kredit ELSE 0 END) as keluar"),
            DB::raw("SUM(kas.debit - kas.kredit) as total")
        )
        ->where('ak.kategori', 'kas_bank')
        ->when($entitas, fn($q) => $q->where('kas.entitas_id', $entitas))
        ->whereBetween('kas.tanggal', [$tglAwal, $tglAkhir])
        ->groupBy('kas.entitas_id', 'e.nama', 'kelompok')
        ->orderBy('e.nama')
        ->get();

        // 3️⃣ Susun laporan
        $laporan = [];
        $grand = [
            'saldo_awal' => 0,
            'operasional_masuk' => 0,
            'operasional_keluar' => 0,
            'investasi_masuk' => 0,
            'investasi_keluar' => 0,
            'pendanaan_masuk' => 0,
            'pendanaan_keluar' => 0,
            'operasional' => 0,
            'investasi' => 0,
            'pendanaan' => 0,
            'kenaikan_kas' => 0,
            'saldo_akhir' => 0,
        ];

        foreach ($arusKas as $row) {
            $id = $row->entitas_id;
            $nama = $row->nama_entitas;
            $k = $row->kelompok;

            if (!isset($laporan[$id])) {
                $laporan[$id] = [
                    'entitas_id' => $id,
                    'nama_entitas' => $nama,
                    'saldo_awal' => $saldoAwal[$id]->saldo_awal ?? 0,

                    'operasional_masuk' => 0,
                    'operasional_keluar' => 0,
                    'investasi_masuk' => 0,
                    'investasi_keluar' => 0,
                    'pendanaan_masuk' => 0,
                    'pendanaan_keluar' => 0,

                    'operasional' => 0,
                    'investasi' => 0,
                    'pendanaan' => 0,

                    'kenaikan_kas' => 0,
                    'saldo_akhir' => 0,
                ];
            }

            // Simpan masuk & keluar per kelompok
            $laporan[$id][$k.'_masuk']  = (float) $row->masuk;
            $laporan[$id][$k.'_keluar'] = (float) $row->keluar;
            $laporan[$id][$k] = (float) $row->total; // net
        }

        // 4️⃣ Hitung total akhir & grand total
        foreach ($laporan as &$r) {
            $r['kenaikan_kas'] = $r['operasional'] + $r['investasi'] + $r['pendanaan'];
            $r['saldo_akhir']  = $r['saldo_awal'] + $r['kenaikan_kas'];

            $grand['saldo_awal'] += $r['saldo_awal'];

            $grand['operasional_masuk'] += $r['operasional_masuk'];
            $grand['operasional_keluar'] += $r['operasional_keluar'];
            $grand['investasi_masuk'] += $r['investasi_masuk'];
            $grand['investasi_keluar'] += $r['investasi_keluar'];
            $grand['pendanaan_masuk'] += $r['pendanaan_masuk'];
            $grand['pendanaan_keluar'] += $r['pendanaan_keluar'];

            $grand['operasional'] += $r['operasional'];
            $grand['investasi']   += $r['investasi'];
            $grand['pendanaan']   += $r['pendanaan'];

            $grand['kenaikan_kas'] += $r['kenaikan_kas'];
            $grand['saldo_akhir']  += $r['saldo_akhir'];
        }

        return view('exports.arus_kas',[
            'per_entitas' => array_values($laporan),
            'grand_total' => $grand
        ]);
      
    }

}
