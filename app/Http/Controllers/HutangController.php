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
class HutangController extends Controller
{
    public function index(Request $request)
    {
        // Jika permintaan AJAX (DataTables)
        $entitas_id = $request->get('entitas_id');
        $hutang_id = $request->get('hutang_id');
        if ($request->ajax()) {
            $query  = DB::table('view_aging_hutang');
                    if (!empty($entitas_id)) {
                        $query->where('entitas_id', $entitas_id);
                    }

                    if (!empty($hutang_id)) {
                        $query->where('akun_hutang_id', $hutang_id);
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
            $footer = [
                'aging_0_14'    => $data->sum('aging_0_14'),
                'aging_15_30'   => $data->sum('aging_15_30'),
                'aging_31_45'   => $data->sum('aging_31_45'),
                'aging_46_60'   => $data->sum('aging_46_60'),
                'aging_60_plus' => $data->sum('aging_60_plus'),
                'total_hutang'  => $data->sum('total_hutang'),
            ];
            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('aging_0_14', fn($row) => number_format($row->aging_0_14, 2, ',', '.'))
                ->editColumn('aging_15_30', fn($row) => number_format($row->aging_15_30, 2, ',', '.'))
                ->editColumn('aging_31_45', fn($row) => number_format($row->aging_31_45, 2, ',', '.'))
                ->editColumn('aging_46_60', fn($row) => number_format($row->aging_46_60, 2, ',', '.'))
                ->editColumn('aging_60_plus', fn($row) => number_format($row->aging_60_plus, 2, ',', '.'))
                ->editColumn('total_hutang', fn($row) => "<b>" . number_format($row->total_hutang, 2, ',', '.') . "</b>")
                ->with('totalFooter', collect($footer)->map(
                    fn ($v) => number_format($v, 2, ',', '.')
                ))
                ->rawColumns(['total_hutang'])
                ->make(true);
        }

        return view('page.hutang.index');
    }

    public function agingHutangExport(Request $request)
    {
        $hutang_id = $request->get('hutang_id');
        $entitas_id = null;
        // Jika user level entitas → paksa entitas user
        if ($request->user()->level == 'entitas') {
            $entitas_id = $request->entitas_scope;
        }
        // Jika admin/pusat → ambil dari dropdown entitas (boleh kosong)
        else {
            $entitas_id = $request->get('entitas_id');
        }
        $query = DB::table('view_aging_hutang');

        if (!empty($entitas_id)) {
            $query->where('entitas_id', $entitas_id);
        }

        if (!empty($hutang_id)) {
            $query->where('akun_hutang_id', $hutang_id);
        }

        $data = $query->get();

        $filename = 'Laporan_Aging_Hutang' . '_' . date('Ymd_His') . '.xlsx';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Aging Hutang');

        // Header
        $sheet->fromArray([
            ['No', 'Partner','Akun Hutang', '0–14 Hari', '15–30 Hari', '31–45 Hari','46–60 Hari', '>90 Hari', 'Total']
        ]);

        // Data
        $row = 2;
        $no = 1;
        foreach ($data as $d) {
            $sheet->fromArray([
                [$no++, $d->partner_nama,$d->akun_kode."-".$d->akun_nama, $d->aging_0_14, $d->aging_15_30, $d->aging_31_45, $d->aging_46_60,$d->aging_60_plus, $d->total_hutang]
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
        $partner_id = $request->input('partner_id');
        $entitas_id = $request->input('entitas_id');
        $hutang_id = $request->input('hutang_id');
        if ($request->ajax()) {
            $query = DB::table('view_daftar_hutang');

            /*
            |----------------------------------------------------------
            | FILTER PARTNER
            |----------------------------------------------------------
            */
            if (!empty($partner_id)) {
                $query->where('partner_id', $partner_id);
            }

            if (!empty($entitas_id)) {
                $query->where('entitas_id', $entitas_id);
            }

            if (!empty($hutang_id)) {
                $query->where('akun_hutang_id', $hutang_id);
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
                // ->filterColumn('no_invoice', function($q, $keyword) {
                //     $q->where(function($query) use ($keyword) {
                //         $query->where('no_invoice', 'LIKE', "%$keyword%")
                //             ->orWhere('kode_jurnal', 'LIKE', "%$keyword%");
                //     });
                // })

                // Search untuk keterangan
                ->filterColumn('keterangan', function($q, $kw) {
                    $q->where('keterangan', 'LIKE', "%$kw%");
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
                ->filterColumn('sisa_hutang', function($q, $kw) {
                    $q->havingRaw("sisa_hutang LIKE ?", ["%$kw%"]);
                })

                // Search untuk total_pelunasan
                ->filterColumn('total_pelunasan', function($q, $kw) {
                    $q->havingRaw("total_pelunasan LIKE ?", ["%$kw%"]);
                })
                ->editColumn('total_tagihan', fn($r) => number_format($r->total_tagihan, 2, ',', '.'))
                ->editColumn('total_pelunasan', fn($r) => number_format($r->total_pelunasan, 2, ',', '.'))
                ->editColumn('sisa_hutang', fn($r) => number_format($r->sisa_hutang, 2, ',', '.'))
                ->with('totalFooter', [
                    'total_tagihan'   => number_format($query->sum('total_tagihan'), 2, ',', '.'),
                    'total_pelunasan'  => number_format($query->sum('total_pelunasan'), 2, ',', '.'),
                    'sisa_hutang'  => number_format($query->sum('sisa_hutang'), 2, ',', '.'),
                ])
                ->make(true);
        }
        
        return view('page.hutang.daftar');
    }

    public function select(Request $request){
        $query = $request->get('q');
        $data = DB::table("m_akun_gl")->select("id","nama")
                ->where('nama','like','%'.$query.'%')
                ->where("kategori","hutang")->get();
         $data->prepend((object)[
            'id' => '',
            'nama' => 'Semua Hutang'
        ]);
        return response()->json($data);
    }

    public function exportExcel(Request $request)
    {
        $partner_id = $request->input('partner_id');
        $entitas_id = $request->input('entitas_id');
        $hutang_id = $request->input('hutang_id');

        $data = DB::table('view_daftar_hutang');

        /*
        |----------------------------------------------------------
        | FILTER PARTNER
        |----------------------------------------------------------
        */
        if (!empty($partner_id)) {
            $query->where('partner_id', $partner_id);
        }

        if (!empty($entitas_id)) {
            $data->where('entitas_id', $entitas_id);
        }
        if (!empty($hutang_id)) {
            $data->where('akun_hutang_id', $hutang_id);
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

        return Excel::download(new \App\Exports\DaftarHutangExport($data), 'daftar_hutang.xlsx');
    }

}
