<?php

namespace App\Helpers;


use App\Models\MCabang;
use App\Models\MPartner;
use App\Helpers\UploadJP\ValidasiJurnalDetail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
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

    private static  function validasi_jurnal_header($request)
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
        $jenis = $request->jenis_upload;
        switch ($jenis) {
            case 'uang_muka':
                ValidasiJurnalDetail::uang_muka($request);
                break;

            case 'acs':
                ValidasiJurnalDetail::acs($request);
                break;
            default:
                throw new Exception('Jenis jurnal tidak ditemukan', 422);
                
                break;
        }
        
    }
    
}
