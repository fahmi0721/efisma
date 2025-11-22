<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Facades\DB;

class BukuBesarExport implements FromView
{
    protected $entitas_id;
    protected $periode;

    public function __construct($entitas_id = null, $periode = null)
    {
        $this->entitas_id = $entitas_id;
        $this->periode = $periode;
    }

    public function view(): View
    {
        $tglAwal = $this->periode . '-01';
        $tglAkhir = date('Y-m-t', strtotime($this->periode));
       

        $data = DB::table('buku_besar as b')
            ->leftJoin('m_akun_gl as a', 'a.id', '=', 'b.akun_id')
            ->leftJoin('m_entitas as e', 'e.id', '=', 'b.entitas_id')
            ->leftJoin('m_partner as p', 'p.id', '=', 'b.partner_id')
            ->select(
                'b.kode_jurnal',
                DB::raw("CONCAT(a.no_akun,' - ',a.nama) AS akun_gl"),
                'e.nama AS entitas',
                'p.nama as partner',
                'b.keterangan',
                'b.tanggal',
                'b.debit',
                'b.kredit'
            )
            ->when($this->entitas_id, fn($q) => $q->where('b.entitas_id', $this->entitas_id))
            ->whereBetween('b.tanggal', [$tglAwal, $tglAkhir])
            ->orderBy('b.tanggal', 'asc')
            ->get();

        return view('exports.buku_besar', [
            'data' => $data,
            'periode' => $this->periode,
        ]);
      
    }

}
