<?php
namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UangMukaExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithColumnFormatting
{
    protected $entitasId;
    protected $cabangId;
    protected $entitasScope;

    public function __construct(
        $entitasId = null,
        $cabangId = null,
        $entitasScope = null
    ) {
        $this->entitasId = $entitasId;
        $this->cabangId = $cabangId;
        $this->entitasScope = $entitasScope;
    }

    
    public function collection()
    {
        $query = DB::table('view_uang_muka_per_akun');

        if (!empty($this->entitasId)) {
            $query->where('entitas_id', $this->entitasId);
        }

        if (!empty($this->cabangId)) {
            $query->where('cabang_id', $this->cabangId);
        }

        if (!empty($this->entitasScope)) {
            $query->where('entitas_id', $this->entitasScope);
        }

        $data = $query->get();

        $rows = collect();
        $no = 1;

        foreach ($data as $row) {

            $rows->push([
                $no++,
                $row->kode_jurnal,
                $row->entitas_nama,
                $row->partner_nama,
                $row->tanggal
                    ? \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y')
                    : '',
                (float) $row->nominal,
                (float) $row->terpakai,
                (float) $row->sisa,
                (int) $row->umur,
                strtoupper($row->status),
            ]);
        }

        // GRAND TOTAL
        $rows->push([
            '',
            '',
            '',
            '',
            'GRAND TOTAL',
            (float) $data->sum('nominal'),
            (float) $data->sum('terpakai'),
            (float) $data->sum('sisa'),
            '',
            '',
        ]);

        return $rows;
    }


    public function headings(): array
    {
        return [
            'No',
            'Kode',
            'Entitas',
            'Partner (Vendor/Pegawai)',
            'Tanggal',
            'Nominal',
            'Terpakai/Dikembalikan',
            'Sisa',
            'Umur',
            'Status',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => '#,##0',
            'G' => '#,##0',
            'H' => '#,##0',
        ];
    }

    
    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // HEADER
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '1F4E78'],
            ],
            'alignment' => [
                'vertical' => 'center',
                'wrapText' => true,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(35);

        // GRAND TOTAL
        $sheet->getStyle("A{$lastRow}:K{$lastRow}")
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'D9EAD3'],
                ],
            ]);

        // BORDER SELURUH TABEL
        $sheet->getStyle("A1:J{$lastRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
            );

        // FREEZE HEADER
        $sheet->freezePane('A2');

        // FILTER HEADER
        $sheet->setAutoFilter("A1:J1");

        return [];
    }

}
