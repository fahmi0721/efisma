<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;    
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\UangMukaService;
use Carbon\Carbon;
use Validator;
class UangmukaController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $entitas_id = null;
            $request->validate([
                'entitas_id' => 'nullable|integer',
                'partner_id' => 'nullable|integer',
                'akun_gl_id' => 'nullable|integer',
                'periode_from' => 'required|date',
                'periode_to' => 'required|date|after_or_equal:periode_from',
            ]);
            if(!empty($request->entitas_id)){
                $entitas_id = ($request->entitas_scope) ? $request->entitas_scope : $request->get('entitas_id');    
            }
            $data = UangMukaService::getDaftarUangMuka(
                entitas_id: $entitas_id,
                partner_id: $request->partner_id,
                akun_gl_id: $request->akun_gl_id,
                tgl_mulai: $request->periode_from,
                tgl_akhir: $request->periode_to
            );

             return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('saldo', function ($row) {
                    return number_format($row['saldo'], 2, ',', '.');
                })
                ->make(true);


        }
        return view('page.uangmuka.index'); 
    }

    public function akun_gl(Request $request)
    {
        $query = $request->get('q');
        $m_akun = DB::table("view_akun_transaksi_only")->where('nama','like','%'.$query.'%')->orWhere('no_akun','like','%'.$query.'%')->get();
        return response()->json($m_akun);
    }

    public function agingIndex(Request $request)
    {
        // Jika permintaan AJAX (DataTables)
        $entitas_id = $request->get('entitas_id');
        $cabang_id = $request->get('cabang_id');
        if ($request->ajax()) {
            $query  = DB::table('view_aging_uang_muka');
                    if (!empty($entitas_id)) {
                        $query->where('entitas_id', $entitas_id);
                    }
                    if (!empty($cabang_id)) {
                        $query->where('cabang_id', $cabang_id);
                    }
                    /*
                    |--------------------------------------------------------------------------
                    | 1. FILTER WAJIB UNTUK USER LEVEL ENTITAS
                    |--------------------------------------------------------------------------
                    */
                    if ($request->entitas_scope) {
                        $query->where('entitas_id', $request->entitas_scope);
                    }
            $data = $query->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('aging_0_7', fn($row) => number_format($row->aging_0_7, 0, ',', '.'))
                ->editColumn('aging_8_14', fn($row) => number_format($row->aging_8_14, 0, ',', '.'))
                ->editColumn('aging_14_30', fn($row) => number_format($row->aging_15_30, 0, ',', '.'))
                ->editColumn('aging_30_up', fn($row) => number_format($row->aging_30_up, 0, ',', '.'))
                ->make(true);
        }

        return view('page.uangmuka.aging');
    }


    public function agingPiutangExport(Request $request)
    {
        $filter = $request->get('filter');
        $cabang_id = $request->get('cabang_id');
        $entitas_id = null;
        // Jika user level entitas → paksa entitas user
        if ($request->user()->level == 'entitas') {
            $entitas_id = $request->entitas_scope;
        }
        // Jika admin/pusat → ambil dari dropdown entitas (boleh kosong)
        else {
            $entitas_id = $request->get('entitas_id');
        }
        $query = DB::table('view_aging_piutang');

        if ($filter === 'customer') {
            $query->where('is_customer', 'active');
        } elseif ($filter === 'vendor') {
            $query->where('is_vendor', 'active');
        }

        if (!empty($entitas_id)) {
            $query->where('entitas_id', $entitas_id);
        }

        if (!empty($cabang_id)) {
            $query->where('cabang_id', $cabang_id);
        }

        $data = $query->get();

        $filename = 'Laporan_Aging_Piutang_' . ucfirst($filter ?: 'semua') . '_' . date('Ymd_His') . '.xlsx';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Aging Piutang');

        // Header
        $sheet->fromArray([
            ['No', 'Partner', '0–14 Hari', '15–30 Hari', '31–45 Hari','46–60 Hari', '>90 Hari', 'Total']
        ]);

        // Data
        $row = 2;
        $no = 1;
        foreach ($data as $d) {
            $sheet->fromArray([
                [$no++, $d->partner_nama, $d->aging_0_14, $d->aging_15_30, $d->aging_31_45, $d->aging_46_60,$d->aging_60_plus, $d->total_piutang]
            ], null, "A{$row}");
            $row++;
        }

        // Auto size
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $path = storage_path('app/public/' . $filename);
        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    
    public function exportExcel(Request $request)
    {
        $entitas_id = null;
        $request->validate([
            'entitas_id' => 'nullable|integer',
            'partner_id' => 'nullable|integer',
            'akun_gl_id' => 'nullable|integer',
            'periode_from' => 'required|date',
            'periode_to' => 'required|date|after_or_equal:periode_from',
        ]);
        if(!empty($request->entitas_id)){
            $entitas_id = ($request->entitas_scope) ? $request->entitas_scope : $request->get('entitas_id');    
        }
        $data = UangMukaService::getDaftarUangMuka(
            entitas_id: $entitas_id,
            partner_id: $request->partner_id,
            akun_gl_id: $request->akun_gl_id,
            tgl_mulai: $request->periode_from,
            tgl_akhir: $request->periode_to
        );
        // dd($data);
        return Excel::download(new \App\Exports\DaftarUangMukaExport($data), 'daftar_uangmuka.xlsx');
    }

}
