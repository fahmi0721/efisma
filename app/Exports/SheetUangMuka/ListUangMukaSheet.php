<?php

namespace App\Exports\SheetUangMuka;

use App\Models\MDaftarUangMuka;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;


class ListUangMukaSheet implements FromCollection, WithHeadings, WithTitle, WithEvents, ShouldAutoSize
{
    protected $id_entitas;

    public function __construct($id_entitas)
    {
        $this->id_entitas = $id_entitas;
    }
    public function title(): string
    {
        return 'data_uang_muka';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Kode',
            'Uraian',
            'Partner',
            'Nominal',
            'Terpakai',
            'Sisa',

        ];
    }

    public function collection()
    {
        return MDaftarUangMuka::query()
            ->select(
                'jurnal_id',
                'kode_jurnal',
                DB::raw("CONCAT('[',kode_jurnal, ']', keterangan) AS uraian"),
                'partner_nama',
                'nominal',
                'terpakai',
                'sisa',
            )
            ->where("entitas_id",$this->id_entitas)
            ->where("status","open")
            ->get();
    }
    

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();


                /**
                 * Heading rata tengah
                 */
                $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'font' => [
                        'bold' => true,
                    ],
                ]);

                /**
                 * Freeze heading
                 */
                $sheet->freezePane('A2');

                /**
                 * Tinggi baris
                 */
                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}