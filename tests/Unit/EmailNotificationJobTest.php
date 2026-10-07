<?php

namespace Tests\Unit;

use App\Jobs\DispatchNotificationJob;
use App\Jobs\SendEmailNotificationJob;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_email_notification_job_sends_email_successfully(): void
    {
        Mail::fake();

        $job = new SendEmailNotificationJob(
            toEmail: 'mizan@jugajug.com',
            subject: 'Welcome to Jugajug',
            messageBody: 'Your registration is complete.'
        );

        $job->handle();

        Mail::assertSentCount(1);
    }

    public function test_dispatch_notification_job_creates_database_record_and_increments_redis(): void
    {
        $user = User::factory()->create();
        $cacheService = app(CacheServiceInterface::class);
        $queueService = app(QueueServiceInterface::class);

        $job = new DispatchNotificationJob(
            recipientId: $user->id,
            type: 'notification.system',
            data: [
                'title' => 'System Alert',
                'message' => 'Scheduled maintenance in 2 hours.',
            ],
            channels: ['database']
        );

        $realtimeService = app(RealtimeServiceInterface::class);
        $job->handle($cacheService, $queueService, $realtimeService);

        // Verify database notification exists
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => 'notification.system',
        ]);

        // Verify Redis unread count incremented
        $count = $cacheService->get("notifications:user:{$user->id}");
        $this->assertEquals(1, (int) $count);
    }
}
