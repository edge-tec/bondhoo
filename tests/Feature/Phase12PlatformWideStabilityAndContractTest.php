<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Page;
use App\Models\PageFollower;
use App\Models\PageMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase12PlatformWideStabilityAndContractTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $admin;

    protected User $stranger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Test User', 'username' => 'testuser']);
        $this->admin = User::factory()->create(['name' => 'Admin User', 'username' => 'adminuser']);
        $this->stranger = User::factory()->create(['name' => 'Stranger User', 'username' => 'stranger']);
    }

    /**
     * 1. Page API Contract & Null-Safe Serialization.
     */
    public function test_page_index_contract_and_social_links_null_safety(): void
    {
        // Create page with NULL social_links in DB
        $page = Page::create([
            'name' => 'Null Links Page',
            'slug' => 'null-links-page',
            'category' => 'Technology',
            'owner_id' => $this->user->id,
            'status' => Page::STATUS_ACTIVE,
            'social_links' => null,
            'business_hours' => null,
            'followers_count' => 0,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/pages');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'category',
                        'social_links',
                        'business_hours',
                        'owner' => ['id', 'name', 'username'],
                        'followers_count',
                        'is_following',
                        'is_owner',
                    ],
                ],
            ]);

        // CRITICAL: social_links must be a JSON array ([]), never null, preventing Android client deserialization crash
        $items = $response->json('data');
        $this->assertNotEmpty($items);
        $this->assertIsArray($items[0]['social_links']);
        $this->assertEmpty($items[0]['social_links']);
    }

    /**
     * 2. Page Follow Contract & Atomic Counter Consistency.
     */
    public function test_page_follow_unfollow_contract_and_counter_consistency(): void
    {
        $page = Page::create([
            'name' => 'Alpha Corp',
            'slug' => 'alpha-corp',
            'category' => 'Business',
            'owner_id' => $this->admin->id,
            'status' => Page::STATUS_ACTIVE,
            'followers_count' => 0,
        ]);

        // Follow
        $followRes = $this->actingAs($this->user)->postJson("/api/v1/pages/{$page->id}/follow");
        $followRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'page_id' => $page->id,
                    'is_following' => true,
                    'followers_count' => 1,
                ],
            ]);

        $this->assertEquals(1, $page->fresh()->followers_count);
        $this->assertDatabaseHas('page_followers', [
            'page_id' => $page->id,
            'user_id' => $this->user->id,
        ]);

        // Unfollow
        $unfollowRes = $this->actingAs($this->user)->postJson("/api/v1/pages/{$page->id}/follow");
        $unfollowRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'page_id' => $page->id,
                    'is_following' => false,
                    'followers_count' => 0,
                ],
            ]);

        $this->assertEquals(0, $page->fresh()->followers_count);
        $this->assertDatabaseMissing('page_followers', [
            'page_id' => $page->id,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * 3. Group Join Contract & Duplicate Join Prevention.
     */
    public function test_group_join_contract_and_duplicate_prevention(): void
    {
        $group = Group::create([
            'name' => 'Kotlin Developers BD',
            'slug' => 'kotlin-devs-bd',
            'creator_id' => $this->admin->id,
            'status' => Group::STATUS_ACTIVE,
            'privacy' => Group::PRIVACY_PUBLIC,
            'members_count' => 1,
            'active_members_count' => 1,
        ]);

        // Initial Join
        $joinRes = $this->actingAs($this->user)->postJson("/api/v1/groups/{$group->id}/join");
        $joinRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.group_id', $group->id)
            ->assertJsonPath('data.status', 'joined')
            ->assertJsonPath('data.members_count', 2);

        $this->assertEquals(2, $group->fresh()->members_count);

        // Immediate Duplicate Join Attempt (Concurrency / Re-attempt safety)
        $dupRes = $this->actingAs($this->user)->postJson("/api/v1/groups/{$group->id}/join");
        $dupRes->assertStatus(200)
            ->assertJsonPath('data.status', 'already_member');

        // Ensure count was NOT incremented twice
        $this->assertEquals(2, $group->fresh()->members_count);
        $this->assertEquals(1, GroupMember::where('group_id', $group->id)->where('user_id', $this->user->id)->count());
    }

    /**
     * 4. Group Leave Contract & Owner Isolation.
     */
    public function test_group_leave_contract_and_owner_cannot_abandon_group(): void
    {
        $group = Group::create([
            'name' => 'Backend Guild',
            'slug' => 'backend-guild',
            'creator_id' => $this->admin->id,
            'status' => Group::STATUS_ACTIVE,
            'privacy' => Group::PRIVACY_PUBLIC,
            'members_count' => 1,
            'active_members_count' => 1,
        ]);

        // Join as regular member
        $this->actingAs($this->user)->postJson("/api/v1/groups/{$group->id}/join");
        $this->assertEquals(2, $group->fresh()->members_count);

        // Member Leaves
        $leaveRes = $this->actingAs($this->user)->postJson("/api/v1/groups/{$group->id}/leave");
        $leaveRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(1, $group->fresh()->members_count);
        $this->assertDatabaseMissing('group_members', [
            'group_id' => $group->id,
            'user_id' => $this->user->id,
        ]);

        // Owner Attempts To Leave Without Transferring Ownership (Security Gate)
        $ownerLeaveRes = $this->actingAs($this->admin)->postJson("/api/v1/groups/{$group->id}/leave");
        $ownerLeaveRes->assertStatus(422)
            ->assertJsonPath('success', false);

        // Owner is still preserved
        $this->assertEquals(1, $group->fresh()->members_count);
    }

    /**
     * 5. Private Group Membership Approval Workflow.
     */
    public function test_private_group_requires_admin_approval(): void
    {
        $privateGroup = Group::create([
            'name' => 'Executive Circle',
            'slug' => 'executive-circle',
            'creator_id' => $this->admin->id,
            'status' => Group::STATUS_ACTIVE,
            'privacy' => Group::PRIVACY_PRIVATE,
            'members_count' => 1,
            'active_members_count' => 1,
        ]);

        // Stranger attempts to join private group -> goes to pending queue
        $joinRes = $this->actingAs($this->stranger)->postJson("/api/v1/groups/{$privateGroup->id}/join");
        $joinRes->assertStatus(200)
            ->assertJsonPath('data.status', 'pending');

        // Member count remains 1 while pending
        $this->assertEquals(1, $privateGroup->fresh()->members_count);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $privateGroup->id,
            'user_id' => $this->stranger->id,
            'status' => GroupMember::STATUS_PENDING,
        ]);

        // Non-admin stranger cannot approve request (Security / IDOR gate)
        $unauthApprove = $this->actingAs($this->user)->postJson("/api/v1/groups/{$privateGroup->id}/requests/{$this->stranger->id}/approve");
        $unauthApprove->assertStatus(403);

        // Group Admin approves request
        $adminApprove = $this->actingAs($this->admin)->postJson("/api/v1/groups/{$privateGroup->id}/requests/{$this->stranger->id}/approve");
        $adminApprove->assertStatus(200);

        // Member count incremented to 2 and status becomes active
        $this->assertEquals(2, $privateGroup->fresh()->members_count);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $privateGroup->id,
            'user_id' => $this->stranger->id,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);
    }

    /**
     * 6. Pages & Groups Filter Isolation (following, your_pages, your_groups, manage).
     */
    public function test_filter_isolation_between_authenticated_users(): void
    {
        // 1. Pages
        $userPage = Page::create([
            'name' => 'User Owned Page',
            'slug' => 'user-owned-page',
            'category' => 'Tech',
            'owner_id' => $this->user->id,
            'status' => Page::STATUS_ACTIVE,
            'followers_count' => 1,
        ]);
        PageMember::create([
            'page_id' => $userPage->id,
            'user_id' => $this->user->id,
            'role' => Page::ROLE_OWNER,
            'status' => 'active',
        ]);

        $adminPage = Page::create([
            'name' => 'Admin Owned Page',
            'slug' => 'admin-owned-page',
            'category' => 'Finance',
            'owner_id' => $this->admin->id,
            'status' => Page::STATUS_ACTIVE,
            'followers_count' => 1,
        ]);
        PageFollower::create([
            'page_id' => $adminPage->id,
            'user_id' => $this->user->id,
        ]);

        // Query your_pages for $user: must contain userPage, must not contain adminPage
        $yourPagesRes = $this->actingAs($this->user)->getJson('/api/v1/pages?filter=your_pages');
        $yourPagesRes->assertStatus(200);
        $pageIds = collect($yourPagesRes->json('data'))->pluck('id')->all();
        $this->assertContains($userPage->id, $pageIds);
        $this->assertNotContains($adminPage->id, $pageIds);

        // Query following for $user: must contain adminPage
        $followingRes = $this->actingAs($this->user)->getJson('/api/v1/pages?filter=following');
        $followingRes->assertStatus(200);
        $followedIds = collect($followingRes->json('data'))->pluck('id')->all();
        $this->assertContains($adminPage->id, $followedIds);

        // 2. Groups
        $groupA = Group::create([
            'name' => 'Managed By User',
            'slug' => 'managed-by-user',
            'creator_id' => $this->user->id,
            'status' => Group::STATUS_ACTIVE,
            'privacy' => Group::PRIVACY_PUBLIC,
            'members_count' => 1,
        ]);
        GroupMember::create([
            'group_id' => $groupA->id,
            'user_id' => $this->user->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        $groupB = Group::create([
            'name' => 'Managed By Admin',
            'slug' => 'managed-by-admin',
            'creator_id' => $this->admin->id,
            'status' => Group::STATUS_ACTIVE,
            'privacy' => Group::PRIVACY_PUBLIC,
            'members_count' => 1,
        ]);
        GroupMember::create([
            'group_id' => $groupB->id,
            'user_id' => $this->admin->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        // Query manage for $user
        $manageGroupsRes = $this->actingAs($this->user)->getJson('/api/v1/groups?filter=manage');
        $manageGroupsRes->assertStatus(200);
        $managedGroupIds = collect($manageGroupsRes->json('data'))->pluck('id')->all();
        $this->assertContains($groupA->id, $managedGroupIds);
        $this->assertNotContains($groupB->id, $managedGroupIds);
    }
}
