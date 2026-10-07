<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\Message;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DedicatedPagesWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_messenger_page_redirects_guest_to_login(): void
    {
        $response = $this->get('/messages');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_messenger_hub(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/messages');
        $response->assertStatus(200);
        $response->assertSee('চ্যাট ও বার্তা');
    }

    public function test_authenticated_user_can_view_specific_conversation_in_messenger(): void
    {
        $user1 = User::factory()->create(['name' => 'মমিনুল হক']);
        $user2 = User::factory()->create(['name' => 'সাকিব আল হাসান']);

        $conv = Conversation::create([
            'type' => Conversation::TYPE_DIRECT,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'user_id' => $user1->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'user_id' => $user2->id,
        ]);

        Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user2->id,
            'body' => 'হ্যালো মমিনুল, কেমন আছো?',
        ]);

        $response = $this->actingAs($user1)->get("/messages/{$conv->id}");
        $response->assertStatus(200);
        $response->assertSee('সাকিব আল হাসান');
        $response->assertSee('হ্যালো মমিনুল, কেমন আছো?');
    }

    public function test_anyone_can_browse_groups_index(): void
    {
        $creator = User::factory()->create();
        $group = Group::create([
            'name' => 'লার্যাভেল বাংলাদেশ',
            'slug' => 'laravel-bangladesh',
            'description' => 'বাংলাদেশের লার্যাভেল ডেভেলপারদের কমিউনিটি',
            'privacy' => Group::PRIVACY_PUBLIC,
            'creator_id' => $creator->id,
            'members_count' => 1,
            'posts_count' => 0,
        ]);

        $response = $this->get('/groups');
        $response->assertStatus(200);
        $response->assertSee('লার্যাভেল বাংলাদেশ');
    }

    public function test_can_view_specific_group_page(): void
    {
        $creator = User::factory()->create();
        $group = Group::create([
            'name' => 'কৃত্রিম বুদ্ধিমত্তা বাংলাদেশ',
            'slug' => 'ai-bangladesh',
            'description' => 'এআই ও মেশিন লার্নিং আলোচনা',
            'privacy' => Group::PRIVACY_PUBLIC,
            'creator_id' => $creator->id,
            'members_count' => 1,
            'posts_count' => 0,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $creator->id,
            'role' => GroupMember::ROLE_ADMIN,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        $response = $this->get("/groups/{$group->slug}");
        $response->assertStatus(200);
        $response->assertSee('কৃত্রিম বুদ্ধিমত্তা বাংলাদেশ');
        $response->assertSee('পাবলিক গ্রুপ');
    }

    public function test_anyone_can_browse_pages_index(): void
    {
        $owner = User::factory()->create();
        Page::create([
            'name' => 'প্রথম আলো প্রযুক্তি',
            'slug' => 'prothom-alo-tech',
            'category' => 'প্রযুক্তি ও মিডিয়া',
            'bio' => 'দৈনন্দিন প্রযুক্তির খবর',
            'owner_id' => $owner->id,
            'followers_count' => 500,
            'posts_count' => 10,
            'is_verified' => true,
        ]);

        $response = $this->get('/pages');
        $response->assertStatus(200);
        $response->assertSee('প্রথম আলো প্রযুক্তি');
    }

    public function test_can_view_specific_page(): void
    {
        $owner = User::factory()->create(['name' => 'তাহসান খান']);
        $page = Page::create([
            'name' => 'তাহসান অফিশিয়াল',
            'slug' => 'tahasan-official',
            'category' => 'সঙ্গীত ও বিনোদন',
            'bio' => 'অফিসিয়াল ফেসবুক পেইজ',
            'owner_id' => $owner->id,
            'followers_count' => 1200,
            'posts_count' => 5,
            'is_verified' => true,
        ]);

        $response = $this->get("/pages/{$page->slug}");
        $response->assertStatus(200);
        $response->assertSee('তাহসান অফিশিয়াল');
        $response->assertSee('সঙ্গীত ও বিনোদন');
    }

    public function test_anyone_can_browse_marketplace_index(): void
    {
        $seller = User::factory()->create();
        $cat = MarketplaceCategory::create([
            'name' => 'ইলেকট্রনিক্স',
            'slug' => 'electronics',
            'icon' => '📱',
            'display_order' => 1,
        ]);

        MarketplaceProduct::create([
            'seller_id' => $seller->id,
            'category_id' => $cat->id,
            'title' => 'MacBook Air M2 8GB/256GB',
            'description' => 'কন্ডিশন সম্পূর্ণ ফ্রেশ',
            'price' => 95000,
            'currency' => 'BDT',
            'condition' => 'used',
            'location' => 'ঢাকা',
            'status' => 'active',
            'views_count' => 12,
        ]);

        $response = $this->get('/marketplace');
        $response->assertStatus(200);
        $response->assertSee('MacBook Air M2');
        $response->assertSee('95,000');
    }

    public function test_can_view_marketplace_product_details(): void
    {
        $seller = User::factory()->create(['name' => 'করিম এন্টারপ্রাইজ']);
        $cat = MarketplaceCategory::create([
            'name' => 'মোটরসাইকেল',
            'slug' => 'motorcycles',
            'icon' => '🏍️',
            'display_order' => 2,
        ]);

        $product = MarketplaceProduct::create([
            'seller_id' => $seller->id,
            'category_id' => $cat->id,
            'title' => 'Yamaha R15 V4 Dark Knight',
            'description' => '৫০০০ কিমি চলা, জরুরি বিক্রয়',
            'price' => 480000,
            'currency' => 'BDT',
            'condition' => 'used',
            'location' => 'চট্টগ্রাম',
            'status' => 'active',
            'views_count' => 50,
        ]);

        $response = $this->get("/marketplace/{$product->id}");
        $response->assertStatus(200);
        $response->assertSee('Yamaha R15 V4 Dark Knight');
        $response->assertSee('480,000');
        $response->assertSee('করিম এন্টারপ্রাইজ');
        $response->assertSee('এসক্রো নিরাপত্তা');
    }

    public function test_can_browse_watch_video_feed(): void
    {
        $response = $this->get('/watch');
        $response->assertStatus(200);
        $response->assertSee('ওয়াচ ও লাইভ স্ট্রিমিং');
    }

    public function test_creator_can_access_live_studio(): void
    {
        $user = User::factory()->create(['name' => 'লাইভ স্ট্রিমার']);

        $response = $this->actingAs($user)->get('/live/studio');
        $response->assertStatus(200);
        $response->assertSee('ক্রিয়েটর লাইভ ব্রডকাস্ট স্টুডিও');
        $response->assertSee('OBS ও সফটওয়্যার কনফিগারেশন');
    }

    public function test_authenticated_user_can_view_notifications_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/notifications');
        $response->assertStatus(200);
        $response->assertSee('নোটিফিকেশন সেন্টার');
    }
}
