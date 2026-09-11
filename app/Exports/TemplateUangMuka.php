<?php

namespace App\Exports;

use App\Exports\Sheet\JurnalHeaderSheet;
use App\Exports\SheetUangMuka\JurnalDetailSheet;
use App\Exports\Sheet\MasterAkunSheet;
use App\Exports\Sheet\MasterCabangSheet;
use App\Exports\Sheet\MasterPartnerSheet;
use App\Exports\SheetUangMuka\ListUangMukaSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TemplateUangMuka implements WithMultipleSheets
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
            new ListUangMukaSheet($this->id_entitas),
        ];
    }
}
