
SELECT
    sa.entitas_id,
    e.nama AS nama_entitas,
    sa.akun_gl_id,
    a.no_akun,
    a.nama AS nama_akun,

    COUNT(*) AS total_baris,

    COUNT(DISTINCT sa.periode) AS jumlah_periode,

    SUM(
        CASE
            WHEN sa.partner_id IS NULL THEN 1
            ELSE 0
        END
    ) AS baris_tanpa_partner,

    SUM(
        CASE
            WHEN sa.partner_id IS NOT NULL THEN 1
            ELSE 0
        END
    ) AS baris_dengan_partner,

    CASE
        WHEN SUM(sa.partner_id IS NULL) > 0
         AND SUM(sa.partner_id IS NOT NULL) > 0
            THEN 'CAMPURAN'
        WHEN SUM(sa.partner_id IS NOT NULL) > 0
            THEN 'PARTNER'
        ELSE 'AKUN'
    END AS indikasi_metode

FROM m_saldo_awal sa

JOIN m_akun_gl a
    ON a.id = sa.akun_gl_id

JOIN m_entitas e
    ON e.id = sa.entitas_id

JOIN view_akun_transaksi_only v
    ON v.id = a.id

GROUP BY
    sa.entitas_id,
    e.nama,
    sa.akun_gl_id,
    a.no_akun,
    a.nama

ORDER BY
    sa.entitas_id,
    a.no_akun;
