<?php

namespace App\Helpers\UploadJP;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;
use App\Models\MAkun;
use App\Models\MAkunMaster;
class ValidasiJurnalDetail
{
    private static function isEmptyCell($value)
    {
        return $value === null || trim((string) $value) === '';
    }

    public static function acs($request){
        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());

        $sheetHeader = $spreadsheet->getSheetByName('jurnal_header');
        $sheetDetail = $spreadsheet->getSheetByName('jurnal_detail');

        if (!$sheetHeader) {
            throw new Exception('Sheet Jurnal Header tidak ditemukan.', 422);
        }

        if (!$sheetDetail) {
            throw new Exception('Sheet Jurnal Detail tidak ditemukan.', 422);
        }

        /**
         * Ambil semua Jurnal Header ID dari sheet Jurnal Header kolom A
         * Mulai baris 3 sesuai validasi header sebelumnya
         */
        $headerIds = [];

        $highestHeaderRow = $sheetHeader->getHighestRow();

        for ($row = 3; $row <= $highestHeaderRow; $row++) {
            $headerId = trim((string) $sheetHeader->getCell('A' . $row)->getValue());

            if ($headerId !== '') {
                $headerIds[] = $headerId;
            }
        }

        /**
         * Untuk menampung Jurnal Header ID yang ada di sheet detail
         */
        $detailHeaderIds = [];

        $highestDetailRow = $sheetDetail->getHighestRow();

        /**
         * Untuk menampung total debit dan kredit berdasarkan Jurnal Header ID
         */
        $balance = [];

        /**
         * Mulai baca Jurnal Detail dari baris 4
         */
        for ($row = 4; $row <= $highestDetailRow; $row++) {

            $colA = trim((string) $sheetDetail->getCell('A' . $row)->getValue()); // Jurnal Header ID
            $colB = trim((string) $sheetDetail->getCell('B' . $row)->getValue()); // Akun ID
            $colD = trim((string) $sheetDetail->getCell('D' . $row)->getValue());
            $colE = $sheetDetail->getCell('E' . $row)->getCalculatedValue(); // Debit / nilai E
            $colF = $sheetDetail->getCell('F' . $row)->getCalculatedValue(); // Kredit / nilai F

            /**
             * Skip baris kosong total
             */
            if (
                $colA === '' &&
                $colB === '' &&
                $colD === '' &&
                self::isEmptyCell($colE) &&
                self::isEmptyCell($colF)
            ) {
                continue;
            }

            /**
             * Kolom A wajib
             */
            if ($colA === '') {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom A / Jurnal Header ID wajib diisi.", 422);
            }

            /**
             * Kolom A wajib ada di sheet Jurnal Header kolom A
             */
            if (!in_array($colA, $headerIds)) {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom A / Jurnal Header ID <b>{$colA}</b> tidak ditemukan di sheet Jurnal Header.", 422);
            }
            $detailHeaderIds[] = $colA;

            /**
             * Kolom B wajib
             */
            if ($colB === '') {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom B / Akun ID wajib diisi.", 422);
            }

            /**
             * Kolom B wajib ada di model MAkun
             */
            $akunExists = MAkun::query()
                ->where('id', $colB)
                ->exists();

            if (!$akunExists) {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom B / Akun ID {$colB} tidak ditemukan di master akun.", 422);
            }

            /**
             * Kolom D wajib
             */
            if ($colD === '') {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom D wajib diisi.", 422);
            }

            /**
             * Kolom E wajib
             */
            if (self::isEmptyCell($colE)) {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom E wajib diisi.", 422);
            }

            /**
             * Kolom E harus angka / decimal
             */
            if (!is_numeric($colE)) {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom E harus berupa angka atau decimal.", 422);
            }

            /**
             * Kolom F wajib
             */
            if (self::isEmptyCell($colF)) {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom F wajib diisi.", 422);
            }

            /**
             * Kolom F harus angka / decimal
             */
            if (!is_numeric($colF)) {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom F harus berupa angka atau decimal.", 422);
            }

            /**
             * Simpan total nilai E dan F berdasarkan Jurnal Header ID
             */
            if (!isset($balance[$colA])) {
                $balance[$colA] = [
                    'total_e' => 0,
                    'total_f' => 0,
                ];
            }

            $balance[$colA]['total_e'] += (float) $colE;
            $balance[$colA]['total_f'] += (float) $colF;
        }

