<?php
namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonitoringPiutangExport implements 
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithStyles,
    ShouldAutoSize
{
    protected Collection $data;

    protected int $nomor = 0;

    public function __construct($data)
    {
        $this->data = $data instanceof Collection
            ? $data
            : collect($data);
    }

     /**
     * Method wajib untuk FromCollection.
     */
    public function collection(): Collection
    {
        return $this->data;
    }

   public function headings(): array
    {
        return [
            'No',
            'Invoice',
            'Entitas',
            'Partner',
            'Cabang',
            'Kode Jurnal Piutang',
            'Tanggal Piutang',
            'Jumlah Piutang',
            'Kode Jurnal Pelunasan',
            'Jumlah Pelunasan',
            'Tanggal Pelunasan',
        ];
    }

    public function map($row): array
    {
        $this->nomor++;

        return [
            $this->nomor,
            $row->invoice ?? '-',
            $row->entitas ?? '-',
            $row->partner ?? '-',
            $row->cabang ?? '-',
            $row->kode_jurnal_piutang ?? '-',
            $row->tanggal_piutang ?? '-',
            (float) ($row->jumlah_piutang ?? 0),
            $row->kode_jurnal_pelunasan ?? '-',
            (float) ($row->jumlah_pelunasan ?? 0),
            $row->tanggal_pelunasan ?? '-',
        ];
    }

     public function title(): string
    {
        return 'Monitoring Piutang';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],

            'H:I' => [
                'numberFormat' => [
                    'formatCode' => '#,##0.00',
                ],
            ],
        ];
    }
}

