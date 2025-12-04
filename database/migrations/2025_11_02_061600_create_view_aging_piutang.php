<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW view_aging_piutang AS
            SELECT
                partner_id,
                partner_nama,
                is_vendor,
                is_customer,
                entitas_id,

                SUM(CASE WHEN umur_piutang BETWEEN 0  AND 14 THEN sisa_piutang ELSE 0 END) AS aging_0_14,
                SUM(CASE WHEN umur_piutang BETWEEN 15 AND 30 THEN sisa_piutang ELSE 0 END) AS aging_15_30,
                SUM(CASE WHEN umur_piutang BETWEEN 31 AND 45 THEN sisa_piutang ELSE 0 END) AS aging_31_45,
                SUM(CASE WHEN umur_piutang BETWEEN 46 AND 60 THEN sisa_piutang ELSE 0 END) AS aging_46_60,
                SUM(CASE WHEN umur_piutang > 60 THEN sisa_piutang ELSE 0 END) AS aging_60_plus,

                SUM(sisa_piutang) AS total_piutang

            FROM view_daftar_piutang
            GROUP BY partner_id, partner_nama, is_vendor, is_customer, entitas_id;
        ");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS view_aging_piutang");
    }
};
