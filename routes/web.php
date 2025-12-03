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
 * Route Module
 */
use App\Http\Controllers\ModuleController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/module', [ModuleController::class, 'index'])->name('module');
    Route::get('/module/select', [ModuleController::class, 'select'])->name('module.select');
    Route::get('/module/detail', [ModuleController::class, 'detail_permission'])->name('module.detail_permission');
    Route::get('/module/add', [ModuleController::class, 'create'])->name('module.create');
    Route::post('/module/save', [ModuleController::class, 'store'])->name('module.save');
    Route::get('/module/edit', [ModuleController::class, 'edit'])->name('module.edit');
    Route::put('/module/update/{id}', [ModuleController::class, 'update'])->name('module.update');
    Route::delete('/module/delete/{id}', [ModuleController::class, 'destroy'])->name('module.destroy');
});

/**
 * Route Role
 */
use App\Http\Controllers\RoleController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/role', [RoleController::class, 'index'])->name('role');
    Route::get('/role/select', [RoleController::class, 'select'])->name('role.select');
    Route::get('/role/detail', [RoleController::class, 'detail_permission'])->name('role.detail_permission');
    Route::get('/role/add', [RoleController::class, 'create'])->name('role.create');
    Route::post('/role/save', [RoleController::class, 'store'])->name('role.save');
    Route::get('/role/edit', [RoleController::class, 'edit'])->name('role.edit');
    Route::put('/role/update/{id}', [RoleController::class, 'update'])->name('role.update');
    Route::delete('/role/delete/{id}', [RoleController::class, 'destroy'])->name('role.destroy');
});

/**
 * Route Users
 */
use App\Http\Controllers\UsersController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/users', [UsersController::class, 'index'])->name('users');
    Route::get('/users/add', [UsersController::class, 'create'])->name('users.create');
    Route::post('/users/save', [UsersController::class, 'store'])->name('users.save');
    Route::get('/users/edit', [UsersController::class, 'edit'])->name('users.edit');
    Route::put('/users/update/{id}', [UsersController::class, 'update'])->name('users.update');
    Route::delete('/users/delete/{id}', [UsersController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/get-role', [UsersController::class, 'getRole'])->name('users.getRole');
    Route::post('/users/save-role', [UsersController::class, 'saveRole'])->name('users.saveRole');
});

/**
 * Route Pengaturan Umum
 */
use App\Http\Controllers\PengaturanUmumController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/setting', [PengaturanUmumController::class, 'index'])->name('setting')->middleware('permission:setting.view');
    Route::post('/setting/save', [PengaturanUmumController::class, 'store'])->name('setting.save')->middleware('permission:setting.view');
});


/**
 * Route Master Data Entitas
 */
use App\Http\Controllers\M_EntitasController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/m_entitas/select', [M_EntitasController::class, 'entitas_select'])->name('entitas.select');
    Route::group(['middleware' => ['permission:entitas.view']], function () {
        Route::get('/m_entitas', [M_EntitasController::class, 'index'])->name('entitas');
    });

    Route::group(['middleware' => ['auth','permission:entitas.create']], function () {
        Route::get('/m_entitas/add', [M_EntitasController::class, 'create'])->name('entitas.create');
        Route::post('/m_entitas/save', [M_EntitasController::class, 'store'])->name('entitas.save');
    });

    Route::group(['middleware' => ['auth','permission:entitas.edit']], function () {
        Route::get('/m_entitas/edit', [M_EntitasController::class, 'edit'])->name('entitas.edit');
        Route::put('/m_entitas/update/{id}', [M_EntitasController::class, 'update'])->name('entitas.update');
    });
    Route::delete('/m_entitas/delete/{id}', [M_EntitasController::class, 'destroy'])->name('entitas.destroy')->middleware('permission:entitas.delete');
});

/**
 * Route Master Data Cabang
 */
