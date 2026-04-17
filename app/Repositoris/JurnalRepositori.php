<?php

namespace App\Repositoris;

use Illuminate\Support\Facades\DB;
use Exception;

class JurnalRepositori
{
    
    /**
     * get Jurnal By ID
     */
    public static function getJurnalHeaderById($id)
    {
        return DB::table('jurnal_header')->where('id', $id)->first();
    }

    /**
     * get Detail Jurnal By Jurnal ID
     */
    public static function getJurnalDetailByJurnalId($jurnal_id)
    {
        return DB::table('jurnal_detail')
            ->where('jurnal_id', $jurnal_id)
            ->get();
    }

    
}
