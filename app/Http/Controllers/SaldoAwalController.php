<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SaldoAwalImport;
use App\Exports\SaldoAwalTemplateExport;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Validator;
use Throwable;

class SaldoAwalController extends Controller
{
    /**
     * Tampilkan halaman utama saldo awal.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = DB::table('m_saldo_awal')
                ->join('m_akun_gl', 'm_saldo_awal.akun_gl_id', '=', 'm_akun_gl.id')
                ->join('m_entitas', 'm_saldo_awal.entitas_id', '=', 'm_entitas.id')
                ->select(
                    'm_saldo_awal.id',
                    DB::raw("CONCAT(m_akun_gl.no_akun, ' - ', m_akun_gl.nama) as akun_gl"),
                    'm_saldo_awal.periode',
                    'm_entitas.nama as entitas',
                    'm_saldo_awal.saldo'
                )
                ->orderBy('m_saldo_awal.periode', 'desc');
                // 🧠 Tambahkan filter jika user memilih periode
                if ($request->filled('periode')) {
                    $query->where('m_saldo_awal.periode', $request->periode);
                }
                /*
                |--------------------------------------------------------------------------
                | 1. FILTER WAJIB UNTUK USER LEVEL ENTITAS
                |--------------------------------------------------------------------------
                */
                if ($request->entitas_scope) {
                    $query->where('m_saldo_awal.entitas_id', $request->entitas_scope);
                }

            return DataTables::of($query)
                // 🔥 FIX PENCARIAN
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && $request->search['value'] != '') {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('m_akun_gl.no_akun', 'like', "%{$search}%")
                            ->orWhere('m_akun_gl.nama', 'like', "%{$search}%")
                            ->orWhere('m_entitas.nama', 'like', "%{$search}%")
                            ->orWhere('m_saldo_awal.periode', 'like', "%{$search}%")
                            ->orWhere('m_saldo_awal.saldo', 'like', "%{$search}%");
                        });
                    }
                })
                ->addIndexColumn()
                ->editColumn('periode', function ($row) {
                    $formatted = Carbon::createFromFormat('Y-m', $row->periode)->translatedFormat('F Y');
                    return $formatted;
                })
                ->addColumn('aksi', function ($row) {
                    $html = '<div class="btn-group btn-group-sm">';
                    if(canAccess('m_saldo_awal.edit')){
                        $url = route('saldo_awal.edit')."?id=".$row->id;
                        $html .= '<a href="' . $url . '" title="Update Data" data-bs-toggle="tooltip" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>';
                    }

                    if(canAccess('m_saldo_awal.delete')){
                        $html .= '<button title="Hapus Data" data-bs-toggle="tooltip" class="btn btn-sm btn-danger btn-delete" onclick="hapusData('.$row->id.')"><i class="fa fa-trash"></i></button>';
                    }
                    $html .= "</div>";
                    return $html;
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }

        return view('page.saldo_awal.index');
    }

    public function create()
    {
        return view('page.saldo_awal.tambah');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $id = $request->id;
        $data = DB::table('m_saldo_awal')->where('id', $id)->first();
        $akun_selected = DB::table('m_akun_gl')->where("id",$data->akun_gl_id)->first();
        $entitas_selected = DB::table('m_entitas')->where("id",$data->entitas_id)->first();
        return view("page.saldo_awal.edit",compact("data","id","akun_selected","entitas_selected"));
    }

    public function akun_gl(Request $request)
    {
        $query = $request->get('q');
        $m_akun = DB::table("view_akun_transaksi_only")->where('nama','like','%'.$query.'%')->orWhere('no_akun','like','%'.$query.'%')->get();
        return response()->json($m_akun);
    }

    public function entitas(Request $request)
    {
        $query = $request->get('q');
        $m_akun = DB::table("m_entitas")->where('nama','like','%'.$query.'%')->orderBy('nama','asc')->get();
        return response()->json($m_akun);
    }
    /**
     * Simpan saldo awal baru.
     */
    public function store(Request $request)
    {
        $rules = [
            'akun_gl_id' => 'required|integer',
            'periode'    => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'], // YYYY-MM
            'entitas_id' => 'required|integer',
            'saldo'      => 'required',
        ];

        $messages = [
            'akun_gl_id.required' => 'Akun GL wajib dipilih.',
            'periode.required'    => 'Periode wajib diisi.',
            'periode.regex'       => 'Format periode harus YYYY-MM (contoh: 2025-10).',
            'entitas_id.required' => 'Entitas wajib dipilih.',
        ];
        $saldo = str_replace(['.', 'Rp', ' '], '', $request->saldo);

        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json([
                'status' => 'warning',
                'message' => $validation->errors()->first(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            DB::table('m_saldo_awal')->insert([
                'akun_gl_id' => $request->akun_gl_id,
                'periode'    => $request->periode,
                'entitas_id' => $request->entitas_id,
                'saldo'      => $saldo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data saldo awal berhasil disimpan.',
            ]);

        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data saldo awal.',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update data saldo awal.
     */
    public function update(Request $request, $id)
    {
        $rules = [
            'akun_gl_id' => 'required|integer',
            'periode'    => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'], // YYYY-MM
            'entitas_id' => 'required|integer',
            'saldo'      => 'required',
        ];

        $messages = [
            'akun_gl_id.required' => 'Akun GL wajib dipilih.',
            'periode.required'    => 'Periode wajib diisi.',
            'periode.regex'       => 'Format periode harus YYYY-MM (contoh: 2025-10).',
            'entitas_id.required' => 'Entitas wajib dipilih.',
        ];
        $saldo = str_replace(['.', 'Rp', ' '], '', $request->saldo);

        $validation = Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json([
                'status' => 'warning',
                'message' => $validation->errors()->first(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            DB::table('m_saldo_awal')
                ->where('id', $id)
                ->update([
                    'akun_gl_id' => $request->akun_gl_id,
                    'periode'    => $request->periode,
                    'entitas_id' => $request->entitas_id,
                    'saldo'      => $saldo,
                    'updated_at' => now(),
                ]);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data saldo awal berhasil diperbarui.',
            ]);

        } catch (Throwable $t) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui data saldo awal.',
                'detail' => $t->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus saldo awal.
     */
    public function destroy($id)
    {
        try {
            DB::table('m_saldo_awal')->where('id', $id)->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data saldo awal berhasil dihapus.',
            ]);
        } catch (Throwable $t) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data saldo awal.',
                'detail' => $t->getMessage(),
            ], 500);
        }
    }

    /**
     * Export template Excel saldo awal.
     */
    public function form_import()
    {
        return view("page.saldo_awal.import");
    }
    public function downloadTemplate()
    {
        return Excel::download(new SaldoAwalTemplateExport, 'template_saldo_awal.xlsx');
    }

    /**
     * Import saldo awal dari file Excel.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'File Excel wajib diupload.',
            'file.mimes' => 'Gunakan file Excel (.xlsx, .xls, .csv).',
        ]);

        try {
            Excel::import(new SaldoAwalImport, $request->file('file'));
            
            return response()->json([
                'status' => 'success',
                'message' => 'Data saldo awal berhasil diimport.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat import data saldo awal.',
                'detail' => $e->getMessage(),
            ], 200);
        }
    }
}
