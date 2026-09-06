<?php

namespace App\Helpers;

use App\Models\MAkun;
use App\Models\MCabang;
use App\Models\MPartner;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Validator;
use Exception;

class UploadJurnalJPValidasiService
{
    public static function validasi($request){
        self::form_validation($request);
        self::validasi_jurnal_header($request);
        self::validasi_jurnal_detail($request);
        return true;
    }

    public static function form_validation($request){
        $rules = [
            'file' => 'required|file|mimes:xls,xlsx|max:5120',
            'jenis_upload' => 'required',
        ];
        $messages = [
            'file.required' => 'File wajib diupload.',
            'file.file'     => 'Upload harus berupa file.',
            'file.mimes'    => 'File harus berformat .xls atau .xlsx.',
            'file.max'      => 'Ukuran file maksimal 5 MB.',
            'jenis_upload.required' => 'Jenis wajib dipilih.',
        ];

        if (!$request->entitas_scope) {
            $rules += [
                'entitas_id' => 'required',
            ];

            $messages += [
                'entitas_id.required' => 'Entitas wajib diupload.',
            ];
        }

        $validation = Validator::make($request->all(), $rules,$messages);
        if ($validation->fails()) {
            throw new Exception($validation->errors()->first(), 422);
        }

        return true;
    }

    private static function validasi_jurnal_header($request)
    {
        $file = $request->file('file');
        $entitas_id = $request->entitas_scope ? $request->entitas_scope : $request->entitas_id;
        $spreadsheet = IOFactory::load($file->getRealPath());

        $sheet = $spreadsheet->getSheetByName('jurnal_header');

        if (!$sheet) {
            throw new Exception('Sheet Jurnal Header tidak ditemukan.', 422);
        }

        $highestRow = $sheet->getHighestRow();

        /**
         * Menampung Jurnal Header ID yang sudah dibaca
         */
        $usedHeaderIds = [];
        
        /**
         * Mulai baca dari baris 3
         * Karena baris 1 = heading
         * Baris 2 = contoh pengisian
         */
        for ($row = 3; $row <= $highestRow; $row++) {

            $colA = trim((string) $sheet->getCell('A' . $row)->getValue());
            $colB = $sheet->getCell('B' . $row)->getValue();
            $colC = trim((string) $sheet->getCell('C' . $row)->getValue());
            $colE = trim((string) $sheet->getCell('E' . $row)->getValue());
            $colG = trim((string) $sheet->getCell('G' . $row)->getValue());

            /**
             * Skip baris kosong total
             */
            if (
                $colA === '' &&
                self::isEmptyCell($colB) &&
                $colC === '' &&
                $colE === '' &&
                $colG === ''
            ) {
                continue;
            }

            /**
             * Kolom A wajib
             */
            if ($colA === '') {
                throw new Exception("Baris {$row}: Kolom A / Jurnal Header ID wajib diisi.", 422);
            }

            /**
             * Validasi Jurnal Header ID tidak boleh duplikat
             */
            if (in_array($colA, $usedHeaderIds)) {
                throw new Exception(
                    "Baris {$row}: Jurnal Header ID <b>{$colA}</b> duplikat di sheet Jurnal Header.",
                    422
                );
            }

            $usedHeaderIds[] = $colA;

            /**
             * Kolom B wajib
             */
            if (self::isEmptyCell($colB)) {
                throw new Exception("Baris {$row}: Kolom B / Tanggal Jurnal wajib diisi.", 422);
            }

            /**
             * Kolom B wajib date
             */
            if (!self::isValidDate($colB)) {
                throw new Exception("Baris {$row}: Kolom B / Tanggal Jurnal harus berformat tanggal yang valid.", 422);
            }

            /**
             * Kolom C jika terisi, cek apakah Cabang ID ada di master cabang
             */
            if ($colC !== '') {
                $cabangExists = MCabang::query()
                    ->where('id', $colC)
                    ->exists();

                if (!$cabangExists) {
                    throw new Exception("Baris {$row}: Kolom C / Cabang ID {$colC} tidak ditemukan di master cabang.", 422);
                }
            }

            /**
             * Kolom E wajib
             */
            if ($colE === '') {
                throw new Exception("Baris {$row}: Kolom E / Partner ID wajib diisi.", 422);
            }else{
                $partnerExists = MPartner::query()
                    ->where('id', $colE)
                    ->where("entitas_id",$entitas_id)
                    ->where(function ($query) {
                        $query->where('is_vendor', 'active')
                            ->orWhere('is_customer', 'active');
                    })
                    ->exists();

                if (!$partnerExists) {
                    throw new Exception("Baris {$row}: Kolom E / Partner ID {$colC} tidak ditemukan di master partner.", 422);
                }
            }

            /**
             * Kolom G wajib
             */
            if ($colG === '') {
                throw new Exception("Baris {$row}: Kolom G / Keterangan wajib diisi.", 422);
            }
        }
    }

    private static function validasi_jurnal_detail($request)
    {
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

    private static function isEmptyCell($value)
    {
        return $value === null || trim((string) $value) === '';
    }

    private static function isValidDate($value)
    {
        try {
            /**
             * Jika tanggal dari Excel berupa angka serial date
             */
            if (is_numeric($value)) {
                ExcelDate::excelToDateTimeObject($value);
                return true;
            }

            /**
             * Jika tanggal berupa string, contoh:
             * 2026-06-01
             * 01/06/2026
             */
            Carbon::parse($value);
            return true;

        } catch (\Throwable $e) {
            return false;
        }
    }
    
}
