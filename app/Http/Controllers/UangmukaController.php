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
                ->editColumn('saldo_awal', function ($row) {
                    return number_format($row['saldo_awal'], 2, ',', '.');
                })
                ->editColumn('total_debit', function ($row) {
                    return number_format($row['total_debit'], 2, ',', '.');
                })
                ->editColumn('total_kredit', function ($row) {
                    return number_format($row['total_kredit'], 2, ',', '.');
                })
                ->make(true);


        }
        return view('page.uangmuka.index'); 
    }

    public function akun_gl(Request $request)
    {
        $query = $request->get('q');
        $m_akun = DB::table("view_akun_transaksi_only")
                ->join(
                    "m_akun_gl",
                    "m_akun_gl.id",
                    "=",
                    "view_akun_transaksi_only.id"
                )
                ->select(
                    "view_akun_transaksi_only.id",
                    "view_akun_transaksi_only.nama",
                    "view_akun_transaksi_only.no_akun"
                )
                ->where("m_akun_gl.kategori", "uang_muka")
                ->where(function ($q) use ($query) {
                    $q->where("view_akun_transaksi_only.nama", "like", "%{$query}%")
                    ->orWhere("view_akun_transaksi_only.no_akun", "like", "%{$query}%");
                })
                ->get();
        return response()->json($m_akun);
    }

    public function agingIndex(Request $request)
    {
        
        if ($request->ajax()) {
            $validated = $request->validate([
                'entitas_id' => 'nullable|integer',
                'partner_id' => 'nullable|integer',
                'akun_gl_id' => 'nullable|integer',
                'periode_from' => 'required|date',
                'periode_to' => 'required|date|after_or_equal:periode_from',
            ]);

            $result = UangMukaService::getMonitoring(
                entitas_id: $validated['entitas_id'] ?? null,
                partner_id: $validated['partner_id'] ?? null,
                akun_gl_id: $validated['akun_gl_id'] ?? null,
                periode_awal: $validated['periode_from'],
                periode_akhir: $validated['periode_to']
            );

            return DataTables::of($result['data'])
                ->addColumn('DT_RowClass', function ($row) {
                    return $row['jenis'] === 'saldo_awal'
                        ? 'table-info fw-bold'
                        : '';
                })
                ->with('summary', $result['summary'])
                ->make(true);
        }

        return view('page.uangmuka.aging');
    }


    public function agingExport(Request $request)
    {
        $validated = $request->validate([
            'entitas_id' => 'nullable|integer',
            'partner_id' => 'nullable|integer',
            'akun_gl_id' => 'nullable|integer',
            'periode_from' => 'required|date',
            'periode_to' => 'required|date|after_or_equal:periode_from',
        ]);

        $data = UangMukaService::getMonitoring(
            entitas_id: $validated['entitas_id'] ?? null,
            partner_id: $validated['partner_id'] ?? null,
            akun_gl_id: $validated['akun_gl_id'] ?? null,
            periode_awal: $validated['periode_from'],
            periode_akhir: $validated['periode_to']
        );
        // dd($data);
        $filename = 'Laporan_Monitoring_UangMuka_' . '_' . date('Ymd_His') . '.xlsx';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Aging Piutang');

        // Header
        $sheet->fromArray([
            ['No','Tanggal', 'Kode Jurnal','Akun GL', 'Keterangan', 'Debit', 'Kredit','Saldo']
        ]);

        // Data
        $row = 2;
        $no = 1;
        foreach ($data['data'] as $d) {
            $sheet->fromArray([
                [$no++, $d['tanggal'], $d['kode_jurnal'],$d['akun_gl'], $d['keterangan'], $d['debit'], $d['kredit'],$d['saldo']]
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
        return Excel::download(new \App\Exports\DaftarUangMukaExport($data), 'daftar_uangmuka.xlsx');
    }

}
