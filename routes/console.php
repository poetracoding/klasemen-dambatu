<?php

use App\Models\VisitorLog;
use App\Services\FcmService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('berita:notify-scheduled', function (FcmService $fcm) {
    $sent = $fcm->dispatchDueScheduledBeritaNotifications();
    $this->info("Notifikasi berita terjadwal diproses: {$sent} artikel.");
})->purpose('Kirim notifikasi FCM untuk berita terjadwal yang sudah waktunya terbit');

Artisan::command('visitor:prune {--days=90}', function () {
    $days = max(7, (int) $this->option('days'));
    $cutoff = now()->subDays($days)->toDateString();
    $deleted = VisitorLog::where('visit_date', '<', $cutoff)->delete();
    $this->info("Berhasil menghapus {$deleted} log pengunjung lebih lama dari {$days} hari.");
})->purpose('Bersihkan riwayat visitor_logs lama agar tabel tetap ringan');

Schedule::command('berita:notify-scheduled')->everyMinute()->withoutOverlapping();
