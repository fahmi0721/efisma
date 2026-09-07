<?php

namespace App\Helpers;

use App\Models\JurnalHeader;
use App\Models\JurnalDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Exception;

class UploadJurnalJPService
{
    public static function uploads($request){
        $jenis = $request->jenis_upload;
        switch ($jenis) {
            case 'acs':
                self::acs($request);
                break;
            
            default:
                # code...
                break;
        }
    }
    public static function acs($request){
        DB::beginTransaction();

        try {
            $file = $request->file('file');

            $spreadsheet = IOFactory::load($file->getRealPath());

             /**
             * Ambil detail dulu dan group berdasarkan Jurnal Header ID dari kolom A
             */
            $detailGroup = self::cleansingJurnalDetailAcs($spreadsheet);


            /**
             * Proses cleansing dan insert jurnal header
             */
            self::prosesJurnalHeaderDanDetailAcs($spreadsheet, $request, $detailGroup);

            DB::commit();

            return true;

        } catch (\Throwable $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), 500);
        }
    }

    private static function prosesJurnalHeaderDanDetailAcs($spreadsheet, $request, array $detailGroup)
    {
        $sheet = $spreadsheet->getSheetByName('jurnal_header');
        $sheet->getProtection()->setSheet(false);
        if (!$sheet) {
            throw new Exception('Sheet Jurnal Header tidak ditemukan.', 422);
        }

        $highestRow = $sheet->getHighestRow();

        /**
         * Mulai baca Jurnal Header dari baris 3
         */
        for ($row = 3; $row <= $highestRow; $row++) {

            /**
             * Mapping kolom Jurnal Header
             * A = Jurnal Header ID template
             * B = Tanggal Jurnal
             * C = Cabang ID
             * D = Cabang / otomatis, tidak masuk DB
             * E = Partner ID
             * F = Partner / otomatis, tidak masuk DB
             * G = Keterangan
             */
            $templateHeaderId = trim((string) $sheet->getCell('A' . $row)->getValue());
            $tanggalJurnal    = $sheet->getCell('B' . $row)->getValue();
            $cabangId         = trim((string) $sheet->getCell('C' . $row)->getValue());
            $partnerId        = trim((string) $sheet->getCell('E' . $row)->getValue());
            $keterangan       = trim((string) $sheet->getCell('G' . $row)->getValue());

            /**
             * Skip baris kosong
             */
            if (
                $templateHeaderId === '' &&
                self::isEmptyCell($tanggalJurnal) &&
                $cabangId === '' &&
                $partnerId === '' &&
                $keterangan === ''
            ) {
                continue;
            }

            /**
             * Ambil detail berdasarkan Jurnal Header ID template
             */
            $details = $detailGroup[$templateHeaderId] ?? [];

            if (count($details) === 0) {
                throw new Exception("Jurnal Header ID {$templateHeaderId} tidak memiliki detail.", 422);
            }

            $totalDebit = collect($details)->sum('debit');
            $totalKredit = collect($details)->sum('kredit');

            /**
             * Insert jurnal_header
             */
            $jurnalHeader = JurnalHeader::create([
                'kode_jurnal'  => self::generateKodeJurnal(),
                'jenis'        => 'JN',
                'tanggal'      => self::formatTanggalExcel($tanggalJurnal),
                'keterangan'   => $keterangan,
                'entitas_id'   => self::getEntitasId($request),
                'partner_id'   => $partnerId !== '' ? $partnerId : null,
                'cabang_id'    => $cabangId !== '' ? $cabangId : null,
                'total_debit'  => $totalDebit,
                'total_kredit' => $totalKredit,
                'created_by'   => Auth::id(),
            ]);

            /**
             * Insert jurnal_detail berdasarkan last insert jurnal_header
             */
            foreach ($details as $detail) {
                JurnalDetail::create([
                    'jurnal_id' => $jurnalHeader->id,
                    'akun_id'   => $detail['akun_id'],
                    'cabang_id' => $cabangId !== '' ? $cabangId : null,
                    'deskripsi' => $detail['deskripsi'],
                    'debit'     => $detail['debit'],
                    'kredit'    => $detail['kredit'],
                ]);
            }
        }
    }

    private static function cleansingJurnalDetailAcs($spreadsheet): array
    {
        $sheet = $spreadsheet->getSheetByName('jurnal_detail');
        $sheet->getProtection()->setSheet(false);         
        if (!$sheet) {
            throw new Exception('Sheet Jurnal Detail tidak ditemukan.', 422);
        }

        $highestRow = $sheet->getHighestRow();

        $detailGroup = [];

        /**
         * Mulai baca Jurnal Detail dari baris 4
         */
        for ($row = 4; $row <= $highestRow; $row++) {

            /**
             * Mapping kolom Jurnal Detail
             * A = Jurnal Header ID template
             * B = Akun ID
             * C = Akun / otomatis, tidak masuk DB
             * D = Cabang ID
             * E = Debit
             * F = Kredit
             *
             * Kalau kolom deskripsi Anda berbeda, sesuaikan di sini.
             */
            $templateHeaderId = trim((string) $sheet->getCell('A' . $row)->getValue());
            $akunId           = trim((string) $sheet->getCell('B' . $row)->getValue());
            $deskripsi         = trim((string) $sheet->getCell('D' . $row)->getValue());
            $debit  = self::cleanDecimal($sheet->getCell('E' . $row)->getCalculatedValue());
            $kredit = self::cleanDecimal($sheet->getCell('F' . $row)->getCalculatedValue());

            /**
             * Skip baris kosong
             */
            if (
                $templateHeaderId === '' &&
                $akunId === '' &&
                $deskripsi === '' &&
                self::isEmptyCell($debit) &&
                self::isEmptyCell($kredit)
            ) {
                continue;
            }

            if (!isset($detailGroup[$templateHeaderId])) {
                $detailGroup[$templateHeaderId] = [];
            }

            $detailGroup[$templateHeaderId][] = [
                'akun_id'   => $akunId,
                'deskripsi' => $deskripsi,
                'debit'     => self::cleanDecimal($debit),
                'kredit'    => self::cleanDecimal($kredit),
            ];
        }

        return $detailGroup;
    }

    

    private static function formatTanggalExcel($value): ?string
    {
        if (self::isEmptyCell($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    private static function cleanDecimal($value): float
    {
        if (self::isEmptyCell($value)) {
            return 0;
        }

        /**
         * Kalau angka sudah numeric dari Excel
         */
        if (is_numeric($value)) {
            return (float) $value;
        }

        /**
         * Kalau user input format Indonesia: 1.500.000,25
         */
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }

    private static function isEmptyCell($value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    private static function getEntitasId($request)
    {
        return $request->entitas_scope ?: $request->entitas_id;
    }

    private static function generateKodeJurnal(): string
    {
        /**
         * Sementara sederhana dulu.
         * Nanti bisa disesuaikan dengan format kode jurnal Anda.
         */
        $prefix = 'JN-' . date('Ym');

        $last = JurnalHeader::query()
            ->where('kode_jurnal', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        if (!$last) {
            return $prefix . '-001';
        }

        $lastNumber = (int) substr($last->kode_jurnal, -3);
        $nextNumber = $lastNumber + 1;

        return $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    
}
