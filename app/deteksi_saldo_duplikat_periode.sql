SELECT
    periode,
    entitas_id,
    akun_gl_id,
    partner_id,
    COUNT(*) AS jumlah_duplikat
FROM m_saldo_awal
GROUP BY
    periode,
    entitas_id,
    akun_gl_id,
    partner_id
HAVING COUNT(*) > 1;