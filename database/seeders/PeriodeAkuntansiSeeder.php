<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PeriodeAkuntansiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $periode = $now->format('Y-m');
        $mulai = $now->copy()->startOfMonth()->format('Y-m-d');
        $selesai = $now->copy()->endOfMonth()->format('Y-m-d');

        DB::table('periode_akuntansi')->updateOrInsert(
            ['periode' => $periode],
            [
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $selesai,
                'status' => 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
