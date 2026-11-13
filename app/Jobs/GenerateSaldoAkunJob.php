<?php

namespace App\Jobs;

use App\Helpers\Akuntansi\SaldoBulananService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Support\Facades\Log;

class GenerateSaldoAkunJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $prosesId,
        public int $entitasId,
        public int $akunId,
        public string $periode
    ) {
        $this->onConnection('database');
        $this->onQueue('saldo-massal');
    }

    public function handle(
        SaldoBulananService $service
    ): void {

        if ($this->batch()?->cancelled()) {
            return;
        }

        $query = fn () => DB::table(
            'saldo_generate_massal_detail'
        )
            ->where('proses_id', $this->prosesId)
            ->where('akun_gl_id', $this->akunId);

        // Jika sudah berhasil dari eksekusi sebelumnya,
        // tidak perlu menjalankan ulang.
        $existing = $query()->first();

        if (!$existing) {
            throw new \RuntimeException(
                'Detail proses akun belum tersedia.'
            );
        }

        if (in_array(
            $existing->status,
            ['completed', 'skipped'],
            true
        )) {
            return;
        }

        $query()->update([
            'status' => 'processing',
            'started_at' => now(),
            'finished_at' => null,
            'message' => null,
            'updated_at' => now(),
        ]);

        try {
            $result = $service->generate(
                $this->entitasId,
                $this->akunId,
                $this->periode
            );

            $status = ($result['status'] ?? 'completed')
                === 'skipped'
                ? 'skipped'
                : 'completed';

            $query()->update([
                'status' => $status,
                'jumlah_baris' =>
                    (int) ($result['jumlah_baris'] ?? 0),
                'message' =>
                    $result['message'] ?? null,
                'finished_at' => now(),
                'updated_at' => now(),
            ]);

        } catch (Throwable $e) {

            // Lempar kembali supaya Laravel dapat
            // menjalankan mekanisme retry.
            $query()->update([
                'message' => mb_substr(
                    $e->getMessage(),
                    0,
                    2000
                ),
                'updated_at' => now(),
            ]);

            throw $e;
        }
    }

    // Dipanggil jika Job gagal permanen
    // setelah seluruh percobaan selesai.
    public function failed(?Throwable $exception): void
    {
        DB::table('saldo_generate_massal_detail')
            ->where('proses_id', $this->prosesId)
            ->where('akun_gl_id', $this->akunId)
            ->update([
                'status' => 'failed',
                'message' => mb_substr(
                    $exception?->getMessage()
                        ?? 'Job gagal diproses.',
                    0,
                    2000
                ),
                'finished_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
