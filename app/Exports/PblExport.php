<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class PblExport implements FromView
{
    protected $entitas_id;
    protected $periode_awal;
    protected $periode_akhir;
    protected $cabang_id;

    public function __construct($entitas_id = null, $periode_awal = null,$periode_akhir = null,$cabang_id = null)
    {
        $this->entitas_id = $entitas_id;
        $this->periode_awal = $periode_awal;
        $this->periode_akhir = $periode_akhir;
        $this->cabang_id = $cabang_id;
    }

    public function view(): View
    {
        // $periode_awal = $this->periode ? $this->periode . '-01' : date('Y-m-01');
        // $periode_akhir = date('Y-m-t', strtotime($periode_awal));
        $periode = date("Y-m", strtotime($this->periode_awal));

        // 🔹 Ambil seluruh akun dari view hirarki
        $akun = DB::table('view_akun_hirarki')
            ->select(
                'id as akun_id',
                'no_akun',
                'nama as akun_nama',
                'tipe_akun',
                'kategori',
                'saldo_normal',
                'parent_id',
                'level',
                'sort_path'
            )
            ->whereIn('tipe_akun', ['pendapatan', 'beban'])
            ->get();

        // 🔹 Ambil saldo awal
        $saldoAwal = DB::table('m_saldo_awal')
            ->when($this->entitas_id, fn($q) => $q->where('entitas_id', $this->entitas_id))
            ->where('periode', '<=', $periode) // periode sebelumnya juga boleh
            ->select('akun_gl_id', DB::raw('SUM(saldo) as saldo'))
            ->groupBy('akun_gl_id')
            ->get()
            ->keyBy('akun_gl_id');

        // 🔹 Ambil saldo mutasi dari buku besar
        $saldoMutasi = DB::table('buku_besar as b')
            ->join('jurnal_header as j', 'j.id', '=', 'b.jurnal_id')
            ->join('m_akun_gl as a', 'a.id', '=', 'b.akun_id')
            ->where('j.status', 'posted')
            ->when($this->entitas_id, fn($q) => $q->where('j.entitas_id', $this->entitas_id))
            ->when($this->cabang_id, fn($q) => $q->where('j.cabang_id', $this->cabang_id))
            ->whereBetween('b.tanggal', [$this->periode_awal, $this->periode_akhir])
            ->select(
                'b.akun_id',
                DB::raw('SUM(b.debit) as total_debit'),
                DB::raw('SUM(b.kredit) as total_kredit'),
                DB::raw("
                    SUM(
                        CASE 
                            WHEN a.saldo_normal = 'debet' THEN (b.debit - b.kredit)
                            WHEN a.saldo_normal = 'kredit' THEN (b.kredit - b.debit)
                            ELSE 0
                        END
                    ) as mutasi
                ")
            )
            ->groupBy('b.akun_id')
            ->get()
            ->keyBy('akun_id');

        // 🔹 Gabungkan semuanya
        $data = $akun->map(function ($row) use ($saldoAwal, $saldoMutasi, $akun) {
            // Ambil semua anak (termasuk dirinya sendiri)
            $children = $akun->filter(fn($a) => str_starts_with($a->sort_path, $row->sort_path));

            // Hitung total mutasi (termasuk akun sendiri + anak-anak)
            $row->total_debit = $children->sum(fn($c) => $saldoMutasi[$c->akun_id]->total_debit ?? 0);
            $row->total_kredit = $children->sum(fn($c) => $saldoMutasi[$c->akun_id]->total_kredit ?? 0);
            $row->mutasi = $children->sum(fn($c) => $saldoMutasi[$c->akun_id]->mutasi ?? 0);
            $row->saldo_awal = $children->sum(fn($c) => $saldoAwal[$c->akun_id]->saldo ?? 0);

            // 🔹 saldo akhir = saldo awal + mutasi
            $row->saldo_akhir = ($row->saldo_awal ?? 0) + ($row->mutasi ?? 0);
            
            // // 🔹 Hanya tampilkan akun yang punya nilai (agar tidak tampil akun nol semua)
            // if (
            //     ($row->saldo_awal != 0) ||
            //     ($row->total_debit != 0) ||
            //     ($row->total_kredit != 0) ||
            //     ($row->saldo_akhir != 0)
            // ) {
            //     return $row;
            // }

            return $row;

        })->filter(); // buang null
        // 🔹 Hitung total pendapatan & beban (hanya akun yang ada transaksi/mutasi)
        $total_pendapatan = $data
            ->where('tipe_akun', 'pendapatan')
            ->filter(fn($a) => isset($saldoMutasi[$a->akun_id])) // ← hanya yang punya transaksi
            ->sum('saldo_akhir');

        $total_beban = $data
            ->where('tipe_akun', 'beban')
            ->filter(fn($a) => isset($saldoMutasi[$a->akun_id])) // ← hanya yang punya transaksi
            ->sum('saldo_akhir');

        $laba_bersih = $total_pendapatan - $total_beban;
        return view('exports.laba_rugi', [
            'data' => $data,
            'total_pendapatan' => $total_pendapatan,
            'total_beban' => $total_beban,
            'laba_bersih' => $laba_bersih,
            'periode' => Carbon::parse($this->periode_awal)->translatedFormat('F Y')." s.d ".Carbon::parse($this->periode_akhir)->translatedFormat('F Y')
        ]);
    }

}
