<?php

namespace App\Services;

use App\Services\Contracts\CacheServiceInterface;
use Closure;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RedisCacheService implements CacheServiceInterface
{
    protected bool $redisAvailable = true;

    /**
     * Safely run a Redis command or fallback to Laravel Cache store.
     */
    protected function executeRedis(Closure $redisCallback, Closure $fallbackCallback): mixed
    {
        if ($this->redisAvailable) {
            try {
                return $redisCallback();
            } catch (Exception $e) {
                $this->redisAvailable = false;
                Log::warning('Redis connection unavailable, degrading gracefully to default cache store.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $fallbackCallback();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->executeRedis(
            fn () => Redis::get($key) ?? $default,
            fn () => Cache::get($key, $default)
        );
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        return $this->executeRedis(
            function () use ($key, $value, $ttl) {
                if ($ttl > 0) {
                    return (bool) Redis::setex($key, $ttl, is_scalar($value) ? $value : serialize($value));
                }

                return (bool) Redis::set($key, is_scalar($value) ? $value : serialize($value));
            },
            fn () => Cache::put($key, $value, $ttl)
        );
    }

    public function has(string $key): bool
    {
        return $this->executeRedis(
            fn () => (bool) Redis::exists($key),
            fn () => Cache::has($key)
        );
    }

    public function forget(string $key): bool
    {
        return $this->executeRedis(
            fn () => (bool) Redis::del($key),
            fn () => Cache::forget($key)
        );
    }

    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $freshValue = $callback();
        $this->set($key, $freshValue, $ttl);

        return $freshValue;
    }

    public function increment(string $key, int $value = 1): int
    {
        return $this->executeRedis(
            fn () => (int) Redis::incrby($key, $value),
            fn () => (int) Cache::increment($key, $value)
        );
    }

    public function decrement(string $key, int $value = 1): int
    {
        return $this->executeRedis(
            fn () => (int) Redis::decrby($key, $value),
            fn () => (int) Cache::decrement($key, $value)
        );
    }

    // --- Presence & Real-time Key Implementations ---

    public function setUserOnline(int $userId, int $ttlSeconds = 45): void
    {
        $key = "user:{$userId}:online";
        $this->set($key, '1', $ttlSeconds);
        $this->setUserLastSeen($userId);
    }

    public function registerSessionHeartbeat(int $userId, string $sessionId, int $ttlSeconds = 45): bool
    {
        $wasOnline = $this->isUserOnline($userId);
        $sessionsKey = "user:{$userId}:sessions";
        $now = time();
        $expiresAt = $now + $ttlSeconds;

        $rawSessions = $this->get($sessionsKey);
        $sessions = is_array($rawSessions) ? $rawSessions : (is_string($rawSessions) ? (json_decode($rawSessions, true) ?: []) : []);

        // Prune expired sessions
        $validSessions = [];
        foreach ($sessions as $sid => $exp) {
            if ($exp > $now) {
                $validSessions[$sid] = (int) $exp;
            }
        }

        $validSessions[$sessionId] = $expiresAt;
        $this->set($sessionsKey, $validSessions, $ttlSeconds * 3);

        // Keep online presence active
        $this->setUserOnline($userId, $ttlSeconds);

        return ! $wasOnline;
    }

    public function removeSessionHeartbeat(int $userId, string $sessionId): bool
    {
        $sessionsKey = "user:{$userId}:sessions";
        $now = time();

        $rawSessions = $this->get($sessionsKey);
        $sessions = is_array($rawSessions) ? $rawSessions : (is_string($rawSessions) ? (json_decode($rawSessions, true) ?: []) : []);

        $validSessions = [];
        foreach ($sessions as $sid => $exp) {
            if ((string) $sid !== (string) $sessionId && $exp > $now) {
                $validSessions[$sid] = (int) $exp;
            }
        }

        if (count($validSessions) > 0) {
            $maxExp = max($validSessions);
            $remainingTtl = max(5, $maxExp - $now);
            $this->set($sessionsKey, $validSessions, $remainingTtl * 2);
            $this->setUserOnline($userId, $remainingTtl);

            return false; // Still online in another session/tab
        }

        // No active sessions remain, transition to offline
        $this->forget($sessionsKey);
        $this->forget("user:{$userId}:online");
        $this->setUserLastSeen($userId);

        return true;
    }

    public function getActiveSessionCount(int $userId): int
    {
        $sessionsKey = "user:{$userId}:sessions";
        $now = time();

        $rawSessions = $this->get($sessionsKey);
        $sessions = is_array($rawSessions) ? $rawSessions : (is_string($rawSessions) ? (json_decode($rawSessions, true) ?: []) : []);

        $count = 0;
        foreach ($sessions as $sid => $exp) {
            if ($exp > $now) {
                $count++;
            }
        }

        if ($count === 0 && $this->has("user:{$userId}:online")) {
            return 1;
        }

        return $count;
    }

    public function setUserOffline(int $userId): void
    {
        $this->forget("user:{$userId}:sessions");
        $this->forget("user:{$userId}:online");
        $this->setUserLastSeen($userId);
    }

    public function isUserOnline(int $userId): bool
    {
        if ($this->has("user:{$userId}:online")) {
            return true;
        }

        // Check if unexpired session exists
        $sessionsKey = "user:{$userId}:sessions";
        $rawSessions = $this->get($sessionsKey);
        if ($rawSessions) {
            $sessions = is_array($rawSessions) ? $rawSessions : (is_string($rawSessions) ? (json_decode($rawSessions, true) ?: []) : []);
            $now = time();
            foreach ($sessions as $exp) {
                if ($exp > $now) {
                    $this->set("user:{$userId}:online", '1', max(5, $exp - $now));

                    return true;
                }
            }
        }

        return false;
    }

    public function setUserLastSeen(int $userId): void
    {
        $timestamp = now()->toIso8601String();
        $this->set("user:{$userId}:last_seen", $timestamp, 86400 * 30);
    }

    public function getUserLastSeen(int $userId): ?string
    {
        $val = $this->get("user:{$userId}:last_seen");

        return $val ? (string) $val : null;
    }

    public function setTyping(int $conversationId, int $userId, int $ttlSeconds = 5): void
    {
        $key = "conversation:{$conversationId}:typing:{$userId}";
        $this->set($key, '1', $ttlSeconds);
    }

    public function isTyping(int $conversationId, int $userId): bool
    {
        return $this->has("conversation:{$conversationId}:typing:{$userId}");
    }

    public function isHealthy(): bool
    {
        try {
            Redis::ping();

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
