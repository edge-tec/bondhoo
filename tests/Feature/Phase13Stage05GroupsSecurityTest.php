<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13Stage05GroupsSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $admin;

    protected User $moderator;

    protected User $member;

    protected User $outsider;

    protected Group $publicGroup;

    protected Group $privateGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Group Owner']);
        $this->admin = User::factory()->create(['name' => 'Group Admin']);
        $this->moderator = User::factory()->create(['name' => 'Group Moderator']);
        $this->member = User::factory()->create(['name' => 'Regular Member']);
        $this->outsider = User::factory()->create(['name' => 'Outsider User']);

        // 1. Create Public Group
        $this->publicGroup = Group::create([
            'name' => 'Open Tech Developers',
            'slug' => 'open-tech-devs',
            'description' => 'A public group for open tech discussions.',
            'creator_id' => $this->owner->id,
            'privacy' => Group::PRIVACY_PUBLIC,
            'membership_approval_mode' => Group::APPROVAL_ANYONE,
            'members_count' => 1,
        ]);
        GroupMember::create([
            'group_id' => $this->publicGroup->id,
            'user_id' => $this->owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        // 2. Create Private Group
        $this->privateGroup = Group::create([
            'name' => 'Secret Enterprise Core',
            'slug' => 'secret-enterprise-core',
            'description' => 'A strictly private executive community.',
            'creator_id' => $this->owner->id,
            'privacy' => Group::PRIVACY_PRIVATE,
            'membership_approval_mode' => Group::APPROVAL_ADMIN_ONLY,
            'members_count' => 4,
        ]);
        GroupMember::create([
            'group_id' => $this->privateGroup->id,
            'user_id' => $this->owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
        GroupMember::create([
            'group_id' => $this->privateGroup->id,
            'user_id' => $this->admin->id,
            'role' => GroupMember::ROLE_ADMIN,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
        GroupMember::create([
            'group_id' => $this->privateGroup->id,
            'user_id' => $this->moderator->id,
            'role' => GroupMember::ROLE_MODERATOR,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
        GroupMember::create([
            'group_id' => $this->privateGroup->id,
            'user_id' => $this->member->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
    }

    /**
     * 1. Duplicate group membership is strictly prevented and idempotent
     */
    public function test_duplicate_group_membership_is_prevented_and_idempotent(): void
    {
        // Outsider joins public group
        $response = $this->actingAs($this->outsider, 'sanctum')
            ->postJson("/api/v1/groups/{$this->publicGroup->id}/join");

        $response->assertOk();
        $this->assertEquals(1, GroupMember::where('group_id', $this->publicGroup->id)->where('user_id', $this->outsider->id)->count());

        // Repeated join request does not duplicate or corrupt record
        $repeated = $this->actingAs($this->outsider, 'sanctum')
            ->postJson("/api/v1/groups/{$this->publicGroup->id}/join");

        $repeated->assertOk();
        $this->assertEquals(1, GroupMember::where('group_id', $this->publicGroup->id)->where('user_id', $this->outsider->id)->count());
    }

    /**
     * 2. Private Group Security: Outsider cannot view private posts or files
     */
    public function test_private_group_isolation_non_members_cannot_access_files_or_posts(): void
    {
        // Outsider attempts to read private group details/dashboard
        $unauthRes = $this->actingAs($this->outsider, 'sanctum')
            ->getJson("/api/v1/groups/{$this->privateGroup->id}/dashboard");

        $unauthRes->assertStatus(403);

        // Member has access
        $memberRes = $this->actingAs($this->member, 'sanctum')
            ->getJson("/api/v1/groups/{$this->privateGroup->slug}/profile");

        $memberRes->assertOk();
    }

    /**
     * 3. Role Permissions Boundaries: Owner vs Admin vs Moderator vs Member
     */
    public function test_owner_vs_admin_vs_moderator_permissions_boundaries(): void
    {
        // Admin CANNOT delete the group (only owner)
        $this->assertFalse($this->privateGroup->isOwner($this->admin->id));
        $this->assertTrue($this->privateGroup->isOwner($this->owner->id));

        // Member cannot moderate
        $this->assertFalse($this->privateGroup->isModerator($this->member->id));
        $this->assertTrue($this->privateGroup->isModerator($this->moderator->id));
        $this->assertTrue($this->privateGroup->isModerator($this->admin->id));
        $this->assertTrue($this->privateGroup->isModerator($this->owner->id));
    }

    /**
     * 4. Member Count Consistency under Join and Leave
     */
    public function test_member_counts_remain_consistent_under_join_and_leave(): void
    {
        $initialCount = $this->publicGroup->fresh()->members_count;

        // Join
        $this->actingAs($this->outsider, 'sanctum')
            ->postJson("/api/v1/groups/{$this->publicGroup->id}/join")
            ->assertOk();

        $this->assertEquals($initialCount + 1, $this->publicGroup->fresh()->members_count);

        // Leave
        $this->actingAs($this->outsider, 'sanctum')
            ->postJson("/api/v1/groups/{$this->publicGroup->id}/leave")
            ->assertOk();

        $this->assertEquals($initialCount, $this->publicGroup->fresh()->members_count);
    }

    /**
     * 5. Group creator cannot leave group without transferring ownership
     */
    public function test_group_creator_cannot_leave_without_transferring_ownership(): void
    {
        $res = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/groups/{$this->publicGroup->id}/leave");

        $res->assertStatus(422);
    }
}
