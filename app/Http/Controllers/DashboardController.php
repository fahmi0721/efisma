<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // default entitas kosong (semua entitas)
        // periode default bulan berjalan: "YYYY-MM"
        $periode = $request->periode ?? Carbon::now()->format('Y-m');
        // kirim ke view, frontend akan inisialisasi Select2 untuk entitas
        return view('dashboard', compact('periode'));
    }

    /**
     * Ringkasan utama: aset, liabilitas, ekuitas, pendapatan, beban, laba, kas, piutang, utang
     */
    public function apiSummary(Request $request)
    {
        $entitasId = $request->entitas_id ?: null;

        // === Ambil periode dari request, default bulan ini ===
        $periode = $request->periode ?: date('Y-m');
        $start = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $periode)->endOfMonth();
        // hanya jurnal posted
        $baseQuery = DB::table('buku_besar as b')
            ->join('jurnal_header as j','j.id','b.jurnal_id')
            ->join('m_akun_gl as a','a.id','b.akun_id')
            ->where('j.status','posted') 
            ->whereBetween('j.tanggal', [$start, $end])  // ★ FILTER PERIODE
            ->when($entitasId, fn($q) => $q->where('j.entitas_id', $entitasId));

        // Aset (aktiva)
        $aset = (clone $baseQuery)
            ->whereIn('a.tipe_akun', ['aktiva'])
            ->selectRaw("SUM(
                CASE WHEN a.saldo_normal = 'debet' THEN (b.debit - b.kredit)
                     WHEN a.saldo_normal = 'kredit' THEN (b.kredit - b.debit)
                     ELSE 0 END
            ) as val")
            ->value('val') ?: 0;

        // Liabilitas (pasiva)
        $liabilitas = (clone $baseQuery)
            ->whereIn('a.tipe_akun', ['pasiva'])
            ->selectRaw("SUM(
                CASE WHEN a.saldo_normal = 'debet' THEN (b.debit - b.kredit)
                     WHEN a.saldo_normal = 'kredit' THEN (b.kredit - b.debit)
                     ELSE 0 END
            ) as val")
            ->value('val') ?: 0;

        // Ekuitas (modal)
        $ekuitas = (clone $baseQuery)
            ->whereIn('a.tipe_akun', ['modal'])
            ->selectRaw("SUM(
                CASE WHEN a.saldo_normal = 'debet' THEN (b.debit - b.kredit)
                     WHEN a.saldo_normal = 'kredit' THEN (b.kredit - b.debit)
                     ELSE 0 END
            ) as val")
            ->value('val') ?: 0;

        // Pendapatan
        $pendapatan = (clone $baseQuery)
            ->where('a.tipe_akun','pendapatan')
            ->selectRaw("SUM(b.kredit - b.debit) as val")
            ->value('val') ?: 0;

        // Beban
        $beban = (clone $baseQuery)
            ->where('a.tipe_akun','beban')
            ->selectRaw("SUM(b.debit - b.kredit) as val")
            ->value('val') ?: 0;

        // Kas: pilih akun dengan kategori 'kas' atau nama like '%kas%'
        $kas = (clone $baseQuery)
            ->where(function($q){
                $q->where('a.kategori','kas')
                  ->orWhere('a.nama','like','%kas%')
                  ->orWhere('a.nama','like','%bank%');
            })
            ->selectRaw("SUM(b.debit - b.kredit) as val")
            ->value('val') ?: 0;

        // Piutang: kategori 'piutang'
        $piutang = (clone $baseQuery)
            ->where('a.kategori','piutang')
            ->selectRaw("SUM(b.debit - b.kredit) as val")
            ->value('val') ?: 0;

        // Utang: kategori 'utang' atau tipe pasiva
        $utang = (clone $baseQuery)
            ->where(function($q){
                $q->where('a.kategori','utang')
                  ->orWhere('a.tipe_akun','pasiva');
            })
            ->selectRaw("SUM(
                CASE WHEN a.saldo_normal = 'debet' THEN (b.debit - b.kredit)
                     WHEN a.saldo_normal = 'kredit' THEN (b.kredit - b.debit)
                     ELSE 0 END
            ) as val")
            ->value('val') ?: 0;

        $laba = $pendapatan - $beban;

        return response()->json([
            'aset' => (float)$aset,
            'liabilitas' => (float)$liabilitas,
            'ekuitas' => (float)$ekuitas,
            'pendapatan' => (float)$pendapatan,
            'beban' => (float)$beban,
            'laba' => (float)$laba,
            'kas' => (float)$kas,
            'piutang' => (float)$piutang,
            'utang' => (float)$utang,
        ]);
    }

    /**
     * Cashflow monthly net change for the selected year-month (periode)
     * returns array labels (days or months) and values
     */
    public function apiCashflow(Request $request)
    {
        $entitasId = $request->entitas_id ?: null;
        // periode default current month
        $periode = $request->periode ?? Carbon::now()->format('Y-m');
        $start = Carbon::parse($periode . '-01')->startOfMonth();
        $end = Carbon::parse($periode . '-01')->endOfMonth();

        // We'll calculate daily cash change for the month for cash accounts
        $data = DB::table('buku_besar as b')
            ->join('jurnal_header as j','j.id','b.jurnal_id')
            ->join('m_akun_gl as a','a.id','b.akun_id')
            ->where('j.status','posted')
            ->whereBetween('b.tanggal', [$start->toDateString(), $end->toDateString()])
            ->when($entitasId, fn($q) => $q->where('j.entitas_id', $entitasId))
            ->where(function($q){
                $q->where('a.kategori','kas_bank');
            })
            ->selectRaw("DATE(b.tanggal) as tanggal, SUM(b.debit - b.kredit) as net")
            ->groupByRaw("DATE(b.tanggal)")
            ->orderBy('tanggal')
            ->get();

        // Prepare labels for each day of month and values (0 if no data)
        $labels = [];
        $values = [];
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end->addDay());
        foreach ($period as $d) {
            $labels[] = $d->format('j M');
            $found = $data->firstWhere('tanggal', $d->format('Y-m-d'));
            $values[] = $found ? (float)$found->net : 0;
        }

        return response()->json(['labels' => $labels, 'values' => $values]);
    }

    /**
     * Composition breakdown: top accounts for aktiva and pasiva (limit configurable)
     */
    public function apiComposition(Request $request)
    {
        $entitasId = $request->entitas_id ?: null;
        $limit = intval($request->limit ?: 10);

        // ► Ambil periode Y-m
        $periode = $request->periode ?: date('Y-m');

        // ► Ubah ke awal & akhir bulan
        $start = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $periode)->endOfMonth();

        $base = DB::table('buku_besar as b')
            ->join('jurnal_header as j','j.id','b.jurnal_id')
            ->join('m_akun_gl as a','a.id','b.akun_id')
            ->where('j.status','posted')
            ->whereBetween('j.tanggal', [$start, $end]) // ★ FILTER PERIODE
            ->when($entitasId, fn($q) => $q->where('j.entitas_id', $entitasId));

        // ASSETS
        $assets = (clone $base)
            ->where('a.tipe_akun','aktiva')
            ->selectRaw("
                a.id, a.no_akun, a.nama, 
                SUM(
                    CASE 
                        WHEN a.saldo_normal='debet'  THEN (b.debit - b.kredit) 
                        WHEN a.saldo_normal='kredit' THEN (b.kredit - b.debit)
                        ELSE 0 
                    END
                ) as val
            ")
            ->groupBy('a.id','a.no_akun','a.nama')
            ->orderByDesc('val')
            ->limit($limit)
            ->get();

        // LIABILITIES
        $liabs = (clone $base)
            ->where('a.tipe_akun','pasiva')
            ->selectRaw("
                a.id, a.no_akun, a.nama, 
                SUM(
                    CASE 
                        WHEN a.saldo_normal='debet'  THEN (b.debit - b.kredit) 
                        WHEN a.saldo_normal='kredit' THEN (b.kredit - b.debit)
                        ELSE 0 
                    END
                ) as val
            ")
            ->groupBy('a.id','a.no_akun','a.nama')
            ->orderByDesc('val')
            ->limit($limit)
            ->get();

        return response()->json([
            'assets' => $assets,
            'liabs' => $liabs,
        ]);
    }


    /**
     * Top 10 aging piutang (using view_aging_piutang if available)
     */
    public function apiAgingTop(Request $request)
    {
        $entitasId = $request->entitas_id ?: null;
        $limit = intval($request->limit ?: 10);

        $q = DB::table('view_aging_piutang')->select('partner_id','partner_nama','aging_0_30','aging_31_60','aging_61_90','aging_90_plus','total_piutang');

        if ($entitasId) {
            $q->where('entitas_id', $entitasId);
        }

        $data = $q->orderByDesc('total_piutang')->limit($limit)->get();

        return response()->json(['data' => $data]);
    }

    function apiPendapatanBeban(Request $request)
    {
        $entitasId = $request->entitas_id ?: null;
        $periode = $request->periode ?: date('Y-m');

        $start = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $periode)->endOfMonth();

        $labels = [];
        $pendapatan = [];
        $beban = [];

        for ($d = $start->copy(); $d <= $end; $d->addDay()) {

            $tanggal = $d->format('Y-m-d');

            // Pendapatan harian
            $pend = DB::table('buku_besar as b')
                ->join('jurnal_header as j','j.id','b.jurnal_id')
                ->join('m_akun_gl as a','a.id','b.akun_id')
                ->where('a.tipe_akun','pendapatan')
                ->where('j.status','posted')
                ->whereDate('j.tanggal', $tanggal)
                ->when($entitasId, fn($q)=>$q->where('j.entitas_id',$entitasId))
                ->sum(DB::raw('b.kredit - b.debit'));

            // Beban harian
            $beb = DB::table('buku_besar as b')
                ->join('jurnal_header as j','j.id','b.jurnal_id')
                ->join('m_akun_gl as a','a.id','b.akun_id')
                ->where('a.tipe_akun','beban')
                ->where('j.status','posted')
                ->whereDate('j.tanggal', $tanggal)
                ->when($entitasId, fn($q)=>$q->where('j.entitas_id',$entitasId))
                ->sum(DB::raw('b.debit - b.kredit'));

            // Assign
            $labels[] = $d->format('d');
            $pendapatan[] = (float)$pend;
            $beban[] = (float)$beb;
        }

        return response()->json([
            'labels'     => $labels,
            'pendapatan' => $pendapatan,
            'beban'      => $beban,
        ]);
    }

    public function apiLabaRugiHarian(Request $request)
    {
        $entitasId = $request->entitas_id;
        $periode   = $request->periode;

        // Panggil fungsi sebelumnya
        $req = Request::create('/fake', 'GET', [
            'entitas_id' => $entitasId,
            'periode'    => $periode
        ]);

        $pb = $this->apiPendapatanBeban($req)->getData();

        $laba = [];
        foreach ($pb->pendapatan as $i => $p) {
            $laba[] = (float)$p - (float)$pb->beban[$i];
        }

        return response()->json([
            'labels' => $pb->labels,
            'laba' => $laba
        ]);
    }
}
