<?php

namespace App\Console\Commands;

use App\Services\Admin\AdminExecutiveAnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateDailyAnalyticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:aggregate-analytics {--date= : The date to aggregate (YYYY-MM-DD), defaults to yesterday}';

    /**
     * The console command description.
     */
    protected $description = 'Precalculate and aggregate enterprise daily analytics into persistent storage';

    /**
     * Execute the console command.
     */
    public function handle(AdminExecutiveAnalyticsService $analyticsService): int
    {
        $dateParam = $this->option('date');
        $date = $dateParam ? Carbon::parse($dateParam) : Carbon::yesterday();

        $this->info("Aggregating enterprise daily analytics for: {$date->toDateString()}");

        $aggregate = $analyticsService->aggregateDailyMetrics($date);

        $this->info("Successfully aggregated analytics for {$aggregate->date->toDateString()}: DAU={$aggregate->dau}, Posts={$aggregate->posts_count}, Registrations={$aggregate->registrations_count}");

        return self::SUCCESS;
    }
}
