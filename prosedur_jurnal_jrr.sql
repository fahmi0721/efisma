DELIMITER $$

DROP PROCEDURE IF EXISTS sp_jurnal_jrr$$

CREATE PROCEDURE sp_jurnal_jrr(
    IN p_tanggal DATE,
    IN p_keterangan TEXT,
    IN p_entitas_id INT,
    IN p_cabang_id INT,
    IN p_partner_id INT,
    IN p_akun_id INT,
    IN p_jumlah DECIMAL(18,2),
    IN p_created_by INT
)
BEGIN
    DECLARE v_jurnal_id INT;
    DECLARE v_kode_jurnal VARCHAR(50);
    DECLARE v_prefix VARCHAR(10);
    DECLARE v_periode VARCHAR(6);
    DECLARE v_next_no INT;

    SET v_prefix  = 'MGR';
    SET v_periode = DATE_FORMAT(p_tanggal, '%Y%m');

    SELECT IFNULL(
        MAX(CAST(RIGHT(kode_jurnal, 3) AS UNSIGNED)), 0
    ) + 1
    INTO v_next_no
    FROM jurnal_header
    WHERE kode_jurnal COLLATE utf8mb4_unicode_ci
      LIKE CONCAT(v_prefix, '-', v_periode, '-%') 
           COLLATE utf8mb4_unicode_ci;

    SET v_kode_jurnal = CONCAT(
        v_prefix, '-', v_periode, '-', LPAD(v_next_no, 3, '0')
    );

    START TRANSACTION;

        INSERT INTO jurnal_header (
            kode_jurnal, jenis, tanggal, keterangan,
            entitas_id, cabang_id, partner_id,
            total_debit, total_kredit,
            status, created_at, created_by
        ) VALUES (
            v_kode_jurnal, 'JN', p_tanggal, p_keterangan,
            p_entitas_id, p_cabang_id, p_partner_id,
            0, p_jumlah,
            'posted', NOW(), p_created_by
        );

        SET v_jurnal_id = LAST_INSERT_ID();

        INSERT INTO jurnal_detail (
            jurnal_id, akun_id, deskripsi, debit, kredit, created_at
        ) VALUES (
            v_jurnal_id, p_akun_id, p_keterangan, 0, p_jumlah, NOW()
        );

        INSERT INTO buku_besar (
            jurnal_id, akun_id, tanggal, kode_jurnal,
            keterangan, debit, kredit,
            entitas_id, partner_id, cabang_id,
            jenis, updated_at, created_at
        ) VALUES (
            v_jurnal_id, p_akun_id, p_tanggal, v_kode_jurnal,
            p_keterangan, 0, p_jumlah,
            p_entitas_id, p_partner_id, p_cabang_id,
            'JN', NOW(), NOW()
        );

    COMMIT;
END$$

DELIMITER ;
