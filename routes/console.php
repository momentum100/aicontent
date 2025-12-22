<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Clean stale jobs every 15 minutes
Schedule::command('queue:cleanup-stale --age=3600')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Prune old failed jobs daily at 2 AM (keep 7 days)
Schedule::command('queue:prune-failed --hours=168')
    ->dailyAt('02:00')
    ->onOneServer();

// Prune old job batches daily at 2:30 AM (keep 7 days)
Schedule::command('queue:prune-batches --hours=168')
    ->dailyAt('02:30')
    ->onOneServer();
