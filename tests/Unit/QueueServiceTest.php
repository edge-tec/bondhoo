<?php

namespace Tests\Unit;

use App\Services\Contracts\QueueServiceInterface;
use App\Services\QueueService;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DummyTestJob
{
    use Queueable;

    public function handle(): void
    {
        // Dummy handler
    }
}

class QueueServiceTest extends TestCase
{
    public function test_queue_service_dispatches_to_priority_queues(): void
    {
        Queue::fake();

        $queueService = new QueueService;
        $job = new DummyTestJob;

        $queueService->dispatchHigh($job);
        Queue::assertPushedOn(QueueServiceInterface::QUEUE_HIGH, DummyTestJob::class);

        $queueService->dispatchMedia($job);
        Queue::assertPushedOn(QueueServiceInterface::QUEUE_MEDIA, DummyTestJob::class);

        $queueService->dispatchLow($job);
        Queue::assertPushedOn(QueueServiceInterface::QUEUE_LOW, DummyTestJob::class);

        $queueService->dispatch($job);
        Queue::assertPushedOn(QueueServiceInterface::QUEUE_DEFAULT, DummyTestJob::class);
    }
}
