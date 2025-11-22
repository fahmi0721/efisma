<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Hash;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

use App\Http\Controllers\DashboardController;

Route::group(['middleware' => 'auth'], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // API untuk chart / widget (dipanggil via AJAX)
    Route::get('/dashboard/keuangan/summary', [DashboardController::class, 'apiSummary'])->name('dashboard.keuangan.summary');
    Route::get('/dashboard/keuangan/cashflow', [DashboardController::class, 'apiCashflow'])->name('dashboard.keuangan.cashflow');
    Route::get('/dashboard/keuangan/composition', [DashboardController::class, 'apiComposition'])->name('dashboard.keuangan.composition');
    Route::get('/dashboard/keuangan/aging-top', [DashboardController::class, 'apiAgingTop'])->name('dashboard.keuangan.aging_top');
    Route::get('/dashboard/keuangan/pendapatan-beban', [DashboardController::class, 'apiPendapatanBeban'])->name('dashboard.keuangan.pendapatan_beban');
    Route::get('/dashboard/keuangan/labarugi-harian', [DashboardController::class, 'apiLabaRugiHarian'])->name('dashboard.keuangan.labarugi_harian');

});
/**
 * Route Pengaturan Umum
 */
use App\Http\Controllers\PengaturanUmumController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/setting', [PengaturanUmumController::class, 'index'])->name('setting');
    Route::post('/setting/save', [PengaturanUmumController::class, 'store'])->name('setting.save');
});

/**
 * Route Master Data Entitas
 */
use App\Http\Controllers\M_EntitasController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/m_entitas', [M_EntitasController::class, 'index'])->name('entitas');
    Route::get('/m_entitas/select', [M_EntitasController::class, 'entitas_select'])->name('entitas.select');
    Route::get('/m_entitas/add', [M_EntitasController::class, 'create'])->name('entitas.create');
    Route::post('/m_entitas/save', [M_EntitasController::class, 'store'])->name('entitas.save');
    Route::get('/m_entitas/edit', [M_EntitasController::class, 'edit'])->name('entitas.edit');
    Route::put('/m_entitas/update/{id}', [M_EntitasController::class, 'update'])->name('entitas.update');
    Route::delete('/m_entitas/delete/{id}', [M_EntitasController::class, 'destroy'])->name('entitas.destroy');
});


/**
 * Route Master Data PeriodeAkutansiController
 */
use App\Http\Controllers\PeriodeAkutansiController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/periode_akutansi', [PeriodeAkutansiController::class, 'index'])->name('periode_akuntansi');
    Route::get('/periode_akutansi/add', [PeriodeAkutansiController::class, 'create'])->name('periode_akuntansi.create');
    Route::post('/periode_akutansi/save', [PeriodeAkutansiController::class, 'store'])->name('periode_akuntansi.save');
    Route::get('/periode_akutansi/edit', [PeriodeAkutansiController::class, 'edit'])->name('periode_akuntansi.edit');
    Route::put('/periode_akutansi/update/{id}', [PeriodeAkutansiController::class, 'update'])->name('periode_akuntansi.update');
    Route::delete('/periode_akutansi/delete/{id}', [PeriodeAkutansiController::class, 'destroy'])->name('periode_akuntansi.destroy');
    Route::post('/periode_akuntansi/update-status/{id}', [PeriodeAkutansiController::class, 'updateStatus'])->name('periode_akuntansi.update_status');
});



/**
 * Route Master Data Partner
 */
use App\Http\Controllers\M_PartnerController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/m_partner', [M_PartnerController::class, 'index'])->name('partner');
    Route::get('/m_partner/add', [M_PartnerController::class, 'create'])->name('partner.create');
    Route::get('/m_partner/select', [M_PartnerController::class, 'partner_select'])->name('partner.select');
    Route::post('/m_partner/save', [M_PartnerController::class, 'store'])->name('partner.save');
    Route::get('/m_partner/edit', [M_PartnerController::class, 'edit'])->name('partner.edit');
    Route::put('/m_partner/update/{id}', [M_PartnerController::class, 'update'])->name('partner.update');
    Route::delete('/m_partner/delete/{id}', [M_PartnerController::class, 'destroy'])->name('partner.destroy');
});


/**
 * Route Master Data M Akun GL
 */
