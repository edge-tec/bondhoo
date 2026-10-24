<?php

namespace Tests\Feature;

use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\Notification;
use App\Models\PrivacySetting;
use App\Models\User;
use App\Models\UserFollower;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Task3CompleteSocialInteractionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => $user->first_name ?? 'Social',
            'last_name' => $user->last_name ?? 'Networker',
        ]);

        return $user;
    }

    /**
     * 1. Send friend request dispatches a database-backed notification to recipient.
     */
    public function test_send_friend_request_creates_database_notification(): void
    {
        $alice = $this->createUser(['name' => 'Alice Sender']);
        $bob = $this->createUser(['name' => 'Bob Recipient']);

        // Alice sends friend request to Bob
        $response = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Friend request sent successfully.');

        // Friendship database record
        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        // Database-backed notification for Bob
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $bob->id,
            'type' => 'friend.request',
        ]);

        // Bob checks notifications endpoint
        $notifRes = $this->actingAs($bob, 'sanctum')
            ->getJson('/api/v1/notifications');

        $notifRes->assertStatus(200)
            ->assertJsonPath('success', true);
        $types = collect($notifRes->json('data'))->pluck('type')->all();
        $this->assertContains('friend.request', $types);
    }

    /**
     * 2. Self-directed and duplicate friend requests are strictly prevented with 422.
     */
    public function test_self_directed_and_duplicate_friend_requests_are_prevented(): void
    {
        $alice = $this->createUser();
        $bob = $this->createUser();

        // 1. Self request fails
        $selfRes = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$alice->id}/request");

        $selfRes->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You cannot send a friend request to yourself.');

        // 2. First request succeeds
        $firstRes = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");
        $firstRes->assertStatus(201);

        // 3. Duplicate request while pending fails
        $dupRes = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");

        $dupRes->assertStatus(422)
            ->assertJsonPath('success', false);

        // Accept request
        $this->actingAs($bob, 'sanctum')->postJson("/api/v1/friends/{$alice->id}/accept");

        // 4. Request when already friends fails
        $alreadyFriendsRes = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");

        $alreadyFriendsRes->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You are already friends with this user.');
    }

    /**
     * 3. Accept friend request dispatches database-backed notification to sender.
     */
    public function test_accept_friend_request_creates_database_notification_and_updates_status(): void
    {
        $alice = $this->createUser(['name' => 'Alice']);
        $bob = $this->createUser(['name' => 'Bob']);

        // Alice sends request to Bob
        $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$bob->id}/request");

        // Bob accepts Alice's request
        $acceptRes = $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/friends/{$alice->id}/accept");

        $acceptRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify status updated to accepted
        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Database notification for Alice
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $alice->id,
            'type' => 'friend.accepted',
        ]);

        // Both see each other in /api/v1/friends
        $aliceFriends = $this->actingAs($alice, 'sanctum')->getJson('/api/v1/friends');
        $aliceFriends->assertStatus(200)->assertJsonPath('meta.total', 1);

        $bobFriends = $this->actingAs($bob, 'sanctum')->getJson('/api/v1/friends');
        $bobFriends->assertStatus(200)->assertJsonPath('meta.total', 1);
    }

    /**
     * 4. Reject and cancel friend request lifecycle.
     */
    public function test_reject_and_cancel_friend_request(): void
    {
        $alice = $this->createUser();
        $bob = $this->createUser();

        // 1. Alice sends to Bob, then cancels
        $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$bob->id}/request");
        $cancelRes = $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$bob->id}/cancel");
        $cancelRes->assertStatus(200);

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
        ]);

        // 2. Alice sends again, Bob rejects/declines
        $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$bob->id}/request");
        $rejectRes = $this->actingAs($bob, 'sanctum')->postJson("/api/v1/friends/{$alice->id}/reject");
        $rejectRes->assertStatus(200);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_DECLINED,
        ]);
    }

    /**
     * 5. Unfriend removes connection from database.
     */
    public function test_unfriend_breaks_connection(): void
    {
        $alice = $this->createUser();
        $bob = $this->createUser();

        Friendship::create([
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $unfriendRes = $this->actingAs($alice, 'sanctum')
            ->deleteJson("/api/v1/friends/{$bob->id}");

        $unfriendRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
        ]);

        $aliceFriends = $this->actingAs($alice, 'sanctum')->getJson('/api/v1/friends');
        $aliceFriends->assertJsonPath('meta.total', 0);
    }

    /**
     * 6. Block/Unblock lifecycle with relationship dissolution and privacy enforcement.
     */
    public function test_block_and_unblock_user_with_privacy_enforcement(): void
    {
        $alice = $this->createUser(['name' => 'Alice']);
        $bob = $this->createUser(['name' => 'Bob']);

        // Pre-existing friendship and follow
        Friendship::create([
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);
        UserFollower::create([
            'user_id' => $alice->id,
            'follower_id' => $bob->id,
        ]);

        // 1. Alice blocks Bob
        $blockRes = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/users/{$bob->id}/block");

        $blockRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Follows dissolved
        $this->assertDatabaseMissing('user_followers', [
            'user_id' => $alice->id,
            'follower_id' => $bob->id,
        ]);

        // Friendship status updated to BLOCKED
        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_BLOCKED,
        ]);

        // BlockedUser table synced
        $this->assertDatabaseHas('blocked_users', [
            'user_id' => $alice->id,
            'identifier' => (string) $bob->id,
        ]);

        // 2. Blocked Bob cannot send friend request to Alice
        $bobRequest = $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/friends/{$alice->id}/request");

        $bobRequest->assertStatus(422)
            ->assertJsonPath('message', 'Action not allowed.');

        // 3. Alice unblocks Bob
        $unblockRes = $this->actingAs($alice, 'sanctum')
            ->deleteJson("/api/v1/users/{$bob->id}/block");

        $unblockRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Block records removed
        $this->assertDatabaseMissing('blocked_users', [
            'user_id' => $alice->id,
            'identifier' => (string) $bob->id,
        ]);
    }

    /**
     * 7. Recipient privacy settings enforcement (nobody, friends_of_friends).
     */
    public function test_recipient_privacy_settings_enforcement(): void
    {
        $alice = $this->createUser();
        $bob = $this->createUser();

        // 1. Bob sets who_can_send_friend_requests = 'nobody'
        PrivacySetting::create([
            'user_id' => $bob->id,
            'who_can_send_friend_requests' => 'nobody',
        ]);

        $resNobody = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");

        $resNobody->assertStatus(422)
            ->assertJsonPath('message', 'This user is not accepting friend requests.');

        // 2. Bob changes to 'friends_of_friends'
        PrivacySetting::where('user_id', $bob->id)->update([
            'who_can_send_friend_requests' => 'friends_of_friends',
        ]);

        // Alice has no mutual friends with Bob -> 422
        $resNoMutual = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");

        $resNoMutual->assertStatus(422)
            ->assertJsonPath('message', 'This user only accepts friend requests from friends of mutual friends.');

        // Add a mutual friend Charlie
        $charlie = $this->createUser();
        Friendship::create([
            'user_id' => $alice->id,
            'friend_id' => $charlie->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);
        Friendship::create([
            'user_id' => $bob->id,
            'friend_id' => $charlie->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Alice now has mutual friends with Bob -> request succeeds
        $resWithMutual = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");

        $resWithMutual->assertStatus(201);
    }

    /**
     * 8. Concurrent friend request safety & bidirectional auto-accept.
     */
    public function test_concurrent_friend_request_safety_and_bidirectional_auto_accept(): void
    {
        $alice = $this->createUser();
        $bob = $this->createUser();

        // Cross-direction request: Alice sends to Bob, then Bob sends to Alice
        $aliceToBob = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");
        $aliceToBob->assertStatus(201);

        // Bob sends to Alice -> auto-accepts without creating a duplicate record
        $bobToAlice = $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/friends/{$alice->id}/request");
        $bobToAlice->assertStatus(201);

        // Exactly 1 relationship record in database and status is ACCEPTED
        $this->assertEquals(1, Friendship::count());
        $this->assertEquals(Friendship::STATUS_ACCEPTED, Friendship::first()->status);
    }
}
