<?php

namespace App\Jobs;

use App\Models\ProfileView;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class RecordProfileViewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $ownerId,
        public ?int $viewerId,
        public ?string $ipHash,
        public ?string $ipAddress,
        public ?string $userAgent,
        public string $deviceType,
        public string $source,
        public ?string $referer,
        public bool $isAnonymous,
        public Carbon|string $viewedAt
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $viewedAt = is_string($this->viewedAt) ? Carbon::parse($this->viewedAt) : $this->viewedAt;

        // Persist view to database
        ProfileView::create([
            'user_id' => $this->ownerId,
            'viewer_id' => $this->viewerId,
            'is_anonymous' => $this->isAnonymous,
            'ip_address' => $this->ipAddress,
            'ip_hash' => $this->ipHash,
            'user_agent' => $this->userAgent ? substr($this->userAgent, 0, 255) : null,
            'device_type' => $this->deviceType,
            'source' => $this->source,
            'referer' => $this->referer ? substr($this->referer, 0, 255) : null,
            'viewed_at' => $viewedAt,
        ]);

        // Redis real-time aggregation (graceful fallback if Redis is not active/available)
        try {
            if (config('database.redis.default') && class_exists(Redis::class)) {
                $today = $viewedAt->format('Y-m-d');
                Redis::hIncrBy("profile:analytics:{$this->ownerId}", 'total_views', 1);
                Redis::hIncrBy("profile:analytics:{$this->ownerId}:daily", $today, 1);

                $uniqueId = $this->viewerId ? "user_{$this->viewerId}" : ($this->ipHash ? "anon_{$this->ipHash}" : null);
                if ($uniqueId) {
                    Redis::sAdd("profile:unique:{$this->ownerId}", $uniqueId);
                }
            }
        } catch (\Throwable) {
            // Ignore Redis connection failures and rely on database & Cache fallback
        }

        // Invalidate cached analytics for the profile owner
        Cache::forget("profile_analytics_{$this->ownerId}");
    }
}
