<?php

namespace App\Helpers;

use App\Models\JurnalHeader;
use App\Models\JurnalDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Exports\TemplateJPAcs;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Exception;

class TemplateJurnalJPService
{

    public static function get_template($request){
        if ($request->entitas_scope) {
            $entitas_id = $request->entitas_scope;
        }elseif($request->filled('entitas_id')){
            $entitas_id = $request->entitas_id;
        }
        $jenis = $request->jenis;
        switch ($jenis) {
            case 'acs':
                return Excel::download(
                    new TemplateJPAcs($entitas_id),
                    'template_jrr_acs.xlsx'
                );
                break;
            
            default:
                # code...
                break;
        }
    }

    
}
