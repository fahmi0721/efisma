<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\UploadJurnalJPValidasiService;
use App\Helpers\UploadJurnalJPService;
use App\Helpers\TemplateJurnalJPService;
use Exception;

class UploadJurnalJPController extends Controller
{
    public function index(Request $request)
    {
        return view("page.jurnal.upload_penyesuaian");
    }

    public function template(Request $request)
    {
        return TemplateJurnalJPService::get_template($request);
    }

    public function upload(Request $request)
    {
        try {
            UploadJurnalJPValidasiService::validasi($request);
            $res = UploadJurnalJPService::uploads($request);

            // lanjut proses upload/import excel di sini

            return response()->json([
                'status'  => 'success',
                'message' => 'Upload Jurnal Berhasil!',
            ]);

        } catch (Exception $e) {
            $code = $e->getCode();
            $statusCode = is_numeric($code)
                && (int) $code >= 100
                && (int) $code <= 599
                    ? (int) $code
                    : 500;

            return response()->json([
                'status'  => 'warning',
                'message' => $e->getMessage(),
            ], $statusCode);
        }
            
    }

   

}
