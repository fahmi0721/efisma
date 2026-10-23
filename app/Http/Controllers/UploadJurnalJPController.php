<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessUploadJurnalJP;
use App\Models\ImportJurnal;
use App\Helpers\UploadJurnalJPValidasiService;
use App\Helpers\UploadJurnalJPService;
use App\Helpers\TemplateJurnalJPService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use Exception;

class UploadJurnalJPController extends Controller
{
    public function index(Request $request)
    {
        return view("page.jurnal.upload_penyesuaian");
    }

    public function template(Request $request)
    {
        return TemplateJurnalJPService::get_template($request);
    }

    public function status($id)
    {
        $import = ImportJurnal::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $import->id,
                'import_status' => $import->status,
                'message' => $import->message,
                'finished_at' => $import->finished_at,
            ],
        ]);
    }

    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xls,xlsx|max:5120',
            'jenis_upload' => 'required|in:acs,uang_muka',
            'entitas_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'warning',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Ambil scope dari otorisasi server, bukan percaya
        // nilai entitas_scope yang dikirim browser.
        // Contoh ini perlu disesuaikan dengan middleware
        // / mekanisme scope entitas EFISMA.
        $entitasId = $request->entitas_scope
            ?: $request->entitas_id;

        $import = null;
        $path = null;

        try {
            $file = $request->file('file');

            $path = $file->storeAs(
                'imports/jurnal-jp',
                (string) Str::uuid() . '.' . $file->extension(),
                'local'
            );

            if (!$path) {
                throw new \RuntimeException(
                    'Gagal menyimpan file upload.'
                );
            }

            $import = ImportJurnal::create([
                'user_id' => Auth::id(),
                'jenis_upload' => $request->jenis_upload,
                'entitas_id' => $entitasId,
                'nama_file' => $file->getClientOriginalName(),
                'path_file' => $path,
                'status' => 'pending',
                'message' => 'Menunggu proses import.',
            ]);

            ProcessUploadJurnalJP::dispatch($import->id)
                ->onQueue('jurnal-import');

            return response()->json([
                'status' => 'success',
                'message' => 'File berhasil masuk antrean import.',
                'import_id' => $import->id,
                'import_status' => 'pending',
            ], 202);

        } catch (Throwable $e) {

            Log::error('Gagal antrekan import jurnal JP', [
                'error' => $e->getMessage(),
            ]);

            if ($import) {
                $import->update([
                    'status' => 'failed',
                    'message' => 'Gagal menambahkan pekerjaan ke antrean.',
                    'finished_at' => now(),
                ]);
            } elseif ($path) {
                Storage::disk('local')->delete($path);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat antrean import jurnal.',
            ], 500);
        }
            
    }

   

}
