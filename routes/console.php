<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Cleanup activity logs older than 7 days every Sunday at 2 AM
Schedule::command('logs:cleanup-activity')->weekly()->sundays()->at('02:00');
