<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Validator;

class PeriodeAkutansiController extends Controller
{
    /**
     * Index - list periode
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = DB::table("periode_akuntansi")->orderBy("id", "desc")->get();

            return Datatables::of($query)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    return $row->status == "open"
                        ? "<span class='badge text-bg-success'>Open</span>"
                        : "<span class='badge text-bg-danger'>Closed</span>";
                })
                ->addColumn('aksi', function ($row) {
                    $url = route('periode_akuntansi.edit') . "?id=" . $row->id;
                    $html = '<div class="btn-group btn-group-sm">';

                    if ($row->status == "open") {
                        $html .= '<a title="Update Data" data-bs-toggle="tooltip" href="' . $url . '" class="btn btn-warning btn-edit">
                                    <i class="fas fa-edit"></i>
                                </a>';
                        $html .= '<button title="Hapus Data" data-bs-toggle="tooltip" class="btn btn-danger btn-delete" 
                                    onclick="hapusData(' . $row->id . ')">
                                    <i class="fas fa-trash"></i>
                                </button>';
                        $html .= '<button title="Close Periode" data-bs-toggle="tooltip" class="btn btn-primary btn-posting"
                                    onclick="update_status(' . $row->id . ', \'close\')">
                                    <i class="fas fa-lock"></i>
                                </button>';
                    } else {
                        $html .= '<button title="Open Periode" data-bs-toggle="tooltip" class="btn btn-success btn-posting"
                                    onclick="update_status(' . $row->id . ', \'open\')">
                                    <i class="fas fa-unlock"></i>
                                </button>';
                    }

                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['aksi', 'status'])
                ->make(true);
        } else {
            return view("page.periode_akuntansi.index");
        }
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view("page.periode_akuntansi.tambah");
    }

    /**
     * Store new periode
     */
    public function store(Request $request)
    {
        $rules = [
            "periode" => "required|date_format:Y-m",
        ];
        $messages = [
            "periode.required" => "Periode wajib diisi.",
            "periode.date_format" => "Format periode harus YYYY-MM.",
        ];

        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json([
                "status"  => "warning",
                "messages" => $validation->errors()->first()
            ], 401);
        }