use App\Http\Controllers\M_AkunGLController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/m_akun', [M_AkunGLController::class, 'index'])->name('m_akun');
    Route::get('/m_akun/add', [M_AkunGLController::class, 'create'])->name('m_akun.create');
    Route::post('/m_akun/save', [M_AkunGLController::class, 'store'])->name('m_akun.save');
    Route::get('/m_akun/edit', [M_AkunGLController::class, 'edit'])->name('m_akun.edit');
    Route::put('/m_akun/update/{id}', [M_AkunGLController::class, 'update'])->name('m_akun.update');
    Route::delete('/m_akun/delete/{id}', [M_AkunGLController::class, 'destroy'])->name('m_akun.destroy');
    Route::get('/m_akun/search', [M_AkunGLController::class, 'search'])->name('m_akun.search');
    Route::get('/m_akun/maping', [M_AkunGLController::class, 'map'])->name('m_akun.map');
    Route::get('/m_akun/transaksi', [M_AkunGLController::class, 'transaksi'])->name('m_akun.transaksi');
});



/**
 * Route Saldo Awal
 */
use App\Http\Controllers\SaldoAwalController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/saldo_awal', [SaldoAwalController::class, 'index'])->name('saldo_awal');
    Route::get('/saldo_awal/add', [SaldoAwalController::class, 'create'])->name('saldo_awal.create');
    Route::post('/saldo_awal/save', [SaldoAwalController::class, 'store'])->name('saldo_awal.save');
    Route::get('/saldo_awal/edit', [SaldoAwalController::class, 'edit'])->name('saldo_awal.edit');
    Route::put('/saldo_awal/update/{id}', [SaldoAwalController::class, 'update'])->name('saldo_awal.update');
    Route::delete('/saldo_awal/delete/{id}', [SaldoAwalController::class, 'destroy'])->name('saldo_awal.destroy');
    Route::get('/saldo_awal/akun_gl', [SaldoAwalController::class, 'akun_gl'])->name('saldo_awal.akun_gl');
    Route::get('/saldo_awal/entitas', [SaldoAwalController::class, 'entitas'])->name('saldo_awal.entitas');
    Route::get('/saldo_awal/form_import', [SaldoAwalController::class, 'form_import'])->name('saldo_awal.form_import');
    Route::get('/saldo_awal/template', [SaldoAwalController::class, 'downloadTemplate'])->name('saldo_awal.template');
    Route::post('/saldo_awal/import', [SaldoAwalController::class, 'import'])->name('saldo_awal.import');
});


/**
 * Route JURNAL
 */
use App\Http\Controllers\JurnalController;
Route::group(['middleware' => 'auth'], function () {
    Route::prefix('jurnal')->group(function() {
        Route::get('/partner/customer', [JurnalController::class, 'partner'])->name('jurnal.partner.customer')->defaults('jenis', 'customer');
        Route::get('/detail_transaksi', [JurnalController::class, 'detail_transaksi'])->name('jurnal.detail_transaksi');
        Route::put('/update/{id}/{jenis}', [JurnalController::class, 'update'])->name('jurnal.update');
        Route::delete('/delete/{id}', [JurnalController::class, 'destroy'])->name('jurnal.delete');
        Route::post('/posting', [JurnalController::class, 'posting'])->name('jurnal.posting');
        Route::post('/unposting', [JurnalController::class, 'unposting'])->name('jurnal.unposting');
        Route::post('/prepare_batch', [JurnalController::class, 'prepareBatch'])->name('jurnal.prepare_batch');
        Route::post('/posting_batch', [JurnalController::class, 'postingBatch'])->name('jurnal.posting_batch');
        Route::post('/unposting_batch', [JurnalController::class, 'unpostingBatch'])->name('jurnal.unposting_batch');
        Route::get('/piutang/datatable', [JurnalController::class, 'datatablePiutang'])->name('jurnal.piutang.datatable');


        Route::get('/pendapatan', [JurnalController::class, 'index'])->name('jurnal.pendapatan')->defaults('jenis', 'JP');
        Route::get('/pendapatan/create', [JurnalController::class, 'create'])->name('jurnal.pendapatan.create')->defaults('jenis', 'JP');
        Route::post('/pendapatan/store', [JurnalController::class, 'store'])->name('jurnal.pendapatan.save')->defaults('jenis', 'JP');
        Route::get('/pendapatan/edit', [JurnalController::class, 'edit'])->name('jurnal.pendapatan.edit')->defaults('jenis', 'JP');
        Route::get('/pendapatan/posting', [JurnalController::class, 'form_posting'])->name('jurnal.pendapatan.posting')->defaults('jenis', 'JP');
        Route::get('/pendapatan/unposting', [JurnalController::class, 'form_unposting'])->name('jurnal.pendapatan.unposting')->defaults('jenis', 'JP');
        
        Route::get('/kas-masuk', [JurnalController::class, 'index'])->name('jurnal.kasmasuk')->defaults('jenis', 'JKM');
        Route::get('/kas-masuk/create', [JurnalController::class, 'create'])->name('jurnal.kasmasuk.create')->defaults('jenis', 'JKM');
        Route::post('/kas-masuk/store', [JurnalController::class, 'store'])->name('jurnal.kasmasuk.save')->defaults('jenis', 'JKM');
        Route::get('/kas-masuk/edit', [JurnalController::class, 'edit'])->name('jurnal.kasmasuk.edit')->defaults('jenis', 'JKM');
        Route::get('/kas-masuk/posting', [JurnalController::class, 'form_posting'])->name('jurnal.kasmasuk.posting')->defaults('jenis', 'JKM');
        Route::get('/kas-masuk/unposting', [JurnalController::class, 'form_unposting'])->name('jurnal.kasmasuk.unposting')->defaults('jenis', 'JKM');

        Route::get('/kas-keluar', [JurnalController::class, 'index'])->name('jurnal.kaskeluar')->defaults('jenis', 'JKK');
        Route::get('/kas-keluar/create', [JurnalController::class, 'create'])->name('jurnal.kaskeluar.create')->defaults('jenis', 'JKK');
        Route::post('/kas-keluar/store', [JurnalController::class, 'store'])->name('jurnal.kaskeluar.save')->defaults('jenis', 'JKK');
        Route::get('/kas-keluar/edit', [JurnalController::class, 'edit'])->name('jurnal.kaskeluar.edit')->defaults('jenis', 'JKK');
        Route::get('/kas-keluar/posting', [JurnalController::class, 'form_posting'])->name('jurnal.kaskeluar.posting')->defaults('jenis', 'JKK');
        Route::get('/kas-keluar/unposting', [JurnalController::class, 'form_unposting'])->name('jurnal.kaskeluar.unposting')->defaults('jenis', 'JKK');

        Route::get('/penyesuaian', [JurnalController::class, 'index'])->name('jurnal.penyesuaian')->defaults('jenis', 'JN');
        Route::get('/penyesuaian/create', [JurnalController::class, 'create'])->name('jurnal.penyesuaian.create')->defaults('jenis', 'JN');
        Route::post('/penyesuaian/store', [JurnalController::class, 'store'])->name('jurnal.penyesuaian.save')->defaults('jenis', 'JN');
        Route::get('/penyesuaian/edit', [JurnalController::class, 'edit'])->name('jurnal.penyesuaian.edit')->defaults('jenis', 'JN');
        Route::get('/penyesuaian/posting', [JurnalController::class, 'form_posting'])->name('jurnal.penyesuaian.posting')->defaults('jenis', 'JN');
        Route::get('/penyesuaian/unposting', [JurnalController::class, 'form_unposting'])->name('jurnal.penyesuaian.unposting')->defaults('jenis', 'JN');
    });
});


