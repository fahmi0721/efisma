<?php

namespace App\Helpers;

use App\Models\JurnalHeader;
use App\Models\JurnalDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\UploadJP\JurnalAcsProcess;
use App\Helpers\UploadJP\JurnalUangMukaProcess;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Exception;

class UploadJurnalJPService
{
    public static function uploads($request){
        $jenis = $request->jenis_upload;
        switch ($jenis) {
            case 'acs':
                JurnalAcsProcess::index($request);
                break;
            case 'uang_muka':
                JurnalUangMukaProcess::index($request);
                break;
            
            default:
                throw new Exception('Jenis jurnal tidak ditemukan', 422);
                break;
        }
    }
    

    
}
