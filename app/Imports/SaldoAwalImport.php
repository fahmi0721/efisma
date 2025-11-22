<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class SaldoAwalImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $errorMessages = [];
        $insertData = [];
        // Ambil semua akun GL dan saldo normal sekaligus
        $akunGlList = DB::table('m_akun_gl')
            ->pluck('saldo_normal', 'id')
            ->toArray();
        // Ambil semua entitas yang valid
        $entitasList = DB::table('m_entitas')
            ->pluck('nama', 'id')
            ->toArray();
        
        // Ambil semua saldo_awal existing untuk cek duplikat
        $existingSaldo = DB::table('m_saldo_awal')
            ->select('akun_gl_id','periode','entitas_id')
            ->get()
            ->map(fn($item) => "{$item->akun_gl_id}-{$item->periode}-{$item->entitas_id}")
            ->toArray();
        
        foreach ($rows as $index => $row) {
            $akunGlRaw = $row['akun_gl_id'] ?? null;
            $entitasRaw = $row['entitas_id'] ?? null;
            $periode = $row['periode'] ?? null;
            $saldo = $row['saldo'] ?? null;
            // Skip jika baris kosong
            if (empty($row['akun_gl_id']) && empty($row['periode']) && empty($row['entitas_id'])) {
                continue;
            }

            // === Validasi Data ===
            $validator = Validator::make($row->toArray(), [
                'akun_gl_id' => 'required',
                'periode'    => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'], // format YYYY-MM
                'entitas_id' => 'required',
                'saldo'      => 'nullable|numeric',
            ], [
                'akun_gl_id.required' => 'Kolom akun_gl_id wajib diisi.',
                'periode.required'    => 'Kolom periode wajib diisi.',
                'periode.regex'       => 'Format periode harus YYYY-MM, contoh: 2025-10.',
                'entitas_id.required' => 'Kolom entitas_id wajib diisi.',
                'saldo.numeric'       => 'Kolom saldo harus berupa angka.',
            ]);

            if ($validator->fails()) {
                $errorMessages[] = "• Baris " . ($index + 2) . ": " . $validator->errors()->first()."<br>";
                continue;
            }
             // Ambil ID dari format "id - no_akun - nama"
            $akunGlId = (int) (explode(' - ', $akunGlRaw)[0] ?? 0);
            $entitasId = (int) (explode(' - ', $entitasRaw)[0] ?? 0);
            // Cek akun GL ada
            if (!isset($akunGlList[$akunGlId])) {
                $errorMessages[] = "• Baris " . ($index + 2) . ": " ."Akun GL ID {$akunGlId} tidak ditemukan.<br>";
                continue;
            }

            // === Cek Entitas ===
            if (!isset($entitasList[$entitasId])) {
                $errorMessages[] = "• Baris " . ($index + 2) . ": Entitas ID {$entitasId} tidak ditemukan.<br>";
                continue;
            }

            // Cek duplikat
            if (in_array("{$akunGlId}-{$periode}-{$entitasId}", $existingSaldo)) {
                $errorMessages[] = "Baris " . ($index + 2) . ": "."Duplikat saldo awal untuk akun {$akunGlId}, periode {$periode}, entitas {$entitasId}. <br>";
                continue;
            }

            // Bersihkan dan ubah saldo menjadi angka
            $saldo = str_replace(['.', 'Rp', ' '], '', $saldo);
            $saldo = is_numeric($saldo) ? (float)$saldo : 0;

            // === Mapping Data ===
            $insertData[] = [
                'akun_gl_id' => $akunGlId,
                'periode'    => $periode,
                'entitas_id' => $entitasId,
                'saldo'      => $saldo,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            // Tambahkan ke existingSaldo untuk mencegah duplikat di file yang sama
            $existingSaldo[] = "{$akunGlId}-{$periode}-{$entitasId}";
        }

        // === Eksekusi Insert ===
        if (!empty($insertData)) {
            DB::table('m_saldo_awal')->insert($insertData);
        }

        // === Jika Ada Error ===
        if (!empty($errorMessages)) {
            throw new \Exception(implode("\n", $errorMessages));
        }
    }
}
