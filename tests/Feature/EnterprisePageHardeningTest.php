<?php

namespace Tests\Feature;

use App\Events\PageConversationAssignedEvent;
use App\Events\PageNewMessageEvent;
use App\Events\PagePostPublishedEvent;
use App\Models\Page;
use App\Models\PageConversation;
use App\Models\PageMember;
use App\Models\PageProduct;
use App\Models\Post;
use App\Models\User;
use App\Services\Page\EnterprisePageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * এন্টারপ্রাইজ সোশ্যাল পেজ সিস্টেমের হার্ডেনিং, কনকারেন্সি, সিকিউরিটি এবং জিরো-ডেমো টেস্ট স্যুট
 */
class EnterprisePageHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected EnterprisePageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EnterprisePageService::class);
    }

    protected function createPage(User $owner, array $attributes = []): Page
    {
        static $counter = 1;
        $counter++;

        return Page::create(array_merge([
            'owner_id' => $owner->id,
            'name' => "Enterprise Page {$counter}",
            'slug' => "enterprise-page-{$counter}",
            'username' => "page{$counter}",
            'category' => 'Business',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 0,
            'posts_count' => 0,
        ], $attributes));
    }

    /**
     * টেস্ট ১: ১০০টি শিডিউলড পোস্ট একাধিক কনকারেন্ট ওয়ার্কার দ্বারা এক্সাক্টলি-ওয়ানস পাবলিশ হয়
     */
    public function test_concurrent_scheduled_posts_publish_exactly_once_without_duplicates(): void
    {
        Event::fake([PagePostPublishedEvent::class]);

        $owner = User::factory()->create();
        $page = $this->createPage($owner, ['posts_count' => 0]);

        // ১০০টি শিডিউলড পোস্ট তৈরি
        for ($i = 0; $i < 100; $i++) {
            Post::create([
                'user_id' => $owner->id,
                'page_id' => $page->id,
                'content' => "Scheduled batch post #{$i}",
                'status' => 'scheduled',
                'scheduled_at' => now()->subMinutes(rand(1, 60)),
            ]);
        }

        $this->assertEquals(100, Post::where('page_id', $page->id)->where('status', 'scheduled')->count());

        // প্রথমবার পাবলিশ চালানো
        $this->artisan('pages:publish-scheduled')->assertSuccessful();

        // অবিলম্বে দ্বিতীয়বার রান করানো (কনকারেন্সি সেফটি এবং আইডেমপোটেন্সি যাচাই)
        $this->artisan('pages:publish-scheduled')->expectsOutput('0 scheduled post(s) published successfully.')->assertSuccessful();

        // যাচাই: সব পোস্ট প্রকাশিত হয়েছে এবং posts_count ১০০ হয়েছে
        $this->assertEquals(0, Post::where('page_id', $page->id)->where('status', 'scheduled')->count());
        $this->assertEquals(100, Post::where('page_id', $page->id)->where('status', 'published')->count());
        $this->assertEquals(100, $page->fresh()->posts_count);

        Event::assertDispatched(PagePostPublishedEvent::class, 100);
    }

    /**
     * টেস্ট ২: ইনভেন্টরি কনকারেন্সি রেস কন্ডিশন (১টি অবশিষ্ট স্টকের জন্য একাধিক ক্রেতার চেষ্টা, স্টক কখনোই নেগেটিভ হবে না)
     */
    public function test_inventory_race_condition_protection_prevents_negative_stock(): void
    {
        $owner = User::factory()->create();
        $page = $this->createPage($owner);

        $product = PageProduct::create([
            'page_id' => $page->id,
            'title' => 'Limited Edition Item',
            'slug' => 'limited-edition-item',
            'price' => 500.00,
            'currency' => 'BDT',
            'stock_quantity' => 1,
            'status' => 'active',
        ]);

        $buyers = User::factory()->count(10)->create();

        $successCount = 0;
        $failCount = 0;

        foreach ($buyers as $buyer) {
            $response = $this->actingAs($buyer)->postJson("/api/v2/pages/{$page->id}/products/{$product->id}/purchase", [
                'quantity' => 1,
            ]);

            if ($response->status() === 200 && $response->json('success') === true) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        // ঠিক ১টি রিকোয়েস্ট সফল হবে এবং বাকি ৯টি ব্যর্থ হবে
        $this->assertEquals(1, $successCount);
        $this->assertEquals(9, $failCount);

        $freshProduct = $product->fresh();
        $this->assertEquals(0, $freshProduct->stock_quantity);
        $this->assertEquals('out_of_stock', $freshProduct->status);
        $this->assertGreaterThanOrEqual(0, $freshProduct->stock_quantity);
    }

    /**
     * টেস্ট ৩: স্টাফ নোটস সিকিউরিটি — সাধারণ ভিজিটর কখনোই ইন্টারনাল স্টাফ নোট দেখতে পাবে না
     */
    public function test_internal_staff_notes_are_never_exposed_to_visitors(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $visitor = User::factory()->create();

        $page = $this->createPage($owner);
        PageMember::create([
            'page_id' => $page->id,
            'user_id' => $staff->id,
            'role' => Page::ROLE_MODERATOR,
            'status' => 'active',
        ]);

        // ভিজিটর পেজে মেসেজ পাঠালো
        $this->actingAs($visitor)->postJson("/api/v2/pages/{$page->id}/inbox/message", [
            'body' => 'Hello, I have a confidential inquiry.',
        ])->assertStatus(201);

        $conversation = PageConversation::where('page_id', $page->id)->where('user_id', $visitor->id)->first();
        $this->assertNotNull($conversation);

        // স্টাফ মেম্বার ইন্টারনাল কনফিডেনশিয়াল নোট যুক্ত করলো
        $this->actingAs($staff)->postJson("/api/v2/pages/{$page->id}/inbox/conversations/{$conversation->id}/notes", [
            'body' => 'CONFIDENTIAL: High priority VIP customer. Internal note only.',
        ])->assertStatus(201);

        // ১. ভিজিটর my-thread এন্ডপয়েন্ট দিয়ে নিজের মেসেজ দেখবে
        $visitorResponse = $this->actingAs($visitor)->getJson("/api/v2/pages/{$page->id}/inbox/my-thread");
        $visitorResponse->assertStatus(200);

        // নিশ্চিত করা যে ইন্টারনাল নোট কোনোভাবেই ভিজিটরের রেসপন্সে নেই
        $visitorResponse->assertDontSee('CONFIDENTIAL: High priority VIP customer');
        $this->assertArrayNotHasKey('notes', $visitorResponse->json('data'));

        // ২. ভিজিটর যদি স্টাফ-অনলি শো এন্ডপয়েন্ট বা নোটস এন্ডপয়েন্ট অ্যাক্সেস করতে চায় তবে 403 Forbidden হবে
        $this->actingAs($visitor)->getJson("/api/v2/pages/{$page->id}/inbox/conversations/{$conversation->id}")
            ->assertStatus(403);

        $this->actingAs($visitor)->postJson("/api/v2/pages/{$page->id}/inbox/conversations/{$conversation->id}/notes", [
            'body' => 'Malicious note injection',
        ])->assertStatus(403);
    }

    /**
     * টেস্ট ৪: ক্রস-পেজ বাউন্ডারি এবং IDOR প্রিভেনশন (Page A বনাম Page B)
     */
    public function test_cross_page_tenant_isolation_prevents_idor(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $pageA = $this->createPage($ownerA);
        $pageB = $this->createPage($ownerB);

        $visitor = User::factory()->create();
        $convB = PageConversation::create([
            'page_id' => $pageB->id,
            'user_id' => $visitor->id,
            'status' => 'open',
        ]);

        $productB = PageProduct::create([
            'page_id' => $pageB->id,
            'title' => 'Product B',
            'slug' => 'product-b',
            'price' => 100,
            'currency' => 'BDT',
            'stock_quantity' => 5,
        ]);

        // ১. Page A-র ওনার Page B-র ইনবক্স দেখতে পারবে না
        $this->actingAs($ownerA)->getJson("/api/v2/pages/{$pageB->id}/inbox/conversations")
            ->assertStatus(403);

        // ২. Page A-র ওনার Page B-র অডিট লগ দেখতে পারবে না
        $this->actingAs($ownerA)->getJson("/api/v2/pages/{$pageB->id}/audit-logs")
            ->assertStatus(403);

        // ৩. Page A-র ওনার Page B-র প্রোডাক্ট এডিট করতে পারবে না
        $this->actingAs($ownerA)->putJson("/api/v2/pages/{$pageB->id}/products/{$productB->id}", [
            'title' => 'Hacked Title',
        ])->assertStatus(403);

        // ৪. Page A-র ইউআরএল দিয়ে Page B-র কনভারসেশন খুঁজতে গেলে 404 হবে
        $this->actingAs($ownerA)->getJson("/api/v2/pages/{$pageA->id}/inbox/conversations/{$convB->id}")
            ->assertStatus(404);
    }

    /**
     * টেস্ট ৫: রিয়েল অ্যানালিটিক্স ও জিরো ফেক ডেটা — খালি অবস্থায় কোনো মক/হার্ডকোড ডেটা নেই
     */
    public function test_real_analytics_returns_zero_and_empty_on_empty_dataset_without_fake_data(): void
    {
        $owner = User::factory()->create();
        $page = $this->createPage($owner);

        $response = $this->actingAs($owner)->getJson("/api/v2/pages/{$page->id}/analytics/overview?days=30");
        $response->assertStatus(200);

        $summary = $response->json('data.summary');
        $this->assertEquals(0, $summary['total_followers']);
        $this->assertEquals(0, $summary['total_posts']);
        $this->assertEquals(0, $summary['total_likes']);
        $this->assertEquals(0, $summary['total_comments']);
        $this->assertEquals('0%', $summary['engagement_rate']);

        // অডিয়েন্স ইনসাইটসে কোনো ফেক শহর থাকবে না
        $audienceResponse = $this->actingAs($owner)->getJson("/api/v2/pages/{$page->id}/analytics/audience");
        $audienceResponse->assertStatus(200);
        $this->assertEquals([], $audienceResponse->json('data.top_cities'));
        $this->assertEquals(0, $audienceResponse->json('data.total_followers'));
    }

    /**
     * টেস্ট ৬: অডিট লগ ট্যাম্পার রেজিস্ট্যান্স — এপিআই দিয়ে অডিট লগ মডিফাই বা ডিলিট করা যায় না
     */
    public function test_audit_logs_are_tamper_resistant_via_api(): void
    {
        $owner = User::factory()->create();
        $page = $this->createPage($owner);

        $log = $this->service->logAudit($page, $owner, 'test.action');

        // এপিআই-তে PUT বা DELETE মেথড এলাউড নয় (405 Method Not Allowed / 404 Route Not Defined)
        $this->actingAs($owner)->putJson("/api/v2/pages/{$page->id}/audit-logs", ['action' => 'altered'])
            ->assertStatus(405);

        $this->actingAs($owner)->deleteJson("/api/v2/pages/{$page->id}/audit-logs")
            ->assertStatus(405);

        $this->actingAs($owner)->putJson("/api/v2/pages/{$page->id}/audit-logs/{$log->id}", ['action' => 'altered'])
            ->assertStatus(404);

        $this->actingAs($owner)->deleteJson("/api/v2/pages/{$page->id}/audit-logs/{$log->id}")
            ->assertStatus(404);
    }

    /**
     * টেস্ট ৭: রিয়েলটাইম ইভেন্ট ব্রডকাস্টিং যাচাই
     */
    public function test_realtime_events_are_broadcast_on_message_and_assignment(): void
    {
        Event::fake([PageNewMessageEvent::class, PageConversationAssignedEvent::class]);

        $owner = User::factory()->create();
        $agent = User::factory()->create();
        $visitor = User::factory()->create();

        $page = $this->createPage($owner);
        PageMember::create([
            'page_id' => $page->id,
            'user_id' => $agent->id,
            'role' => Page::ROLE_MODERATOR,
            'status' => 'active',
        ]);

        // ভিজিটর মেসেজ পাঠালো
        $this->actingAs($visitor)->postJson("/api/v2/pages/{$page->id}/inbox/message", [
            'body' => 'Realtime test message',
        ])->assertStatus(201);

        Event::assertDispatched(PageNewMessageEvent::class);

        $conv = PageConversation::where('page_id', $page->id)->first();

        // কনভারসেশন এজেন্টকে অ্যাসাইন করা হলো
        $this->actingAs($owner)->postJson("/api/v2/pages/{$page->id}/inbox/conversations/{$conv->id}/assign", [
            'assigned_to' => $agent->id,
        ])->assertStatus(200);

        Event::assertDispatched(PageConversationAssignedEvent::class);
    }

    /**
     * টেস্ট ৮: পেজ ট্র্যাশ এবং রিস্টোর লাইফসাইকেল
     */
    public function test_page_trash_and_restore_lifecycle(): void
    {
        $owner = User::factory()->create();
        $page = $this->createPage($owner);

        // ট্র্যাশ করা
        $this->actingAs($owner)->deleteJson("/api/v2/pages/{$page->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('pages', ['id' => $page->id]);

        // পাবলিক ইনডেক্সে আর প্রদর্শিত হবে না
        $this->getJson('/api/v2/pages')->assertDontSee($page->name);

        // রিস্টোর করা
        $this->actingAs($owner)->postJson("/api/v2/pages/{$page->id}/restore")
            ->assertStatus(200);

        $this->assertNotSoftDeleted('pages', ['id' => $page->id]);
        $this->getJson('/api/v2/pages')->assertSee($page->name);
    }

    /**
     * টেস্ট ৯: রেট লিমিটিং ও বার্স্ট ট্রাফিক প্রোটেকশন (HTTP 429 Too Many Requests)
     */
    public function test_rate_limiting_protects_against_burst_traffic(): void
    {
        $owner = User::factory()->create();
        $page = $this->createPage($owner);

        $has429 = false;
        for ($i = 0; $i < 70; $i++) {
            $response = $this->actingAs($owner)->getJson("/api/v2/pages/{$page->id}/posts");
            if ($response->status() === 429) {
                $has429 = true;
                break;
            }
        }

        $this->assertTrue($has429, 'Rate limiter did not trigger HTTP 429 Too Many Requests under burst traffic.');
    }
}
