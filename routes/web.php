<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BeritaController as AdminBeritaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PertandinganController;
use App\Http\Controllers\Admin\PosterController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TimController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\BeritaController;
use App\Http\Controllers\FcmController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PdfController;
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
Route::get('/perbandingan', [HomeController::class, 'perbandingan'])->name('perbandingan');
Route::get('/klasemen.php', function (\Illuminate\Http\Request $request) {
    return redirect()->to('/klasemen' . ($request->getQueryString() ? '?' . $request->getQueryString() : ''), 301);
});
Route::get('/history', [HomeController::class, 'history'])->name('history');
Route::get('/tentang', [HomeController::class, 'tentang'])->name('tentang');
Route::get('/berita', [BeritaController::class, 'index'])->name('berita.index');
Route::get('/berita/{slug}', [BeritaController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('berita.show');
Route::post('/berita/{slug}/like', [BeritaController::class, 'toggleLike'])
    ->where('slug', '[a-z0-9-]+')
    ->middleware('throttle:30,1')
    ->name('berita.like');
Route::get('/games', [HomeController::class, 'gamesHub'])->name('games.hub');
Route::get('/game', [HomeController::class, 'game'])->name('game');
Route::get('/game/monopoli', [HomeController::class, 'monopoli'])->name('game.monopoli');
Route::get('/game/flappy-bird', [HomeController::class, 'flappyBird'])->name('game.flappy_bird');
Route::get('/game/ludo-queen', [HomeController::class, 'ludoQueen'])->name('game.ludo_queen');
Route::get('/game/catur', [HomeController::class, 'catur'])->name('game.catur');
Route::get('/tim/{id}', [HomeController::class, 'timDetail'])->name('tim.detail');
Route::get('/pertandingan/{id}', [HomeController::class, 'pertandinganDetail'])->name('pertandingan.detail');
Route::post('/pertandingan/{id}/vote', [HomeController::class, 'voteMatch'])
    ->middleware('throttle:30,1')
    ->name('pertandingan.vote');
Route::post('/pertandingan/{id}/cheer', [HomeController::class, 'cheerMatch'])
    ->middleware('throttle:40,1')
    ->name('pertandingan.cheer');

// Cetak & Download PDF (DomPDF)
Route::get('/cetak/klasemen', [PdfController::class, 'klasemen'])->name('klasemen.pdf');
Route::get('/cetak/jadwal', [PdfController::class, 'jadwal'])->name('jadwal.pdf');
Route::get('/cetak/tim', [PdfController::class, 'tim'])->name('tim.pdf');

// Live HTML auto-update API endpoint (both paths supported)
Route::get('/api/check-update', [ApiController::class, 'checkUpdate'])->name('api.check_update');
Route::get('/api/check_update.php', [ApiController::class, 'checkUpdate']);

// FCM Web Push & PWA Manifest
Route::get('/firebase-messaging-sw.js', [FcmController::class, 'serviceWorker'])->name('fcm.sw');
Route::get('/manifest.json', [FcmController::class, 'manifest'])->name('fcm.manifest');
Route::post('/api/fcm/subscribe', [FcmController::class, 'subscribe'])->middleware('throttle:20,1')->name('fcm.subscribe');
Route::post('/api/fcm/unsubscribe', [FcmController::class, 'unsubscribe'])->middleware('throttle:20,1')->name('fcm.unsubscribe');

/*
|--------------------------------------------------------------------------
| Admin Auth Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
Route::get('/admin/login', [AuthController::class, 'showLogin']);
Route::post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Admin Protected Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/tren-tim', [DashboardController::class, 'trenTim'])->name('tren_tim.index');

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

    // Poster Informasi
    Route::get('/poster', [PosterController::class, 'index'])->name('poster.index');
    Route::post('/poster', [PosterController::class, 'store'])->name('poster.store');
    Route::put('/poster/{id}', [PosterController::class, 'update'])->name('poster.update');
    Route::post('/poster/{id}/toggle', [PosterController::class, 'toggleStatus'])->name('poster.toggle');
    Route::delete('/poster/{id}', [PosterController::class, 'destroy'])->name('poster.destroy');

    // Kelola Berita
    Route::get('/berita', [AdminBeritaController::class, 'index'])->name('berita.index');
    Route::post('/berita', [AdminBeritaController::class, 'store'])->name('berita.store');
    Route::put('/berita/{id}', [AdminBeritaController::class, 'update'])->name('berita.update');
    Route::post('/berita/{id}/toggle', [AdminBeritaController::class, 'toggleStatus'])->name('berita.toggle');
    Route::post('/berita/{id}/publish-now', [AdminBeritaController::class, 'publishNow'])->name('berita.publish_now');
    Route::delete('/berita/{id}', [AdminBeritaController::class, 'destroy'])->name('berita.destroy');

    // Pengaturan
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::get('/pengaturan/password', [PengaturanController::class, 'password'])->name('pengaturan.password');
    Route::get('/pengaturan/backup-restore', [PengaturanController::class, 'backupRestore'])->name('pengaturan.backup_restore');
    Route::get('/pengaturan/analytics', [PengaturanController::class, 'analytics'])->name('pengaturan.analytics');
    Route::post('/pengaturan/analytics/reset', [PengaturanController::class, 'resetAnalytics'])->name('pengaturan.analytics.reset');
    Route::get('/pengaturan/report', [ReportController::class, 'index'])->name('pengaturan.report');
    Route::get('/pengaturan/report/pdf', [ReportController::class, 'pdf'])->name('pengaturan.report.pdf');
    Route::get('/pengaturan/monopoli', fn () => redirect()->route('game.monopoli'))->name('pengaturan.monopoli');
    Route::get('/monopoli', fn () => redirect()->route('game.monopoli'))->name('monopoli');
    Route::get('/pengaturan/flappy-bird', fn () => redirect()->route('game.flappy_bird'))->name('pengaturan.flappy_bird');
    Route::get('/flappy-bird', fn () => redirect()->route('game.flappy_bird'))->name('flappy_bird');
    Route::get('/pengaturan/ludo-queen', fn () => redirect()->route('game.ludo_queen'))->name('pengaturan.ludo_queen');
    Route::get('/ludo-queen', fn () => redirect()->route('game.ludo_queen'))->name('ludo_queen');
    Route::get('/pengaturan/catur', fn () => redirect()->route('game.catur'))->name('pengaturan.catur');
    Route::get('/catur', fn () => redirect()->route('game.catur'))->name('catur');
    Route::post('/pengaturan/liga', [PengaturanController::class, 'updateLiga'])->name('pengaturan.update_liga');
    Route::post('/pengaturan/test-fcm', [PengaturanController::class, 'testFcm'])->name('pengaturan.test_fcm');
    Route::post('/pengaturan/password', [PengaturanController::class, 'gantiPassword'])->name('pengaturan.ganti_password');
    Route::post('/pengaturan/backup', [PengaturanController::class, 'backup'])->name('pengaturan.backup');
    Route::post('/pengaturan/upload-db', [PengaturanController::class, 'uploadDb'])->name('pengaturan.upload_db');
});
