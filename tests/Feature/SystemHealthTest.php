<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SystemHealthTest — সিস্টেম স্বাস্থ্য ও মেট্রিক্স এন্ডপয়েন্ট টেস্ট
 */
class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * হেলথ চেক এন্ডপয়েন্ট রেসপন্স ফরম্যাট ও সার্ভিস স্ট্যাটাস যাচাই।
     */
    public function test_health_check_returns_operational_status_with_services(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'status',
                    'services' => [
                        'database' => ['status', 'driver', 'latency_ms'],
                        'redis_cache' => ['status', 'client', 'latency_ms'],
                        'queue' => ['status', 'default_connection', 'priorities'],
                        'storage' => ['status', 'disk', 'cdn_configured'],
                    ],
                    'timestamp',
                    'version',
                ],
                'message',
            ]);

        $this->assertContains($response->json('data.status'), ['operational', 'degraded']);
        $this->assertEquals('healthy', $response->json('data.services.database.status'));
        $this->assertEquals('healthy', $response->json('data.services.storage.status'));
    }

    /**
     * অ্যাডমিন মেট্রিক্স এন্ডপয়েন্ট ডাটা যাচাই।
     */
    public function test_admin_metrics_returns_memory_and_system_information(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/admin/metrics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'application' => ['name', 'environment', 'debug_mode', 'laravel_version', 'php_version'],
                    'memory' => ['current_usage_mb', 'peak_usage_mb', 'memory_limit'],
                    'system' => ['load_average', 'server_time', 'timezone'],
                    'queues' => ['connection', 'queues_monitored'],
                ],
                'message',
            ]);

        $this->assertGreaterThan(0, $response->json('data.memory.current_usage_mb'));
    }
}
