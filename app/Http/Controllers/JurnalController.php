<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use App\Helpers\PeriodeHelper;
use App\Helpers\JurnalService;
use App\Helpers\PelunasanPiutangService;
use App\Helpers\UangMukaService;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Validator;

class JurnalController extends Controller
{
    public function index(Request $request, $jenis = null)
    {
        if ($request->ajax()) {
            $query = DB::table('jurnal_header as j')
                ->leftJoin('m_entitas as e', 'j.entitas_id', '=', 'e.id')
                ->leftJoin('m_partner as p', 'j.partner_id', '=', 'p.id')
                ->leftJoin('m_cabang as c', 'j.cabang_id', '=', 'c.id') // join cabang
                ->select(
                    'j.id',
                    'j.kode_jurnal',
                    'j.jenis',
                    'j.tanggal',
                    'j.keterangan',
                    'j.no_invoice',
                    'e.nama as entitas',
                    'p.nama as partner',
                    'c.nama as cabang',       // ambil nama cabang
                    'j.total_debit',
                    'j.total_kredit',
                    'j.status'
                )
                ->where('j.jenis', $jenis);
            /*
            |--------------------------------------------------------------------------
            | 1. FILTER WAJIB UNTUK USER LEVEL ENTITAS
            |--------------------------------------------------------------------------
            */
            if ($request->entitas_scope) {
                $query->where('j.entitas_id', $request->entitas_scope);
            }
            // 🔹 Filter tanggal
            if ($request->filled('from') && $request->filled('to')) {
                $query->whereBetween('j.tanggal', [$request->from, $request->to]);
            }

            // 🔹 Filter status
            if ($request->filled('status')) {
                $query->where('j.status', $request->status);
            }

            $query->orderByRaw("CASE WHEN j.status = 'draft' THEN 0 ELSE 1 END")
                ->orderByDesc('j.id');

            return DataTables::of($query)
                ->addIndexColumn()

                // 🔍 Perbaiki pencarian global agar kolom alias ikut terdeteksi
                ->filterColumn('entitas', function ($query, $keyword) {
                    $query->where('e.nama', 'like', "%{$keyword}%");
                })
                ->filterColumn('partner', function ($query, $keyword) {
                    $query->where('p.nama', 'like', "%{$keyword}%");
                })
                ->filterColumn('cabang', function ($query, $keyword) {
                    $query->where('c.nama', 'like', "%{$keyword}%");
                })
                ->filterColumn('kode_jurnal', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(j.kode_jurnal, ' ', COALESCE(j.no_invoice, '')) LIKE ?", ["%{$keyword}%"]);
                })
                ->filterColumn('keterangan', function ($query, $keyword) {
                    $query->where('j.keterangan', 'like', "%{$keyword}%");
                })

                // 🔸 Format tanggal ke tampilan Indonesia
                ->editColumn('tanggal', function ($row) {
                    return \Carbon\Carbon::parse($row->tanggal)->translatedFormat('d F Y');
                })

                // 🔸 Badge status
                ->editColumn('status', function ($row) {
                    return $row->status === "draft"
                        ? "<span class='badge text-bg-secondary'>Draft</span>"
                        : "<span class='badge text-bg-success'>Posted</span>";
                })

                // 🔸 Tombol aksi
                ->addColumn('aksi', function ($row) use ($jenis) {
                    $pg = [
                        "JP" => "pendapatan",
                        "JKM" => "kasmasuk",
                        "JKK" => "kaskeluar",
                        "JN" => "penyesuaian",
                    ];

                    $url = route('jurnal.' . $pg[$jenis] . '.edit') . "?id=" . $row->id;
                    $html = '<div class="btn-group btn-group-sm">';
                    if(canAccess('pendapatan.view|kas_masuk.view|kas_keluar.view|penyesuaian.view')){
                        $html .= '<button title="Detail Transaksi" data-toggle="tooltip" class="btn btn-info btn-view" onclick="detail_transaksi(' . $row->id . ')"><i class="fas fa-eye"></i></button>';
                    }
                    // 🔒 Cek status periode akuntansi
                    $periodeStatus = DB::table('periode_akuntansi')
                        ->whereRaw("? BETWEEN tanggal_mulai AND tanggal_selesai", [$row->tanggal])
                        ->value('status');

                    $isClosed = $periodeStatus === 'closed';

                    if ($row->status === "draft" && !$isClosed) {
                        /** tombol edit */
                        if(canAccess('pendapatan.edit') && $jenis == "JP"){
                            $html .= '<a title="Edit" data-toggle="tooltip" href="' . $url . '" class="btn btn-warning btn-edit"><i class="fas fa-edit"></i></a>';
                        }elseif(canAccess('kas_masuk.edit') && $jenis == "JKM"){
                            $html .= '<a title="Edit" data-toggle="tooltip" href="' . $url . '" class="btn btn-warning btn-edit"><i class="fas fa-edit"></i></a>';
                        }elseif(canAccess('kas_keluar.edit') && $jenis == "JKK"){
                            $html .= '<a title="Edit" data-toggle="tooltip" href="' . $url . '" class="btn btn-warning btn-edit"><i class="fas fa-edit"></i></a>';
                        }elseif(canAccess('penyesuaian.edit') && $jenis == "JN"){
                            $html .= '<a title="Edit" data-toggle="tooltip" href="' . $url . '" class="btn btn-warning btn-edit"><i class="fas fa-edit"></i></a>';
                        }

                        /** tombol delete */
                        if(canAccess('pendapatan.delete') && $jenis == "JP"){
                            $html .= '<button title="Delete" data-toggle="tooltip" class="btn btn-danger btn-delete" onclick="hapusData(' . $row->id . ')"><i class="fas fa-trash"></i></button>';
                        }elseif(canAccess('kas_masuk.delete') && $jenis == "JKM"){
                            $html .= '<button title="Delete" data-toggle="tooltip" class="btn btn-danger btn-delete" onclick="hapusData(' . $row->id . ')"><i class="fas fa-trash"></i></button>';
                        }elseif(canAccess('kas_keluar.delete') && $jenis == "JKK"){
                            $html .= '<button title="Delete" data-toggle="tooltip" class="btn btn-danger btn-delete" onclick="hapusData(' . $row->id . ')"><i class="fas fa-trash"></i></button>';
                        }elseif(canAccess('penyesuaian.delete') && $jenis == "JN"){
                            $html .= '<button title="Delete" data-toggle="tooltip" class="btn btn-danger btn-delete" onclick="hapusData(' . $row->id . ')"><i class="fas fa-trash"></i></button>';
                        }

                        /** tombol posting */
                        if(canAccess('pendapatan.posting') && $jenis == "JP"){
                            $html .= '<button title="Posting" data-toggle="tooltip" class="btn btn-success btn-posting" onclick="posting(' . $row->id . ')"><i class="fas fa-bolt"></i></button>';
                        }elseif(canAccess('kas_masuk.posting') && $jenis == "JKM"){
                            $html .= '<button title="Posting" data-toggle="tooltip" class="btn btn-success btn-posting" onclick="posting(' . $row->id . ')"><i class="fas fa-bolt"></i></button>';
                        }elseif(canAccess('kas_keluar.posting') && $jenis == "JKK"){
                            $html .= '<button title="Posting" data-toggle="tooltip" class="btn btn-success btn-posting" onclick="posting(' . $row->id . ')"><i class="fas fa-bolt"></i></button>';
                        }elseif(canAccess('penyesuaian.posting') && $jenis == "JN"){
                            $html .= '<button title="Posting" data-toggle="tooltip" class="btn btn-success btn-posting" onclick="posting(' . $row->id . ')"><i class="fas fa-bolt"></i></button>';
                        }

                    } elseif ($row->status === "posted") {
                        if ($isClosed) {
                            $html .= '<button title="Periode Closed" data-toggle="tooltip" class="btn btn-secondary" disabled><i class="fas fa-lock"></i></button>';
                        } else {
                            if(canAccess('pendapatan.unposting') && $jenis == "JP"){
                                $html .= '<button title="Unposting" data-toggle="tooltip" class="btn btn-danger btn-posting" onclick="unposting(' . $row->id . ')"><i class="fas fa-bolt"></i></button>';
                            }elseif(canAccess('kas_masuk.unposting') && $jenis == "JKM"){
                                $html .= '<button title="Unposting" data-toggle="tooltip" class="btn btn-danger btn-posting" onclick="unposting(' . $row->id . ')"><i class="fas fa-bolt"></i></button>';
                            }elseif(canAccess('kas_keluar.unposting') && $jenis == "JKK"){
                                $html .= '<button title="Unposting" data-toggle="tooltip" class="btn btn-danger btn-posting" onclick="unposting(' . $row->id . ')"><i class="fas fa-bolt"></i></button>';
                            }elseif(canAccess('penyesuaian.unposting') && $jenis == "JN"){
                                $html .= '<button title="Unposting" data-toggle="tooltip" class="btn btn-danger btn-posting" onclick="unposting(' . $row->id . ')"><i class="fas fa-bolt"></i></button>';
                            }
                            
                        }
                    }

                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['aksi', 'status'])
                ->make(true);
        }

        // 🔹 Pilih view sesuai jenis jurnal
        $pg = [
            "JP" => "pendapatan",
            "JKM" => "kas_masuk",
            "JKK" => "kas_keluar",
            "JN" => "penyesuaian",
        ];

        return view('page.jurnal.index_' . $pg[$jenis]);
    }

