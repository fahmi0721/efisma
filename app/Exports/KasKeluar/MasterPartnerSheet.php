<?php

namespace App\Exports\KasKeluar;

use App\Models\MPartner;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;


class MasterPartnerSheet implements FromCollection, WithHeadings, WithTitle, WithEvents, ShouldAutoSize
{
    protected $id_entitas;

    public function __construct($id_entitas)
    {
        $this->id_entitas = $id_entitas;
    }
    public function title(): string
    {
        return 'master_partner';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Partner',
            'Is Vendor',
            'Is Customer',
            'No Telepon',
            'Alamat',

        ];
    }

    public function collection()
    {
        return MPartner::query()
            ->select(
                'id',
                'nama',
                'is_vendor',
                'is_customer',
                'no_telpon',
                'alamat',
            )
            ->where("entitas_id",$this->id_entitas)
            ->where(function ($query) {
                $query->where('is_vendor', 'active')
                    ->orWhere('is_customer', 'active');
            })
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