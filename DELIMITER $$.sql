DELIMITER $$

CREATE PROCEDURE hapus_jurnal (
    IN p_kode_jurnal VARCHAR(50)
)
BEGIN
    DECLARE v_jurnal_id INT;

    -- Ambil id jurnal berdasarkan kode jurnal
    SELECT id
    INTO v_jurnal_id
    FROM jurnal_header
    WHERE kode_jurnal = p_kode_jurnal
    LIMIT 1;

    -- Cek apakah data ditemukan
    IF v_jurnal_id IS NOT NULL THEN

        -- Hapus detail jurnal
        DELETE FROM jurnal_detail
        WHERE jurnal_id = v_jurnal_id;

        -- Hapus buku besar
        DELETE FROM buku_besar
        WHERE jurnal_id = v_jurnal_id;

        -- Hapus header jurnal
        DELETE FROM jurnal_header
        WHERE id = v_jurnal_id;

    END IF;

END$$

DELIMITER ;