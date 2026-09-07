<?php

namespace App\Exports\SheetJPAcs;

use App\Models\MCabang;
use App\Models\MPartner;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class JurnalHeaderSheet implements FromArray, WithHeadings, WithTitle, WithEvents, ShouldAutoSize
{
    public function title(): string
    {
        return 'jurnal_header';
    }

    public function headings(): array
    {
        return [
            'Jurnal Header ID',
            'Tanggal Jurnal',
            'Cabang ID',
            'Cabang',
            'Partner ID',
            'Partner',
            'Keterangan',
        ];
    }

    public function array(): array
    {
        $cabang = MCabang::select("id","nama")->first();
        $partner = MPartner::query()
            ->select(
                'id',
                'nama',
            )
            ->where(function ($query) {
                $query->where('is_vendor', 'active')
                    ->orWhere('is_customer', 'active');
            })
            ->first();
        return [
            [
                '1',
                '2026-06-01',
                $cabang->id,
                $cabang->nama,
                $partner->id,
                $partner->nama,
                'tes',
                'Contoh Pengisian',
            ],
        ];
    }

    

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                /**
                 * Warna kuning untuk baris 2 sampai 3
                 */
                $sheet->getStyle('A2:' . $highestColumn . '2')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'FFF2CC',
                        ],
                    ],
                ]);

                /**
                 * Formula otomatis Cabang:
                 * Jika kolom C / Cabang ID diisi,
                 * maka kolom D / Cabang otomatis terisi dari sheet Master Cabang.
                 */
                for ($row = 3; $row <= 201; $row++) {
                    $sheet->setCellValue(
                        'D' . $row,
                        '=IFERROR(VLOOKUP(C' . $row . ',\'master_cabang\'!$A:$B,2,FALSE),"")'
                    );
                }

                /**
                 * Formula otomatis Partner:
                 * Jika kolom E / Partner ID diisi,
                 * maka kolom F / Partner otomatis terisi dari sheet Master Partner.
                 */
                for ($row = 3; $row <= 201; $row++) {
                    $sheet->setCellValue(
                        'F' . $row,
                        '=IFERROR(VLOOKUP(E' . $row . ',\'master_partner\'!$A:$B,2,FALSE),"")'
                    );
                }

                /**
                 * Heading rata tengah
                 */
                $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                /**
                 * Unlock semua cell dulu agar user bisa input
                 */
                $sheet->getStyle('A1:' . $highestColumn . '201')
                    ->getProtection()
                    ->setLocked(Protection::PROTECTION_UNPROTECTED);

                /**
                 * Lock heading
                 */
                $sheet->getStyle('A1:' . $highestColumn . '1')
                    ->getProtection()
                    ->setLocked(Protection::PROTECTION_PROTECTED);

                /**
                 * Lock kolom otomatis
                 * D = Cabang otomatis
                 * F = Partner otomatis
                 */
                $sheet->getStyle('D2:D201')
                    ->getProtection()
                    ->setLocked(Protection::PROTECTION_PROTECTED);

                $sheet->getStyle('F2:F201')
                    ->getProtection()
                    ->setLocked(Protection::PROTECTION_PROTECTED);

                /**
                 * Aktifkan proteksi sheet
                 */
                $sheet->getProtection()->setSheet(true);

                /**
                 * Optional password
                 */
                $sheet->getProtection()->setPassword('123456');
                /**
                 * Background kolom otomatis seperti disabled
                 */
                $sheet->getStyle('D2:D201')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'E7E6E6', // abu-abu soft
                        ],
                    ],
                    'font' => [
                        'color' => [
                            'rgb' => '666666',
                        ],
                    ],
                ]);

                $sheet->getStyle('F2:F201')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'E7E6E6',
                        ],
                    ],
                    'font' => [
                        'color' => [
                            'rgb' => '666666',
                        ],
                    ],
                ]);

                /**
                 * Heading kolom wajib warna hijau
                 * A1, B1, E1, F1, G1
                 */
                $wajibCells = ['A1', 'B1', 'E1', 'F1', 'G1'];

                foreach ($wajibCells as $cell) {
                    $sheet->getStyle($cell)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => [
                                'rgb' => '92D050', // hijau
                            ],
                        ],
                        'font' => [
                            'bold' => true,
                            'color' => [
                                'rgb' => '000000',
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                }
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