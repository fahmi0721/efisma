<?php

namespace App\Exports;

use App\Exports\KasKeluar\JurnalHeaderSheet;
use App\Exports\KasKeluar\JurnalDetailSheet;
use App\Exports\KasKeluar\MasterAkunSheet;
use App\Exports\KasKeluar\MasterCabangSheet;
use App\Exports\KasKeluar\MasterPartnerSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TemplateJurnalKasKeluar implements WithMultipleSheets
{
    protected $id_entitas;

    public function __construct($id_entitas)
    {
        $this->id_entitas = $id_entitas;
    }

   public function sheets(): array
    {
        return [
            new JurnalHeaderSheet(),
            new JurnalDetailSheet(),
            new MasterAkunSheet(),
            new MasterCabangSheet(),
            new MasterPartnerSheet($this->id_entitas),
        ];
    }
}
