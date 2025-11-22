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
            CREATE OR REPLACE ALGORITHM = UNDEFINED 
            DEFINER = `root`@`localhost` 
            SQL SECURITY DEFINER 
            VIEW `view_akun_hirarki` AS
            WITH RECURSIVE akun_hirarki AS (
                -- Level 1: Akun tanpa parent
                SELECT 
                    m_akun_gl.id,
                    m_akun_gl.no_akun,
                    m_akun_gl.nama,
                    m_akun_gl.tipe_akun,
                    m_akun_gl.kategori,
                    m_akun_gl.saldo_normal,
                    m_akun_gl.parent_id,
                    1 AS level,
                    CAST(LPAD(m_akun_gl.no_akun, 10, '0') AS CHAR(1000) CHARACTER SET utf8mb4) AS sort_path
                FROM m_akun_gl
                WHERE m_akun_gl.parent_id IS NULL

                UNION ALL

                -- Rekursif: ambil anak dari setiap akun parent
                SELECT 
                    a.id,
                    a.no_akun,
                    a.nama,
                    a.tipe_akun,
                    a.kategori,
                    a.saldo_normal,
                    a.parent_id,
                    h.level + 1 AS level,
                    CAST(CONCAT(h.sort_path, '.', LPAD(a.no_akun, 10, '0')) AS CHAR(1000) CHARACTER SET utf8mb4) AS sort_path
                FROM m_akun_gl a
                JOIN akun_hirarki h ON a.parent_id = h.id
            )
            SELECT 
                akun_hirarki.id,
                akun_hirarki.no_akun,
                akun_hirarki.nama,
                akun_hirarki.tipe_akun,
                akun_hirarki.kategori,
                akun_hirarki.saldo_normal,
                akun_hirarki.parent_id,
                akun_hirarki.level,
                akun_hirarki.sort_path
            FROM akun_hirarki
            ORDER BY akun_hirarki.sort_path;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_akun_hirarki');
    }
};
