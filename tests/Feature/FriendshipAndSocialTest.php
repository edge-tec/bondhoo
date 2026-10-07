<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FriendshipAndSocialTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_and_accept_friend_request(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Send request
        $response = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/friends/{$userB->id}/request");

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
            'status' => 'pending',
        ]);

        // User B sees pending request
        $reqResponse = $this->actingAs($userB, 'sanctum')
            ->getJson('/api/v1/friends/requests');

        $reqResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // User B accepts request
        $acceptResponse = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/friends/{$userA->id}/accept");

        $acceptResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
            'status' => 'accepted',
        ]);

        // Both see each other in friends list
        $listResponse = $this->actingAs($userA, 'sanctum')->getJson('/api/v1/friends');
        $listResponse->assertStatus(200)->assertJsonPath('meta.total', 1);

        $listResponseB = $this->actingAs($userB, 'sanctum')->getJson('/api/v1/friends');
        $listResponseB->assertStatus(200)->assertJsonPath('meta.total', 1);
    }

    public function test_user_cannot_friend_request_self(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/friends/{$user->id}/request");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_user_can_follow_and_unfollow(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Follow
        $followResponse = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/users/{$userB->id}/follow");

        $followResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('user_followers', [
            'user_id' => $userB->id,
            'follower_id' => $userA->id,
        ]);

        // Unfollow
        $unfollowResponse = $this->actingAs($userA, 'sanctum')
            ->deleteJson("/api/v1/users/{$userB->id}/follow");

        $unfollowResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('user_followers', [
            'user_id' => $userB->id,
            'follower_id' => $userA->id,
        ]);
    }

    public function test_feed_strict_privacy_filtering_for_friends(): void
    {
        $author = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();

        // Make author and friend friends
        Friendship::create([
            'user_id' => $author->id,
            'friend_id' => $friend->id,
            'status' => 'accepted',
        ]);

        // Create a friends-only post
        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Friends only secret post',
            'audience' => 'friends',
        ]);

        // Friend CAN see the post
        $feedFriend = $this->actingAs($friend, 'sanctum')->getJson('/api/v1/feed');
        $feedFriend->assertStatus(200);
        $idsFriend = collect($feedFriend->json('data'))->pluck('id')->toArray();
        $this->assertContains($post->id, $idsFriend);

        // Stranger CANNOT see the post
        $feedStranger = $this->actingAs($stranger, 'sanctum')->getJson('/api/v1/feed');
        $feedStranger->assertStatus(200);
        $idsStranger = collect($feedStranger->json('data'))->pluck('id')->toArray();
        $this->assertNotContains($post->id, $idsStranger);
    }

    public function test_user_can_share_post(): void
    {
        $user = User::factory()->create();
        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Original interesting post',
            'audience' => 'public',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/share", [
                'caption' => 'Check this out!',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.shares_count', 1);

        $this->assertDatabaseHas('post_shares', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'caption' => 'Check this out!',
        ]);

        $this->assertEquals(1, $post->fresh()->shares_count);
    }

    public function test_user_can_submit_report(): void
    {
        $user = User::factory()->create();
        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Suspicious content',
            'audience' => 'public',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'spam',
                'details' => 'Repeated crypto links',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $user->id,
            'reportable_id' => $post->id,
            'reason' => 'spam',
            'status' => 'pending',
        ]);
    }

    public function test_password_forgot_and_reset_flow(): void
    {
        $user = User::factory()->create([
            'email' => 'resetme@example.com',
            'password' => Hash::make('old-password-123'),
        ]);

        // Request reset
        $forgotResponse = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'resetme@example.com',
        ]);

        $forgotResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $token = $forgotResponse->json('data.reset_token');
        $this->assertNotEmpty($token);

        // Reset with token
        $resetResponse = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'resetme@example.com',
            'token' => $token,
            'password' => 'new-secure-pass-456',
            'password_confirmation' => 'new-secure-pass-456',
        ]);

        $resetResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify login works with new password
        $this->assertTrue(Hash::check('new-secure-pass-456', $user->fresh()->password));
    }

    public function test_two_factor_setup_and_login_challenge(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('mypassword123'),
        ]);

        // 1. Setup 2FA
        $setupResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/2fa/setup');

        $setupResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertNotNull($user->fresh()->two_factor_secret);

        // 2. Enable 2FA with 6-digit code
        $enableResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/2fa/enable', [
                'code' => '123456',
            ]);

        $enableResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue($user->fresh()->two_factor_enabled);

        // 3. Login now requires 2FA challenge
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->email,
            'password' => 'mypassword123',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonPath('data.requires_2fa', true);

        $challengeToken = $loginResponse->json('data.challenge_token');
        $this->assertNotEmpty($challengeToken);

        // 4. Verify 2FA challenge
        $challengeResponse = $this->postJson('/api/v1/auth/2fa/challenge', [
            'challenge_token' => $challengeToken,
            'code' => '654321',
        ]);

        $challengeResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertNotEmpty($challengeResponse->json('data.token'));
    }
}
