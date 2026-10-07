<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * InfrastructureDashboardTest — এন্টারপ্রাইজ ইনফ্রাস্ট্রাকচার ড্যাশবোর্ড ও এসআরই টেস্ট
 */
class InfrastructureDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * অ্যাডমিন ইনফ্রাস্ট্রাকচার ড্যাশবোর্ড ডেটা স্ট্রাকচার ও রেসপন্স যাচাই।
     */
    public function test_admin_infrastructure_dashboard_returns_full_metrics_payload(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/admin/infrastructure');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'monitoring' => ['system', 'database', 'redis', 'queues', 'realtime'],
                    'queue_infrastructure' => ['horizon_status', 'queues'],
                    'storage' => ['active_provider', 'image_variants', 'video_variants'],
                    'cdn' => ['cdn_url', 'cache_busting', 'image_optimization'],
                    'backups',
                    'security',
                    'deployment' => ['current_version', 'zero_downtime_ready'],
                ],
                'message',
            ]);

        $this->assertTrue($response->json('data.deployment.zero_downtime_ready'));
        $this->assertEquals('1.0.0-phase10', $response->json('data.deployment.current_version'));
    }

    /**
     * সিস্টেম স্ট্যাটাস এন্ডপয়েন্ট রেসপন্স যাচাই (/system/status)।
     */
    public function test_system_status_endpoint_returns_operational_response(): void
    {
        $response = $this->getJson('/api/v1/system/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'operational',
                ],
            ]);

        $this->assertNotNull($response->json('data.timestamp'));
    }

    /**
     * ক্যাশ ক্লিয়ার এপিআই যাচাই।
     */
    public function test_admin_can_clear_cache_via_api(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/admin/cache/clear');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['cleared' => true],
            ]);
    }
}
