<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\QueueManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HorizonQueueTest — হরাইজন ও কিউ সুপারভাইজার ম্যানেজমেন্ট টেস্ট
 */
class HorizonQueueTest extends TestCase
{
    use RefreshDatabase;

    /**
     * কিউ ওয়ার্কার রিস্টার্ট সিগন্যাল যাচাই।
     */
    public function test_admin_can_dispatch_queue_restart_signal(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/admin/queue/restart');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['restarted' => true],
            ]);
    }

    /**
     * কিউ পজ ও রিজিউম মেকানিজম যাচাই।
     */
    public function test_queue_pause_and_resume_operations(): void
    {
        $service = app(QueueManagementService::class);

        $this->assertFalse($service->isQueuePaused('media'));

        $service->pauseQueue('media');
        $this->assertTrue($service->isQueuePaused('media'));

        $service->resumeQueue('media');
        $this->assertFalse($service->isQueuePaused('media'));
    }

    /**
     * কিউ কনফিগারেশনে সমস্ত আবশ্যক কিউ উপস্থিত থাকা যাচাই।
     */
    public function test_horizon_config_defines_all_enterprise_queues(): void
    {
        $defaults = config('horizon.defaults.supervisor-high.queue');
        $this->assertContains('high', $defaults);
        $this->assertContains('notifications', $defaults);
        $this->assertContains('emails', $defaults);

        $defaultQueues = config('horizon.defaults.supervisor-default.queue');
        $this->assertContains('default', $defaultQueues);
        $this->assertContains('analytics', $defaultQueues);
        $this->assertContains('search', $defaultQueues);
        $this->assertContains('stories', $defaultQueues);

        $mediaQueues = config('horizon.defaults.supervisor-media.queue');
        $this->assertContains('media', $mediaQueues);
    }
}
