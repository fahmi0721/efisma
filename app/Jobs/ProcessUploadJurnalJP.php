<?php

namespace App\Jobs;

use App\Models\ImportJurnal;
use App\Helpers\UploadJurnalJPValidasiService;
use App\Helpers\UploadJurnalJPService;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use Throwable;
use RuntimeException;

class ProcessUploadJurnalJP implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue,
        Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 40;

    public function __construct(
        public int $importId
    ) {}

    public function handle(): void
    {
        $import = ImportJurnal::findOrFail(
            $this->importId
        );

        if ($import->status !== 'pending') {
            return;
        }

        $import->update([
            'status' => 'processing',
            'started_at' => now(),
            'message' => 'Sedang memproses jurnal.',
        ]);

        $path = Storage::disk('local')->path(
            $import->path_file
        );

        if (!is_file($path)) {
            throw new RuntimeException(
                'File import tidak ditemukan.'
            );
        }

        // Job tidak otomatis memiliki sesi login HTTP.
        if (!Auth::onceUsingId($import->user_id)) {
            throw new RuntimeException(
                'User pengunggah tidak ditemukan.'
            );
        }

        // Membuat request kompatibel dengan Service lama.
        $request = Request::create(
            '/internal/import-jurnal-jp',
            'POST',
            [
                'jenis_upload' => $import->jenis_upload,
                'entitas_id' => $import->entitas_id,
            ]
        );

        $uploadedFile = new UploadedFile(
            $path,
            $import->nama_file,
            null,
            null,
            true
        );

        $request->files->set('file', $uploadedFile);

        Log::info('IMPORT JP START', [
            'import_id' => $import->id,
        ]);

        UploadJurnalJPValidasiService::validasi(
            $request
        );

        UploadJurnalJPService::uploads(
            $request
        );

        $import->update([
            'status' => 'completed',
            'message' => 'Import jurnal berhasil.',
            'finished_at' => now(),
        ]);

        Log::info('IMPORT JP COMPLETED', [
            'import_id' => $import->id,
        ]);
    }

    public function failed(Throwable $e): void
    {
        ImportJurnal::where('id', $this->importId)
            ->update([
                'status' => 'failed',
                'message' => mb_substr(
                    $e->getMessage(),
                    0,
                    5000
                ),
                'finished_at' => now(),
            ]);

        Log::error('IMPORT JP FAILED', [
            'import_id' => $this->importId,
            'message' => $e->getMessage(),
        ]);
    }
}