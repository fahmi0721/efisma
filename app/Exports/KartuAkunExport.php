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
    protected $akun_id;
    protected $entitas_id;
    protected $periode;
    protected $tgl_awal;
    protected $tgl_akhir;

    public function __construct($akun_id, $entitas_id, $tanggal_awal,$tanggal_akhir)
    {
        $this->akun_id    = $akun_id;
        $this->entitas_id = $entitas_id;
        $this->tgl_awal = $tanggal_awal;
        $this->tgl_akhir = $tanggal_akhir;
        $this->periode    = date('Y-m', strtotime($tanggal_awal));
    }

    public function collection()
    {
        $tglAwal  = $this->tgl_awal;
        $tglAkhir = $this->tgl_akhir;

        // 🔹 Saldo Awal
        $saldoAwal = DB::table('m_saldo_awal as s')
            ->join('m_akun_gl as a', 'a.id', '=', 's.akun_gl_id')
            ->where('s.akun_gl_id', $this->akun_id)
            ->where('s.entitas_id', $this->entitas_id)
            ->where('s.periode', $this->periode)
            ->select('s.saldo', 'a.saldo_normal', 'a.no_akun', 'a.nama')
            ->first();

        if (!$saldoAwal) {
            $saldo       = 0;
            $saldoNormal = "debet";
        }else{
            $saldo       = (float) $saldoAwal->saldo;
            $saldoNormal = $saldoAwal->saldo_normal;
        }

        

        $rows = collect();

        // Baris saldo awal
        $rows->push([
            'tanggal'     => date('d-m-Y', strtotime($tglAwal)),
            'kode_jurnal' => 'SA',
            'deskripsi'   => 'Saldo Awal',
            'debet'       => '',
            'kredit'      => '',
            'saldo'       => $saldo
        ]);

        // 🔹 Mutasi
        $mutasi = DB::table('buku_besar')
            ->where('akun_id', $this->akun_id)
            ->where('entitas_id', $this->entitas_id)
            ->whereBetween('tanggal', [$tglAwal, $tglAkhir])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        foreach ($mutasi as $m) {
            if ($saldoNormal === 'debet') {
                $saldo += ($m->debit - $m->kredit);
            } else {
                $saldo += ($m->kredit - $m->debit);
            }

            $rows->push([
                'tanggal'     => date('d-m-Y', strtotime($m->tanggal)),
                'kode_jurnal' => $m->kode_jurnal,
                'deskripsi'   => $m->keterangan,
                'debet'       => $m->debit,
                'kredit'      => $m->kredit,
                'saldo'       => $saldo
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Kode Jurnal',
            'Deskripsi',
            'Debet',
            'Kredit',
            'Saldo'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header bold
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        // Format rupiah
        $sheet->getStyle('D:F')->getNumberFormat()
            ->setFormatCode('#,##0');

        return [];
    }
}
