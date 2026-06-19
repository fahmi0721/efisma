<?php

namespace App\Exports\Sheet;

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

class JurnalDetailSheet implements FromArray, WithHeadings, WithTitle, WithEvents, ShouldAutoSize
{
    public function title(): string
    {
        return 'jurnal_detail';
    }

    public function headings(): array
    {
        return [
            'Jurnal Header ID',
            'Akun ID',
            'Akun',
            'Deskripsi',
            'Debit',
            'Kredit',
        ];
    }

    public function array(): array
    {
        return [
            [
                '1',
                '156',
                '1010301 - Bank Mandiri Rupiah MBA',
                'Pembayaran A',
                '0',
                '100000',
                'Contoh Pengisian',
            ],
            [
                '1',
                '21',
                '10301 - Piutang Usaha Swasta',
                'Pembayaran A',
                '100000',
                '0',
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
                $sheet->getStyle('A2:' . $highestColumn . '3')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'FFF2CC',
                        ],
                    ],
                ]);

                /**
                 * Heading kolom wajib warna hijau
                 * A1, B1, E1, F1, G1
                 */
                $wajibCells = ['A1', 'B1', 'C1', 'D1', 'E1', 'F1'];

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
                 * Formula otomatis Akun:
                 * Jika kolom B / Akun ID diisi,
                 * maka kolom C / Akun otomatis terisi dari sheet Master Akun.
                 */
                for ($row = 4; $row <= 100; $row++) {
                    $sheet->setCellValue(
                        'C' . $row,
                        '=IFERROR(VLOOKUP(B' . $row . ',\'master_akun\'!$A:$B,2,FALSE),"")'
                    );
                }

                 /**
                 * Unlock semua cell dulu agar user bisa input
                 */
                $sheet->getStyle('A1:' . $highestColumn . '100')
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
                $sheet->getStyle('C2:C100')
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
                $sheet->getStyle('C2:C100')->applyFromArray([
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