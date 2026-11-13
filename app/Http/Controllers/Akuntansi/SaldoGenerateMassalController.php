<?php

namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateSaldoAkunJob;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class SaldoGenerateMassalController extends Controller
{
    public function index()
    {
        $entitas = DB::table('m_entitas')
            ->orderBy('nama')
            ->get();

        $history = DB::table('saldo_generate_massal as s')
            ->join('m_entitas as e', 'e.id', '=', 's.entitas_id')
            ->select('s.*', 'e.nama as entitas_nama')
            ->orderByDesc('s.id')
            ->limit(20)
            ->get();

        return view(
            'page.saldo-generate-massal.index',
            compact('entitas', 'history')
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'entitas_id' => [
                'required',
                'integer',
                'exists:m_entitas,id'
            ],
        ]);

        $entitasId = (int) $data['entitas_id'];
        $periode = $data['periode'];

        // Otorisasi wajib diterapkan di route/middleware.
        // Jangan hanya mengandalkan @canAccess di Blade.

        /*
         * Mengunci baris entitas ketika membuat proses
         * agar dua permintaan untuk entitas dan periode
         * yang sama tidak didaftarkan bersamaan.
         */
        $prosesId = DB::transaction(function () use (
            $entitasId,
            $periode,
            $request
        ) {
            DB::table('m_entitas')
                ->where('id', $entitasId)
                ->lockForUpdate()
                ->first();

            $aktif = DB::table('saldo_generate_massal')
                ->where('entitas_id', $entitasId)
                ->where('periode', $periode)
                ->whereIn('status', ['pending', 'processing'])
                ->exists();

            if ($aktif) {
                throw ValidationException::withMessages([
                    'periode' => 'Generate massal untuk entitas '
                        . 'dan periode ini sedang berjalan.',
                ]);
            }

            return DB::table('saldo_generate_massal')
                ->insertGetId([
                    'entitas_id' => $entitasId,
                    'periode' => $periode,
                    'created_by' => $request->user()->id,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        try {
            /*
             * Hanya akun transaksi aktif dengan
             * pengaturan saldo aktif.
             *
             * Pada versi pertama, proses massal
             * dibatasi ke carry_forward.
             */
            $akunIds = DB::table('m_akun_saldo_setting as s')
                ->join(
                    'view_akun_transaksi_only as v',
                    'v.id',
                    '=',
                    's.akun_gl_id'
                )
                ->where('s.entitas_id', $entitasId)
                ->where('s.is_active', 1)
                ->where('s.perlakuan_tahun_buku', 'carry_forward')
                ->distinct()
                ->orderBy('s.akun_gl_id')
                ->pluck('s.akun_gl_id');

            if ($akunIds->isEmpty()) {
                throw new \RuntimeException(
                    'Tidak ada akun transaksi aktif '
                    . 'yang dapat digenerate.'
                );
            }

            $now = now();

            // Siapkan detail masing-masing akun
            $details = $akunIds->map(
                fn ($akunId) => [
                    'proses_id' => $prosesId,
                    'akun_gl_id' => (int) $akunId,
                    'status' => 'pending',
                    'jumlah_baris' => 0,
                    'message' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            )->all();

            // Insert detail secara batch
            foreach (array_chunk($details, 200) as $chunk) {
                DB::table('saldo_generate_massal_detail')
                    ->insert($chunk);
            }

            // Buat Job per akun
            $jobs = $akunIds->map(
                fn ($akunId) => new GenerateSaldoAkunJob(
                    $prosesId,
                    $entitasId,
                    (int) $akunId,
                    $periode
                )
            )->all();

            DB::table('saldo_generate_massal')
                ->where('id', $prosesId)
                ->update([
                    'total_akun' => count($jobs),
                    'updated_at' => now(),
                ]);

            $batch = Bus::batch($jobs)
                ->name(
                    "Saldo Massal {$entitasId} - {$periode}"
                )
                ->allowFailures()
                ->finally(function (Batch $batch) use ($prosesId) {
                    $rekap = DB::table('saldo_generate_massal_detail')
                        ->where('proses_id', $prosesId)
                        ->selectRaw('COUNT(*) AS total')
                        ->selectRaw(
                            "SUM(CASE WHEN status = 'completed'
                            THEN 1 ELSE 0 END) AS completed_count"
                        )
                        ->selectRaw(
                            "SUM(CASE WHEN status = 'skipped'
                            THEN 1 ELSE 0 END) AS skipped_count"
                        )
                        ->selectRaw(
                            "SUM(CASE WHEN status = 'failed'
                            THEN 1 ELSE 0 END) AS failed_count"
                        )
                        ->selectRaw(
                            "SUM(CASE WHEN status IN ('pending', 'processing')
                            THEN 1 ELSE 0 END) AS remaining_count"
                        )
                        ->first();

                    $failed = (int) $rekap->failed_count;
                    $remaining = (int) $rekap->remaining_count;

                    if ($remaining > 0) {
                        $status = 'incomplete';
                    } elseif ($failed > 0) {
                        $status = 'partial';
                    } else {
                        $status = 'completed';
                    }

                    DB::table('saldo_generate_massal')
                        ->where('id', $prosesId)
                        ->update([
                            'status' => $status,
                            'finished_at' => now(),
                            'updated_at' => now(),
                        ]);
                })
                ->onConnection('database')
                ->onQueue('saldo-massal')
                ->dispatch();

            DB::table('saldo_generate_massal')
                ->where('id', $prosesId)
                ->update([
                    'batch_id' => $batch->id,
                    'status' => 'processing',
                    'started_at' => now(),
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Generate massal masuk antrean.',
                'id' => $prosesId,
                'batch_id' => $batch->id,
                'total_akun' => count($jobs),
            ], 202);

        } catch (Throwable $e) {

            DB::table('saldo_generate_massal')
                ->where('id', $prosesId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'finished_at' => now(),
                    'updated_at' => now(),
                ]);

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat antrean generate massal.',
            ], 500);
        }
    }

    
    
    public function status(int $id)
    {
        $proses = DB::table('saldo_generate_massal')
            ->where('id', $id)
            ->first();

        abort_if(
            !$proses,
            404,
            'Data proses generate massal tidak ditemukan.'
        );

        // Pastikan user memiliki akses ke entitas ini.

        $batch = $proses->batch_id
            ? \Illuminate\Support\Facades\Bus::findBatch(
                $proses->batch_id
            )
            : null;

        $rekap = DB::table('saldo_generate_massal_detail')
            ->where('proses_id', $id)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw(
                "SUM(CASE WHEN status = 'completed'
                THEN 1 ELSE 0 END) AS completed"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'skipped'
                THEN 1 ELSE 0 END) AS skipped"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'failed'
                THEN 1 ELSE 0 END) AS failed"
            )
            ->selectRaw(
                "SUM(CASE WHEN status IN ('pending','processing')
                THEN 1 ELSE 0 END) AS remaining"
            )
            ->first();

        $total = (int) $rekap->total;
        $completed = (int) $rekap->completed;
        $skipped = (int) $rekap->skipped;
        $failed = (int) $rekap->failed;
        $remaining = (int) $rekap->remaining;

        $finished = $batch
            && $batch->finished()
            && $remaining === 0;

        $status = $proses->status;

        if ($finished) {
            $status = $failed > 0
                ? 'partial'
                : 'completed';
        }

        $progress = $total > 0
            ? round(
                (($completed + $skipped + $failed) / $total) * 100,
                2
            )
            : 0;

        return response()->json([
            'success' => true,
            'id' => $proses->id,
            'status' => $status,
            'total_akun' => $total,
            'completed' => $completed,
            'skipped' => $skipped,
            'failed' => $failed,
            'remaining' => $remaining,
            'progress' => $progress,
            'finished' => (bool) $finished,
        ]);
    }


    public function detail(int $id)
    {
        $proses = DB::table('saldo_generate_massal as s')
            ->leftJoin('m_entitas as e', 'e.id', '=', 's.entitas_id')
            ->where('s.id', $id)
            ->select('s.*', 'e.nama as entitas_nama')
            ->first();

        abort_if(
            !$proses,
            404,
            'Proses Generate Massal tidak ditemukan.'
        );

        // Wajib tambahkan pemeriksaan permission dan
        // akses user terhadap entitas dari $proses.

        $details = DB::table('saldo_generate_massal_detail as d')
            ->leftJoin('m_akun_gl as a', 'a.id', '=', 'd.akun_gl_id')
            ->where('d.proses_id', $id)
            ->select(
                'd.id',
                'd.akun_gl_id',
                'a.no_akun',
                'a.nama as nama_akun',
                'd.status',
                'd.jumlah_baris',
                'd.message',
                'd.started_at',
                'd.finished_at'
            )
            ->orderByRaw("
                CASE d.status
                    WHEN 'failed' THEN 1
                    WHEN 'processing' THEN 2
                    WHEN 'pending' THEN 3
                    WHEN 'skipped' THEN 4
                    WHEN 'completed' THEN 5
                    ELSE 6
                END
            ")
            ->orderBy('a.no_akun')
            ->get();

        return response()->json([
            'success' => true,
            'proses' => [
                'id' => $proses->id,
                'entitas' => $proses->entitas_nama,
                'periode' => $proses->periode,
                'status' => $proses->status,
                'total_akun' => $proses->total_akun,
            ],
            'details' => $details,
        ]);
    }



}
