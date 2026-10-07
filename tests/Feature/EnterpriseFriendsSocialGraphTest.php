<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\PrivacySetting;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseFriendsSocialGraphTest extends TestCase
{
    use RefreshDatabase;

    public function test_friend_request_lifecycle_send_accept_unfriend(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);

        // 1. Send request
        $sendRes = $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$bob->id}/request");
        $sendRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        // 2. Bob sees request in requests list
        $reqRes = $this->actingAs($bob, 'sanctum')->getJson('/api/v1/friends/requests');
        $reqRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // 3. Bob accepts request
        $acceptRes = $this->actingAs($bob, 'sanctum')->postJson("/api/v1/friends/{$alice->id}/accept");
        $acceptRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // 4. Verify both are friends via status endpoint
        $statusRes = $this->actingAs($alice, 'sanctum')->getJson("/api/v1/friends/status/{$bob->id}");
        $statusRes->assertStatus(200)
            ->assertJsonPath('state', 'FRIENDS')
            ->assertJsonPath('is_friend', true);

        // 5. Unfriend
        $unfriendRes = $this->actingAs($alice, 'sanctum')->deleteJson("/api/v1/friends/{$bob->id}");
        $unfriendRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
        ]);
    }

    public function test_friend_request_decline_and_cancel(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        // Alice sends to Bob
        $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$bob->id}/request");

        // Alice cancels request
        $cancelRes = $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$bob->id}/cancel");
        $cancelRes->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
        ]);

        // Alice sends again
        $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$bob->id}/request");

        // Bob declines request
        $declineRes = $this->actingAs($bob, 'sanctum')->postJson("/api/v1/friends/{$alice->id}/decline");
        $declineRes->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_DECLINED,
        ]);
    }

    public function test_cannot_send_request_to_self(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user, 'sanctum')->postJson("/api/v1/friends/{$user->id}/request");
        $res->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_duplicate_and_already_friends_protection(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // 1. Send first request
        $this->actingAs($userA, 'sanctum')->postJson("/api/v1/friends/{$userB->id}/request");

        // 2. Second request while pending fails
        $dupRes = $this->actingAs($userA, 'sanctum')->postJson("/api/v1/friends/{$userB->id}/request");
        $dupRes->assertStatus(422)
            ->assertJsonPath('success', false);

        // Accept
        $this->actingAs($userB, 'sanctum')->postJson("/api/v1/friends/{$userA->id}/accept");

        // 3. Request when already friends fails
        $alreadyRes = $this->actingAs($userA, 'sanctum')->postJson("/api/v1/friends/{$userB->id}/request");
        $alreadyRes->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_cross_simultaneous_friend_requests_auto_accept(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        // Alice sends to Bob
        $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$bob->id}/request");

        // Bob also sends to Alice at the same time
        $crossRes = $this->actingAs($bob, 'sanctum')->postJson("/api/v1/friends/{$alice->id}/request");
        $crossRes->assertStatus(201);

        // They are automatically accepted as friends
        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);
    }

    public function test_privacy_who_can_send_friend_requests_nobody(): void
    {
        $alice = User::factory()->create();
        $charlie = User::factory()->create();

        PrivacySetting::create([
            'user_id' => $charlie->id,
            'who_can_send_friend_requests' => 'nobody',
        ]);

        $res = $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$charlie->id}/request");
        $res->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_privacy_who_can_send_friend_requests_friends_of_friends(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $target = User::factory()->create();

        PrivacySetting::create([
            'user_id' => $target->id,
            'who_can_send_friend_requests' => 'friends_of_friends',
        ]);

        // Alice has no mutual friends with target -> fails
        $failRes = $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$target->id}/request");
        $failRes->assertStatus(422);

        // Bob is friends with target
        Friendship::create([
            'user_id' => $bob->id,
            'friend_id' => $target->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Alice is friends with Bob (so Alice and target have Bob as mutual friend)
        Friendship::create([
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Now Alice CAN send to target
        $okRes = $this->actingAs($alice, 'sanctum')->postJson("/api/v1/friends/{$target->id}/request");
        $okRes->assertStatus(201);
    }

    public function test_block_and_unblock_user_lifecycle(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Make them friends first
        Friendship::create([
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // User A blocks User B
        $blockRes = $this->actingAs($userA, 'sanctum')->postJson("/api/v1/users/{$userB->id}/block");
        $blockRes->assertStatus(200);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
            'status' => Friendship::STATUS_BLOCKED,
        ]);

        $this->assertDatabaseHas('blocked_users', [
            'user_id' => $userA->id,
            'identifier' => (string) $userB->id,
        ]);

        // State check
        $statusA = $this->actingAs($userA, 'sanctum')->getJson("/api/v1/friends/status/{$userB->id}");
        $statusA->assertJsonPath('state', 'BLOCKED');

        $statusB = $this->actingAs($userB, 'sanctum')->getJson("/api/v1/friends/status/{$userA->id}");
        $statusB->assertJsonPath('state', 'BLOCKED_BY_USER');

        // Unblock
        $unblockRes = $this->actingAs($userA, 'sanctum')->deleteJson("/api/v1/users/{$userB->id}/block");
        $unblockRes->assertStatus(200);

        $this->assertDatabaseMissing('blocked_users', [
            'user_id' => $userA->id,
            'identifier' => (string) $userB->id,
        ]);
    }

    public function test_mutual_friends_computation_and_endpoint(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();
        $mutual1 = User::factory()->create(['name' => 'Mutual One']);
        $mutual2 = User::factory()->create(['name' => 'Mutual Two']);
        $otherFriend = User::factory()->create(['name' => 'Viewer Only Friend']);

        // Viewer friends with mutual1, mutual2, otherFriend
        Friendship::create(['user_id' => $viewer->id, 'friend_id' => $mutual1->id, 'status' => Friendship::STATUS_ACCEPTED]);
        Friendship::create(['user_id' => $viewer->id, 'friend_id' => $mutual2->id, 'status' => Friendship::STATUS_ACCEPTED]);
        Friendship::create(['user_id' => $viewer->id, 'friend_id' => $otherFriend->id, 'status' => Friendship::STATUS_ACCEPTED]);

        // Target friends with mutual1, mutual2
        Friendship::create(['user_id' => $target->id, 'friend_id' => $mutual1->id, 'status' => Friendship::STATUS_ACCEPTED]);
        Friendship::create(['user_id' => $target->id, 'friend_id' => $mutual2->id, 'status' => Friendship::STATUS_ACCEPTED]);

        $res = $this->actingAs($viewer, 'sanctum')->getJson("/api/v1/friends/mutual/{$target->id}");
        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_suggestions_engine_signals_and_endpoint(): void
    {
        $user = User::factory()->create();
        UserProfile::create(['user_id' => $user->id, 'city' => 'Dhaka']);

        $candidateCity = User::factory()->create(['status' => 'active']);
        UserProfile::create(['user_id' => $candidateCity->id, 'city' => 'Dhaka']);

        $candidateMutual = User::factory()->create(['status' => 'active']);
        $mutual = User::factory()->create();

        // user friends with mutual
        Friendship::create(['user_id' => $user->id, 'friend_id' => $mutual->id, 'status' => Friendship::STATUS_ACCEPTED]);
        // candidateMutual friends with mutual
        Friendship::create(['user_id' => $candidateMutual->id, 'friend_id' => $mutual->id, 'status' => Friendship::STATUS_ACCEPTED]);

        $res = $this->actingAs($user, 'sanctum')->getJson('/api/v1/friends/suggestions');
        $res->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $res->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_birthdays_categorization_and_endpoint(): void
    {
        $user = User::factory()->create();
        $bdayTodayFriend = User::factory()->create([
            'birth_date' => Carbon::today()->subYears(25)->format('Y-m-d'),
        ]);
        $bdayUpcomingFriend = User::factory()->create([
            'birth_date' => Carbon::today()->addDays(5)->subYears(22)->format('Y-m-d'),
        ]);

        Friendship::create(['user_id' => $user->id, 'friend_id' => $bdayTodayFriend->id, 'status' => Friendship::STATUS_ACCEPTED]);
        Friendship::create(['user_id' => $user->id, 'friend_id' => $bdayUpcomingFriend->id, 'status' => Friendship::STATUS_ACCEPTED]);

        $res = $this->actingAs($user, 'sanctum')->getJson('/api/v1/friends/birthdays');
        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.counts.today', 1)
            ->assertJsonPath('data.counts.upcoming', 1);
    }

    public function test_custom_friend_lists_crud_and_membership(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        Friendship::create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => Friendship::STATUS_ACCEPTED]);

        // 1. Create list
        $createRes = $this->actingAs($user, 'sanctum')->postJson('/api/v1/friends/lists', [
            'name' => 'College Buddies',
            'description' => 'University friends',
            'type' => 'school',
        ]);
        $createRes->assertStatus(201)->assertJsonPath('success', true);
        $listId = $createRes->json('data.id');

        // 2. Add member to list
        $addRes = $this->actingAs($user, 'sanctum')->postJson("/api/v1/friends/lists/{$listId}/members/{$friend->id}");
        $addRes->assertStatus(200)->assertJsonPath('success', true);

        // 3. View list members
        $membersRes = $this->actingAs($user, 'sanctum')->getJson("/api/v1/friends/lists/{$listId}/members");
        $membersRes->assertStatus(200)->assertJsonPath('meta.total', 1);

        // 4. Remove member
        $remRes = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/friends/lists/{$listId}/members/{$friend->id}");
        $remRes->assertStatus(200)->assertJsonPath('success', true);

        // 5. Delete list
        $delRes = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/friends/lists/{$listId}");
        $delRes->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_toggle_favorite_close_friend_and_restricted(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        Friendship::create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => Friendship::STATUS_ACCEPTED]);

        // Favorite
        $favRes = $this->actingAs($user, 'sanctum')->postJson("/api/v1/friends/{$friend->id}/favorite");
        $favRes->assertStatus(200)->assertJsonPath('is_favorite', true);

        // Close Friend
        $closeRes = $this->actingAs($user, 'sanctum')->postJson("/api/v1/friends/{$friend->id}/close");
        $closeRes->assertStatus(200)->assertJsonPath('is_close_friend', true);

        // Restricted
        $restRes = $this->actingAs($user, 'sanctum')->postJson("/api/v1/friends/{$friend->id}/restricted");
        $restRes->assertStatus(200)->assertJsonPath('is_restricted', true);
    }

    public function test_bulk_operations_accept_decline_delete_block(): void
    {
        $user = User::factory()->create();
        $senders = User::factory()->count(3)->create();

        foreach ($senders as $s) {
            Friendship::create([
                'user_id' => $s->id,
                'friend_id' => $user->id,
                'status' => Friendship::STATUS_PENDING,
            ]);
        }

        // Bulk accept first 2
        $acceptIds = [$senders[0]->id, $senders[1]->id];
        $bulkAcceptRes = $this->actingAs($user, 'sanctum')->postJson('/api/v1/friends/requests/bulk-accept', [
            'ids' => $acceptIds,
        ]);
        $bulkAcceptRes->assertStatus(200)->assertJsonCount(2, 'data.success');

        // Bulk decline 3rd
        $declineIds = [$senders[2]->id];
        $bulkDeclineRes = $this->actingAs($user, 'sanctum')->postJson('/api/v1/friends/requests/bulk-decline', [
            'ids' => $declineIds,
        ]);
        $bulkDeclineRes->assertStatus(200)->assertJsonCount(1, 'data.success');
    }

    public function test_counters_and_relationship_status_api(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        Friendship::create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => Friendship::STATUS_ACCEPTED]);

        $res = $this->actingAs($user, 'sanctum')->getJson('/api/v1/friends/counters');
        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_friends', 1);
    }

    public function test_friends_web_page_rendering(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user, 'web')->get('/friends');
        $res->assertStatus(200)
            ->assertSee('বন্ধু ও সংযোগ')
            ->assertSee('সকল বন্ধুরা');

        // Test tabs
        $resReq = $this->actingAs($user, 'web')->get('/friends?tab=requests');
        $resReq->assertStatus(200)->assertSee('অনুরোধসমূহ');

        $resSugg = $this->actingAs($user, 'web')->get('/friends?tab=suggestions');
        $resSugg->assertStatus(200)->assertSee('সুপারিশসমূহ');

        $resBday = $this->actingAs($user, 'web')->get('/friends?tab=birthdays');
        $resBday->assertStatus(200)->assertSee('জন্মদিন');
    }
}