use App\Http\Controllers\M_CabangController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/m_cabang/select', [M_CabangController::class, 'cabang_select'])->name('cabang.select');
    Route::group(['middleware' => ['auth','permission:m_cabang.view']], function () {
        Route::get('/m_cabang', [M_CabangController::class, 'index'])->name('cabang');
    });

    Route::group(['middleware' => ['auth','permission:m_cabang.create']], function () {
         Route::get('/m_cabang/add', [M_CabangController::class, 'create'])->name('cabang.create');
        Route::post('/m_cabang/save', [M_CabangController::class, 'store'])->name('cabang.save');
    });

    Route::group(['middleware' => ['auth','permission:m_cabang.edit']], function () {
        Route::get('/m_cabang/edit', [M_CabangController::class, 'edit'])->name('cabang.edit');
        Route::put('/m_cabang/update/{id}', [M_CabangController::class, 'update'])->name('cabang.update');
    });
    Route::delete('/m_cabang/delete/{id}', [M_CabangController::class, 'destroy'])->name('cabang.destroy')->middleware('permission:m_cabang.delete');
});


/**
 * Route Master Data PeriodeAkutansiController
 */
use App\Http\Controllers\PeriodeAkutansiController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/periode_akutansi', [PeriodeAkutansiController::class, 'index'])->name('periode_akuntansi')->middleware('permission:periode.view');
    Route::group(['middleware' => ['auth','permission:periode.create']], function () {
        Route::get('/periode_akutansi/add', [PeriodeAkutansiController::class, 'create'])->name('periode_akuntansi.create');
        Route::post('/periode_akutansi/save', [PeriodeAkutansiController::class, 'store'])->name('periode_akuntansi.save');
    });
    Route::group(['middleware' => ['auth','permission:periode.edit']], function () {
        Route::get('/periode_akutansi/edit', [PeriodeAkutansiController::class, 'edit'])->name('periode_akuntansi.edit');
        Route::put('/periode_akutansi/update/{id}', [PeriodeAkutansiController::class, 'update'])->name('periode_akuntansi.update');
    });
    Route::delete('/periode_akutansi/delete/{id}', [PeriodeAkutansiController::class, 'destroy'])->name('periode_akuntansi.destroy')->middleware('permission:periode.delete');
    Route::post('/periode_akuntansi/update-status/{id}', [PeriodeAkutansiController::class, 'updateStatus'])->name('periode_akuntansi.update_status')->middleware('permission:periode.open_close');
});



/**
 * Route Master Data Partner
 */
use App\Http\Controllers\M_PartnerController;
Route::group(['middleware' => ['auth', 'entitas_scope']], function () {
    Route::get('/m_partner/select', [M_PartnerController::class, 'partner_select'])->name('partner.select');
    Route::get('/m_partner', [M_PartnerController::class, 'index'])->name('partner')->middleware('permission:partner.view');
    Route::get('/m_partner/add', [M_PartnerController::class, 'create'])->name('partner.create')->middleware('permission:partner.create');
    Route::post('/m_partner/save', [M_PartnerController::class, 'store'])->name('partner.save')->middleware('permission:partner.create');
    Route::get('/m_partner/edit', [M_PartnerController::class, 'edit'])->name('partner.edit')->middleware('permission:partner.edit');
    Route::put('/m_partner/update/{id}', [M_PartnerController::class, 'update'])->name('partner.update')->middleware('permission:partner.edit');
    Route::delete('/m_partner/delete/{id}', [M_PartnerController::class, 'destroy'])->name('partner.destroy')->middleware('permission:partner.delete');
});


/**
 * Route Master Data M Akun GL
 */
use App\Http\Controllers\M_AkunGLController;
Route::group(['middleware' => 'auth'], function () {
    Route::get('/m_akun/search', [M_AkunGLController::class, 'search'])->name('m_akun.search');
    Route::get('/m_akun', [M_AkunGLController::class, 'index'])->name('m_akun')->middleware('permission:m_akun.view');
    Route::get('/m_akun/maping', [M_AkunGLController::class, 'map'])->name('m_akun.map')->middleware('permission:m_akun.view');
    Route::get('/m_akun/transaksi', [M_AkunGLController::class, 'transaksi'])->name('m_akun.transaksi')->middleware('permission:m_akun.view');
    Route::get('/m_akun/add', [M_AkunGLController::class, 'create'])->name('m_akun.create')->middleware('permission:m_akun.create');
    Route::post('/m_akun/save', [M_AkunGLController::class, 'store'])->name('m_akun.save')->middleware('permission:m_akun.create');
    Route::get('/m_akun/edit', [M_AkunGLController::class, 'edit'])->name('m_akun.edit')->middleware('permission:m_akun.edit');
    Route::put('/m_akun/update/{id}', [M_AkunGLController::class, 'update'])->name('m_akun.update')->middleware('permission:m_akun.edit');
    Route::delete('/m_akun/delete/{id}', [M_AkunGLController::class, 'destroy'])->name('m_akun.destroy')->middleware('permission:m_akun.delete');
    
});



