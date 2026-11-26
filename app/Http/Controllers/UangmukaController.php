<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;    
use Yajra\DataTables\Facades\DataTables;
use App\Exports\UangMukaExport;
use Carbon\Carbon;
use Validator;
class UangmukaController extends Controller
{
    public function index(Request $request)
    {
        $entitas_id = $request->get('entitas_id');
        $cabang_id  = $request->get('cabang_id');

        // Jika permintaan AJAX (DataTables)
        if ($request->ajax()) {

            $query = DB::table('view_uang_muka_per_akun');

            // Filter entitas
            if (!empty($entitas_id)) {
                $query->where('entitas_id', $entitas_id);
            }

            // Filter cabang
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

                ->editColumn('nominal', fn($row) => number_format($row->nominal, 0, ',', '.'))
                ->editColumn('terpakai', fn($row) => number_format($row->terpakai, 0, ',', '.'))
                ->editColumn('sisa', function ($row) {
                    $sisa = number_format($row->sisa, 0, ',', '.');
                    return $row->sisa > 0 
                        ? "<span class='text-success fw-bold'>{$sisa}</span>"
                        : "<span class='text-danger fw-bold'>{$sisa}</span>";
                })

                ->editColumn('umur', fn($row) => "{$row->umur} hari")

                ->editColumn('status', function ($row) {
                    if ($row->status === 'open') {
                        return "<span class='badge bg-warning text-dark'>OPEN</span>";
                    }
                    return "<span class='badge bg-success'>CLOSED</span>";
                })
                 ->with('totalFooter', [
                    'total_uangmuka'   => $query->sum('nominal'),
                    'total_terpakai'  => $query->sum('terpakai'),
                    'sisa_uangmuka'  => $query->sum('sisa'),
                ])
                ->rawColumns(['sisa', 'status'])
                ->make(true);
        }

        return view('page.uangmuka.index');
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
        $entitasId = $request->get('entitas_id');
        $cabangId = $request->get('cabang_id');

        // Scope wajib dari middleware
        $entitasScope = $request->entitas_scope;

        $namaFile = 'Monitoring_Uang_Muka_'
            . date('Ymd_His')
            . '.xlsx';

        return Excel::download(
            new UangMukaExport(
                $entitasId,
                $cabangId,
                $entitasScope
            ),
            $namaFile
        );
    }


}
