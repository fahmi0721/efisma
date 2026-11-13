SELECT
    periode,
    entitas_id,
    akun_gl_id,
    partner_id,
    saldo,
    total_debit,
    total_kredit,
    saldo_akhir
FROM m_saldo_awal
WHERE saldo <> 0
  AND saldo_akhir = 0
ORDER BY
    periode,
    entitas_id,
    akun_gl_id;


  -- Hasil query C tidak otomatis menunjukkan kesalahan. Saldo akhir nol bisa saja benar karena transaksi pelunasan atau penyesuaian. Namun, jika total_debit dan total_kredit juga nol, sementara saldo tidak nol, kemungkinan ada kolom yang belum dihitung atau belum diperbarui.