<?php
namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use App\Helpers\SaldoAwalGeneratorService;
use App\Helpers\Akuntansi\SaldoBulananService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SaldoAwalGenerateController extends Controller
{
    public function index(Request $request)
    {
        $entitas = DB::table('m_entitas')
            ->select('id', 'nama')
            ->orderBy('nama')
            ->get();

        $akuns = DB::table('view_akun_transaksi_only')
            ->select('id', 'no_akun', 'nama')
            ->orderBy('no_akun')
            ->get();

        return view('page.saldo_generate.index', compact(
            'entitas',
            'akuns'
        ));
    }

    public function generate(
        Request $request,
        SaldoBulananService $service
    ) {
        $data = $request->validate([
            'periode' => [
                'required',
                'date_format:Y-m',
            ],
            'entitas_id' => [
                'required',
                'integer',
                'exists:m_entitas,id',
            ],
            'akun_gl_id' => [
                'required',
                'integer',
                'exists:m_akun_gl,id',
            ],
        ]);

        $hasil = $service->generate(
            (int) $data['entitas_id'],
            (int) $data['akun_gl_id'],
            $data['periode']
        );

        return response()->json($hasil);
    }

    public function preview(
        Request $request,
        SaldoAwalGeneratorService $service
    ) {
        $data = $request->validate([
            'periode' => [
                'required',
                'date_format:Y-m',
            ],
            'entitas_id' => [
                'required',
                'integer',
                Rule::exists('m_entitas', 'id'),
            ],
            'akun_gl_id' => [
                'required',
                'integer',
                Rule::exists('m_akun_gl', 'id'),
            ],
        ]);

        $hasil = $service->preview(
            (int) $data['entitas_id'],
            (int) $data['akun_gl_id'],
            $data['periode']
        );

        // Ambil seluruh partner ID dari hasil preview
        $partnerIds = collect($hasil['rows'])
            ->pluck('partner_id')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->unique()
            ->values();

        // Ambil master nama partner sekaligus
        $partners = DB::table('m_partner')
            ->whereIn('id', $partnerIds)
            ->pluck('nama', 'id');
        
        // Tambahkan nama partner ke setiap baris
        $hasil['rows'] = collect($hasil['rows'])
            ->map(function ($row) use ($partners) {
                $row['partner_nama'] = $partners->get(
                    $row['partner_id'] ?? null,
                    '-'
                );

                return $row;
            })
            ->all();
        $hasil['entitas_nama'] = DB::table('m_entitas')
            ->where('id', $request->entitas_id)
            ->value('nama') ?? '-';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Preview saldo berhasil dihitung.',
                'html' => view(
                    'page.saldo_generate.preview',
                    compact('hasil')
                    
                )->render()
            ]);
        }

       
    }
}
