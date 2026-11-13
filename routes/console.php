<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tự động quét các lô hàng cận hạn sử dụng mỗi sáng lúc 06:00
Schedule::command('inventory:check-expiry')->dailyAt('06:00');