/**
 * Route Piutang
 */
use App\Http\Controllers\PiutangController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/piutang/aging', [PiutangController::class, 'index'])->name('piutang.aging');
    Route::get('/piutang/aging/export', [PiutangController::class, 'agingPiutangExport'])->name('piutang.aging.export');
    Route::get('/piutang/daftar', [PiutangController::class, 'daftar'])->name('piutang.daftar');
    Route::get('/piutang/daftarexport', [PiutangController::class, 'exportExcel'])->name('piutang.daftar.export');
});


/**
 * Route Laporan Keuangan
 */
use App\Http\Controllers\LaporanKeuanganController;
Route::group(['middleware' => 'auth'], function () {
   Route::prefix('laporan')->group(function () {
        Route::get('neraca', [LaporanKeuanganController::class, 'indexNeraca'])->name('laporan.neraca');
        Route::get('neraca/data', [LaporanKeuanganController::class, 'dataNeraca'])->name('laporan.neraca.data');
        Route::get('neraca/export', [LaporanKeuanganController::class, 'exportNeraca'])->name('laporan.neraca.export');

        Route::get('laba-rugi', [LaporanKeuanganController::class, 'indexLabaRugi'])->name('laporan.laba_rugi');
        Route::get('laba-rugi/data', [LaporanKeuanganController::class, 'dataLabaRugi'])->name('laporan.laba_rugi.data');
        Route::get('laba-rugi/export', [LaporanKeuanganController::class, 'exportLabaRugi'])->name('laporan.laba_rugi.export');

        Route::get('cashflow', [LaporanKeuanganController::class, 'indexAruskas'])->name('laporan.arus_kas');
        Route::get('cashflow/data', [LaporanKeuanganController::class, 'dataArusKas'])->name('laporan.arus_kas.data');
        Route::get('cashflow/export', [LaporanKeuanganController::class, 'exportArusKas'])->name('laporan.arus_kas.export');

        Route::get('buku_besar', [LaporanKeuanganController::class, 'indexBukuBesar'])->name('laporan.bukubesar');
        Route::get('buku_besar/export', [LaporanKeuanganController::class, 'exportBukuBesar'])->name('laporan.bukubesar.export');
    });
});
