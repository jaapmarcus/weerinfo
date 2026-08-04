<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('weather:daily-report')
    ->dailyAt('06:00')
    ->timezone('Europe/Amsterdam')
    ->appendOutputTo(storage_path('logs/weather-daily-report.log'));
