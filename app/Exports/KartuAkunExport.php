<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KartuAkunExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $iData;

    public function __construct($iData)
    {
        $this->iData = $iData['data'];
    }

    public function collection()
    {
        $rows = collect();
        foreach ($this->iData as $m) {
           
            $rows->push([
                'tanggal'     => date('d-m-Y', strtotime($m['tanggal'])),
                'kode_jurnal' => $m['kode_jurnal'],
                'entitas'   => $m['entitas'],
                'akun_gl'   => $m['akun_gl'],
                'deskripsi'   => $m['keterangan'],
                'debet'       => $m['debit'],
                'kredit'      => $m['kredit'],
                'saldo'       => $m['saldo']
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Kode Jurnal',
            'Entitas',
            'Akun GL',
            'Deskripsi',
            'Debet',
            'Kredit',
            'Saldo'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header bold
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        // Format rupiah
        $sheet->getStyle('F:H')->getNumberFormat()
            ->setFormatCode('#,##0');

        return [];
    }
}
