<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule meeting status updates to run every minute
Schedule::command('meetings:update-statuses')
    ->everyMinute()
    ->withoutOverlapping();

// Schedule email queue processing to run every minute (5 emails per run)
Schedule::command('email:process-queue --limit=5')
    ->everyMinute()
    ->withoutOverlapping();

// Schedule audit log cleanup to run daily at 2 AM
Schedule::command('audit:cleanup')->dailyAt('02:00');

// Schedule old data cleanup to run daily at 3 AM
Schedule::command('cleanup:old-data')->dailyAt('03:00');
