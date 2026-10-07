<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

class DistributedCacheClusterService
{
    /**
     * Format a Redis Cluster slot hashtag to pin co-located keys to the same cluster shard.
     */
    public function formatClusterKey(string $slotTag, string $subKey): string
    {
        return "{{$slotTag}}:{$subKey}";
    }

    /**
     * Retrieve or compute with cache stampede protection via atomic lock.
     *
     * @param  \Closure(): mixed  $callback
     */
    public function rememberWithStampedeProtection(string $key, int $ttlSeconds, \Closure $callback): mixed
    {
        if (Cache::has($key)) {
            return Cache::get($key);
        }

        $lockKey = "lock:{$key}";
        $lock = Cache::lock($lockKey, 10);

        if ($lock->get()) {
            try {
                // Double check after acquiring lock
                if (Cache::has($key)) {
                    return Cache::get($key);
                }

                $data = $callback();
                Cache::put($key, $data, $ttlSeconds);

                return $data;
            } finally {
                $lock->release();
            }
        }

        // Another worker is generating; wait brief moment and read
        usleep(150000);

        return Cache::get($key, $callback);
    }

    /**
     * Warm up cache for prominent entities (e.g. public feed, trending topics).
     *
     * @param  array<string, \Closure(): mixed>  $warmupDefinitions
     * @return array<string, string>
     */
    public function warmUp(array $warmupDefinitions): array
    {
        $status = [];
        foreach ($warmupDefinitions as $key => $closure) {
            $data = $closure();
            Cache::put($key, $data, 3600);
            $status[$key] = 'warmed';
        }

        return $status;
    }

    /**
     * Invalidate cached tags/prefixes across the cluster.
     *
     * @param  string[]  $keys
     */
    public function invalidateKeys(array $keys): int
    {
        $count = 0;
        foreach ($keys as $k) {
            if (Cache::forget($k)) {
                $count++;
            }
        }

        return $count;
    }
}
