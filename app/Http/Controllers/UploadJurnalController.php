<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\TemplateJurnalKasKeluar;
use Maatwebsite\Excel\Facades\Excel;
// use App\Helpers\JurnalService;
// use App\Helpers\PelunasanPiutangService;
// use App\Helpers\UangMukaService;
// use App\Helpers\HutangService;
// use Illuminate\Support\Facades\Cache;
// use Carbon\Carbon;
// use Validator;
// use Exception;
class UploadJurnalController extends Controller
{
    public function index(Request $request)
    {
        return view("page.jurnal.upload_kas_keluar");
    }

    public function template(Request $request)
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

   

}
