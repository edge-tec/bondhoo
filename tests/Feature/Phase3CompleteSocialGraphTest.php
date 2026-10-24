<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\User;
use App\Models\UserFollower;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3CompleteSocialGraphTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Requirement 3.1: Send Friend Request, Self Request Block, and Duplicate Prevention
     */
    public function test_phase3_friend_request_dispatch_and_validations(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);

        // 1. Cannot send friend request to self
        $selfResponse = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$alice->id}/request");

        $selfResponse->assertStatus(422)
            ->assertJson(['success' => false]);

        // 2. Send friend request to Bob
        $sendResponse = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");

        $sendResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Friend request sent successfully.',
            ]);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        // 3. Duplicate request while pending must fail
        $dupResponse = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");

        $dupResponse->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /**
     * Requirement 3.2: Inspect Pending Requests (Received & Sent)
     */
    public function test_phase3_view_received_and_sent_requests(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);

        // Alice sends to Bob
        $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$bob->id}/request");

        // Bob inspects incoming requests
        $receivedRes = $this->actingAs($bob, 'sanctum')
            ->getJson('/api/v1/friends/requests');

        $receivedRes->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');

        // Alice inspects sent requests
        $sentRes = $this->actingAs($alice, 'sanctum')
            ->getJson('/api/v1/friends/requests/sent');

        $sentRes->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    }

    /**
     * Requirement 3.3: Accept Friend Request and List Friends
     */
    public function test_phase3_accept_friend_request_and_verify_friends_list(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);

        $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$bob->id}/request");

        // Bob accepts Alice's request
        $acceptResponse = $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/friends/{$alice->id}/accept");

        $acceptResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Both see each other in /api/v1/friends
        $aliceFriends = $this->actingAs($alice, 'sanctum')->getJson('/api/v1/friends');
        $aliceFriends->assertStatus(200)->assertJsonPath('meta.total', 1);

        $bobFriends = $this->actingAs($bob, 'sanctum')->getJson('/api/v1/friends');
        $bobFriends->assertStatus(200)->assertJsonPath('meta.total', 1);
    }

    /**
     * Requirement 3.4: Reject / Decline and Cancel Friend Request
     */
    public function test_phase3_reject_and_cancel_friend_request(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);

        // 1. Alice sends to Bob, then cancels
        $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$bob->id}/request");
        $cancelRes = $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$bob->id}/cancel");
        $cancelRes->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
        ]);

        // 2. Alice sends again, Bob rejects/declines
        $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$bob->id}/request");
        $rejectRes = $this->actingAs($bob, 'sanctum')->postJson("/api/v1/friends/{$alice->id}/reject");
        $rejectRes->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_DECLINED,
        ]);
    }

    /**
     * Requirement 3.5: Unfriend Existing Connection
     */
    public function test_phase3_unfriend_user_breaks_connection(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);

        Friendship::create([
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $unfriendRes = $this->actingAs($alice, 'sanctum')
            ->deleteJson("/api/v1/friends/{$bob->id}");

        $unfriendRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
        ]);
    }

    /**
     * Requirement 3.6: Follow and Unfollow Users
     */
    public function test_phase3_follow_and_unfollow_lifecycle(): void
    {
        $follower = User::factory()->create(['name' => 'Follower']);
        $leader = User::factory()->create(['name' => 'Leader']);

        // 1. Follow
        $followRes = $this->actingAs($follower, 'sanctum')
            ->postJson("/api/v1/users/{$leader->id}/follow");

        $followRes->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_followers', [
            'user_id' => $leader->id,
            'follower_id' => $follower->id,
        ]);

        // Check follower/following listings
        $followersRes = $this->actingAs($leader, 'sanctum')->getJson('/api/v1/followers');
        $followersRes->assertStatus(200)->assertJsonCount(1, 'data');

        $followingRes = $this->actingAs($follower, 'sanctum')->getJson('/api/v1/following');
        $followingRes->assertStatus(200)->assertJsonCount(1, 'data');

        // 2. Unfollow
        $unfollowRes = $this->actingAs($follower, 'sanctum')
            ->deleteJson("/api/v1/users/{$leader->id}/follow");

        $unfollowRes->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseMissing('user_followers', [
            'user_id' => $leader->id,
            'follower_id' => $follower->id,
        ]);
    }

    /**
     * Requirement 3.7: Block and Unblock Lifecycle with Security Isolation
     */
    public function test_phase3_block_and_unblock_user_with_security_isolation(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);

        // Initial friendship and follow
        Friendship::create([
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        UserFollower::create([
            'user_id' => $alice->id,
            'follower_id' => $bob->id,
        ]);

        // Alice blocks Bob
        $blockRes = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/users/{$bob->id}/block");

        $blockRes->assertStatus(200)->assertJson(['success' => true]);

        // 1. Friendship status changed to blocked
        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_BLOCKED,
        ]);

        // 2. BlockedUser table has record
        $this->assertDatabaseHas('blocked_users', [
            'user_id' => $alice->id,
            'identifier' => (string) $bob->id,
        ]);

        // 3. Follow relation was deleted
        $this->assertDatabaseMissing('user_followers', [
            'user_id' => $alice->id,
            'follower_id' => $bob->id,
        ]);

        // 4. Bob cannot send friend request to Alice while blocked
        $bobRequestRes = $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/friends/{$alice->id}/request");

        $bobRequestRes->assertStatus(422);

        // 5. Alice unblocks Bob
        $unblockRes = $this->actingAs($alice, 'sanctum')
            ->deleteJson("/api/v1/users/{$bob->id}/block");

        $unblockRes->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseMissing('blocked_users', [
            'user_id' => $alice->id,
            'identifier' => (string) $bob->id,
        ]);
    }
}
