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
            CREATE OR REPLACE VIEW view_daftar_piutang AS
            SELECT
                j.id AS jurnal_id,
                j.kode_jurnal,
                j.no_invoice,
                j.tanggal,
                j.keterangan,
                j.entitas_id,
                j.cabang_id,          -- ✅ ditambahkan
                j.partner_id,
                p.nama AS partner_nama,
                p.is_vendor,
                p.is_customer,

                j.total_debit AS total_tagihan,

                COALESCE(SUM(
                    CASE 
                        WHEN jk.status = 'posted' THEN pp.jumlah
                        ELSE 0
                    END
                ), 0) AS total_pelunasan,

                (j.total_debit - COALESCE(SUM(
                    CASE 
                        WHEN jk.status = 'posted' THEN pp.jumlah
                        ELSE 0
                    END
                ), 0)) AS sisa_piutang,

                DATEDIFF(CURDATE(), j.tanggal) AS umur_piutang

            FROM jurnal_header j
            LEFT JOIN pelunasan_piutang pp ON pp.jurnal_piutang_id = j.id
            LEFT JOIN jurnal_header jk ON jk.id = pp.jurnal_kas_id
            LEFT JOIN m_partner p ON p.id = j.partner_id

            WHERE 
                j.status = 'posted'

                -- 1️⃣ Harus menyentuh akun kategori PIUTANG
                AND EXISTS (
                    SELECT 1
                    FROM jurnal_detail d
                    JOIN m_akun_gl a2 ON a2.id = d.akun_id
                    WHERE d.jurnal_id = j.id
                    AND a2.kategori = 'piutang'
                )

                -- 2️⃣ BUKAN jurnal pelunasan
                AND NOT EXISTS (
                    SELECT 1
                    FROM pelunasan_piutang pp2
                    WHERE pp2.jurnal_kas_id = j.id
                )

                -- 3️⃣ Jurnal harus punya nilai tagihan (debit > 0)
                AND j.total_debit > 0

            GROUP BY 
                j.id, j.kode_jurnal, j.no_invoice, j.tanggal,
                j.entitas_id, j.cabang_id, j.partner_id, p.nama, 
                p.is_vendor, p.is_customer, j.total_debit,j.keterangan

            HAVING sisa_piutang > 0

            ORDER BY j.tanggal DESC;



        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS view_daftar_piutang");
    }
};