    public function rincian_deposito(Request $request){
        if ($request->entitas_scope) {
            $entitasId  = $request->entitas_scope;
        }else{
            $entitasId  = $request->entitas_id;
        }
        $customerId = $request->customer_id;

        // -----------------------------------------
        // Ambil daftar akun deposit
        // -----------------------------------------
        $akunDeposits = DB::table('m_akun_gl')
            ->where('kategori', 'deposito_customer')
            ->pluck('id')
            ->toArray();

        if (empty($akunDeposits)) {
            return DataTables::of([])->make(true);
        }

        // -----------------------------------------
        // AMBIL TOTAL MASUK (POSTED JOURNAL)
        // Group per entitas, customer, akun
        // -----------------------------------------
        $depositIn = DB::table('jurnal_detail AS d')
            ->join('jurnal_header AS h', 'h.id', '=', 'd.jurnal_id')
            ->join('m_akun_gl AS a', 'a.id', '=', 'd.akun_id')
            ->join('m_entitas AS e', 'e.id', '=', 'h.entitas_id')
            ->join('m_partner AS p', 'p.id', '=', 'h.partner_id')
            ->select(
                'h.entitas_id',
                'h.partner_id',
                'd.akun_id',
                'a.no_akun',
                'a.nama AS akun_nama',
                'e.nama AS entitas_nama',
                'p.nama AS customer_nama',
                DB::raw('SUM(kredit) AS total_in')
            )
            ->whereIn('d.akun_id', $akunDeposits)
            ->where('h.status', 'posted')
            ->when($entitasId, fn($q) => $q->where('h.entitas_id', $entitasId))
            ->when($customerId, fn($q) => $q->where('h.partner_id', $customerId))
            ->groupBy(
                'h.entitas_id',
                'h.partner_id',
                'd.akun_id',
                'a.no_akun',
                'a.nama',
                'e.nama',
                'p.nama'
            )
            ->get();

        // -----------------------------------------
        // AMBIL TOTAL DIGUNAKAN (pelunasan_deposit)
        // -----------------------------------------
        $depositUsed = DB::table('pelunasan_deposit')
            ->select(
                'entitas_id',
                'partner_id',
                'akun_deposit_id AS akun_id',
                DB::raw('SUM(jumlah) AS total_used')
            )
            ->when($entitasId, fn($q) => $q->where('entitas_id', $entitasId))
            ->when($customerId, fn($q) => $q->where('partner_id', $customerId))
            ->groupBy('entitas_id', 'partner_id', 'akun_deposit_id')
            ->get();

        // Index untuk lookup cepat
        $usedIndex = [];
        foreach ($depositUsed as $u) {
            $key = "{$u->entitas_id}-{$u->partner_id}-{$u->akun_id}";
            $usedIndex[$key] = $u->total_used;
        }

        // -----------------------------------------
        // BUILD final data
        // -----------------------------------------
        $data = [];

        foreach ($depositIn as $row) {

            $key = "{$row->entitas_id}-{$row->partner_id}-{$row->akun_id}";
            $totalUsed = $usedIndex[$key] ?? 0;

            $saldo = $row->total_in - $totalUsed;

            $data[] = [
                'entitas'     => $row->entitas_nama,
                'customer'    => $row->customer_nama,
                'akun'        => "{$row->no_akun} - {$row->akun_nama}",
                'total_in'    => $row->total_in,
                'total_used'  => $totalUsed,
                'saldo'       => $saldo,
            ];
        }

        return DataTables::of($data)
            ->editColumn('total_in', fn($row) => number_format($row['total_in'], 0, ',', '.'))
            ->editColumn('total_used', fn($row) => number_format($row['total_used'], 0, ',', '.'))
            ->editColumn('saldo', function ($row) {
                $saldo = number_format($row['saldo'], 0, ',', '.');
                return $row['saldo'] > 0
                    ? "<span class='text-success fw-bold'>Rp {$saldo}</span>"
                    : "<span class='text-danger fw-bold'>Rp {$saldo}</span>";
            })
            ->rawColumns(['saldo'])
            ->make(true);
    }

    public function create(Request $request,$jenis=null){
        $pg = array(
            "JP" => "pendapatan",
            "JKM" => "kas_masuk",
            "JKK" => "kas_keluar",
            "JN" => "penyesuaian",
        );
        return view('page.jurnal.tambah_'.$pg[$jenis]);
    }

    public function edit(Request $request, $jenis = null)
    {
        $pg = [
            "JP" => "pendapatan",
            "JKM" => "kas_masuk",
            "JKK" => "kas_keluar",
            "JN" => "penyesuaian",
        ];

        $id = $request->id;

        $data = DB::table("jurnal_header")->where("id", $id)->first();
        $entitas_id = DB::table('m_entitas')->where("id", $data->entitas_id)->first();
        $partner_id = DB::table('m_partner')->where("id", $data->partner_id)->first();
        $cabang_id = DB::table('m_cabang')->where("id", $data->cabang_id)->first();

        $detail = DB::table('jurnal_detail as d')
            ->join('m_akun_gl as a', 'd.akun_id', '=', 'a.id')
            ->where('d.jurnal_id', $id)
            ->select(
                DB::raw("CONCAT(a.no_akun, ' - ', a.nama) as akun_gl"),
                'd.deskripsi',
                'akun_id',
                DB::raw("CAST(d.debit AS UNSIGNED) as debit"),
                DB::raw("CAST(d.kredit AS UNSIGNED) as kredit"),
                'a.kategori' // 🔹 tambahkan ini
            )
            ->get();

        // 🔹 Tambahan khusus untuk JKM → Cek apakah jurnal ini melunasi invoice JP
        $pelunasan = null;
        if (in_array($jenis,array("JKM","JN"))) {
            $pelunasan = DB::table('pelunasan_piutang as pp')
                ->join('jurnal_header as jp', 'jp.id', '=', 'pp.jurnal_piutang_id')
                ->select(
                    'pp.id',
                    'pp.jurnal_piutang_id',
                    'pp.jumlah',
                    'jp.kode_jurnal as kode_invoice',
                    'jp.total_debit as total_tagihan'
                )
                ->where('pp.jurnal_kas_id', $id)
                ->first();
        }

        return view('page.jurnal.edit_' . $pg[$jenis], compact(
            "data",
            "id",
            "entitas_id",
            "cabang_id",
            "partner_id",
            "detail",
            "pelunasan"
        ));
    }

