<?php

namespace App\Exports;

use App\Exports\Sheet\JurnalHeaderPelunasnPiutangJkm;
use App\Exports\Sheet\JurnalDetailPelunasnPiutangJkm;
use App\Exports\Sheet\MasterAkunSheet;
use App\Exports\Sheet\MasterCabangSheet;
use App\Exports\Sheet\MasterPartnerSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TemplateJkmPelunasanPiutang implements WithMultipleSheets
{
    protected $id_entitas;

    public function __construct($id_entitas)
    {
        $this->id_entitas = $id_entitas;
    }

   public function sheets(): array
    {
        return [
            new JurnalHeaderPelunasnPiutangJkm(),
            new JurnalHeaderPelunasnPiutangJkm(),
            new MasterAkunSheet(),
            new MasterCabangSheet(),
            new MasterPartnerSheet($this->id_entitas),
        ];
    }
}
