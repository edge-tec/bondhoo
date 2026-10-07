<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageAuditLog;
use App\Models\PageConversation;
use App\Models\PageEvent;
use App\Models\PageFollower;
use App\Models\PageMessage;
use App\Models\PageProduct;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * এন্টারপ্রাইজ সোশ্যাল পেজ সিস্টেম - রিয়েল পারফরম্যান্স ও লার্জ ডেটাসেট বেঞ্চমার্ক টেস্ট
 */
class EnterprisePageBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        $this->owner = User::factory()->create();
        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'name' => 'Benchmark Enterprise Hub',
            'slug' => 'benchmark-enterprise-hub',
            'username' => 'benchhub',
            'category' => 'Technology',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 500,
            'posts_count' => 300,
        ]);

        // ডেটাসেট সিডিং: পোস্ট, ফলোয়ার, মেসেজ, প্রোডাক্ট, ইভেন্ট, অডিট লগ
        $postsData = [];
        for ($i = 0; $i < 300; $i++) {
            $postsData[] = [
                'user_id' => $this->owner->id,
                'page_id' => $this->page->id,
                'content' => "Benchmark post content #{$i} with rich enterprise metadata and details.",
                'status' => $i < 50 ? 'scheduled' : 'published',
                'scheduled_at' => $i < 50 ? now()->addHours($i) : null,
                'likes_count' => rand(5, 50),
                'comments_count' => rand(1, 20),
                'created_at' => now()->subDays(rand(1, 60)),
                'updated_at' => now(),
            ];
        }
        Post::insert($postsData);

        // ফলোয়ার সিড
        $followerUsers = User::factory()->count(100)->create();
        $followersData = [];
        foreach ($followerUsers as $u) {
            $followersData[] = [
                'page_id' => $this->page->id,
                'user_id' => $u->id,
                'created_at' => now()->subDays(rand(1, 30)),
            ];
        }
        PageFollower::insert($followersData);

        // কনভারসেশন ও মেসেজ সিড
        $convData = [];
        for ($c = 1; $c <= 50; $c++) {
            $u = $followerUsers[$c - 1];
            $convData[] = [
                'id' => $c,
                'page_id' => $this->page->id,
                'user_id' => $u->id,
                'status' => 'open',
                'unread_page_count' => rand(0, 3),
                'unread_user_count' => 0,
                'last_message_at' => now()->subMinutes(rand(1, 500)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        PageConversation::insert($convData);

        // মেসেজসমূহ
        $messagesData = [];
        for ($m = 1; $m <= 200; $m++) {
            $messagesData[] = [
                'conversation_id' => ($m % 50) + 1,
                'sender_type' => $m % 2 === 0 ? 'page' : 'user',
                'sender_id' => $this->owner->id,
                'body' => "Message body #{$m} communicating details.",
                'is_read' => true,
                'created_at' => now()->subMinutes(rand(1, 1000)),
                'updated_at' => now(),
            ];
        }
        PageMessage::insert($messagesData);

        // প্রোডাক্টস ও ইভেন্টস
        $productsData = [];
        for ($p = 1; $p <= 40; $p++) {
            $productsData[] = [
                'page_id' => $this->page->id,
                'title' => "Product SKU #{$p}",
                'slug' => "product-sku-{$p}",
                'price' => rand(100, 2000),
                'currency' => 'BDT',
                'stock_quantity' => rand(5, 50),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        PageProduct::insert($productsData);

        $eventsData = [];
        for ($e = 1; $e <= 20; $e++) {
            $eventsData[] = [
                'page_id' => $this->page->id,
                'title' => "Enterprise Event #{$e}",
                'slug' => "enterprise-event-{$e}",
                'start_time' => now()->addDays($e),
                'status' => 'published',
                'rsvp_count' => rand(10, 100),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        PageEvent::insert($eventsData);

        // অডিট লগ সিড
        $auditData = [];
        for ($a = 1; $a <= 100; $a++) {
            $auditData[] = [
                'page_id' => $this->page->id,
                'actor_id' => $this->owner->id,
                'action' => 'benchmark.sample_action',
                'target_type' => 'Post',
                'target_id' => $a,
                'created_at' => now()->subHours($a),
            ];
        }
        PageAuditLog::insert($auditData);
    }

    /**
     * বেঞ্চমার্ক এক্সিকিউটর হেল্পার
     *
     * @return array{p50: float, p95: float, p99: float, avg_queries: int, iterations: int}
     */
    protected function benchmarkEndpoint(string $method, string $uri, array $data = [], int $iterations = 30): array
    {
        DB::enableQueryLog();
        $durations = [];
        $queryCounts = [];

        for ($i = 0; $i < $iterations; $i++) {
            DB::flushQueryLog();

            $start = microtime(true);
            $response = $this->actingAs($this->owner)->json($method, $uri, $data);
            $durationMs = (microtime(true) - $start) * 1000;

            $this->assertTrue(in_array($response->status(), [200, 201]), "Endpoint {$uri} returned status {$response->status()}");

            $durations[] = $durationMs;
            $queryCounts[] = count(DB::getQueryLog());
        }

        sort($durations);
        $count = count($durations);

        $p50Index = (int) floor($count * 0.50);
        $p95Index = (int) floor($count * 0.95);
        $p99Index = (int) floor($count * 0.99);

        return [
            'p50' => round($durations[$p50Index], 2),
            'p95' => round($durations[min($p95Index, $count - 1)], 2),
            'p99' => round($durations[min($p99Index, $count - 1)], 2),
            'avg_queries' => (int) round(array_sum($queryCounts) / $count),
            'iterations' => $iterations,
        ];
    }

    /**
     * টেস্ট: সকল ক্রিটিক্যাল এপিআই এন্ডপয়েন্টের ল্যাটেন্সি, P50, P95 ও কোয়েরি কাউন্ট পরিমাপ
     */
    public function test_enterprise_page_api_performance_benchmarks(): void
    {
        $benchmarks = [];

        // ১. পেজ ডিটেইলস / ড্যাশবোর্ড
        $benchmarks['page_show'] = $this->benchmarkEndpoint('GET', "/api/v2/pages/{$this->page->id}");

        // ২. পোস্ট ফিড (প্যাজিনেটেড)
        $benchmarks['page_posts'] = $this->benchmarkEndpoint('GET', "/api/v2/pages/{$this->page->id}/posts");

        // ৩. শিডিউলড পোস্ট লিস্ট
        $benchmarks['scheduled_posts'] = $this->benchmarkEndpoint('GET', "/api/v2/pages/{$this->page->id}/posts/scheduled");

        // ৪. ইনবক্স কনভারসেশনস (প্যাজিনেটেড)
        $benchmarks['inbox_conversations'] = $this->benchmarkEndpoint('GET', "/api/v2/pages/{$this->page->id}/inbox/conversations");

        // ৫. অ্যানালিটিক্স ওভারভিউ (রিয়েল এগ্রিগেশন)
        $benchmarks['analytics_overview'] = $this->benchmarkEndpoint('GET', "/api/v2/pages/{$this->page->id}/analytics/overview");

        // ৬. প্রোডাক্ট ক্যাটালগ
        $benchmarks['products_catalog'] = $this->benchmarkEndpoint('GET', "/api/v2/pages/{$this->page->id}/products");

        // ৭. ইভেন্টস ক্যাটালগ
        $benchmarks['events_catalog'] = $this->benchmarkEndpoint('GET', "/api/v2/pages/{$this->page->id}/events");

        // ৮. অডিট লগস
        $benchmarks['audit_logs'] = $this->benchmarkEndpoint('GET', "/api/v2/pages/{$this->page->id}/audit-logs");

        // ফলাফল আউটপুট ও অ্যাসার্শন
        echo "\n\n=== ENTERPRISE ADVANCED SOCIAL PAGE BENCHMARK RESULTS ===\n";
        echo sprintf("%-22s | %-8s | %-8s | %-8s | %-12s\n", 'Endpoint', 'P50 (ms)', 'P95 (ms)', 'P99 (ms)', 'Avg Queries');
        echo str_repeat('-', 65)."\n";

        foreach ($benchmarks as $name => $metrics) {
            echo sprintf(
                "%-22s | %-8.2f | %-8.2f | %-8.2f | %-12d\n",
                $name,
                $metrics['p50'],
                $metrics['p95'],
                $metrics['p99'],
                $metrics['avg_queries']
            );

            // Phase 9 Acceptance Target: Standard API reads P95 <= 500ms
            $this->assertLessThanOrEqual(500.0, $metrics['p95'], "Endpoint {$name} exceeded P95 SLA limit of 500ms");
            // No unbounded/excessive queries (e.g. <= 20 queries per request)
            $this->assertLessThanOrEqual(25, $metrics['avg_queries'], "Endpoint {$name} has excessive queries: {$metrics['avg_queries']}");
        }
        echo str_repeat('=', 65)."\n\n";
    }
}
