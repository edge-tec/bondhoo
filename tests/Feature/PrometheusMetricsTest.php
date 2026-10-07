<?php

namespace Tests\Feature;

use App\Services\ObservabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PrometheusMetricsTest — প্রমিথিউস স্ক্র্যাপিং ও মেট্রিক্স এক্সপোজিশন টেস্ট
 */
class PrometheusMetricsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * প্রমিথিউস মেট্রিক্স এন্ডপয়েন্ট সফল রেসপন্স ও কন্টেন্ট-টাইপ যাচাই।
     */
    public function test_prometheus_metrics_endpoint_returns_valid_text_format(): void
    {
        $response = $this->get('/api/v1/metrics');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));

        $content = $response->getContent();
        $this->assertStringContainsString('jugajug_cpu_load_1m', $content);
        $this->assertStringContainsString('jugajug_ram_usage_mb', $content);
        $this->assertStringContainsString('jugajug_database_latency_ms', $content);
        $this->assertStringContainsString('jugajug_queue_pending_jobs', $content);
        $this->assertStringContainsString('jugajug_online_users', $content);
        $this->assertStringContainsString('jugajug_websocket_connections', $content);
    }

    /**
     * অবজারভেবিলিটি সার্ভিস দ্বারা মেট্রিক্স কালেকশন অবজেক্ট যাচাই।
     */
    public function test_observability_service_collects_all_required_dimensions(): void
    {
        $service = app(ObservabilityService::class);
        $metrics = $service->collectMetrics();

        $this->assertArrayHasKey('system', $metrics);
        $this->assertArrayHasKey('database', $metrics);
        $this->assertArrayHasKey('redis', $metrics);
        $this->assertArrayHasKey('queues', $metrics);
        $this->assertArrayHasKey('realtime', $metrics);

        $this->assertGreaterThan(0, $metrics['system']['ram_usage_mb']);
    }
}