/**
 * Route Saldo Awal
 */
use App\Http\Controllers\SaldoAwalController;
Route::group(['middleware' => ['auth','entitas_scope']], function () {
    Route::get('/saldo_awal/akun_gl', [SaldoAwalController::class, 'akun_gl'])->name('saldo_awal.akun_gl');
    Route::get('/saldo_awal/entitas', [SaldoAwalController::class, 'entitas'])->name('saldo_awal.entitas');

    Route::get('/saldo_awal', [SaldoAwalController::class, 'index'])->name('saldo_awal')->middleware('permission:m_saldo_awal.view');
    Route::get('/saldo_awal/add', [SaldoAwalController::class, 'create'])->name('saldo_awal.create')->middleware('permission:m_saldo_awal.create');
    Route::post('/saldo_awal/save', [SaldoAwalController::class, 'store'])->name('saldo_awal.save')->middleware('permission:m_saldo_awal.create');
    Route::get('/saldo_awal/edit', [SaldoAwalController::class, 'edit'])->name('saldo_awal.edit')->middleware('permission:m_saldo_awal.edit');
    Route::put('/saldo_awal/update/{id}', [SaldoAwalController::class, 'update'])->name('saldo_awal.update')->middleware('permission:m_saldo_awal.edit');
    Route::delete('/saldo_awal/delete/{id}', [SaldoAwalController::class, 'destroy'])->name('saldo_awal.destroy')->middleware('permission:m_saldo_awal.delete');
 

    Route::get('/saldo_awal/form_import', [SaldoAwalController::class, 'form_import'])->name('saldo_awal.form_import')->middleware('permission:m_saldo_awal.import');
    Route::get('/saldo_awal/template', [SaldoAwalController::class, 'downloadTemplate'])->name('saldo_awal.template')->middleware('permission:m_saldo_awal.import');
    Route::post('/saldo_awal/import', [SaldoAwalController::class, 'import'])->name('saldo_awal.import')->middleware('permission:m_saldo_awal.import');
});


/**
 * Route JURNAL
 */