        DB::beginTransaction();
        try {
            $periode = $request->periode;

            $cek = DB::table("periode_akuntansi")->where("periode", $periode)->first();
            if ($cek) {
                return response()->json([
                    "status" => "warning",
                    "messages" => "Periode $periode sudah terdaftar."
                ], 401);
            }

            $tanggalMulai   = date("Y-m-01", strtotime($periode . "-01"));
            $tanggalSelesai = date("Y-m-t", strtotime($periode . "-01"));

            DB::table("periode_akuntansi")->insert([
                "periode" => $periode,
                "tanggal_mulai" => $tanggalMulai,
                "tanggal_selesai" => $tanggalSelesai,
                "status" => "open",
                "created_at" => now(),
                "updated_at" => now(),
            ]);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'messages' => "Periode $periode berhasil ditambahkan."
            ], 200);

        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'messages' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'messages' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show edit form
     */
    public function edit(Request $request)
    {
        $id = $request->id;
        $data = DB::table("periode_akuntansi")->where("id",$id)->first();
        return view("page.periode_akuntansi.edit",compact("data","id"));
    }

    /**
     * Update periode
     */
    public function update(Request $request)
    {
        $rules = [
            "id" => "required|integer",
            "periode" => "required|date_format:Y-m",
        ];
        $messages = [
            "id.required" => "ID periode tidak ditemukan.",
            "periode.required" => "Periode wajib diisi.",
            "periode.date_format" => "Format periode harus YYYY-MM.",
        ];

        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json([
                "status" => "warning",
                "messages" => $validation->errors()->first()
            ], 401);
        }

        DB::beginTransaction();
        try {
            $periodeLama = DB::table("periode_akuntansi")->where("id", $request->id)->first();
            if (!$periodeLama) {
                return response()->json([
                    "status" => "error",
                    "messages" => "Data periode tidak ditemukan."
                ], 401);
            }

            $cekJurnal = DB::table('jurnal_header')
                ->whereBetween('tanggal', [$periodeLama->tanggal_mulai, $periodeLama->tanggal_selesai])
                ->exists();

            if ($cekJurnal) {
                return response()->json([
                    "status" => "error",
                    "messages" => "Periode {$periodeLama->periode} tidak dapat diubah karena sudah memiliki transaksi jurnal."
                ], 401);
            }

            $periodeBaru     = $request->periode;
            $tanggalMulai    = date("Y-m-01", strtotime($periodeBaru . "-01"));
            $tanggalSelesai  = date("Y-m-t", strtotime($periodeBaru . "-01"));

            $cekDuplikat = DB::table("periode_akuntansi")
                ->where("periode", $periodeBaru)
                ->where("id", "!=", $request->id)
                ->exists();

            if ($cekDuplikat) {
                return response()->json([
                    "status" => "warning",
                    "messages" => "Periode $periodeBaru sudah terdaftar."
                ], 401);
            }

            DB::table("periode_akuntansi")->where("id", $request->id)->update([
                "periode" => $periodeBaru,
                "tanggal_mulai" => $tanggalMulai,
                "tanggal_selesai" => $tanggalSelesai,
                "updated_at" => now(),
            ]);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'messages' => "Periode $periodeBaru berhasil diperbarui."
            ], 200);

        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'messages' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'messages' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete periode
     */
    public function destroy(Request $request)
    {
        $id = $request->id;

        DB::beginTransaction();
        try {
            $periode = DB::table('periode_akuntansi')->where('id', $id)->first();

            if (!$periode) {
                return response()->json(['status' => 'error', 'messages' => 'Periode tidak ditemukan.']);
            }

            $cekJurnal = DB::table('jurnal_header')
                ->whereBetween('tanggal', [$periode->tanggal_mulai, $periode->tanggal_selesai])
                ->exists();

            if ($cekJurnal) {
                return response()->json([
                    'status' => 'error',
                    'messages' => "Tidak dapat menghapus periode {$periode->periode}, karena sudah terdapat transaksi jurnal."
                ]);
            }
            DB::table("periode_akuntansi")->where("id",$id)->delete();
            DB::commit();
            return response()->json(['status'=>'success', 'messages'=>"Data berhasil dihapus."], 200);
        } catch(QueryException $e) {
            DB::rollback();
            return response()->json(['status'=>'error','messages'=> $e->errorInfo ], 500);
        }
    }

    /**
     * Update status (open / close) - FINALIZED
     */
    public function updateStatus(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $periode = DB::table('periode_akuntansi')->where('id', $id)->first();
            if (!$periode) {
                throw new \Exception("Periode tidak ditemukan.");
            }

            $statusBaru = $request->status;

            /**
             * OPEN
             */
            if ($statusBaru === 'open') {
                $periodeNext = DB::table('periode_akuntansi')
                    ->where('periode', '>', $periode->periode)
                    ->orderBy('periode', 'asc')
                    ->first();

                if ($periodeNext && $periodeNext->status === 'closed') {
                    throw new \Exception("Tidak dapat membuka periode ini karena periode berikutnya sudah ditutup.");
                }

                if ($periodeNext) {
                    DB::table('m_saldo_awal')->where('periode', $periodeNext->periode)->delete();
                }

                // Hapus jurnal otomatis (LRB/CLS)
                $jurnalAuto = DB::table('jurnal_header')
                    ->where(function ($q) {
                        $q->where('kode_jurnal', 'like', 'LRB-%')
                        ->orWhere('kode_jurnal', 'like', 'CLS-%');
                    })
                    ->whereBetween('tanggal', [$periode->tanggal_mulai, $periode->tanggal_selesai])
                    ->pluck('id');

                if ($jurnalAuto->count() > 0) {
                    DB::table('jurnal_detail')->whereIn('jurnal_id', $jurnalAuto)->delete();
                    DB::table('buku_besar')->whereIn('jurnal_id', $jurnalAuto)->delete();
                    DB::table('jurnal_header')->whereIn('id', $jurnalAuto)->delete();
                }

                DB::table('periode_akuntansi')
                    ->where('id', $id)
                    ->update([
                        'status' => 'open',
                        'closed_by' => null,
                        'closed_at' => null,
                        'updated_at' => now(),
                    ]);

                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Periode berhasil dibuka kembali.'
                ]);
            }

            /**
             * CLOSE (FINAL)
             *
             * Urutan:
             * 1. buatJurnalLabaRugiBulanan() -> agar jurnal LRB masuk ke buku_besar
             * 2. buatJurnalPenutup() jika bulan = 12
             * 3. hitung mutasi & saldo akhir (menggunakan saldo_awal + mutasi)
             * 4. simpan saldo_awal periode berikut
             */
            if ($statusBaru === 'close') {

                if ($periode->status === 'closed') {
                    throw new \Exception("Periode ini sudah ditutup sebelumnya.");
                }

                $nextOpen = DB::table('periode_akuntansi')
                    ->where('periode', '>', $periode->periode)
                    ->where('status', 'open')
                    ->exists();

                if ($nextOpen) {
                    throw new \Exception("Periode berikutnya masih terbuka.");
                }

                $adaDraft = DB::table('jurnal_header')
                    ->whereBetween('tanggal', [$periode->tanggal_mulai, $periode->tanggal_selesai])
                    ->whereIn('status', ['draft', 'void'])
                    ->exists();

                if ($adaDraft) {
                    throw new \Exception("Masih ada jurnal draft/void.");
                }

                // 1) Buat jurnal laba rugi bulanan terlebih dahulu
                $this->buatJurnalLabaRugiBulanan($periode);

                // 2) Jika Desember -> buat jurnal penutup (CLS)
                if (Carbon::parse($periode->periode)->format('m') === '12') {
                    $this->buatJurnalPenutup($periode);
                }

                // 3) Ambil saldo_awal periode berjalan (keyed)
                $saldoAwal = DB::table('m_saldo_awal as s')
                    ->join('m_akun_gl as a','a.id','=','s.akun_gl_id')
                    ->select(
                        's.akun_gl_id as akun_id',
                        's.entitas_id',
                        's.saldo as saldo_awal',
                        'a.saldo_normal'
                    )
                    ->where('s.periode', $periode->periode)
                    ->get()
                    ->keyBy(fn($x)=>$x->entitas_id.'-'.$x->akun_id);

                // 4) Ambil mutasi bulan berjalan (hanya akun neraca)
                $mutasi = DB::table('buku_besar as b')
                    ->join('m_akun_gl as a','a.id','=','b.akun_id')
                    ->select(
                        'a.id as akun_id',
                        'a.saldo_normal',
                        'b.entitas_id',
                        DB::raw('SUM(b.debit) as total_debit'),
                        DB::raw('SUM(b.kredit) as total_kredit')
                    )
                    ->whereBetween('b.tanggal', [$periode->tanggal_mulai, $periode->tanggal_selesai])
                    ->whereIn('a.tipe_akun',['aktiva','pasiva','modal'])
                    ->groupBy('a.id','a.saldo_normal','b.entitas_id')
                    ->get()
                    ->keyBy(fn($x)=>$x->entitas_id.'-'.$x->akun_id);

                // 5) Gabungkan semua key
                $allKeys = collect(array_merge(
                    array_keys($saldoAwal->toArray()),
                    array_keys($mutasi->toArray())
                ))->unique()->values();

                $saldoAkhir = [];
                foreach ($allKeys as $key) {
                    $awalRow = $saldoAwal->get($key);
                    $mutRow  = $mutasi->get($key);

                    [$entitas_id, $akun_id] = explode('-', $key);

                    $saldo_awal = $awalRow->saldo_awal ?? 0;
                    $saldo_normal = $awalRow->saldo_normal ?? ($mutRow->saldo_normal ?? 'debet');

                    $total_debit  = $mutRow->total_debit  ?? 0;
                    $total_kredit = $mutRow->total_kredit ?? 0;

                    if ($saldo_normal === 'debet') {
                        $akhir = $saldo_awal + ($total_debit - $total_kredit);
                    } else {
                        $akhir = $saldo_awal + ($total_kredit - $total_debit);
                    }

                    $saldoAkhir[] = (object)[
                        'akun_id' => (int)$akun_id,
                        'entitas_id' => (int)$entitas_id,
                        'saldo' => $akhir
                    ];
                }

                // 6) Generate periode berikut jika belum ada
                $nextPeriode = Carbon::parse($periode->periode)->addMonth()->format('Y-m');
                $periodeNext = DB::table('periode_akuntansi')->where('periode',$nextPeriode)->first();

                if (!$periodeNext) {
                    DB::table('periode_akuntansi')->insert([
                        'periode' => $nextPeriode,
                        'tanggal_mulai' => Carbon::parse($periode->tanggal_selesai)->addDay(),
                        'tanggal_selesai' => Carbon::parse($periode->tanggal_selesai)->addDay()->endOfMonth(),
                        'status' => 'open',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                // 7) Simpan semua saldo akhir menjadi saldo_awal periode berikut
                foreach ($saldoAkhir as $row) {
                    DB::table('m_saldo_awal')->updateOrInsert(
                        [
                            'periode' => $nextPeriode,
                            'akun_gl_id' => $row->akun_id,
                            'entitas_id' => $row->entitas_id,
                        ],
                        [
                            'saldo' => $row->saldo,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }

                // 8) Update status periode -> closed
                DB::table('periode_akuntansi')
                    ->where('id',$id)
                    ->update([
                        'status' => 'closed',
                        'closed_by' => Auth::id(),
                        'closed_at' => now(),
                        'updated_at' => now()
                    ]);

                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Periode berhasil ditutup dan saldo awal periode berikut disiapkan.'
                ]);
            }

            throw new \Exception("Status tidak valid.");

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buat jurnal Laba Rugi Bulanan (tetap memastikan tidak duplikasi)
     */
    private function buatJurnalLabaRugiBulanan($periode)
    {
        $entitasList = DB::table('buku_besar')
            ->select('entitas_id')
            ->whereBetween('tanggal', [$periode->tanggal_mulai, $periode->tanggal_selesai])
            ->distinct()
            ->pluck('entitas_id');

        $system = DB::table('base_sistem')->first();
        if (!$system) {
            throw new \Exception("Konfigurasi sistem belum diset di tabel base_sistem.");
        }

        $akunLabaBerjalan = $system->akun_lr_berjalan_id;
        if (!$akunLabaBerjalan) {
            throw new \Exception("Akun laba rugi belum dikonfigurasi di sistem. Silakan set di base_sistem.");
        }

        foreach ($entitasList as $entitasId) {
            $totalPendapatan = DB::table('buku_besar as b')
                ->join('m_akun_gl as a', 'a.id', '=', 'b.akun_id')
                ->where('b.entitas_id', $entitasId)
                ->whereBetween('b.tanggal', [$periode->tanggal_mulai, $periode->tanggal_selesai])
                ->where('a.tipe_akun', 'pendapatan')
                ->sum(DB::raw('b.kredit - b.debit'));

            $totalBeban = DB::table('buku_besar as b')
                ->join('m_akun_gl as a', 'a.id', '=', 'b.akun_id')
                ->where('b.entitas_id', $entitasId)
                ->whereBetween('b.tanggal', [$periode->tanggal_mulai, $periode->tanggal_selesai])
                ->where('a.tipe_akun', 'beban')
                ->sum(DB::raw('b.debit - b.kredit'));

            $labaRugi = $totalPendapatan - $totalBeban;

            // Cegah duplikasi
            $kodeJurnal = 'LRB-' . $entitasId . '-' . Carbon::parse($periode->periode)->format('Ym');
            $cek = DB::table('jurnal_header')->where('kode_jurnal', $kodeJurnal)->exists();
            if ($cek) continue;

            $jurnalId = DB::table('jurnal_header')->insertGetId([
                'kode_jurnal' => $kodeJurnal,
                'jenis' => 'JN',
                'tanggal' => $periode->tanggal_selesai,
                'keterangan' => 'Jurnal Laba Rugi Bulanan - ' . Carbon::parse($periode->periode)->translatedFormat('F Y'),
                'entitas_id' => $entitasId,
                'total_debit' => abs($labaRugi),
                'total_kredit' => abs($labaRugi),
                'status' => 'posted',
                'created_by' => Auth::id(),
                'posted_by' => Auth::id(),
                'posted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('jurnal_detail')->insert([
                'jurnal_id' => $jurnalId,
                'akun_id' => $akunLabaBerjalan,
                'debit' => ($labaRugi < 0 ? abs($labaRugi) : 0),
                'kredit' => ($labaRugi > 0 ? $labaRugi : 0),
                'deskripsi' => 'Laba Rugi Bulan ' . Carbon::parse($periode->periode)->translatedFormat('F Y'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('buku_besar')->insert([
                'jurnal_id' => $jurnalId,
                'akun_id' => $akunLabaBerjalan,
                'tanggal' => $periode->tanggal_selesai,
                'kode_jurnal' => $kodeJurnal,
                'keterangan' => 'Laba Rugi Bulan ' . Carbon::parse($periode->periode)->translatedFormat('F Y'),
                'debit' => ($labaRugi < 0 ? abs($labaRugi) : 0),
                'kredit' => ($labaRugi > 0 ? $labaRugi : 0),
                'entitas_id' => $entitasId,
                'jenis' => 'JN',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // updateSaldoAwalLabaRugi dipindahkan ke level close sehingga konsisten (tidak dipanggil di sini)
        }
    }

    /**
     * Jurnal Penutup (Akhir Tahun)
     */
    private function buatJurnalPenutup($periode)
    {
        $system = DB::table('base_sistem')->first();
        if (!$system) {
            throw new \Exception("Konfigurasi sistem belum diset di base_sistem.");
        }

        $akunLabaBerjalan = $system->akun_lr_berjalan_id; // contoh: 30703
        $akunLabaDitahan  = $system->akun_lr_lalu_id;      // contoh: 30702

        if (!$akunLabaBerjalan || !$akunLabaDitahan) {
            throw new \Exception("Akun Laba Rugi belum dikonfigurasi di base_sistem.");
        }

        // Ambil entitas yang ada transaksi dalam tahun berjalan
        $entitasList = DB::table('buku_besar')
            ->select('entitas_id')
            ->whereBetween('tanggal', [$periode->tanggal_mulai, $periode->tanggal_selesai])
            ->distinct()
            ->pluck('entitas_id');

        foreach ($entitasList as $entitasId) {

            /**
             * ----------------------------------------------------------------
             * 1️⃣ HITUNG SALDO AKHIR AKUN LABA RUGI TAHUN BERJALAN (30703)
             * ----------------------------------------------------------------
             * Rumus:
             * saldo_awal + (mutasi sesuai saldo_normal)
             */
            $saldoAwal = DB::table('m_saldo_awal')
                ->where('periode', $periode->periode)
                ->where('akun_gl_id', $akunLabaBerjalan)
                ->where('entitas_id', $entitasId)
                ->value('saldo') ?? 0;

            $mutasi = DB::table('buku_besar as b')
                ->join('m_akun_gl as a','a.id','=','b.akun_id')
                ->where('b.entitas_id', $entitasId)
                ->where('b.akun_id', $akunLabaBerjalan)
                ->whereBetween('b.tanggal', [$periode->tanggal_mulai, $periode->tanggal_selesai])
                ->selectRaw("
                    SUM(
                        CASE 
                            WHEN a.saldo_normal='debet'  THEN (b.debit - b.kredit)
                            WHEN a.saldo_normal='kredit' THEN (b.kredit - b.debit)
                            ELSE 0
                        END
                    ) as mutasi
                ")
                ->value('mutasi') ?? 0;

            $saldoAkhirLR = $saldoAwal + $mutasi;

            // Jika saldo 0 → tidak perlu buat jurnal penutup
            if ($saldoAkhirLR == 0) continue;


            /**
             * ----------------------------------------------------------------
             * 2️⃣ CEGAH DUPLIKASI JURNAL PENUTUP
             * ----------------------------------------------------------------
             */
            $kodePrefix = "CLS-$entitasId-".Carbon::parse($periode->periode)->format('Y');
            $cek = DB::table('jurnal_header')
                ->where('kode_jurnal', 'like', "$kodePrefix%")
                ->exists();

            if ($cek) continue;


            /**
             * ----------------------------------------------------------------
             * 3️⃣ BUAT JURNAL PENUTUP
             * ----------------------------------------------------------------
             */
            $kodeJurnal = $kodePrefix.'-'.uniqid();

            $jurnalId = DB::table('jurnal_header')->insertGetId([
                'kode_jurnal' => $kodeJurnal,
                'jenis' => 'JN',
                'tanggal' => $periode->tanggal_selesai,
                'keterangan' => 'Jurnal Penutup Laba Rugi Tahun Berjalan',
                'entitas_id' => $entitasId,
                'total_debit' => abs($saldoAkhirLR),
                'total_kredit' => abs($saldoAkhirLR),
                'status' => 'posted',
                'created_by' => Auth::id(),
                'posted_by' => Auth::id(),
                'posted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Jika laba → saldoAkhirLR > 0
            // Jika rugi → saldoAkhirLR < 0
            $isLaba = $saldoAkhirLR > 0;

            // JURNAL DETAIL
            DB::table('jurnal_detail')->insert([
                [
                    'jurnal_id' => $jurnalId,
                    'akun_id' => $akunLabaBerjalan,
                    'debit'  => $isLaba ? $saldoAkhirLR : 0,
                    'kredit' => !$isLaba ? abs($saldoAkhirLR) : 0,
                    'deskripsi' => 'Tutup Laba Rugi Tahun Berjalan',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'jurnal_id' => $jurnalId,
                    'akun_id' => $akunLabaDitahan,
                    'debit'  => !$isLaba ? abs($saldoAkhirLR) : 0,
                    'kredit' => $isLaba ? $saldoAkhirLR : 0,
                    'deskripsi' => 'Pindahkan ke Laba Ditahan',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);

            // BUKU BESAR
            DB::table('buku_besar')->insert([
                [
                    'jurnal_id' => $jurnalId,
                    'akun_id' => $akunLabaBerjalan,
                    'tanggal' => $periode->tanggal_selesai,
                    'kode_jurnal' => $kodeJurnal,
                    'keterangan' => 'Tutup Laba Rugi Tahun Berjalan',
                    'debit'  => $isLaba ? $saldoAkhirLR : 0,
                    'kredit' => !$isLaba ? abs($saldoAkhirLR) : 0,
                    'entitas_id' => $entitasId,
                    'jenis' => 'JN',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'jurnal_id' => $jurnalId,
                    'akun_id' => $akunLabaDitahan,
                    'tanggal' => $periode->tanggal_selesai,
                    'kode_jurnal' => $kodeJurnal,
                    'keterangan' => 'Pindahkan ke Laba Ditahan',
                    'debit'  => !$isLaba ? abs($saldoAkhirLR) : 0,
                    'kredit' => $isLaba ? $saldoAkhirLR : 0,
                    'entitas_id' => $entitasId,
                    'jenis' => 'JN',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);


            /**
             * ----------------------------------------------------------------
             * 4️⃣ UPDATE SALDO AWAL PER JANUARI TAHUN BERIKUTNYA
             * ----------------------------------------------------------------
             */
            $nextPeriode = Carbon::parse($periode->periode)->addMonth()->format('Y-m');

            DB::table('m_saldo_awal')->updateOrInsert(
                [
                    'periode' => $nextPeriode,
                    'akun_gl_id' => $akunLabaDitahan,
                    'entitas_id' => $entitasId,
                ],
                [
                    'saldo' => $saldoAkhirLR,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

}
