<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;    
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Validator;
class PiutangController extends Controller
{
    public function index(Request $request)
    {
        // Jika permintaan AJAX (DataTables)
        $filter = $request->get('filter');
        $entitas_id = $request->get('entitas_id');
        if ($request->ajax()) {
            $query  = DB::table('view_aging_piutang');
                    if ($filter === 'customer') {
                        $query->where('is_customer', 'active');
                    } elseif ($filter === 'vendor') {
                        $query->where('is_vendor', 'active');
                    }
                    if (!empty($entitas_id)) {
                        $query->where('entitas_id', $entitas_id);
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
                ->editColumn('aging_0_14', fn($row) => number_format($row->aging_0_14, 0, ',', '.'))
                ->editColumn('aging_15_30', fn($row) => number_format($row->aging_15_30, 0, ',', '.'))
                ->editColumn('aging_31_45', fn($row) => number_format($row->aging_31_45, 0, ',', '.'))
                ->editColumn('aging_46_60', fn($row) => number_format($row->aging_46_60, 0, ',', '.'))
                ->editColumn('aging_60_plus', fn($row) => number_format($row->aging_60_plus, 0, ',', '.'))
                ->editColumn('total_piutang', fn($row) => "<b>" . number_format($row->total_piutang, 0, ',', '.') . "</b>")
                ->rawColumns(['total_piutang'])
                ->make(true);
        }

        return view('page.piutang.index');
    }

    public function agingPiutangExport(Request $request)
    {
        $filter = $request->get('filter');
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

    public function daftar(Request $request)
    {
        $filter = $request->input('filter', 'all');
        $entitas_id = $request->input('entitas_id');
        if ($request->ajax()) {
            $query = DB::table('view_daftar_piutang');

            if ($filter === 'customer') {
                $query->where('is_customer', 'active');
            } elseif ($filter === 'vendor') {
                $query->where('is_vendor', 'active');
            }

            if (!empty($entitas_id)) {
                $query->where('entitas_id', $entitas_id);
            }

            /*
            |--------------------------------------------------------------------------
            | 1. FILTER WAJIB UNTUK USER LEVEL ENTITAS
            |--------------------------------------------------------------------------
            */
            if ($request->entitas_scope) {
                $query->where('entitas_id', $request->entitas_scope);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                // Search untuk kode_jurnal
                ->filterColumn('no_invoice', function($q, $keyword) {
                    $q->where(function($query) use ($keyword) {
                        $query->where('no_invoice', 'LIKE', "%$keyword%")
                            ->orWhere('kode_jurnal', 'LIKE', "%$keyword%");
                    });
                })

                // Search untuk partner_nama
                ->filterColumn('partner_nama', function($q, $kw) {
                    $q->where('partner_nama', 'LIKE', "%$kw%");
                })

                // Search untuk total_tagihan
                ->filterColumn('total_tagihan', function($q, $kw) {
                    $q->havingRaw("total_tagihan LIKE ?", ["%$kw%"]);
                })

                // Search untuk sisa_piutang
                ->filterColumn('sisa_piutang', function($q, $kw) {
                    $q->havingRaw("sisa_piutang LIKE ?", ["%$kw%"]);
                })

                // Search untuk total_pelunasan
                ->filterColumn('total_pelunasan', function($q, $kw) {
                    $q->havingRaw("total_pelunasan LIKE ?", ["%$kw%"]);
                })
                ->editColumn('total_tagihan', fn($r) => number_format($r->total_tagihan, 0, ',', '.'))
                ->editColumn('total_pelunasan', fn($r) => number_format($r->total_pelunasan, 0, ',', '.'))
                ->editColumn('sisa_piutang', fn($r) => number_format($r->sisa_piutang, 0, ',', '.'))
                ->make(true);
        }
        
        return view('page.piutang.daftar');
    }

    public function exportExcel(Request $request)
    {
        $filter = $request->input('filter', 'all');
        $entitas_id = $request->input('entitas_id');

        $data = DB::table('view_daftar_piutang');

        if ($filter === 'customer') {
            $data->where('is_customer', 'active');
        } elseif ($filter === 'vendor') {
            $data->where('is_vendor', 'active');
        }

        if (!empty($entitas_id)) {
            $data->where('entitas_id', $entitas_id);
        }
        /*
        |--------------------------------------------------------------------------
        | 1. FILTER WAJIB UNTUK USER LEVEL ENTITAS
        |--------------------------------------------------------------------------
        */
        if ($request->entitas_scope) {
            $data->where('entitas_id', $request->entitas_scope);
        }

        $data = $data->get();

        return Excel::download(new \App\Exports\DaftarPiutangExport($data), 'daftar_piutang.xlsx');
    }

}
