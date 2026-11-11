<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PertandinganController;
use App\Http\Controllers\Admin\TimController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/jadwal', [HomeController::class, 'jadwal'])->name('jadwal');
Route::get('/jadwal.php', function (\Illuminate\Http\Request $request) {
    return redirect()->to('/jadwal' . ($request->getQueryString() ? '?' . $request->getQueryString() : ''), 301);
});

Route::get('/jadwal-hari', [HomeController::class, 'jadwalHari'])->name('jadwal.hari');
Route::get('/jadwal_hari.php', fn() => redirect()->route('jadwal.hari'));

Route::get('/klasemen', [HomeController::class, 'klasemen'])->name('klasemen');
Route::get('/klasemen.php', function (\Illuminate\Http\Request $request) {
    return redirect()->to('/klasemen' . ($request->getQueryString() ? '?' . $request->getQueryString() : ''), 301);
});
Route::get('/history', [HomeController::class, 'history'])->name('history');
Route::get('/tim/{id}', [HomeController::class, 'timDetail'])->name('tim.detail');
Route::get('/pertandingan/{id}', [HomeController::class, 'pertandinganDetail'])->name('pertandingan.detail');

// Live HTML auto-update API endpoint (both paths supported)
Route::get('/api/check-update', [ApiController::class, 'checkUpdate'])->name('api.check_update');
Route::get('/api/check_update.php', [ApiController::class, 'checkUpdate']);

/*
|--------------------------------------------------------------------------
| Admin Auth Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/admin/login', [AuthController::class, 'showLogin']);
Route::post('/admin/login', [AuthController::class, 'login']);
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');
Route::match(['get', 'post'], '/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Admin Protected Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Tim Management
    Route::get('/tim', [TimController::class, 'index'])->name('tim.index');
    Route::post('/tim', [TimController::class, 'store'])->name('tim.store');
    Route::put('/tim/{id}', [TimController::class, 'update'])->name('tim.update');
    Route::delete('/tim/{id}', [TimController::class, 'destroy'])->name('tim.destroy');

    // Jadwal Management
    Route::get('/jadwal', [JadwalController::class, 'index'])->name('jadwal.index');
    Route::post('/jadwal/generate', [JadwalController::class, 'generate'])->name('jadwal.generate');
    Route::post('/jadwal/publikasi', [JadwalController::class, 'publikasi'])->name('jadwal.publikasi');
    Route::post('/jadwal/batal-publikasi', [JadwalController::class, 'batalPublikasi'])->name('jadwal.batal_publikasi');
    Route::post('/jadwal/batal', [JadwalController::class, 'batalJadwal'])->name('jadwal.batal');
    Route::post('/jadwal/geser', [JadwalController::class, 'geserJadwal'])->name('jadwal.geser');
    Route::post('/jadwal/slot/{id}', [JadwalController::class, 'updateSlot'])->name('jadwal.update_slot');

    // Input Hasil & Skor Pertandingan
    Route::get('/pertandingan', [PertandinganController::class, 'index'])->name('pertandingan.index');
    Route::post('/pertandingan/{id}', [PertandinganController::class, 'simpanHasil'])->name('pertandingan.simpan');

    // Pengaturan
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::post('/pengaturan/liga', [PengaturanController::class, 'updateLiga'])->name('pengaturan.update_liga');
    Route::post('/pengaturan/password', [PengaturanController::class, 'gantiPassword'])->name('pengaturan.ganti_password');
    Route::post('/pengaturan/backup', [PengaturanController::class, 'backup'])->name('pengaturan.backup');
    Route::post('/pengaturan/upload-db', [PengaturanController::class, 'uploadDb'])->name('pengaturan.upload_db');
});
