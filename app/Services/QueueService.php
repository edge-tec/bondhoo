<?php

namespace App\Services;

use App\Services\Contracts\QueueServiceInterface;
use Exception;
use Illuminate\Support\Facades\Queue;

class QueueService implements QueueServiceInterface
{
    public function dispatch(object $job): void
    {
        $this->dispatchToQueue($job, self::QUEUE_DEFAULT);
    }

    public function dispatchToQueue(object $job, string $queue): void
    {
        Queue::pushOn($queue, $job);
    }

    public function dispatchHigh(object $job): void
    {
        $this->dispatchToQueue($job, self::QUEUE_HIGH);
    }

    public function dispatchMedia(object $job): void
    {
        $this->dispatchToQueue($job, self::QUEUE_MEDIA);
    }

    public function dispatchLow(object $job): void
    {
        $this->dispatchToQueue($job, self::QUEUE_LOW);
    }

    public function isHealthy(): bool
    {
        try {
            Queue::size(self::QUEUE_DEFAULT);

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