    public function datatableUangMuka(Request $request){
        $entitas = $request->entitas_id;

        $query = DB::table('view_uang_muka_per_akun')
                    ->join("m_cabang","m_cabang.id","view_uang_muka_per_akun.cabang_id")
                    ->select("view_uang_muka_per_akun.*","m_cabang.nama as nama_cabang")
                    ->where('view_uang_muka_per_akun.sisa', '>', 0);

        if ($entitas) {
            $query->where('view_uang_muka_per_akun.entitas_id', $entitas);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('aksi', function($row){
                return "<button class='btn btn-sm btn-primary pilihUangMuka'
                            data-id='{$row->jurnal_id}'
                            data-nominal='{$row->nominal}'
                            data-kode='{$row->kode_jurnal}'
                            data-akun_nama='{$row->akun_uang_muka}'
                            data-akun_id='{$row->akun_uang_muka_id}'
                            data-terpakai='{$row->terpakai}'
                            data-umur='{$row->umur}'
                            data-sisa='{$row->sisa}'
                            data-partner='{$row->partner_nama}'
                            data-partner_id='{$row->partner_id}'
                            data-cabang='{$row->nama_cabang}'
                            data-cabang_id='{$row->cabang_id}'
                            data-entitas='{$row->entitas_id}'>
                            Pilih
                        </button>";
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    function detail_transaksi(Request $request){
        $jurnal_id = $request->id;
        $detail = DB::table('jurnal_detail as d')
        ->join('m_akun_gl as a', 'd.akun_id', '=', 'a.id')
        ->where('d.jurnal_id', $jurnal_id)
        ->select(
           DB::raw("CONCAT(a.no_akun, ' - ', a.nama) as akun_gl"),
            'd.deskripsi',
            DB::raw("CAST(d.debit AS UNSIGNED) as debit"),
            DB::raw("CAST(d.kredit AS UNSIGNED) as kredit")
        )
        ->get();
        return response()->json($detail);
    }

    public function datatablePiutang(Request $request)
    {
        $partner_id = $request->partner_id;

        $query = DB::table('jurnal_header as j')
            ->leftJoin('jurnal_detail as jd', function($join) {
                $join->on('jd.jurnal_id', '=', 'j.id')
                    ->where('jd.debit', '>', 0); // posisi debit (piutang)
            })
            ->leftJoin('m_akun_gl as a', function($join) {
                $join->on('a.id', '=', 'jd.akun_id')
                    ->where('a.kategori', '=', 'piutang'); // hanya akun kategori piutang
            })
            ->leftJoin('pelunasan_piutang as pp', 'pp.jurnal_piutang_id', '=', 'j.id')
            ->leftJoin('m_cabang as c', 'c.id', '=', 'j.cabang_id')
            ->select(
                'j.id',
                'j.kode_jurnal',
                'j.cabang_id',
                'j.no_invoice',
                DB::raw('DATE_FORMAT(j.tanggal, "%d %M %Y") as tanggal'),
                DB::raw('j.total_debit as total_tagihan'),
                DB::raw('COALESCE(SUM(pp.jumlah),0) as total_bayar'),
                DB::raw('(j.total_debit - COALESCE(SUM(pp.jumlah),0)) as sisa_piutang'),
                'a.id as akun_piutang_id',
                'a.nama as akun_piutang_nama',
                'c.nama as cabang',
                'j.jenis as jenis'
            )
            ->where('j.partner_id', $partner_id)
            ->where('j.status', 'posted')
            ->whereNotNull('a.id') // hanya jika benar-benar ada akun piutang
            ->groupBy(
                'j.id',
                'j.cabang_id',
                'j.kode_jurnal',
                'j.no_invoice',
                'j.tanggal',
                'j.total_debit',
                'a.id',
                'a.nama',
                'c.nama',
                'j.jenis'
            )
            ->havingRaw('(j.total_debit - COALESCE(SUM(pp.jumlah),0)) > 0')
            ->orderBy('j.tanggal', 'asc');

             /*
            |--------------------------------------------------------------------------
            | 1. FILTER WAJIB UNTUK USER LEVEL ENTITAS
            |--------------------------------------------------------------------------
            */
            if ($request->entitas_scope) {
                $query->where('j.entitas_id', $request->entitas_scope);
            }

            

        return DataTables::of($query)
            ->addIndexColumn()
            ->filterColumn('kode_jurnal', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(j.kode_jurnal, ' ', COALESCE(j.no_invoice, '')) LIKE ?", ["%{$keyword}%"]);
                })
            ->addColumn('aksi', function($row) {
                return "<button type='button' class='btn btn-sm btn-success btn-pilih-invoice'
                            data-id='{$row->id}'
                            data-no_invoice='{$row->no_invoice}'
                            data-kode='{$row->kode_jurnal}'
                            data-cabang_id='{$row->cabang_id}'
                            data-cabang='{$row->cabang}'
                            data-tanggal='{$row->tanggal}'
                            data-total='{$row->total_tagihan}'
                            data-akun_piutang_id='{$row->akun_piutang_id}'
                            data-akun_piutang_nama='{$row->akun_piutang_nama}'
                            data-sisa='{$row->sisa_piutang}'>
                            <i class='fas fa-check'></i> Pilih
                        </button>";
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }



    /**
     * Fungsi Stotre
     */
    public function store(Request $request, $jenis = null)
    {
        /* =========================================================
        VALIDASI JENIS JURNAL
        ==========================================================*/
        $allowedJenis = ['JP', 'JKM', 'JKK', 'JN'];
        if (!in_array($jenis, $allowedJenis)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jenis jurnal tidak valid.'
            ], 422);
        }

        /* =========================================================
        RULES VALIDASI
        ==========================================================*/
        $rules = [
            'tanggal'            => 'required|date',
            'entitas_id'         => 'required|integer',
            'cabang_id'          => 'required|integer',
            'keterangan'         => 'nullable|string',
            'detail'             => 'required|array|min:2',
            'detail.*.akun_id'   => 'required|integer',
            'detail.*.debit'     => 'nullable',
            'detail.*.kredit'    => 'nullable',
        ];
        // 🔹 Messages awal
        $messages = [
            'tanggal.required'        => 'Tanggal wajib dipilih.',
            'tanggal.date'            => 'Format tanggal tidak valid.',

            'entitas_id.required'     => 'Entitas wajib dipilih.',
            'entitas_id.integer'      => 'Entitas tidak valid.',

            'cabang_id.required'      => 'Cabang wajib dipilih.',
            'cabang_id.integer'       => 'Cabang tidak valid.',

            'keterangan.string'       => 'Keterangan harus berupa teks.',

            'detail.required'         => 'Detail transaksi wajib diisi.',
            'detail.array'            => 'Format detail transaksi tidak valid.',
            'detail.min'              => 'Detail transaksi minimal harus berisi 2 baris (debit dan kredit).',

            'detail.*.akun_id.required' => 'Akun wajib dipilih di setiap baris.',
            'detail.*.akun_id.integer'  => 'Akun tidak valid.',
            'detail.*.deskripsi.string' => 'Deskripsi harus berupa teks.',
        ];

        if ($jenis === 'JP') {
            $rules += [
                'no_invoice'       => 'required|string',
                'tanggal_invoice'  => 'required|date',
                'partner_id'       => 'required|integer',
            ];$messages += [
                    'no_invoice.required' => 'No Invoice wajib diisi.',
                    'no_invoice.string'   => 'No Invoice tidak valid.',
            ];


        } elseif(in_array($jenis,["JKM"])) { 
            $rules['partner_id'] = 'required|integer';
            $messages += [
                'partner_id.required' => 'Partner wajib dipilih.',
                'partner_id.integer'  => 'Partner tidak valid.',
            ];
        }

        $validation = Validator::make($request->all(), $rules,$messages);
        if ($validation->fails()) {
            return response()->json([
                'status' => 'warning',
                'message' => $validation->errors()->first()
            ], 422);
        }

        /* =========================================================
        PRE-VALIDASI DETAIL
        ==========================================================*/
        $warningMessages = [];
        $adaPiutang = false;

        // FLAG uang muka
        $isUangMuka = false;
        $akunUangMukaId = null;
        $nominalUangMuka = 0;

        foreach ($request->detail as $row) {

            $akun = JurnalService::getAkun($row['akun_id']);
            $debit  = floatval(str_replace('.', '', $row['debit'] ?? 0));
            $kredit = floatval(str_replace('.', '', $row['kredit'] ?? 0));

            /* =========================================================
            1) DETEKSI UANG MUKA
            ==========================================================*/
            $um = JurnalService::detectUangMukaDetail($row);
            if ($um['is']) {
                $isUangMuka     = true;
                $akunUangMukaId = $um['akun_id'];
                $nominalUangMuka = $um['nominal'];
            }

            /* =========================================================
            2) VALIDASI DEPOSIT (JP)
            ==========================================================*/
            if ($jenis === 'JP' && JurnalService::isDepositAkun($akun)) {

                $saldoDeposit = JurnalService::getSaldoDeposit(
                    $akun->id,
                    $request->partner_id,
                    $request->entitas_id
                );

                if ($debit <= 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Pemakaian deposit harus diisi pada kolom Debit."
                    ], 422);
                }

                if ($debit > $saldoDeposit) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Saldo deposit tidak cukup. Saldo: " . number_format($saldoDeposit)
                    ], 422);
                }
                
                continue; // skip saldo normal
            }

