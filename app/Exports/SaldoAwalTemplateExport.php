<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class SaldoAwalTemplateExport implements WithMultipleSheets, WithEvents
{
    protected $akunList;
    protected $entitasList;

    public function __construct()
    {
        // Ambil akun dari view_akun_transaksi_only
        $this->akunList = DB::table('view_akun_transaksi_only')
            ->orderBy('no_akun')
            ->get()
            ->map(fn($a) => "{$a->id} - {$a->no_akun} - {$a->nama}")
            ->toArray();

        // Ambil entitas dari m_entitas
        $this->entitasList = DB::table('m_entitas')
            ->orderBy('nama')
            ->get()
            ->map(fn($e) => "{$e->id} - {$e->nama}")
            ->toArray();
    }

    public function sheets(): array
    {
        return [
            

            // 🟢 SHEET 2: TEMPLATE SALDO
            new class($this->akunList, $this->entitasList) implements FromArray, WithTitle {
                private $akunList;
                private $entitasList;

                public function __construct($akunList, $entitasList)
                {
                    $this->akunList = $akunList;
                    $this->entitasList = $entitasList;
                }

                public function array(): array
                {
                    return [
                        ['akun_gl_id', 'periode', 'entitas_id', 'saldo'],
                        ['', '', '', ''],
                    ];
                }

                public function title(): string { return 'template saldo'; }
            },
            // 🟢 SHEET 1: PETUNJUK
            new class implements FromArray, WithTitle {
                public function array(): array
                {
                    return [
                        ['Panduan Pengisian Template Saldo Awal'],
                        ['---------------------------------------'],
                        ['Kolom', 'Keterangan', 'Contoh'],
                        ['akun_gl_id', 'Pilih dari dropdown daftar akun', '12 - 1101 - Kas'],
                        ['periode', 'Isi dengan format YYYY-MM (contoh: 2025-10)', '2025-10'],
                        ['entitas_id', 'Pilih dari dropdown daftar entitas', '3 - PT ABC'],
                        ['saldo', 'Masukkan angka saldo awal (boleh decimal)', '5000000.00'],
                        ['Catatan:', 'Pastikan format periode 7 karakter, gunakan tanda "-" di tengah.', ''],
                    ];
                }
                public function title(): string { return 'Petunjuk'; }
            },

            // 🟢 SHEET 3: DATA SOURCE (hidden)
            new class($this->akunList, $this->entitasList) implements FromArray, WithTitle {
                private $akunList;
                private $entitasList;

                public function __construct($akunList, $entitasList)
                {
                    $this->akunList = $akunList;
                    $this->entitasList = $entitasList;
                }

                public function array(): array
                {
                    $rows = [];
                    $rows[] = ['akun_gl', 'entitas'];
                    $max = max(count($this->akunList), count($this->entitasList));
                    for ($i = 0; $i < $max; $i++) {
                        $rows[] = [
                            $this->akunList[$i] ?? null,
                            $this->entitasList[$i] ?? null,
                        ];
                    }
                    return $rows;
                }

                public function title(): string { return 'data_source'; }
            },
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $spreadsheet = $event->getWriter()->getSpreadsheet();
                $template = $spreadsheet->getSheetByName('template saldo');
                $dataSource = $spreadsheet->getSheetByName('data_source');

                // Sembunyikan data_source
                $dataSource->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

                $akunEndRow = max(1, count($this->akunList)) + 1;
                $entitasEndRow = max(1, count($this->entitasList)) + 1;

                $akunRange = "'data_source'!\$A\$2:\$A\$" . $akunEndRow;
                $entitasRange = "'data_source'!\$B\$2:\$B\$" . $entitasEndRow;

                $highestRow = 1000; // batas template input

                for ($row = 2; $row <= $highestRow; $row++) {
                    // Dropdown akun_gl_id
                    $akunValidation = $template->getCell("A{$row}")->getDataValidation();
                    $akunValidation->setType(DataValidation::TYPE_LIST);
                    $akunValidation->setFormula1($akunRange);
                    $akunValidation->setShowDropDown(true);

                    // Input message (periode)
                    $periodeValidation = $template->getCell("B{$row}")->getDataValidation();
                    $periodeValidation->setType(DataValidation::TYPE_CUSTOM);
                    $periodeValidation->setFormula1('=AND(LEN(B'.$row.')=7, MID(B'.$row.',5,1)="-")');
                    $periodeValidation->setShowInputMessage(true);
                    $periodeValidation->setPromptTitle('Format Periode');
                    $periodeValidation->setPrompt('Gunakan format YYYY-MM, contoh: 2025-10');

                    // Dropdown entitas_id
                    $entitasValidation = $template->getCell("C{$row}")->getDataValidation();
                    $entitasValidation->setType(DataValidation::TYPE_LIST);
                    $entitasValidation->setFormula1($entitasRange);
                    $entitasValidation->setShowDropDown(true);
                }
            }
        ];
    }
}
