<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6ProfessionalGroupMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $admin;

    private User $moderator;

    private User $member1;

    private User $member2;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Alice Owner', 'username' => 'alice_owner']);
        $this->admin = User::factory()->create(['name' => 'Bob Admin', 'username' => 'bob_admin']);
        $this->moderator = User::factory()->create(['name' => 'Charlie Mod', 'username' => 'charlie_mod']);
        $this->member1 = User::factory()->create(['name' => 'David Member', 'username' => 'david_dev']);
        $this->member2 = User::factory()->create(['name' => 'Eva Explorer', 'username' => 'eva_exp']);

        $this->group = Group::create([
            'name' => 'Silicon Valley Developers',
            'slug' => 'silicon-valley-devs',
            'description' => 'A community for developers.',
            'category' => 'Technology',
            'privacy' => 'public',
            'creator_id' => $this->owner->id,
            'members_count' => 5,
        ]);

        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now()->subDays(10),
        ]);

        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->admin->id,
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now()->subDays(8),
        ]);

        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->moderator->id,
            'role' => 'moderator',
            'status' => 'active',
            'joined_at' => now()->subDays(5),
        ]);

        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->member1->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now()->subDays(3),
        ]);

        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->member2->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now()->subDay(),
        ]);
    }

    public function test_can_list_members_with_search_and_role_filter(): void
    {
        $response = $this->actingAs($this->owner)->getJson("/api/v1/groups/{$this->group->id}/members?role=admin");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->admin->name, $data[0]['name']);
        $this->assertEquals('admin', $data[0]['role']);
        $this->assertArrayHasKey('posts_published', $data[0]);
        $this->assertArrayHasKey('comments_count', $data[0]);
        $this->assertArrayHasKey('account_status', $data[0]);
    }

    public function test_can_search_members_by_keyword(): void
    {
        $response = $this->actingAs($this->owner)->getJson("/api/v1/groups/{$this->group->id}/members?query=David");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('David Member', $data[0]['name']);
    }

    public function test_can_sort_members_alphabetically_and_by_date(): void
    {
        $responseAsc = $this->actingAs($this->owner)->getJson("/api/v1/groups/{$this->group->id}/members?sort=alphabetical");
        $responseAsc->assertStatus(200);
        $names = array_column($responseAsc->json('data'), 'name');
        $this->assertEquals('Alice Owner', $names[0]);
        $this->assertEquals('Bob Admin', $names[1]);

        $responseOldest = $this->actingAs($this->owner)->getJson("/api/v1/groups/{$this->group->id}/members?sort=join_date_asc");
        $responseOldest->assertStatus(200);
        $oldestNames = array_column($responseOldest->json('data'), 'name');
        $this->assertEquals('Alice Owner', $oldestNames[0]);
    }

    public function test_can_inspect_member_activity_details(): void
    {
        Post::create([
            'user_id' => $this->member1->id,
            'group_id' => $this->group->id,
            'content' => 'Hello Silicon Valley Devs!',
            'type' => 'text',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->owner)->getJson("/api/v1/groups/{$this->group->id}/members/{$this->member1->id}/activity");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.member.name', 'David Member')
            ->assertJsonPath('data.activity_summary.posts_count', 1);
    }

    public function test_can_restrict_and_unrestrict_member(): void
    {
        $restrictResponse = $this->actingAs($this->admin)->postJson("/api/v1/groups/{$this->group->id}/members/{$this->member1->id}/restrict", [
            'duration_days' => 7,
            'reason' => 'Posting promotional links without approval',
        ]);

        $restrictResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.account_status', 'restricted');

        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'user_id' => $this->member1->id,
            'status' => 'restricted',
        ]);

        $unrestrictResponse = $this->actingAs($this->admin)->postJson("/api/v1/groups/{$this->group->id}/members/{$this->member1->id}/unrestrict");

        $unrestrictResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.account_status', 'active');

        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'user_id' => $this->member1->id,
            'status' => 'active',
        ]);
    }

    public function test_can_execute_bulk_actions_on_members(): void
    {
        $bulkRoleResponse = $this->actingAs($this->owner)->postJson("/api/v1/groups/{$this->group->id}/members/bulk-action", [
            'action' => 'assign_role',
            'user_ids' => [$this->member1->id, $this->member2->id],
            'role' => 'moderator',
        ]);

        $bulkRoleResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.processed_count', 2);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'user_id' => $this->member1->id,
            'role' => 'moderator',
        ]);

        $bulkRemoveResponse = $this->actingAs($this->owner)->postJson("/api/v1/groups/{$this->group->id}/members/bulk-action", [
            'action' => 'remove',
            'user_ids' => [$this->member1->id],
            'reason' => 'Inactivity cleanup',
        ]);

        $bulkRemoveResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.processed_count', 1);

        $this->assertDatabaseMissing('group_members', [
            'group_id' => $this->group->id,
            'user_id' => $this->member1->id,
        ]);
    }

    public function test_regular_member_cannot_restrict_or_perform_bulk_actions(): void
    {
        $response = $this->actingAs($this->member1)->postJson("/api/v1/groups/{$this->group->id}/members/{$this->member2->id}/restrict", [
            'duration_days' => 3,
            'reason' => 'Unwarranted report',
        ]);

        $response->assertStatus(403);
    }
}
