<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Post;
use App\Models\ProfileVerification;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Phase1SocialParityVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['label' => 'Super Admin', 'description' => 'Full access']);
        Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Admin', 'description' => 'Staff administrator']);
        Role::firstOrCreate(['name' => 'USER'], ['label' => 'Regular User', 'description' => 'Platform user']);
        Role::firstOrCreate(['name' => 'VERIFIED_USER'], ['label' => 'Verified User', 'description' => 'Identity verified citizen']);
    }

    protected function createUser(array $attributes = []): User
    {
        static $counter = 200;
        $counter++;

        $user = User::factory()->create(array_merge([
            'username' => "phase1_user_{$counter}",
            'name' => "Phase1 User {$counter}",
            'email' => "phase1_user_{$counter}@example.com",
            'phone' => "+880181100{$counter}",
            'status' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ], $attributes));

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => 'Phase 1 test bio',
                'about' => 'Phase 1 test about',
                'is_locked' => false,
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'profile_visibility' => 'public',
            ]
        );

        return $user;
    }

    protected function createAdmin(array $attributes = []): User
    {
        $admin = $this->createUser(array_merge(['username' => 'admin_'.uniqid()], $attributes));
        $adminRole = Role::where('name', 'ADMIN')->first();
        if ($adminRole) {
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        return $admin;
    }

    /**
     * 1. Saved Posts DB Persistence & Polymorphic Relations.
     */
    public function test_user_can_toggle_and_fetch_saved_posts_from_database(): void
    {
        $user = $this->createUser();
        $author = $this->createUser(['name' => 'Author Citizen']);

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'এটি একটি গুরুত্বপূর্ণ সংরক্ষিত পোস্টের নমুনা।',
            'visibility' => 'public',
        ]);

        // 1. Toggle save (save item)
        $toggleRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/saved-items/toggle', [
                'item_type' => 'post',
                'item_id' => $post->id,
            ]);

        $toggleRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'saved' => true,
                ],
            ]);

        $this->assertDatabaseHas('saved_items', [
            'user_id' => $user->id,
            'item_id' => $post->id,
        ]);

        // 2. Fetch saved items list
        $fetchRes = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile/saved-items');

        $fetchRes->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $items = $fetchRes->json('data');
        $this->assertNotEmpty($items);
        $this->assertEquals($post->id, $items[0]['item_id']);
        $this->assertEquals($post->content, $items[0]['post']['content']);
        $this->assertEquals($author->name, $items[0]['post']['author']['name']);

        // 3. Toggle save again (remove saved item)
        $toggleAgainRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/saved-items/toggle', [
                'item_type' => 'post',
                'item_id' => $post->id,
            ]);

        $toggleAgainRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'saved' => false,
                ],
            ]);

        $this->assertDatabaseMissing('saved_items', [
            'user_id' => $user->id,
            'item_id' => $post->id,
        ]);
    }

    /**
     * 2. Memories ("On This Day") calculation & Banglaphones.
     */
    public function test_user_can_fetch_memories_on_this_day(): void
    {
        $user = $this->createUser();
        $frozenDate = Carbon::create(2026, 10, 17, 12, 0, 0);
        Carbon::setTestNow($frozenDate);

        // Historical post: exactly 1 year ago today
        $post1YearAgo = Post::create([
            'user_id' => $user->id,
            'content' => '১ বছর আগের স্মৃতি পোস্ট',
            'visibility' => 'public',
        ]);
        $post1YearAgo->timestamps = false;
        $post1YearAgo->created_at = Carbon::create(2025, 10, 17, 10, 0, 0);
        $post1YearAgo->save();

        // Historical post: exactly 2 years ago today
        $post2YearsAgo = Post::create([
            'user_id' => $user->id,
            'content' => '২ বছর আগের স্মৃতি পোস্ট',
            'visibility' => 'public',
        ]);
        $post2YearsAgo->timestamps = false;
        $post2YearsAgo->created_at = Carbon::create(2024, 10, 17, 8, 30, 0);
        $post2YearsAgo->save();

        // Today's current post (should NOT be in historical memories)
        $todayPost = Post::create([
            'user_id' => $user->id,
            'content' => 'আজকের পোস্ট',
            'visibility' => 'public',
        ]);
        $todayPost->timestamps = false;
        $todayPost->created_at = Carbon::create(2026, 10, 17, 11, 0, 0);
        $todayPost->save();

        $res = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/memories');

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $todayMemories = $res->json('data.today_memories');
        $this->assertCount(2, $todayMemories);

        $contents = array_column($todayMemories, 'content');
        $this->assertContains('১ বছর আগের স্মৃতি পোস্ট', $contents);
        $this->assertContains('২ বছর আগের স্মৃতি পোস্ট', $contents);
        $this->assertNotContains('আজকের পোস্ট', $contents);

        Carbon::setTestNow(); // clear mock
    }

    /**
     * 3. Community Events listing, creation, and RSVP.
     */
    public function test_user_can_create_list_and_rsvp_events(): void
    {
        $user = $this->createUser();
        $attendeeUser = $this->createUser();

        // 1. Create Event
        $createRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/events', [
                'title' => 'জাতীয় প্রযুক্তি সম্মেলন ২০২৬',
                'description' => 'প্রযুক্তি ও উদ্ভাবন বিষয়ক আলোচনা ও মিলনমেলা।',
                'location_type' => 'online',
                'start_time' => now()->addDays(5)->toDateTimeString(),
                'end_time' => now()->addDays(5)->addHours(4)->toDateTimeString(),
            ]);

        $createRes->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'event' => [
                        'title' => 'জাতীয় প্রযুক্তি সম্মেলন ২০২৬',
                        'location_type' => 'online',
                    ],
                ],
            ]);

        $eventId = $createRes->json('data.event.id');

        // Creator is automatically attending (going)
        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $eventId,
            'user_id' => $user->id,
            'status' => 'going',
        ]);

        // 2. Filter & list events
        $listRes = $this->actingAs($attendeeUser, 'sanctum')
            ->getJson('/api/v1/events?location_type=online');

        $listRes->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $events = $listRes->json('data.events');
        $this->assertNotEmpty($events);
        $this->assertEquals($eventId, $events[0]['id']);

        // 3. Attendee RSVPs as interested
        $rsvpRes = $this->actingAs($attendeeUser, 'sanctum')
            ->postJson("/api/v1/events/{$eventId}/rsvp", [
                'status' => 'interested',
            ]);

        $rsvpRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_rsvp' => 'interested',
                ],
            ]);

        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $eventId,
            'user_id' => $attendeeUser->id,
            'status' => 'interested',
        ]);
    }

    /**
     * 4. Web navigation redirects to /dashboard?tab=...
     */
    public function test_saved_memories_events_redirect_to_dashboard_with_tabs(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get('/saved')
            ->assertRedirect('/dashboard?tab=saved');

        $this->actingAs($user)
            ->get('/memories')
            ->assertRedirect('/dashboard?tab=memories');

        $this->actingAs($user)
            ->get('/events')
            ->assertRedirect('/dashboard?tab=events');
    }

    /**
     * 5. Citizen Identity Verification Queue review, approve & reject.
     */
    public function test_admin_can_review_approve_and_reject_citizen_verifications(): void
    {
        $admin = $this->createAdmin();
        $applicant1 = $this->createUser(['username' => 'citizen_applicant_1']);
        $applicant2 = $this->createUser(['username' => 'citizen_applicant_2']);

        // Pending verification for applicant 1
        $v1 = ProfileVerification::create([
            'user_id' => $applicant1->id,
            'verification_type' => ProfileVerification::TYPE_NID,
            'document_number' => '19958849201948',
            'document_front_key' => 'verifications/test_front.jpg',
            'status' => ProfileVerification::STATUS_PENDING,
            'submitted_at' => now()->subHours(3),
        ]);

        // Pending verification for applicant 2
        $v2 = ProfileVerification::create([
            'user_id' => $applicant2->id,
            'verification_type' => ProfileVerification::TYPE_PASSPORT,
            'document_number' => 'BD-9988776',
            'status' => ProfileVerification::STATUS_PENDING,
            'submitted_at' => now()->subHours(1),
        ]);

        // Admin lists queue
        $listRes = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v2/admin/verifications?status=pending');

        $listRes->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $items = $listRes->json('data.data');
        $this->assertCount(2, $items);

        // Admin views details of applicant 1 (verifies show endpoint exposes URLs)
        $showRes = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v2/admin/verifications/{$v1->id}");

        $showRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $v1->id,
                    'document_number' => '19958849201948',
                ],
            ]);
        $this->assertArrayHasKey('document_front_url', $showRes->json('data'));

        // Admin approves applicant 1
        $approveRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v2/admin/verifications/{$v1->id}/approve", [
                'admin_notes' => 'সকল তথ্য ও ছবি যথাযথভাবে যাচাইকৃত।',
            ]);

        $approveRes->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertEquals(ProfileVerification::STATUS_VERIFIED, $v1->fresh()->status);
        $this->assertTrue($applicant1->fresh()->hasRole('VERIFIED_USER'));

        // Admin rejects applicant 2 with reason
        $rejectRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v2/admin/verifications/{$v2->id}/reject", [
                'rejection_reason' => 'পাসপোর্টের ছবি অস্পষ্ট এবং স্ক্যান কপি অপরিচ্ছন্ন।',
            ]);

        $rejectRes->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertEquals(ProfileVerification::STATUS_REJECTED, $v2->fresh()->status);
        $this->assertEquals('পাসপোর্টের ছবি অস্পষ্ট এবং স্ক্যান কপি অপরিচ্ছন্ন।', $v2->fresh()->rejection_reason);
    }
}
