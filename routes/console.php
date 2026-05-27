<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Закрытие просроченных чартов каждый час
Schedule::command('charts:close-expired')->hourly();
Schedule::command('metrica:sync-conversions --hours=6')->everyFourHours();
Schedule::command('charts:gift-votes --count=6 --votes=1 --exclude=30251')->dailyAt('14:00');