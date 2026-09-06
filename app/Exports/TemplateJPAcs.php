<?php

namespace App\Exports;

use App\Exports\SheetJPAcs\JurnalHeaderSheet;
use App\Exports\SheetJPAcs\JurnalDetailSheet;
use App\Exports\SheetJPAcs\MasterAkunSheet;
use App\Exports\SheetJPAcs\MasterCabangSheet;
use App\Exports\SheetJPAcs\MasterPartnerSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TemplateJPAcs implements WithMultipleSheets
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
