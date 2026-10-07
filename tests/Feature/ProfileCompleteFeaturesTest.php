<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileCompleteFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_profile_page_loads_with_completion_and_analytics_for_owner(): void
    {
        $user = User::factory()->create(['username' => 'testuser', 'name' => 'Test User']);
        UserProfile::create([
            'user_id' => $user->id,
            'bio' => 'Sample bio',
            'city' => 'Dhaka',
        ]);

        $response = $this->actingAs($user)->get('/user/testuser');

        $response->assertStatus(200);
        $response->assertSee('প্রোফাইল সম্পূর্ণতা');
        $response->assertSee('অ্যানালিটিক্স ও ভিউ');
        $response->assertSee('পরিচয় যাচাইকরণ');
        $response->assertSee('প্রাইভেসি সেটিংস');
    }

    public function test_friendship_request_and_cancellation_flow(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        // 1. Send request
        $resSend = $this->actingAs($sender, 'sanctum')->postJson("/api/v1/friends/{$receiver->id}/request");
        $resSend->assertStatus(201);
        $this->assertDatabaseHas('friendships', [
            'user_id' => $sender->id,
            'friend_id' => $receiver->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        // 2. Cancel request
        $resCancel = $this->actingAs($sender, 'sanctum')->deleteJson("/api/v1/friends/{$receiver->id}");
        $resCancel->assertStatus(200);
        $this->assertDatabaseMissing('friendships', [
            'user_id' => $sender->id,
            'friend_id' => $receiver->id,
        ]);
    }

    public function test_friendship_accept_and_unfriend_flow(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // User A sends to User B
        $this->actingAs($userA, 'sanctum')->postJson("/api/v1/friends/{$userB->id}/request");

        // User B accepts
        $resAccept = $this->actingAs($userB, 'sanctum')->postJson("/api/v1/friends/{$userA->id}/accept");
        $resAccept->assertStatus(200);
        $this->assertDatabaseHas('friendships', [
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // User A unfriends User B
        $resUnfriend = $this->actingAs($userA, 'sanctum')->deleteJson("/api/v1/friends/{$userB->id}");
        $resUnfriend->assertStatus(200);
        $this->assertDatabaseMissing('friendships', [
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
        ]);
    }

    public function test_identity_verification_submission_from_modal(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $file = UploadedFile::fake()->image('nid_front.jpg', 800, 600);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/verification/submit', [
            'document_type' => 'nid',
            'document_number' => '1234567890123',
            'full_name' => 'Verified Citizen',
            'document_front' => $file,
            'reason' => 'Verifying my public creator profile.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('profile_verifications', [
            'user_id' => $user->id,
            'document_number' => '1234567890123',
            'status' => 'pending',
        ]);
    }

    public function test_privacy_settings_update_from_modal(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v2/profile/privacy/settings', [
            'profile_visibility' => 'friends',
            'bio_privacy' => 'only_me',
            'work_privacy' => 'friends',
            'social_links_privacy' => 'friends',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('privacy_settings', [
            'user_id' => $user->id,
            'profile_visibility' => 'friends',
            'bio_privacy' => 'only_me',
        ]);
    }
}
