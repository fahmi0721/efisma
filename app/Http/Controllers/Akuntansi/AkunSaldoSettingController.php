<?php
namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AkunSaldoSettingController extends Controller
{
    public function index(Request $request)
    {
        $entitas = DB::table('m_entitas')
            ->select('id', 'nama')
            ->orderBy('nama')
            ->get();

        $entitasId = $request->filled('entitas_id')
            ? (int) $request->entitas_id
            : null;

        $search = trim((string) $request->input('search', ''));

        $akuns = null;

        if ($entitasId) {
            abort_unless(
                DB::table('m_entitas')
                    ->where('id', $entitasId)
                    ->exists(),
                404,
                'Entitas tidak ditemukan.'
            );

            $akuns = DB::table('view_akun_transaksi_only as v')
                ->join('m_akun_gl as a', 'a.id', '=', 'v.id')
                ->leftJoin('m_akun_saldo_setting as s', function ($join) use ($entitasId) {
                    $join->on('s.akun_gl_id', '=', 'v.id')
                         ->where('s.entitas_id', '=', $entitasId);
                })
                ->select(
                    'v.id',
                    'v.no_akun',
                    'v.nama',
                    'a.tipe_akun',
                    'a.saldo_normal',
                    's.metode_saldo',
                    's.perlakuan_tahun_buku',
                    's.is_active'
                )
                ->selectRaw(
                    'EXISTS (
                        SELECT 1
                        FROM m_saldo_awal sa
                        WHERE sa.akun_gl_id = v.id
                          AND sa.entitas_id = ?
                    ) AS memiliki_saldo',
                    [$entitasId]
                )
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('v.no_akun', 'like', "%{$search}%")
                          ->orWhere('v.nama', 'like', "%{$search}%");
                    });
                })
                ->orderBy('v.no_akun')
                ->paginate(25)
                ->withQueryString();
        }

        return view('page.saldo_setting.index', compact(
            'entitas',
            'entitasId',
            'search',
            'akuns'
        ));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
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
            'metode_saldo' => [
                'required',
                Rule::in(['akun', 'partner']),
            ],
            'perlakuan_tahun_buku' => [
                'required',
                Rule::in(['carry_forward', 'reset_tahunan']),
            ],
        ]);

        $akunId = (int) $data['akun_gl_id'];
        $entitasId = (int) $data['entitas_id'];

        if (! DB::table('view_akun_transaksi_only')
            ->where('id', $akunId)
            ->exists()) {
            throw ValidationException::withMessages([
                'akun_gl_id' => 'Akun harus merupakan akun transaksi.',
            ]);
        }

        DB::transaction(function () use ($data, $akunId, $entitasId) {

            $existing = DB::table('m_akun_saldo_setting')
                ->where('entitas_id', $entitasId)
                ->where('akun_gl_id', $akunId)
                ->lockForUpdate()
                ->first();

            $memilikiSaldo = DB::table('m_saldo_awal')
                ->where('entitas_id', $entitasId)
                ->where('akun_gl_id', $akunId)
                ->exists();

            if ($memilikiSaldo) {
                $berubah = ! $existing
                    || $existing->metode_saldo !== $data['metode_saldo']
                    || $existing->perlakuan_tahun_buku !== $data['perlakuan_tahun_buku'];

                if ($berubah) {
                    throw ValidationException::withMessages([
                        'akun_gl_id' =>
                            'Akun sudah memiliki saldo awal. '
                            . 'Perubahan konfigurasi membutuhkan '
                            . 'rekonsiliasi atau migrasi saldo.',
                    ]);
                }
            }

            $values = [
                'metode_saldo' => $data['metode_saldo'],
                'perlakuan_tahun_buku' => $data['perlakuan_tahun_buku'],
                'is_active' => true,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('m_akun_saldo_setting')
                    ->where('id', $existing->id)
                    ->update($values);
            } else {
                DB::table('m_akun_saldo_setting')->insert(
                    array_merge($values, [
                        'entitas_id' => $entitasId,
                        'akun_gl_id' => $akunId,
                        'created_at' => now(),
                    ])
                );
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengaturan saldo berhasil disimpan.'
            ]);
        }


        return back()->with(
            'success',
            'Pengaturan saldo berhasil disimpan.'
        );
    }


    public function bulkIndex(Request $request)
    {
        $entitas = DB::table('m_entitas')
            ->select('id', 'nama')
            ->orderBy('nama')
            ->get();

        $entitasId = $request->filled('entitas_id')
            ? (int) $request->entitas_id
            : null;

        $akuns = collect();

        if ($entitasId) {
            abort_unless(
                DB::table('m_entitas')
                    ->where('id', $entitasId)
                    ->exists(),
                404
            );

            $akuns = DB::table('view_akun_transaksi_only as v')
                ->join('m_akun_gl as a', 'a.id', '=', 'v.id')
                ->leftJoin('m_akun_saldo_setting as s', function ($join) use ($entitasId) {
                    $join->on('s.akun_gl_id', '=', 'v.id')
                        ->where('s.entitas_id', '=', $entitasId);
                })
                ->select(
                    'v.id',
                    'v.no_akun',
                    'v.nama',
                    'a.saldo_normal',
                    'a.tipe_akun',
                    's.metode_saldo',
                    's.perlakuan_tahun_buku'
                )
                ->selectRaw(
                    'EXISTS (
                        SELECT 1 FROM m_saldo_awal sa
                        WHERE sa.entitas_id = ?
                        AND sa.akun_gl_id = v.id
                    ) AS memiliki_saldo',
                    [$entitasId]
                )
                ->orderBy('v.no_akun')
                ->get();
        }

        return view('page.saldo_setting.bulk', compact(
            'entitas',
            'entitasId',
            'akuns'
        ));
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'entitas_id' => [
                'required', 'integer',
                'exists:m_entitas,id',
            ],
            'akun_ids' => [
                'required', 'array', 'min:1',
            ],
            'akun_ids.*' => [
                'required', 'integer', 'distinct',
            ],
            'metode_saldo' => [
                'required',
                Rule::in(['akun', 'partner']),
            ],
            'perlakuan_tahun_buku' => [
                'required',
                Rule::in(['carry_forward', 'reset_tahunan']),
            ],
        ]);

        $entitasId = (int) $data['entitas_id'];
        $akunIds = array_map('intval', $data['akun_ids']);

        $hasil = DB::transaction(function () use (
            $entitasId, $akunIds, $data
        ) {
            // Serialisasi proses konfigurasi pada entitas ini.
            $entitasLock = DB::table('m_entitas')
                ->where('id', $entitasId)
                ->lockForUpdate()
                ->first();

            if (! $entitasLock) {
                throw ValidationException::withMessages([
                    'entitas_id' => 'Entitas tidak ditemukan.',
                ]);
            }

            $validIds = DB::table('view_akun_transaksi_only')
                ->whereIn('id', $akunIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (count($validIds) !== count($akunIds)) {
                throw ValidationException::withMessages([
                    'akun_ids' => 'Ada akun yang bukan akun transaksi.',
                ]);
            }

            $sudahSetting = DB::table('m_akun_saldo_setting')
                ->where('entitas_id', $entitasId)
                ->whereIn('akun_gl_id', $akunIds)
                ->pluck('akun_gl_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $sudahSaldo = DB::table('m_saldo_awal')
                ->where('entitas_id', $entitasId)
                ->whereIn('akun_gl_id', $akunIds)
                ->distinct()
                ->pluck('akun_gl_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $terkunci = array_unique(array_merge(
                $sudahSetting,
                $sudahSaldo
            ));

            $bolehInsert = array_values(
                array_diff($akunIds, $terkunci)
            );

            $now = now();

            $rows = array_map(function ($akunId) use (
                $entitasId, $data, $now
            ) {
                return [
                    'entitas_id' => $entitasId,
                    'akun_gl_id' => $akunId,
                    'metode_saldo' => $data['metode_saldo'],
                    'perlakuan_tahun_buku' =>
                        $data['perlakuan_tahun_buku'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $bolehInsert);

            if (! empty($rows)) {
                DB::table('m_akun_saldo_setting')
                    ->insert($rows);
            }

            return [
                'berhasil' => count($rows),
                'dilewati' => count($akunIds) - count($rows),
            ];
        }, 3);

        return redirect()
            ->route('akuntansi.saldo-setting-bulk.index', [
                'entitas_id' => $entitasId,
            ])
            ->with(
                'success',
                "Konfigurasi berhasil: {$hasil['berhasil']} akun. "
                . "Dilewati: {$hasil['dilewati']} akun "
                . "(sudah dikonfigurasi atau memiliki saldo existing)."
            );
    }

}
