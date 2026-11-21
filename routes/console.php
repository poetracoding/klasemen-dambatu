<?php

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

Schedule::command('berita:notify-scheduled')->everyMinute()->withoutOverlapping();
