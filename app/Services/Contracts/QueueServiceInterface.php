<?php

namespace App\Services\Contracts;

interface QueueServiceInterface
{
    public const QUEUE_HIGH = 'high';

    public const QUEUE_DEFAULT = 'default';

    public const QUEUE_MEDIA = 'media';

    public const QUEUE_LOW = 'low';

    /**
     * Dispatch a job to the default queue.
     */
    public function dispatch(object $job): void;

    /**
     * Dispatch a job to a specific priority queue.
     */
    public function dispatchToQueue(object $job, string $queue): void;

    /**
     * Dispatch to the HIGH priority queue.
     */
    public function dispatchHigh(object $job): void;

    /**
     * Dispatch to the MEDIA processing queue.
     */
    public function dispatchMedia(object $job): void;

    /**
     * Dispatch to the LOW priority queue.
     */
    public function dispatchLow(object $job): void;

    /**
     * Check if queue driver is healthy and accessible.
     */
    public function isHealthy(): bool;
}
