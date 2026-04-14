<?php

namespace App\Helpers;

use App\Repositoris\GLRepositori;
use Exception;

class GlService
{
    /**
     * Saat POSTING JN → Insert pelunasan uang muka
     */
    public static function getGlTransaksi($search = null)
    {
        $data = GLRepositori::getGlTransaksi($search);
        if(count($data) > 0){
            return [
                "status" => true,
                "data" => $data
            ];
        }else{
            return [
                "status" => false,
                "data" => null
            ];
        }
    }

    
}