            /* =========================================================
            3) VALIDASI SALDO NORMAL (JP)
            ==========================================================*/
            if ($jenis === 'JP') {
                $err = JurnalService::validateSaldoNormal($akun, $debit, $kredit);
                if ($err) {
                    return response()->json(['status' => 'error','message' => $err], 422);
                }

                if (JurnalService::isPiutangAkun($akun)) {
                    $adaPiutang = true;
                }
            }
        }

        if ($jenis === 'JP' && !$adaPiutang) {
            return response()->json([
                'status' => 'error',
                'message' => 'JP harus memiliki akun piutang.'
            ], 422);
        }

        

        /* =========================================================
        HITUNG TOTAL DEBIT / KREDIT
        ==========================================================*/
        $totalDebit  = collect($request->detail)->sum(fn($i) => floatval(str_replace('.', '', $i['debit'] ?? 0)));
        $totalKredit = collect($request->detail)->sum(fn($i) => floatval(str_replace('.', '', $i['kredit'] ?? 0)));

        if ($totalDebit != $totalKredit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Total Debit dan Kredit tidak balance.'
            ], 422);
        }

        // =========================================================
        // VALIDASI PELUNASAN PIUTANG (JKM / JN)
        // =========================================================
        if (($jenis === 'JKM' || $jenis === 'JN') && $request->filled('jurnal_id_jp')) {

            $valid = PelunasanPiutangService::validatePelunasanWithDate(
                $request->jurnal_id_jp, // ID JP
                $totalDebit,             // pelunasan baru
                $request->tanggal   // tanggal jurnal kas (JKM / JN)
            );

            if (!$valid['status']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $valid['message']
                ], 422);
            }
        }

       

        /* =========================================================
        SIMPAN DATA
        ==========================================================*/
        DB::beginTransaction();
        try {
            PeriodeHelper::cekPeriodeOpen($request->tanggal);
            // nomor jurnal
            $prefix = $jenis . '-' . date('Ym');
            $last = DB::table('jurnal_header')->where('kode_jurnal', 'like', $prefix . '%')->max('kode_jurnal');
            $urut = $last ? intval(substr($last, -3)) + 1 : 1;
            $kode = $prefix . '-' . str_pad($urut, 3, '0', STR_PAD_LEFT);
            
            $dataHeader = [
                'kode_jurnal'  => $kode,
                'jenis'        => $jenis,
                'tanggal'      => $request->tanggal,
                'entitas_id'   => $request->entitas_id,
                'cabang_id'    => $request->cabang_id,
                'partner_id'   => $request->partner_id,
                'keterangan'   => $request->keterangan,
                'total_debit'  => $totalDebit,
                'total_kredit' => $totalKredit,
                'status'       => 'draft',
                'created_by'   => Auth::id(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ];

            // tambah referensi uang muka jika JN dan ada id JKK
            if ($jenis === 'JN' && $request->filled('jurnal_id_jkk')) {
                $dataHeader['jurnal_id_jkk'] = $request->jurnal_id_jkk;
            }
            // header
            $jurnalId = DB::table('jurnal_header')->insertGetId($dataHeader);

            if (($jenis === 'JKM' || $jenis === 'JN') && $request->filled('jurnal_id_jp')) {

                PelunasanPiutangService::insertPelunasan(
                    $jurnalId,
                    $request->jurnal_id_jp,
                    $totalDebit
                );
            }

            if (($jenis === 'JN') && $request->filled('jurnal_id_jkk')) {

                UangMukaService::validateDraftPelunasan($request);
            }

            // detail
            foreach ($request->detail as $row) {
                DB::table('jurnal_detail')->insert([
                    'jurnal_id' => $jurnalId,
                    'akun_id'   => $row['akun_id'],
                    'deskripsi' => $row['deskripsi'] ?? "",
                    'debit'     => str_replace('.', '', $row['debit'] ?? 0),
                    'kredit'    => str_replace('.', '', $row['kredit'] ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            /* =========================================================
            INSERT AUTO-UANG MUKA JIKA JKK
            ==========================================================*/
            if ($jenis === 'JKK' && $isUangMuka && $nominalUangMuka > 0) {

                DB::table('pelunasan_uang_muka')->insert([
                    'entitas_id'          => $request->entitas_id,
                    'partner_id'          => $request->partner_id,
                    'jurnal_uang_muka_id' => $jurnalId,
                    'jurnal_biaya_id'     => null,
                    'akun_biaya_id'       => null,
                    'jumlah'              => 0,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Jurnal berhasil disimpan.',
                'kode_jurnal' => $kode
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }




    public function update(Request $request, $id, $jenis = null)
    {

        $id = $request->id;

        /* =========================================================
        VALIDASI JENIS JURNAL
        ==========================================================*/
        $allowedJenis = ['JP', 'JKM', 'JKK', 'JN'];
        if (!in_array($jenis, $allowedJenis)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jenis jurnal tidak valid.'
            ], 422);
        }

        /* =========================================================
        VALIDASI FORM INPUT
        ==========================================================*/
        $rules = [
            'tanggal'            => 'required|date',
            'entitas_id'         => 'required|integer',
            'cabang_id'          => 'required|integer',
            'keterangan'         => 'nullable|string',
            'detail'             => 'required|array|min:2',
            'detail.*.akun_id'   => 'required|integer',
        ];
        $messages = [
            'tanggal.required'        => 'Tanggal wajib dipilih.',
            'tanggal.date'            => 'Format tanggal tidak valid.',

            'entitas_id.required'     => 'Entitas wajib dipilih.',
            'entitas_id.integer'      => 'Entitas tidak valid.',

            'cabang_id.required'      => 'Cabang wajib dipilih.',
            'cabang_id.integer'       => 'Cabang tidak valid.',

            'keterangan.string'       => 'Keterangan harus berupa teks.',

            'detail.required'         => 'Detail transaksi wajib diisi.',
            'detail.array'            => 'Format detail transaksi tidak valid.',
            'detail.min'              => 'Detail transaksi minimal harus berisi 2 baris (debit dan kredit).',

            'detail.*.akun_id.required' => 'Akun wajib dipilih di setiap baris.',
            'detail.*.akun_id.integer'  => 'Akun tidak valid.',
            'detail.*.deskripsi.string' => 'Deskripsi harus berupa teks.',
        ];


        if ($jenis === 'JP') {
            $rules += [
                'no_invoice'       => 'required|string',
                'tanggal_invoice'  => 'required|date',
                'partner_id'       => 'required|integer',
            ];$messages += [
                    'no_invoice.required' => 'No Invoice wajib diisi.',
                'no_invoice.string'   => 'No Invoice tidak valid.',
            ];


        } elseif(in_array($jenis,["JKK","JKM"])) { 
            $rules['partner_id'] = 'required|integer';
            $messages += [
                'partner_id.required' => 'Partner wajib dipilih.',
                'partner_id.integer'  => 'Partner tidak valid.',
            ];
        }

        $validation = Validator::make($request->all(), $rules,$messages);
        if ($validation->fails()) {
            return response()->json([
                'status' => 'warning',
                'message' => $validation->errors()->first(),
            ], 422);
        }

        /* =========================================================
        HITUNG TOTAL DEBIT / KREDIT
        ==========================================================*/
        $totalDebit = collect($request->detail)->sum(fn($i) => floatval(str_replace('.', '', $i['debit'] ?? 0)));
        $totalKredit = collect($request->detail)->sum(fn($i) => floatval(str_replace('.', '', $i['kredit'] ?? 0)));

        if ($totalDebit != $totalKredit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Total debit dan kredit tidak balance!'
            ], 422);
        }

        /* =========================================================
        LOOP VALIDASI DETAIL
        ==========================================================*/

        $warningMessages = [];
        $adaPiutang = false;

        // flag uang muka
        $isUangMuka = false;
        $akunUangMukaId = null;
        $nominalUangMuka = 0;

        foreach ($request->detail as $row) {

            $akun = JurnalService::getAkun($row['akun_id']);
            $debit  = floatval(str_replace('.', '', $row['debit'] ?? 0));
            $kredit = floatval(str_replace('.', '', $row['kredit'] ?? 0));

            if (!$akun) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Akun {$row['akun_id']} tidak ditemukan."
                ], 422);
            }

            /* =========================================================
            1) DETEKSI UANG MUKA
            ==========================================================*/
            $um = JurnalService::detectUangMukaDetail($row);
            if ($um['is']) {
                $isUangMuka     = true;
                $akunUangMukaId = $um['akun_id'];
                $nominalUangMuka = $um['nominal'];
            }

            

            /* =========================================================
            3) VALIDASI SALDO NORMAL (JP)
            ==========================================================*/
            if ($jenis === 'JP') {

                $err = JurnalService::validateSaldoNormal($akun, $debit, $kredit);
                if ($err) return response()->json(['status' => 'error', 'message' => $err], 422);

                if (JurnalService::isPiutangAkun($akun)) {
                    $adaPiutang = true;
                }
            }

            /* =========================================================
            4) WARNING UNTUK JKM/JKK (bukan error)
            ==========================================================*/
            if (in_array($jenis, ['JKM', 'JKK'])) {
                if (($akun->saldo_normal === 'debet' && $kredit > 0) ||
                    ($akun->saldo_normal === 'kredit' && $debit > 0)) {

                    $warningMessages[] = "Akun {$akun->no_akun}-{$akun->nama} tidak sesuai saldo normal.";
                }
            }
        }

        if ($jenis === 'JP' && !$adaPiutang) {
            return response()->json([
                'status' => 'error',
                'message' => 'JP harus memiliki akun piutang.'
            ], 422);
        }

        if (count($warningMessages) > 0 && !$request->has('confirm')) {
            return response()->json([
                'status' => 'warning',
                'message' => implode("\n", $warningMessages),
                'need_confirm' => true,
            ]);
        }

        /* =========================================================
        VALIDASI PELUNASAN PIUTANG (JKM / JN)
        ==========================================================*/
        if (in_array($jenis, ['JKM','JN']) && $request->filled('jurnal_piutang_id')) {

            $valid = PelunasanPiutangService::validatePelunasanWithDate(
                $request->jurnal_piutang_id,
                $totalDebit,
                $request->tanggal
            );

            if (!$valid['status']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $valid['message']
                ], 422);
            }
        }
        if (($jenis === 'JN') && $request->filled('jurnal_id_jkk')) {

            UangMukaService::validateDraftPelunasan($request);
        }
        /* =========================================================
        UPDATE JURNAL HEADER + DETAIL
        ==========================================================*/
        DB::beginTransaction();
        try {
            
            PeriodeHelper::cekPeriodeOpen($request->tanggal);

            $data = [
                'tanggal'      => $request->tanggal,
                'entitas_id'   => $request->entitas_id,
                'cabang_id'    => $request->cabang_id,
                'partner_id'   => $request->partner_id,
                'keterangan'   => $request->keterangan,
                'total_debit'  => $totalDebit,
                'total_kredit' => $totalKredit,
                'updated_at'   => now(),
            ];

            DB::table('jurnal_header')->where('id', $id)->update($data);

            /* =========================================================
            UPDATE PELUNASAN PIUTANG
            ==========================================================*/
            if (in_array($jenis, ['JKM','JN'])) {

                // hapus pelunasan lama
                DB::table('pelunasan_piutang')->where('jurnal_kas_id', $id)->delete();

                if ($request->filled('jurnal_piutang_id')) {
                    PelunasanPiutangService::insertPelunasan(
                        $id,
                        $request->jurnal_piutang_id,
                        $totalDebit
                    );
                }
            }

            /* =========================================================
            UPDATE DETAIL
            ==========================================================*/
            DB::table('jurnal_detail')->where('jurnal_id', $id)->delete();

            foreach ($request->detail as $row) {
                DB::table('jurnal_detail')->insert([
                    'jurnal_id'  => $id,
                    'akun_id'    => $row['akun_id'],
                    'deskripsi'  => $row['deskripsi'] ?? '',
                    'debit'      => floatval(str_replace('.', '', $row['debit'] ?? 0)),
                    'kredit'     => floatval(str_replace('.', '', $row['kredit'] ?? 0)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Jurnal berhasil diperbarui.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }





    /**
     * Remove the specified resource.
     */
    public function destroy(Request $request)
    {
        $id = $request->id;
        DB::beginTransaction();
        try {
            // Pastikan jurnal ada
            $jurnal = DB::table('jurnal_header')->where('id', $id)->first();

            if (!$jurnal) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data jurnal tidak ditemukan.'
                ], 404);
            }
            // Hapus semua jurnal_detail terkait
            DB::table('jurnal_detail')->where('jurnal_id', $id)->delete();
            DB::table('pelunasan_piutang')->where('jurnal_kas_id', $id)->delete();

            // Hapus header jurnal
            DB::table('jurnal_header')->where('id', $id)->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data jurnal dan detail berhasil dihapus.'
            ]);
        } catch(QueryException $e) { 
            DB::rollback();
            return response()->json(['status'=>'error','messages'=> $e->errorInfo ], 500);
        }
    }

    /**
     * Posting Jurnal
     */
    public function posting(Request $request)
    {
        $id = $request->id;

        $jurnal = DB::table('jurnal_header')->where('id', $id)->first();

        if (!$jurnal) {
            return response()->json(['status' => false, 'message' => 'Data jurnal tidak ditemukan']);
        }

        if ($jurnal->status === 'posted') {
            return response()->json(['status' => false, 'message' => 'Jurnal ini sudah diposting sebelumnya']);
        }

        // Pastikan total debit = kredit
        if ($jurnal->total_debit != $jurnal->total_kredit) {
            return response()->json(['status' => false, 'message' => 'Total debit dan kredit tidak balance, tidak bisa diposting!']);
        }

        if (($jurnal->jenis === 'JN')) {
            UangMukaService::postingPelunasan($id);
        }



        DB::beginTransaction();
        try {
            // ✅ Cek periode terbuka dulu
            PeriodeHelper::cekPeriodeOpen($jurnal->tanggal);
            DB::table('jurnal_header')->where('id', $id)->update([
                'status' => 'posted',
                'posted_by' => Auth::id(),
                'posted_at' => now(),
                'updated_at' => now(),
            ]);

            // Posting Ke Buku Besar
            $details = DB::table('jurnal_detail as d')
                ->join('jurnal_header as h', 'd.jurnal_id', '=', 'h.id')
                ->select('h.id as jurnal_id', 'h.kode_jurnal', 'h.tanggal', 'd.deskripsi', 'h.jenis', 'h.entitas_id', 'h.partner_id', 'd.akun_id', 'd.debit', 'd.kredit','h.cabang_id')
                ->where('h.id', $id)
                ->get();
            $tes=null;
            foreach ($details as $row) {
                $debit  = floatval($row->debit);
                $kredit = floatval($row->kredit);
                $akun = JurnalService::getAkun($row->akun_id);
                if ($jurnal->jenis === 'JP' && JurnalService::isDepositAkun($akun)) {
                    $nominal = $debit;

                    $saldoDeposit = JurnalService::getSaldoDeposit(
                        $akun->id,
                        $jurnal->partner_id,
                        $jurnal->entitas_id
                    );

                    if ($debit > $saldoDeposit) {
                        return response()->json([
                            'status' => false,
                            'message' => "Saldo deposit tidak cukup. Saldo: " .number_format($saldoDeposit,2,",")
                        ]);
                    }
                    if ($nominal > 0) {
                        DB::table('pelunasan_deposit')->insert([
                            'entitas_id'        => $jurnal->entitas_id,
                            'partner_id'        => $jurnal->partner_id,
                            'jurnal_piutang_id' => $id,
                            'akun_deposit_id'   => $row->akun_id,
                            'jumlah'            => $nominal,
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ]);
                    }
                    
                    // continue; // skip saldo normal
                }
                DB::table('buku_besar')->insert([
                    'jurnal_id' => $row->jurnal_id,
                    'akun_id' => $row->akun_id,
                    'tanggal' => $row->tanggal,
                    'kode_jurnal' => $row->kode_jurnal,
                    'keterangan' => $row->deskripsi,
                    'debit' => $row->debit,
                    'kredit' => $row->kredit,
                    'entitas_id' => $row->entitas_id,
                    'partner_id' => $row->partner_id,
                    'cabang_id' => $row->cabang_id,
                    'jenis' => $row->jenis,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
            }
            DB::table('log_jurnal')->insert([
                'jurnal_id' => $id,
                'action' => 'posting',
                'user_id' => Auth::id(),
                'created_at' => now(),
            ]);
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Jurnal berhasil diposting' .$tes]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Gagal posting jurnal: '.$e->getMessage()]);
        }
    }

    public function unposting(Request $request)
    {
        $id = $request->id;
        $jurnal = DB::table('jurnal_header')->where('id', $id)->first();

        if (!$jurnal) {
            return response()->json(['status' => false, 'message' => 'Data jurnal tidak ditemukan']);
        }

        if ($jurnal->status !== 'posted') {
            return response()->json(['status' => false, 'message' => 'Hanya jurnal yang sudah diposting yang bisa di-unposting']);
        }
        // 🔹 Cek apakah jurnal ini JP dan sudah digunakan di pelunasan
        if ($jurnal->jenis === 'JP') {
            $cekPelunasan = DB::table('pelunasan_piutang')
                ->where('jurnal_piutang_id', $id)
                ->exists();
            
        }

        // =====================================================
        // 🔵 VALIDASI UNPOSTING UNTUK JKM YANG MENCATAT DEPOSIT
        // =====================================================
        if ($jurnal->jenis === 'JKM') {

            // Ambil detail JKM untuk cek apakah ada akun deposito_customer
            $details = DB::table('jurnal_detail')
                ->join('m_akun_gl', 'm_akun_gl.id', '=', 'jurnal_detail.akun_id')
                ->where('jurnal_detail.jurnal_id', $id)
                ->select('jurnal_detail.*', 'm_akun_gl.kategori')
                ->get();

            // Filter baris yang merupakan akun deposito customer
            $depositRows = $details->filter(fn($d) => $d->kategori === 'deposito_customer');

            if ($depositRows->count() > 0) {

                foreach ($depositRows as $row) {

                    $akunId     = $row->akun_id;
                    $customerId = $jurnal->partner_id;
                    $entitasId  = $jurnal->entitas_id;
                    $nominalJKM = floatval($row->kredit); // deposit IN

                    // -----------------------------
                    // 1️⃣ HITUNG TOTAL SALDO DEPOSIT SAAT INI (POSTED)
                    // -----------------------------
                    $totalIn = DB::table('buku_besar')
                    ->where('akun_id', $akunId)
                    ->where('partner_id', $customerId)
                    ->where('entitas_id', $entitasId)
                    ->sum(DB::raw('kredit - debit'));

                    // total yang sudah digunakan di JP lain
                    $totalUsed = DB::table('pelunasan_deposit')
                        ->where('akun_deposit_id', $akunId)
                        ->where('partner_id', $customerId)
                        ->where('entitas_id', $entitasId)
                        ->sum('jumlah');

                    $saldoSekarang = $totalIn - $totalUsed;

                    // -----------------------------
                    // 2️⃣ HITUNG SALDO SETELAH UNPOSTING JKM INI
                    // -----------------------------
                    $saldoSetelahUnpost = $saldoSekarang - $nominalJKM;

                    if ($saldoSetelahUnpost < 0) {
                        return response()->json([
                            'status' => false,
                            'message' => "JKM dengan No Dokumen {$jurnal->kode_jurnal} tidak dapat di-unposting karena deposit yang berasal dari jurnal ini sudah dipakai dan akan menyebabkan saldo deposit customer menjadi negatif."
                        ]);
                    }
                }
            }
        }

        if($jurnal->jenis === "JN"){
            UangMukaService::unpostingPelunasan($id);
        }

        if ($jurnal->jenis === 'JKK') {
            try {
                JurnalService::validateJKKNotUsed($id);
            } catch (\Exception  $e) {
                return response()->json(['status' => false, 'message' => 'Gagal unposting jurnal: '.$e->getMessage()]);
            }
            
        }

        

        DB::beginTransaction();
        try {
            PeriodeHelper::cekPeriodeOpen($jurnal->tanggal);
            DB::table('jurnal_header')->where('id', $id)->update([
                'status' => 'draft',
                'posted_by' => null,
                'posted_at' => null,
                'updated_at' => now(),
            ]);

            DB::table('pelunasan_deposit')->where('jurnal_piutang_id', $id)->delete();
            DB::table('buku_besar')->where('jurnal_id', $id)->delete();

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Jurnal berhasil di-unposting']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Gagal unposting jurnal: '.$e->getMessage()]);
        }
    }

    public function form_unposting(Request $request,$jenis = null){
        // jika request AJAX → berarti dari DataTables
        if ($request->ajax()) {
            $query = DB::table('jurnal_header as j')
                ->leftJoin('m_entitas as e', 'j.entitas_id', '=', 'e.id')
                ->leftJoin('m_partner as p', 'j.partner_id', '=', 'p.id')
                ->leftJoin('m_cabang as c', 'j.cabang_id', '=', 'c.id')
                ->select(
                    'j.id',
                    'j.kode_jurnal',
                    'j.keterangan',
                    'j.tanggal',
                    'e.nama as entitas',
                    'p.nama as partner',
                    'c.nama as cabang',
                    'j.total_debit',
                    'j.status'
                )
                ->where('j.jenis', $jenis) // hanya pendapatan
                ->where('j.status', 'posted');
             /*
            |--------------------------------------------------------------------------
            | 1. FILTER WAJIB UNTUK USER LEVEL ENTITAS
            |--------------------------------------------------------------------------
            */
            if ($request->entitas_scope) {
                $query->where('j.entitas_id', $request->entitas_scope);
            }

            // filter tanggal
            if ($request->filled('tanggal_awal') && $request->filled('tanggal_akhir')) {
                $query->whereBetween('j.tanggal', [
                    $request->tanggal_awal,
                    $request->tanggal_akhir
                ]);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                 // 🔍 Perbaiki pencarian global agar kolom alias ikut terdeteksi
                ->filterColumn('entitas', function ($query, $keyword) {
                    $query->where('e.nama', 'like', "%{$keyword}%");
                })
                ->filterColumn('partner', function ($query, $keyword) {
                    $query->where('p.nama', 'like', "%{$keyword}%");
                })
                ->filterColumn('cabang', function ($query, $keyword) {
                    $query->where('c.nama', 'like', "%{$keyword}%");
                })
                ->filterColumn('kode_jurnal', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(j.kode_jurnal, ' ', COALESCE(j.no_invoice, '')) LIKE ?", ["%{$keyword}%"]);
                })
                ->filterColumn('keterangan', function ($query, $keyword) {
                    $query->where('j.keterangan', 'like', "%{$keyword}%");
                })
                ->editColumn('tanggal', function ($row) {
                    return Carbon::parse($row->tanggal)->translatedFormat('d F Y');
                })
                ->editColumn('status', function ($row) {
                    $res = $row->status == "draft"
                        ? "<span class='badge text-bg-secondary'>Draft</span>"
                        : "<span class='badge text-bg-success'>Posted</span>";
                    return $res;
                })
                ->addColumn('aksi', function ($row) {
                    $html = '<div class="btn-group btn-group-sm">';
                    $html .= '<button title="Detail Transaksi" type="button" data-toggle="tooltip" class="btn btn-info btn-view" onclick="detail_transaksi(' . $row->id . ')"><i class="fas fa-eye"></i></button>';
                    $html .= "</div>";
                    return $html;
                })
                ->rawColumns(['aksi','status'])
                ->make(true);
        }
        $pg = array(
            "JP" => "pendapatan",
            "JKM" => "kas_masuk",
            "JKK" => "kas_keluar",
            "JN" => "penyesuaian",
        );
        return view('page.jurnal.unposting_'.$pg[$jenis]);
    }

    public function form_posting(Request $request,$jenis = null){
        // jika request AJAX → berarti dari DataTables
        if ($request->ajax()) {
            $query = DB::table('jurnal_header as j')
                ->leftJoin('m_entitas as e', 'j.entitas_id', '=', 'e.id')
                ->leftJoin('m_partner as p', 'j.partner_id', '=', 'p.id')
                ->leftJoin('m_cabang as c', 'j.cabang_id', '=', 'c.id')
                ->select(
                    'j.id',
                    'j.kode_jurnal',
                    'j.keterangan',
                    'j.tanggal',
                    'e.nama as entitas',
                    'p.nama as partner',
                    'c.nama as cabang',
                    'j.total_debit',
                    'j.status'
                )
                ->where('j.jenis', $jenis) // hanya pendapatan
                ->where('j.status', 'draft');
             /*
            |--------------------------------------------------------------------------
            | 1. FILTER WAJIB UNTUK USER LEVEL ENTITAS
            |--------------------------------------------------------------------------
            */
            if ($request->entitas_scope) {
                $query->where('j.entitas_id', $request->entitas_scope);
            }else{
                $query->when($request->entitas_id, fn($q) => $q->where('j.entitas_id', $request->entitas_id));
            }
            // filter tanggal
            if ($request->filled('tanggal_awal') && $request->filled('tanggal_akhir')) {
                $query->whereBetween('j.tanggal', [
                    $request->tanggal_awal,
                    $request->tanggal_akhir
                ]);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                 // 🔍 Perbaiki pencarian global agar kolom alias ikut terdeteksi
                ->filterColumn('entitas', function ($query, $keyword) {
                    $query->where('e.nama', 'like', "%{$keyword}%");
                })
                ->filterColumn('partner', function ($query, $keyword) {
                    $query->where('p.nama', 'like', "%{$keyword}%");
                })
                ->filterColumn('cabang', function ($query, $keyword) {
                    $query->where('c.nama', 'like', "%{$keyword}%");
                })
                ->filterColumn('kode_jurnal', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(j.kode_jurnal, ' ', COALESCE(j.no_invoice, '')) LIKE ?", ["%{$keyword}%"]);
                })
                ->filterColumn('keterangan', function ($query, $keyword) {
                    $query->where('j.keterangan', 'like', "%{$keyword}%");
                })
                ->editColumn('tanggal', function ($row) {
                    return Carbon::parse($row->tanggal)->translatedFormat('d F Y');
                })
                ->editColumn('status', function ($row) {
                    $res = $row->status == "draft"
                        ? "<span class='badge text-bg-secondary'>Draft</span>"
                        : "<span class='badge text-bg-success'>Posted</span>";
                    return $res;
                })
                ->addColumn('detail', function ($row) {

                    // ambil detail
                    $detail = DB::table('jurnal_detail as d')
                        ->join('m_akun_gl as a', 'a.id', '=', 'd.akun_id')
                        ->select(
                            'a.no_akun as kode_akun',
                            'a.nama as nama_akun',
                            'd.debit',
                            'd.kredit',
                            'd.deskripsi'
                        )
                        ->where('d.jurnal_id', $row->id)
                        ->orderBy('d.id')
                        ->get();

                    // buat tabel detail
                    $html = "
                        <div class='bg-light p-2 rounded border mt-2'>
                            <strong>Detail Transaksi:</strong>
                            <table class='table table-sm table-bordered mt-2 mb-0'>
                                <thead class='table-secondary'>
                                    <tr>
                                        <th>Akun</th>
                                        <th class='text-end'>Debit</th>
                                        <th class='text-end'>Kredit</th>
                                        <th>Deskripsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                    ";

                    foreach ($detail as $d) {
                        $html .= "
                            <tr>
                                <td>{$d->kode_akun} - {$d->nama_akun}</td>
                                <td class='text-end'>" . number_format($d->debit) . "</td>
                                <td class='text-end'>" . number_format($d->kredit) . "</td>
                                <td>{$d->deskripsi}</td>
                            </tr>
                        ";
                    }

                    $html .= "
                                </tbody>
                            </table>
                        </div>
                    ";

                    return $html;
                })
                ->rawColumns(['detail','status'])
                ->make(true);
        }
        $pg = array(
            "JP" => "pendapatan",
            "JKM" => "kas_masuk",
            "JKK" => "kas_keluar",
            "JN" => "penyesuaian",
        );
        return view('page.jurnal.posting_'.$pg[$jenis]);
    }

    public function prepareBatch(Request $request)
    {
        $rules = [
            'tanggal_awal'   => 'required|date',
            'tanggal_akhir'  => 'required|date|after_or_equal:tanggal_awal',
            'jenis'          => 'required',
            'status'         => 'required',
        ];

        $messages = [
            'tanggal_awal.required' => 'Tanggal awal wajib diisi.',
            'tanggal_awal.date'     => 'Format tanggal awal tidak valid.',

            'tanggal_akhir.required'        => 'Tanggal akhir wajib diisi.',
            'tanggal_akhir.date'            => 'Format tanggal akhir tidak valid.',
            'tanggal_akhir.after_or_equal'  => 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal.',

            'jenis.required' => 'Jenis jurnal wajib dipilih.',
            'status.required' => 'Status jurnal wajib dipilih.',
        ];

      

        $request->validate($rules, $messages);
        if ($request->entitas_scope) {
            $entitas =  $request->entitas_scope;
        }else{
            $entitas =  $request->entitas_id;
        }
        $query = DB::table('jurnal_header')
            ->whereBetween('tanggal', [$request->tanggal_awal, $request->tanggal_akhir])
            ->where('status', $request->status)
            ->when($entitas, fn($q) => $q->where('entitas_id', $entitas));
       

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $total = $query->count();
        if ($total == 0) {
            return response()->json(['status' => 'empty']);
        }

        $batchSize = 50;
        $totalBatch = ceil($total / $batchSize);

        // Simpan ke cache (sementara)
        Cache::put('posting_data', $query->pluck('id')->toArray(), now()->addMinutes(5));

        return response()->json([
            'status' => 'ready',
            'total_batch' => $totalBatch
        ]);
    }

    public function postingBatch(Request $request)
    {
        $batch = $request->input('batch', 0);
        $ids = Cache::get('posting_data', []);
        $batchSize = 50;

        $chunk = array_slice($ids, $batch * $batchSize, $batchSize);
        if (empty($chunk)) {
            return response()->json(['status' => 'done']);
        }

        DB::beginTransaction();
        try {
            // Ambil semua jurnal draft berdasarkan filter tanggal
            $jurnalList = DB::table('jurnal_header')->whereIn('id', $chunk)->get();

            if ($jurnalList->isEmpty()) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Tidak ada jurnal draft pada rentang tanggal tersebut.'
                ], 422);
            }
            

            foreach ($jurnalList as $jurnal) {
                PeriodeHelper::cekPeriodeOpen($jurnal->tanggal);
                if (($jurnal->jenis === 'JN')) {
                    UangMukaService::postingPelunasan($jurnal->id);
                }
                // Ambil detail jurnal
                $detail = DB::table('jurnal_detail')
                    ->where('jurnal_id', $jurnal->id)
                    ->get();

                // Cegah duplikasi di buku besar
                $cek = DB::table('buku_besar')
                    ->where('jurnal_id', $jurnal->id)
                    ->exists();
                if ($cek) continue;

                // Simpan ke buku besar
                foreach ($detail as $d) {
                    $debit  = floatval($d->debit);
                    $kredit = floatval($d->kredit);
                    $akun = JurnalService::getAkun($d->akun_id);
                    if ($jurnal->jenis === 'JP' && JurnalService::isDepositAkun($akun)) {
                        $nominal = $debit;

                        $saldoDeposit = JurnalService::getSaldoDeposit(
                            $akun->id,
                            $jurnal->partner_id,
                            $jurnal->entitas_id
                        );

                        if ($debit > $saldoDeposit) {
                            return response()->json([
                                'status' => false,
                                'message' => "Saldo deposit tidak cukup. Saldo: " .number_format($saldoDeposit,2,",")
                            ],500);
                        }
                        if ($nominal > 0) {
                            DB::table('pelunasan_deposit')->insert([
                                'entitas_id'        => $jurnal->entitas_id,
                                'partner_id'        => $jurnal->partner_id,
                                'jurnal_piutang_id' => $jurnal->id,
                                'akun_deposit_id'   => $d->akun_id,
                                'jumlah'            => $nominal,
                                'created_at'        => now(),
                                'updated_at'        => now(),
                            ]);
                        }
                        
                        // continue; // skip saldo normal
                    }
                    DB::table('buku_besar')->insert([
                        'jurnal_id' => $jurnal->id,
                        'akun_id' => $d->akun_id,
                        'tanggal' => $jurnal->tanggal,
                        'kode_jurnal' => $jurnal->kode_jurnal,
                        'keterangan' => $jurnal->keterangan ?? $d->deskripsi,
                        'debit' => $d->debit,
                        'kredit' => $d->kredit,
                        'entitas_id' => $jurnal->entitas_id,
                        'partner_id' => $jurnal->partner_id,
                        'cabang_id' => $jurnal->cabang_id,
                        'jenis' => $jurnal->jenis,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Update status jurnal
                DB::table('jurnal_header')->where('id', $jurnal->id)->update([
                    'status' => 'posted',
                    'posted_by' => Auth::id(),
                    'posted_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'messages' => 'Semua jurnal berhasil diposting ke buku besar.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal posting batch: '.$e->getMessage()
            ], 500);
        }
    }

    public function unpostingBatch(Request $request)
    {
        $batch = $request->input('batch', 0);
        $ids = Cache::get('posting_data', []);
        $batchSize = 50;

        $chunk = array_slice($ids, $batch * $batchSize, $batchSize);
        if (empty($chunk)) {
            return response()->json(['status' => 'done']);
        }

        DB::beginTransaction();
        try {
            // 🔹 Ambil semua jurnal posted di batch ini
            $jurnalList = DB::table('jurnal_header')
                ->whereIn('id', $chunk)
                ->get();

            if ($jurnalList->isEmpty()) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Tidak ada jurnal posted pada batch ini.'
                ], 422);
            }

            // 🔹 Ambil semua JP di batch ini yang sudah punya pelunasan piutang
            $jpIds = $jurnalList->where('jenis', 'JP')->pluck('id');
            $jpTerpakai = DB::table('pelunasan_piutang as p')
                ->join('jurnal_header as j', 'j.id', '=', 'p.jurnal_piutang_id')
                ->whereIn('p.jurnal_piutang_id', $jpIds)
                ->select('p.jurnal_piutang_id', 'j.kode_jurnal')
                ->get();

            if ($jpTerpakai->count() > 0) {
                $kodeTerpakai = $jpTerpakai->pluck('kode_jurnal')->implode(', ');
                return response()->json([
                    'status' => 'error',
                    'message' => "Unposting dibatalkan. Beberapa JP sudah memiliki pelunasan piutang dan tidak bisa di-unposting: {$kodeTerpakai}"
                ], 422);
            }

            // 🔹 Jika aman, lanjut hapus dari buku besar dan ubah status jadi draft
            foreach ($jurnalList as $jurnal) {
                PeriodeHelper::cekPeriodeOpen($jurnal->tanggal);
                if ($jurnal->jenis === 'JKK') {
                    JurnalService::validateJKKNotUsed($jurnal->id);
                }

                // =====================================================
                // 🔵 VALIDASI UNPOSTING UNTUK JKM YANG MENCATAT DEPOSIT
                // =====================================================
                if ($jurnal->jenis === 'JKM') {

                    // Ambil detail JKM untuk cek apakah ada akun deposito_customer
                    $details = DB::table('jurnal_detail')
                        ->join('m_akun_gl', 'm_akun_gl.id', '=', 'jurnal_detail.akun_id')
                        ->where('jurnal_detail.jurnal_id', $jurnal->id)
                        ->select('jurnal_detail.*', 'm_akun_gl.kategori')
                        ->get();

                    // Filter baris yang merupakan akun deposito customer
                    $depositRows = $details->filter(fn($d) => $d->kategori === 'deposito_customer');

                    if ($depositRows->count() > 0) {

                        foreach ($depositRows as $row) {

                            $akunId     = $row->akun_id;
                            $customerId = $jurnal->partner_id;
                            $entitasId  = $jurnal->entitas_id;
                            $nominalJKM = floatval($row->kredit); // deposit IN

                            // -----------------------------
                            // 1️⃣ HITUNG TOTAL SALDO DEPOSIT SAAT INI (POSTED)
                            // -----------------------------
                            $totalIn = DB::table('buku_besar')
                            ->where('akun_id', $akunId)
                            ->where('partner_id', $customerId)
                            ->where('entitas_id', $entitasId)
                            ->sum(DB::raw('kredit - debit'));

                            // total yang sudah digunakan di JP lain
                            $totalUsed = DB::table('pelunasan_deposit')
                                ->where('akun_deposit_id', $akunId)
                                ->where('partner_id', $customerId)
                                ->where('entitas_id', $entitasId)
                                ->sum('jumlah');

                            $saldoSekarang = $totalIn - $totalUsed;

                            // -----------------------------
                            // 2️⃣ HITUNG SALDO SETELAH UNPOSTING JKM INI
                            // -----------------------------
                            $saldoSetelahUnpost = $saldoSekarang - $nominalJKM;

                            if ($saldoSetelahUnpost < 0) {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => "Unposting dibatalkan. JKM {$jurnal->kode_jurnal} mengandung deposit yang sudah dipakai. Menghapus JKM ini akan membuat saldo deposit menjadi negatif."
                                ],422);
                            }
                        }
                    }
                }
                DB::table('buku_besar')
                    ->where('jurnal_id', $jurnal->id)
                    ->delete();
                DB::table('pelunasan_deposit')->where('jurnal_piutang_id', $jurnal->id)->delete();
                DB::table('jurnal_header')
                    ->where('id', $jurnal->id)
                    ->update([
                        'status' => 'draft',
                        'posted_by' => null,
                        'posted_at' => null,
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'messages' => 'Batch unposting berhasil, semua jurnal dikembalikan ke draft.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal unposting batch: ' . $e->getMessage()
            ], 500);
        }
    }



}