        /**
         * Validasi balance:
         * Untuk Jurnal Header ID yang sama,
         * total kolom E dan F harus sama.
         */
        foreach ($balance as $jurnalHeaderId => $total) {
            $totalE = round($total['total_e'], 2);
            $totalF = round($total['total_f'], 2);

            if ($totalE !== $totalF) {
                $totalEFormatted = number_format((float) $totalE, 2, ',', '.');
                $totalFFormatted = number_format((float) $totalF, 2, ',', '.');
                throw new Exception(
                    "Jurnal Detail dengan Jurnal Header ID <b>{$jurnalHeaderId}</b> tidak balance. Total kolom Debit: {$totalEFormatted}, total kolom Kredit: {$totalFFormatted}.",
                    422
                );
            }
        }

        /**
         * Validasi:
         * Semua Jurnal Header ID di sheet Jurnal Header
         * wajib memiliki detail di sheet Jurnal Detail.
         */
        $detailHeaderIds = array_unique($detailHeaderIds);

        foreach ($headerIds as $headerId) {
            if (!in_array($headerId, $detailHeaderIds)) {
                throw new Exception(
                    "Jurnal Header ID <b>{$headerId}</b> di sheet Jurnal Header belum memiliki detail di sheet Jurnal Detail.",
                    422
                );
            }
        }
    }

    public static function uang_muka($request){
        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());

        $sheetHeader = $spreadsheet->getSheetByName('jurnal_header');
        $sheetDetail = $spreadsheet->getSheetByName('jurnal_detail');

        if (!$sheetHeader) {
            throw new Exception('Sheet Jurnal Header tidak ditemukan.', 422);
        }

        if (!$sheetDetail) {
            throw new Exception('Sheet Jurnal Detail tidak ditemukan.', 422);
        }

        /**
         * Ambil semua Jurnal Header ID dari sheet Jurnal Header kolom A
         * Mulai baris 3 sesuai validasi header sebelumnya
         */
        $headerIds = [];

        $highestHeaderRow = $sheetHeader->getHighestRow();

        for ($row = 3; $row <= $highestHeaderRow; $row++) {
            $headerId = trim((string) $sheetHeader->getCell('A' . $row)->getValue());

            if ($headerId !== '') {
                $headerIds[] = $headerId;
            }
        }

        /**
         * Untuk menampung Jurnal Header ID yang ada di sheet detail
         */
        $detailHeaderIds = [];

        $highestDetailRow = $sheetDetail->getHighestRow();

        /**
         * Untuk menampung total debit dan kredit berdasarkan Jurnal Header ID
         */
        $balance = [];

        /**
         * Mulai baca Jurnal Detail dari baris 4
         */
        for ($row = 4; $row <= $highestDetailRow; $row++) {

            $colA = trim((string) $sheetDetail->getCell('A' . $row)->getValue()); // Jurnal Header ID
            $colB = trim((string) $sheetDetail->getCell('B' . $row)->getValue()); // Akun ID
            $colD = trim((string) $sheetDetail->getCell('D' . $row)->getCalculatedValue());
            $colE = $sheetDetail->getCell('E' . $row)->getCalculatedValue(); // Debit / nilai E
            $colF = $sheetDetail->getCell('F' . $row)->getCalculatedValue(); // Kredit / nilai F
            $colG = $sheetDetail->getCell('G' . $row)->getCalculatedValue(); // Uang Muka ID / nilai G

            /**
             * Skip baris kosong total
             */
            if (
                $colA === '' &&
                $colB === '' &&
                ($colD === '' || $colD === 'PJ Uang Muka #N/A') &&
                self::isEmptyCell($colE) &&
                self::isEmptyCell($colF)
            ) {
                continue;
            }

            /**
             * Kolom A wajib
             */
            if ($colA === '') {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom A / Jurnal Header ID wajib diisi.", 422);
            }

            /**
             * Kolom A wajib ada di sheet Jurnal Header kolom A
             */
            if (!in_array($colA, $headerIds)) {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom A / Jurnal Header ID <b>{$colA}</b> tidak ditemukan di sheet Jurnal Header.", 422);
            }
            $detailHeaderIds[] = $colA;

            /**
             * Kolom B wajib
             */
            if ($colB === '') {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom B / Akun ID wajib diisi.", 422);
            }

            /**
             * Kolom B wajib ada di model MAkun
             */
            $akunExists = MAkun::query()
                ->where('id', $colB)
                ->exists();

            if (!$akunExists) {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom B / Akun ID {$colB} tidak ditemukan di master akun.", 422);
            }

            /**
             * Kolom D wajib
             */
            if ($colD === '') {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom D wajib diisi.", 422);
            }

            if ($colD === 'PJ Uang Muka #N/A') {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom D wajib diisi.", 422);
            }

            /**
             * Kolom E wajib
             */
            if (self::isEmptyCell($colE)) {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom E wajib diisi.", 422);
            }

            /**
             * Kolom E harus angka / decimal
             */
            if (!is_numeric($colE)) {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom E harus berupa angka atau decimal.", 422);
            }

            /**
             * Kolom F wajib
             */
            if (self::isEmptyCell($colF)) {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom F wajib diisi.", 422);
            }

            /**
             * Kolom F harus angka / decimal
             */
            if (!is_numeric($colF)) {
                throw new Exception("Baris {$row} Jurnal Detail: Kolom F harus berupa angka atau decimal.", 422);
            }

            /**
             * Kolom G wajib ada di model MAkun
             * Jika Kolom B adalah akun Uang Muka
             */
            $akunExists = MAkunMaster::query()
                ->where('id', $colB)
                ->where('kategori', "uang_muka")
                ->exists();

            if ($akunExists) {
                 // Kolom G wajib diisi
                if (trim((string) $colG) === '') {
                    throw new \Exception(
                        "Baris {$row} Jurnal Detail: Kolom G wajib diisi karena akun yang digunakan adalah akun uang muka",
                        422
                    );
                }
                
                // Kolom D wajib mengandung teks "PJ Uang Muka"
                if (!str_contains(
                    strtolower(trim((string) $colD)),
                    strtolower('PJ Uang Muka')
                )) {
                    throw new \Exception(
                        "Baris {$row} Jurnal Detail: Kolom D wajib berisi deskripsi 'PJ Uang Muka (Kode Jurnal)' karena akun yang digunakan adalah akun uang muka, contohnya (PJ Uang Muka JKK-202609-0001)",
                        422
                    );
                }
            }

            /**
             * Simpan total nilai E dan F berdasarkan Jurnal Header ID
             */
            if (!isset($balance[$colA])) {
                $balance[$colA] = [
                    'total_e' => 0,
                    'total_f' => 0,
                ];
            }

            $balance[$colA]['total_e'] += (float) $colE;
            $balance[$colA]['total_f'] += (float) $colF;
        }

        /**
         * Validasi balance:
         * Untuk Jurnal Header ID yang sama,
         * total kolom E dan F harus sama.
         */
        foreach ($balance as $jurnalHeaderId => $total) {
            $totalE = round($total['total_e'], 2);
            $totalF = round($total['total_f'], 2);

            if ($totalE !== $totalF) {
                $totalEFormatted = number_format((float) $totalE, 2, ',', '.');
                $totalFFormatted = number_format((float) $totalF, 2, ',', '.');
                throw new Exception(
                    "Jurnal Detail dengan Jurnal Header ID <b>{$jurnalHeaderId}</b> tidak balance. Total kolom Debit: {$totalEFormatted}, total kolom Kredit: {$totalFFormatted}.",
                    422
                );
            }
        }

        /**
         * Validasi:
         * Semua Jurnal Header ID di sheet Jurnal Header
         * wajib memiliki detail di sheet Jurnal Detail.
         */
        $detailHeaderIds = array_unique($detailHeaderIds);

        foreach ($headerIds as $headerId) {
            if (!in_array($headerId, $detailHeaderIds)) {
                throw new Exception(
                    "Jurnal Header ID <b>{$headerId}</b> di sheet Jurnal Header belum memiliki detail di sheet Jurnal Detail.",
                    422
                );
            }
        }
    }
}