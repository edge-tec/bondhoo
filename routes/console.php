<?php

use App\Services\Admin\ProductionReadinessService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:scheduler-heartbeat', function (ProductionReadinessService $readiness) {
    $ts = $readiness->recordSchedulerHeartbeat();
    $this->info("Scheduler heartbeat recorded at {$ts}");
})->purpose('Record operational scheduler heartbeat for SOC & Production Readiness monitoring');

Schedule::command('admin:scheduler-heartbeat')->everyMinute()->withoutOverlapping();
Schedule::command('stories:expire')->everyMinute();
Schedule::command('reels:expire')->everyMinute();
Schedule::command('media:cleanup-abandoned')->daily();
Schedule::command('media:recover-stuck-jobs')->everyFifteenMinutes();
Schedule::command('media:reconcile-storage --repair')->daily();
Schedule::command('pages:publish-scheduled')->everyMinute();
Schedule::command('live:prune-stale')->everyMinute();
Schedule::command('admin:aggregate-analytics')->dailyAt('00:00')->withoutOverlapping();
Schedule::command('admin:enforce-data-retention')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('jugajug:backup --type=db')->hourly()->withoutOverlapping();
