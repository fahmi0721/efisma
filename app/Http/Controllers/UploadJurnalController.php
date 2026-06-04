<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\TemplateJurnalKasKeluar;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\UploadJurnalValidasiService;
use App\Helpers\UploadJurnalService;
use Exception;

class UploadJurnalController extends Controller
{
    public function index(Request $request)
    {
        return view("page.jurnal.upload_kas_keluar");
    }

    public function template_kaskeluar(Request $request)
    {
        if ($request->entitas_scope) {
            $entitas_id = $request->entitas_scope;
        }elseif($request->filled('entitas_id')){
            $entitas_id = $request->entitas_id;
        }
        return Excel::download(
            new TemplateJurnalKasKeluar($entitas_id),
            'template_jurnal_kas_keluar.xlsx'
        );
    }

    public function kaskeluar(Request $request)
    {
        try {
            UploadJurnalValidasiService::kaskeluar($request);
            $res = UploadJurnalService::kaskeluar($request);

            // lanjut proses upload/import excel di sini

            return response()->json([
                'status'  => 'success',
                'message' => 'File berhasil diupload.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'warning',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
            
    }

   

}
