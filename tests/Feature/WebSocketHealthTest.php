<?php

namespace Tests\Feature;

use App\Services\ObservabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * WebSocketHealthTest — রিয়েল-টাইম ওয়েবসকেট ও রিভার্ব স্বাস্থ্য পরীক্ষা
 */
class WebSocketHealthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ওয়েবসকেট ও প্রেজেন্স মেট্রিক্স পর্যবেক্ষণ যাচাই।
     */
    public function test_websocket_health_and_connection_metrics_reporting(): void
    {
        $service = app(ObservabilityService::class);
        $metrics = $service->collectMetrics();

        $this->assertArrayHasKey('realtime', $metrics);
        $this->assertArrayHasKey('websocket_connections', $metrics['realtime']);
        $this->assertArrayHasKey('active_online_users', $metrics['realtime']);

        $this->assertGreaterThanOrEqual(0, $metrics['realtime']['websocket_connections']);
        $this->assertGreaterThanOrEqual(0, $metrics['realtime']['active_online_users']);
    }

    /**
     * রিভার্ব ব্রডকাস্টার কনফিগারেশন উপস্থিত থাকা যাচাই।
     */
    public function test_reverb_broadcasting_configuration_exists(): void
    {
        $connections = config('broadcasting.connections');

        $this->assertArrayHasKey('reverb', $connections);
        $this->assertEquals('reverb', $connections['reverb']['driver']);
    }
}
