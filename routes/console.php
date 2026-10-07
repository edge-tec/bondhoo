<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('stories:expire')->everyMinute();
Schedule::command('reels:expire')->everyMinute();
Schedule::command('media:cleanup-abandoned')->daily();
Schedule::command('media:recover-stuck-jobs')->everyFifteenMinutes();
Schedule::command('media:reconcile-storage --repair')->daily();
Schedule::command('pages:publish-scheduled')->everyMinute();
Schedule::command('live:prune-stale')->everyMinute();
