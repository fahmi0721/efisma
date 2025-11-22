<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW view_laporan_keuangan AS
            WITH saldo_akun AS (
                SELECT 
                    b.akun_id,
                    a.saldo_normal,
                    MIN(b.tanggal) AS tanggal_awal,
                    MAX(b.tanggal) AS tanggal_akhir,
                    SUM(b.debit) AS total_debit,
                    SUM(b.kredit) AS total_kredit,
                    SUM(
                        CASE 
                            WHEN a.saldo_normal = 'debet' THEN (b.debit - b.kredit)
                            WHEN a.saldo_normal = 'kredit' THEN (b.kredit - b.debit)
                            ELSE 0
                        END
                    ) AS saldo_akhir
                FROM buku_besar b
                INNER JOIN jurnal_header j ON j.id = b.jurnal_id
                INNER JOIN m_akun_gl a ON a.id = b.akun_id
                WHERE j.status = 'posted'
                GROUP BY b.akun_id, a.saldo_normal
            ),

            saldo_hirarki AS (
                SELECT 
                    h.id AS akun_id,
                    h.no_akun,
                    h.nama AS akun_nama,
                    h.tipe_akun,
                    h.kategori,
                    h.saldo_normal,
                    h.parent_id,
                    h.level,
                    h.sort_path,
                    s.tanggal_awal,
                    s.tanggal_akhir,
                    COALESCE(s.total_debit, 0) AS total_debit,
                    COALESCE(s.total_kredit, 0) AS total_kredit,
                    COALESCE(s.saldo_akhir, 0) AS saldo_sendiri
                FROM view_akun_hirarki h
                LEFT JOIN saldo_akun s ON s.akun_id = h.id
            ),

            subtotal AS (
                SELECT 
                    p.akun_id,
                    p.no_akun,
                    p.akun_nama,
                    p.tipe_akun,
                    p.kategori,
                    p.saldo_normal,
                    p.parent_id,
                    p.level,
                    p.sort_path,
                    MIN(c.tanggal_awal) AS tanggal_awal,
                    MAX(c.tanggal_akhir) AS tanggal_akhir,
                    SUM(c.total_debit) AS total_debit,
                    SUM(c.total_kredit) AS total_kredit,
                    SUM(c.saldo_sendiri) AS saldo_total
                FROM saldo_hirarki p
                LEFT JOIN saldo_hirarki c ON c.sort_path LIKE CONCAT(p.sort_path, '%')
                GROUP BY 
                    p.akun_id, p.no_akun, p.akun_nama, 
                    p.tipe_akun, p.kategori, p.saldo_normal, 
                    p.parent_id, p.level, p.sort_path
            )

            SELECT 
                akun_id,
                no_akun,
                akun_nama,
                tipe_akun,
                kategori,
                saldo_normal,
                parent_id,
                level,
                sort_path,
                tanggal_awal,
                tanggal_akhir,
                total_debit,
                total_kredit,
                saldo_total AS saldo_akhir
            FROM subtotal
            ORDER BY sort_path;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS view_laporan_keuangan");
    }
};
