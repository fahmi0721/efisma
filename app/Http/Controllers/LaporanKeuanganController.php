<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\NeracaExport;
use App\Exports\PblExport;
use App\Exports\ArusKasExport;
use App\Exports\BukuBesarExport;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
class LaporanKeuanganController extends Controller
{
    // ==============================
    // 📊 NERACA
    // ==============================
    public function indexNeraca()
    {
        return view('page.laporan.neraca');
    }

    public function dataNeraca(Request $request)
    {
        // Tentukan entitas yang digunakan
        if (auth()->user()->level == 'entitas') {
            $entitas_id = $request->entitas_scope; 
        } else {
            $entitas_id = $request->entitas_id; // filter dari dropdown jika admin/pusat
        }
        $periode = $request->periode ?? date('Y-m'); // contoh: "2025-11"
        $periode_awal = $periode . '-01';
        $periode_akhir = date('Y-m-t', strtotime($periode_awal));

        // 🔹 Ambil seluruh akun dari view_akun_hirarki
        $akun = DB::table('view_akun_hirarki')
            ->select(
                'id as akun_id',
                'no_akun',
                'nama as akun_nama',
                'tipe_akun',
                'kategori',
                'saldo_normal',
                'parent_id',
                'level',
                'sort_path'
            )
            ->whereIn('tipe_akun', ['aktiva', 'pasiva', 'modal'])
            ->get();

        // 🔹 Ambil saldo awal dari tabel m_saldo_awal
        $saldo_awal = DB::table('m_saldo_awal')
            ->when($entitas_id, fn($q) => $q->where('entitas_id', $entitas_id))
            ->where('periode', '=', $periode)
            ->select('akun_gl_id', DB::raw('SUM(saldo) as saldo_awal'))
            ->groupBy('akun_gl_id')
            ->get()
            ->keyBy('akun_gl_id');

        // 🔹 Ambil saldo mutasi (periode berjalan) dari buku besar
        $mutasi = DB::table('buku_besar as b')
            ->join('jurnal_header as j', 'j.id', '=', 'b.jurnal_id')
            ->join('m_akun_gl as a', 'a.id', '=', 'b.akun_id')
            ->where('j.status', 'posted')
            ->when($entitas_id, fn($q) => $q->where('j.entitas_id', $entitas_id))
            ->whereBetween('b.tanggal', [$periode_awal, $periode_akhir])
            ->select(
                'b.akun_id',
                DB::raw('MIN(b.tanggal) as tanggal_awal'),
                DB::raw('MAX(b.tanggal) as tanggal_akhir'),
                DB::raw('SUM(b.debit) as total_debit'),
                DB::raw('SUM(b.kredit) as total_kredit'),
                DB::raw("
                    SUM(
                        CASE 
                            WHEN a.saldo_normal = 'debet' THEN (b.debit - b.kredit)
                            WHEN a.saldo_normal = 'kredit' THEN (b.kredit - b.debit)
                            ELSE 0
                        END
                    ) as mutasi
                ")
            )
            ->groupBy('b.akun_id')
            ->get()
            ->keyBy('akun_id');

        // 🔹 Gabungkan akun + saldo awal + mutasi
        $data = $akun->map(function ($row) use ($saldo_awal, $mutasi, $akun) {
            $children = $akun->filter(fn($a) => str_starts_with($a->sort_path, $row->sort_path . '.'));

            // Ambil saldo awal sendiri + anak
            $row->saldo_awal = ($saldo_awal[$row->akun_id]->saldo_awal ?? 0)
                + $children->sum(fn($c) => $saldo_awal[$c->akun_id]->saldo_awal ?? 0);

            // Ambil mutasi debit/kredit sendiri + anak
            $row->total_debit = ($mutasi[$row->akun_id]->total_debit ?? 0)
                + $children->sum(fn($c) => $mutasi[$c->akun_id]->total_debit ?? 0);

            $row->total_kredit = ($mutasi[$row->akun_id]->total_kredit ?? 0)
                + $children->sum(fn($c) => $mutasi[$c->akun_id]->total_kredit ?? 0);

            // Mutasi (selisih debit-kredit)
            $row->mutasi = ($mutasi[$row->akun_id]->mutasi ?? 0)
                + $children->sum(fn($c) => $mutasi[$c->akun_id]->mutasi ?? 0);

            // Hitung saldo akhir
            $row->saldo_akhir = ($row->saldo_awal ?? 0) + ($row->mutasi ?? 0);

            // Ambil tanggal min dan max
            $row->tanggal_awal = $mutasi[$row->akun_id]->tanggal_awal ?? null;
            $row->tanggal_akhir = $mutasi[$row->akun_id]->tanggal_akhir ?? null;

            return $row;
        });

        return response()->json(['data' => $data]);
    }


    public function exportNeraca(Request $request)
    {
        // Tentukan entitas yang digunakan
        if (auth()->user()->level == 'entitas') {
            $entitas_id = $request->entitas_scope; 
        } else {
            $entitas_id = $request->entitas_id; // filter dari dropdown jika admin/pusat
        }
        $periode = $request->periode ?? date('Y-m');

        $filename = 'Laporan_Neraca_' . $periode . '.xlsx';
        return Excel::download(new NeracaExport($entitas_id, $periode), $filename);
    }

    // ==============================
    // 💰 LABA RUGI
    // ==============================
    public function indexLabaRugi()
    {
        return view('page.laporan.pbl');
    }

    public function dataLabaRugi(Request $request)
    {
        // Tentukan entitas yang digunakan
        if (auth()->user()->level == 'entitas') {
            $entitas_id = $request->entitas_scope; 
        } else {
            $entitas_id = $request->entitas_id; // filter dari dropdown jika admin/pusat
        }
        $periode = $request->periode ?? date('Y-m'); // contoh: "2025-11"
        $periode_awal = $periode . '-01';
        $periode_akhir = date('Y-m-t', strtotime($periode_awal));

        // 🔹 Ambil seluruh akun dari view_akun_hirarki
        $akun = DB::table('view_akun_hirarki')
            ->select(
                'id as akun_id',
                'no_akun',
                'nama as akun_nama',
                'tipe_akun',
                'kategori',
                'saldo_normal',
                'parent_id',
                'level',
                'sort_path'
            )
            ->whereIn('tipe_akun', ['pendapatan', 'beban'])
            ->get();


        // 🔹 Ambil saldo mutasi (periode berjalan) dari buku besar
        $mutasi = DB::table('buku_besar as b')
            ->join('jurnal_header as j', 'j.id', '=', 'b.jurnal_id')
            ->join('m_akun_gl as a', 'a.id', '=', 'b.akun_id')
            ->where('j.status', 'posted')
            ->when($entitas_id, fn($q) => $q->where('j.entitas_id', $entitas_id))
            ->whereBetween('b.tanggal', [$periode_awal, $periode_akhir])
            ->select(
                'b.akun_id',
                DB::raw('MIN(b.tanggal) as tanggal_awal'),
                DB::raw('MAX(b.tanggal) as tanggal_akhir'),
                DB::raw('SUM(b.debit) as total_debit'),
                DB::raw('SUM(b.kredit) as total_kredit'),
                DB::raw("
                    SUM(
                        CASE 
                            WHEN a.saldo_normal = 'debet' THEN (b.debit - b.kredit)
                            WHEN a.saldo_normal = 'kredit' THEN (b.kredit - b.debit)
                            ELSE 0
                        END
                    ) as mutasi
                ")
            )
            ->groupBy('b.akun_id')
            ->get()
            ->keyBy('akun_id');

        // 🔹 Gabungkan akun + saldo awal + mutasi
        $data = $akun->map(function ($row) use ($mutasi, $akun) {
            $children = $akun->filter(fn($a) => str_starts_with($a->sort_path, $row->sort_path . '.'));

            // Ambil mutasi debit/kredit sendiri + anak
            $row->total_debit = ($mutasi[$row->akun_id]->total_debit ?? 0)
                + $children->sum(fn($c) => $mutasi[$c->akun_id]->total_debit ?? 0);

            $row->total_kredit = ($mutasi[$row->akun_id]->total_kredit ?? 0)
                + $children->sum(fn($c) => $mutasi[$c->akun_id]->total_kredit ?? 0);

            // Mutasi (selisih debit-kredit)
            $row->mutasi = ($mutasi[$row->akun_id]->mutasi ?? 0)
                + $children->sum(fn($c) => $mutasi[$c->akun_id]->mutasi ?? 0);

            // Hitung saldo akhir
            $row->saldo_akhir = ($row->mutasi ?? 0);

            // Ambil tanggal min dan max
            $row->tanggal_awal = $mutasi[$row->akun_id]->tanggal_awal ?? null;
            $row->tanggal_akhir = $mutasi[$row->akun_id]->tanggal_akhir ?? null;

            return $row;
        });
        
        // 🔹 Hitung total pendapatan & beban (hanya akun yang ada transaksi/mutasi)
        $total_pendapatan = $data
            ->where('tipe_akun', 'pendapatan')
            ->filter(fn($a) => isset($mutasi[$a->akun_id])) // ← hanya yang punya transaksi
            ->sum('saldo_akhir');

        $total_beban = $data
            ->where('tipe_akun', 'beban')
            ->filter(fn($a) => isset($mutasi[$a->akun_id])) // ← hanya yang punya transaksi
            ->sum('saldo_akhir');

        $laba_bersih = $total_pendapatan - $total_beban;
        // if ($laba_bersih > 0 && $total_pendapatan == 0) {
        //     // Ini kasus BEBAN SAJA → laba harus negatif
        //     $laba_bersih = -$total_beban;
        // }
        
        return response()->json([
            'data' => $data,
            'total_pendapatan' => $total_pendapatan,
            'total_beban' => $total_beban,
            'laba_bersih' => $laba_bersih,
        ]);
    }

    public function exportLabaRugi(Request $request)
    {
        // Tentukan entitas yang digunakan
        if (auth()->user()->level == 'entitas') {
            $entitas_id = $request->entitas_scope; 
        } else {
            $entitas_id = $request->entitas_id; // filter dari dropdown jika admin/pusat
        }
        $periode = $request->periode ?? date('Y-m');

        $filename = 'Laporan_PBL_' . $periode . '.xlsx';
        return Excel::download(new PblExport($entitas_id, $periode), $filename);
    }

     public function indexAruskas()
    {
        return view('page.laporan.aruskas');
    }

     public function exportArusKas(Request $request)
    {
         // Tentukan entitas yang digunakan
        if (auth()->user()->level == 'entitas') {
            $entitas = $request->entitas_scope; 
        } else {
            $entitas = $request->entitas_id; // filter dari dropdown jika admin/pusat
        }
        $periode = $request->periode ?? date('Y-m');
        
        $filename = 'Laporan_Arus_Kas_' . $periode . '.xlsx';
        return Excel::download(new ArusKasExport($entitas, $periode), $filename);
    }

    public function dataArusKas(Request $request)
    {
         // Tentukan entitas yang digunakan
        if (auth()->user()->level == 'entitas') {
            $entitas = $request->entitas_scope; 
        } else {
            $entitas = $request->entitas_id; // filter dari dropdown jika admin/pusat
        }
        $periode = $request->periode ?? date('Y-m'); // contoh: "2025-11"
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

        // 5️⃣ Return JSON
        return response()->json([
            'per_entitas' => array_values($laporan),
            'grand_total' => $grand
        ]);
    }

    public function indexBukuBesar(Request $request)
    {
         // Tentukan entitas yang digunakan
        if (auth()->user()->level == 'entitas') {
            $entitas = $request->entitas_scope; 
        } else {
            $entitas = $request->entitas_id; // filter dari dropdown jika admin/pusat
        }
        $periode  = $request->periode ?? date('Y-m');

        $tglAwal  = $periode . '-01';
        $tglAkhir = date('Y-m-t', strtotime($periode . '-01'));

        if ($request->ajax()) {

            $query = DB::table('buku_besar as b')
                 ->leftJoin('m_akun_gl as a', 'a.id', '=', 'b.akun_id')
                ->leftJoin('m_entitas as e', 'e.id', '=', 'b.entitas_id')
                ->leftJoin('m_partner as p', 'p.id', '=', 'b.partner_id')
                ->select(
                    'b.id',
                    'b.kode_jurnal',
                    DB::raw("CONCAT(a.no_akun,' - ',a.nama) AS akun_gl"),
                    'e.nama as entitas',
                    'p.nama as partner',
                    'b.keterangan',
                    'b.tanggal',
                    'b.debit',
                    'b.kredit'
                )
                ->whereBetween('b.tanggal', [$tglAwal, $tglAkhir])
                ->when($entitas, fn($q) => $q->where('b.entitas_id', $entitas))
                ->orderBy('b.tanggal', 'asc');

            return DataTables::of($query)
                ->addIndexColumn()
                // Format tanggal
                ->editColumn('tanggal', function ($row) {
                    return date('d-m-Y', strtotime($row->tanggal));
                })

                // Filter search kolom custom
                ->filterColumn('akun_gl', function ($q, $keyword) {
                    $q->whereRaw("CONCAT(a.no_akun,' - ',a.nama) LIKE ?", ["%{$keyword}%"]);
                })

                ->filterColumn('entitas', function ($q, $keyword) {
                    $q->where('e.nama', 'LIKE', "%{$keyword}%");
                })

                ->filterColumn('partner', function ($q, $keyword) {
                    $q->where('p.nama', 'LIKE', "%{$keyword}%");
                })

            ->make(true);
        }
        return view('page.laporan.bukubesar');
    }

    public function exportBukuBesar(Request $request)
    {
         // Tentukan entitas yang digunakan
        if (auth()->user()->level == 'entitas') {
            $entitas = $request->entitas_scope; 
        } else {
            $entitas = $request->entitas_id; // filter dari dropdown jika admin/pusat
        }
        $periode = $request->periode ?? date('Y-m');

        $tglAwal = $periode . '-01';
        $tglAkhir = date('Y-m-t', strtotime($periode));

        $filename = "Buku-Besar-{$periode}.xlsx";

        return Excel::download(
            new BukuBesarExport($entitas, $periode),
            $filename
        );
    }
}