use App\Http\Controllers\JurnalController;
Route::group(['middleware' => ['auth','entitas_scope']], function () {
    Route::prefix('jurnal')->group(function() {
        Route::get('/partner/customer', [JurnalController::class, 'partner'])->name('jurnal.partner.customer')->defaults('jenis', 'customer');
        Route::get('/detail_transaksi', [JurnalController::class, 'detail_transaksi'])->name('jurnal.detail_transaksi')->middleware('permission:kas_keluar.view|kas_masuk.view|pendapatan.view|penyesuaian.view');
        Route::put('/update/{id}/{jenis}', [JurnalController::class, 'update'])->name('jurnal.update')->middleware('permission:kas_keluar.edit|kas_masuk.edit|pendapatan.edit|penyesuaian.edit');
        Route::delete('/delete/{id}', [JurnalController::class, 'destroy'])->name('jurnal.delete')->middleware('permission:kas_keluar.delete|kas_masuk.delete|pendapatan.delete|penyesuaian.delete');
        Route::post('/posting', [JurnalController::class, 'posting'])->name('jurnal.posting')->middleware('permission:kas_keluar.posting|kas_masuk.posting|pendapatan.posting|penyesuaian.posting');
        Route::post('/unposting', [JurnalController::class, 'unposting'])->name('jurnal.unposting')->middleware('permission:kas_keluar.unposting|kas_masuk.unposting|pendapatan.unposting|penyesuaian.unposting');
        Route::post('/prepare_batch', [JurnalController::class, 'prepareBatch'])->name('jurnal.prepare_batch')->middleware('permission:kas_keluar.posting|kas_masuk.posting|pendapatan.posting|penyesuaian.posting');
        Route::post('/posting_batch', [JurnalController::class, 'postingBatch'])->name('jurnal.posting_batch')->middleware('permission:kas_keluar.posting|kas_masuk.posting|pendapatan.posting|penyesuaian.posting');
        Route::post('/unposting_batch', [JurnalController::class, 'unpostingBatch'])->name('jurnal.unposting_batch')->middleware('permission:kas_keluar.unposting|kas_masuk.unposting|pendapatan.unposting|penyesuaian.unposting');
        Route::get('/piutang/datatable', [JurnalController::class, 'datatablePiutang'])->name('jurnal.piutang.datatable')->middleware('permission:kas_keluar.view|kas_masuk.view|pendapatan.view|penyesuaian.view');


        Route::get('/pendapatan', [JurnalController::class, 'index'])->name('jurnal.pendapatan')->defaults('jenis', 'JP')->middleware('permission:pendapatan.view');
        Route::get('/pendapatan/create', [JurnalController::class, 'create'])->name('jurnal.pendapatan.create')->defaults('jenis', 'JP')->middleware('permission:pendapatan.create');
        Route::post('/pendapatan/store', [JurnalController::class, 'store'])->name('jurnal.pendapatan.save')->defaults('jenis', 'JP')->middleware('permission:pendapatan.create');
        Route::get('/pendapatan/edit', [JurnalController::class, 'edit'])->name('jurnal.pendapatan.edit')->defaults('jenis', 'JP')->middleware('permission:pendapatan.edit');
        Route::get('/pendapatan/posting', [JurnalController::class, 'form_posting'])->name('jurnal.pendapatan.posting')->defaults('jenis', 'JP')->middleware('permission:pendapatan.posting');
        Route::get('/pendapatan/unposting', [JurnalController::class, 'form_unposting'])->name('jurnal.pendapatan.unposting')->defaults('jenis', 'JP')->middleware('permission:pendapatan.unposting');
        
        Route::get('/kas-masuk', [JurnalController::class, 'index'])->name('jurnal.kasmasuk')->defaults('jenis', 'JKM')->middleware('permission:kas_masuk.view');
        Route::get('/kas-masuk/create', [JurnalController::class, 'create'])->name('jurnal.kasmasuk.create')->defaults('jenis', 'JKM')->middleware('permission:kas_masuk.create');
        Route::post('/kas-masuk/store', [JurnalController::class, 'store'])->name('jurnal.kasmasuk.save')->defaults('jenis', 'JKM')->middleware('permission:kas_masuk.create');
        Route::get('/kas-masuk/edit', [JurnalController::class, 'edit'])->name('jurnal.kasmasuk.edit')->defaults('jenis', 'JKM')->middleware('permission:kas_masuk.edit');
        Route::get('/kas-masuk/posting', [JurnalController::class, 'form_posting'])->name('jurnal.kasmasuk.posting')->defaults('jenis', 'JKM')->middleware('permission:kas_masuk.posting');
        Route::get('/kas-masuk/unposting', [JurnalController::class, 'form_unposting'])->name('jurnal.kasmasuk.unposting')->defaults('jenis', 'JKM')->middleware('permission:kas_masuk.unposting');

        Route::get('/kas-keluar', [JurnalController::class, 'index'])->name('jurnal.kaskeluar')->defaults('jenis', 'JKK')->middleware('permission:kas_keluar.view');
        Route::get('/kas-keluar/create', [JurnalController::class, 'create'])->name('jurnal.kaskeluar.create')->defaults('jenis', 'JKK')->middleware('permission:kas_keluar.create');
        Route::post('/kas-keluar/store', [JurnalController::class, 'store'])->name('jurnal.kaskeluar.save')->defaults('jenis', 'JKK')->middleware('permission:kas_keluar.create');
        Route::get('/kas-keluar/edit', [JurnalController::class, 'edit'])->name('jurnal.kaskeluar.edit')->defaults('jenis', 'JKK')->middleware('permission:kas_keluar.edit');
        Route::get('/kas-keluar/posting', [JurnalController::class, 'form_posting'])->name('jurnal.kaskeluar.posting')->defaults('jenis', 'JKK')->middleware('permission:kas_keluar.posting');
        Route::get('/kas-keluar/unposting', [JurnalController::class, 'form_unposting'])->name('jurnal.kaskeluar.unposting')->defaults('jenis', 'JKK')->middleware('permission:kas_keluar.unposting');

        Route::get('/penyesuaian', [JurnalController::class, 'index'])->name('jurnal.penyesuaian')->defaults('jenis', 'JN')->middleware('permission:penyesuaian.view');
        Route::get('/penyesuaian/create', [JurnalController::class, 'create'])->name('jurnal.penyesuaian.create')->defaults('jenis', 'JN')->middleware('permission:penyesuaian.create');
        Route::post('/penyesuaian/store', [JurnalController::class, 'store'])->name('jurnal.penyesuaian.save')->defaults('jenis', 'JN')->middleware('permission:penyesuaian.create');
        Route::get('/penyesuaian/edit', [JurnalController::class, 'edit'])->name('jurnal.penyesuaian.edit')->defaults('jenis', 'JN')->middleware('permission:penyesuaian.edit');
        Route::get('/penyesuaian/posting', [JurnalController::class, 'form_posting'])->name('jurnal.penyesuaian.posting')->defaults('jenis', 'JN')->middleware('permission:penyesuaian.posting');
        Route::get('/penyesuaian/unposting', [JurnalController::class, 'form_unposting'])->name('jurnal.penyesuaian.unposting')->defaults('jenis', 'JN')->middleware('permission:penyesuaian.unposting');
    });
});


