<?php

namespace App\Services\Contracts;

use Closure;

interface CacheServiceInterface
{
    /**
     * Retrieve an item from the cache.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store an item in the cache with a TTL (in seconds).
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * Determine if an item exists in the cache.
     */
    public function has(string $key): bool;

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key): bool;

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     */
    public function remember(string $key, int $ttl, Closure $callback): mixed;

    /**
     * Increment the value of an item in the cache.
     */
    public function increment(string $key, int $value = 1): int;

    /**
     * Decrement the value of an item in the cache.
     */
    public function decrement(string $key, int $value = 1): int;

    // --- Social Network Real-Time Key Schemas & Presence Methods ---

    /**
     * Mark a user as online (Redis key: user:{id}:online).
     */
    public function setUserOnline(int $userId, int $ttlSeconds = 45): void;

    /**
     * Register a heartbeat for a specific session/device ID.
     * Returns true if the user transitioned from offline to online.
     */
    public function registerSessionHeartbeat(int $userId, string $sessionId, int $ttlSeconds = 45): bool;

    /**
     * Remove a session/device heartbeat.
     * Returns true if the user transitioned from online to offline (no remaining active sessions).
     */
    public function removeSessionHeartbeat(int $userId, string $sessionId): bool;

    /**
     * Get the count of active sessions for a user.
     */
    public function getActiveSessionCount(int $userId): int;

    /**
     * Mark a user as offline.
     */
    public function setUserOffline(int $userId): void;

    /**
     * Check if a user is currently online.
     */
    public function isUserOnline(int $userId): bool;

    /**
     * Update user's last seen timestamp (Redis key: user:{id}:last_seen).
     */
    public function setUserLastSeen(int $userId): void;

    /**
     * Get user's last seen timestamp.
     */
    public function getUserLastSeen(int $userId): ?string;

    /**
     * Mark user as typing in a conversation (Redis key: conversation:{id}:typing:{user_id}).
     */
    public function setTyping(int $conversationId, int $userId, int $ttlSeconds = 5): void;

    /**
     * Check if user is typing in a conversation.
     */
    public function isTyping(int $conversationId, int $userId): bool;

    /**
     * Check if Redis backend is currently connected and healthy.
     */
    public function isHealthy(): bool;
}