/**
 * Route Piutang
 */
use App\Http\Controllers\PiutangController;
Route::group(['middleware' => ['auth','entitas_scope']], function () {
    Route::get('/piutang/aging', [PiutangController::class, 'index'])->name('piutang.aging')->middleware('permission:piutang.aging.view');
    Route::get('/piutang/aging/export', [PiutangController::class, 'agingPiutangExport'])->name('piutang.aging.export')->middleware('permission:piutang.aging.export');
    Route::get('/piutang/daftar', [PiutangController::class, 'daftar'])->name('piutang.daftar')->middleware('permission:piutang.daftar.view');
    Route::get('/piutang/daftarexport', [PiutangController::class, 'exportExcel'])->name('piutang.daftar.export')->middleware('permission:piutang.daftar.export');
});


/**
 * Route Laporan Keuangan
 */
use App\Http\Controllers\LaporanKeuanganController;
Route::group(['middleware' => 'auth'], function () {
   Route::prefix('laporan')->group(function () {
        Route::get('neraca', [LaporanKeuanganController::class, 'indexNeraca'])->name('laporan.neraca')->middleware('permission:neraca.view');
        Route::get('neraca/data', [LaporanKeuanganController::class, 'dataNeraca'])->name('laporan.neraca.data')->middleware('permission:neraca.view');
        Route::get('neraca/export', [LaporanKeuanganController::class, 'exportNeraca'])->name('laporan.neraca.export')->middleware('permission:neraca.export');

        Route::get('laba-rugi', [LaporanKeuanganController::class, 'indexLabaRugi'])->name('laporan.laba_rugi')->middleware('permission:pbl.view');
        Route::get('laba-rugi/data', [LaporanKeuanganController::class, 'dataLabaRugi'])->name('laporan.laba_rugi.data')->middleware('permission:pbl.view');
        Route::get('laba-rugi/export', [LaporanKeuanganController::class, 'exportLabaRugi'])->name('laporan.laba_rugi.export')->middleware('permission:pbl.export');

        Route::get('cashflow', [LaporanKeuanganController::class, 'indexAruskas'])->name('laporan.arus_kas')->middleware('permission:arus_kas.view');
        Route::get('cashflow/data', [LaporanKeuanganController::class, 'dataArusKas'])->name('laporan.arus_kas.data')->middleware('permission:arus_kas.view');
        Route::get('cashflow/export', [LaporanKeuanganController::class, 'exportArusKas'])->name('laporan.arus_kas.export')->middleware('permission:arusa_kas.export');

        Route::get('buku_besar', [LaporanKeuanganController::class, 'indexBukuBesar'])->name('laporan.bukubesar')->middleware('permission:buku_besar.index');
        Route::get('buku_besar/export', [LaporanKeuanganController::class, 'exportBukuBesar'])->name('laporan.bukubesar.export')->middleware('permission:buku_besar.export');
    });
});
