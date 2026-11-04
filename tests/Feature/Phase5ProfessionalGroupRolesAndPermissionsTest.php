<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupCategory;
use App\Models\GroupCustomRole;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase5ProfessionalGroupRolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        GroupCategory::firstOrCreate(
            ['slug' => 'technology'],
            [
                'name' => 'Technology',
                'description' => 'Tech discussions and community engineering.',
                'icon' => 'Cpu',
                'color' => '#3B82F6',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }

    public function test_user_can_fetch_roles_catalog_with_default_and_custom_roles(): void
    {
        $owner = User::factory()->create(['name' => 'RBAC Owner']);
        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Enterprise Architecture Network',
            'username' => 'enterprise-arch',
            'slug' => 'enterprise-architecture-network',
            'privacy' => 'public',
            'category' => 'Technology',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        // Create 1 custom role
        GroupCustomRole::create([
            'group_id' => $group->id,
            'name' => 'community_evangelist',
            'display_name' => 'Community Evangelist',
            'description' => 'Champions community growth and partnerships',
            'color' => '#EC4899',
            'permissions' => ['publish_content', 'manage_events', 'invite_members'],
            'is_system' => false,
            'sort_order' => 1,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/groups/{$group->id}/roles");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.can_manage_roles', true);

        // Verify default 6 system roles
        $defaultRoles = $response->json('data.default_roles');
        $this->assertCount(6, $defaultRoles);
        $roleNames = array_column($defaultRoles, 'name');
        $this->assertContains('owner', $roleNames);
        $this->assertContains('admin', $roleNames);
        $this->assertContains('moderator', $roleNames);
        $this->assertContains('content_manager', $roleNames);
        $this->assertContains('member', $roleNames);
        $this->assertContains('restricted', $roleNames);

        // Verify custom roles list
        $customRoles = $response->json('data.custom_roles');
        $this->assertCount(1, $customRoles);
        $this->assertEquals('community_evangelist', $customRoles[0]['name']);
        $this->assertEquals('Community Evangelist', $customRoles[0]['display_name']);

        // Verify available permissions categories
        $categories = $response->json('data.available_permissions');
        $this->assertNotEmpty($categories);
    }

    public function test_group_owner_can_create_update_and_delete_custom_role(): void
    {
        $owner = User::factory()->create(['name' => 'Custom Role Manager']);
        $member = User::factory()->create(['name' => 'Assigned User']);

        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Custom Roles Test Group',
            'username' => 'custom-roles-test',
            'slug' => 'custom-roles-test-group',
            'privacy' => 'public',
            'category' => 'Technology',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($owner);

        // 1. Create Custom Role
        $createResponse = $this->postJson("/api/v1/groups/{$group->id}/roles", [
            'name' => 'event_coordinator',
            'display_name' => 'Event Coordinator',
            'description' => 'Responsible for scheduling community webinars',
            'color' => '#10B981',
            'permissions' => ['manage_events', 'publish_content'],
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'event_coordinator')
            ->assertJsonPath('data.display_name', 'Event Coordinator');

        $roleId = $createResponse->json('data.id');

        // 2. Update Custom Role
        $updateResponse = $this->putJson("/api/v1/groups/{$group->id}/roles/{$roleId}", [
            'display_name' => 'Lead Event Director',
            'color' => '#F59E0B',
            'permissions' => ['manage_events', 'publish_content', 'manage_announcements'],
        ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.display_name', 'Lead Event Director')
            ->assertJsonPath('data.color', '#F59E0B');

        // 3. Assign member to this custom role
        $this->postJson("/api/v1/groups/{$group->id}/roles/assign", [
            'role' => 'event_coordinator',
            'user_ids' => [$member->id],
        ])->assertStatus(200);

        $this->assertEquals('event_coordinator', $group->getMembership($member->id)->role);

        // 4. Delete Custom Role -> member reverts to 'member'
        $deleteResponse = $this->deleteJson("/api/v1/groups/{$group->id}/roles/{$roleId}");
        $deleteResponse->assertStatus(200);

        $this->assertDatabaseMissing('group_custom_roles', ['id' => $roleId]);

        $updatedMember = GroupMember::where('group_id', $group->id)->where('user_id', $member->id)->first();
        $this->assertEquals(GroupMember::ROLE_MEMBER, $updatedMember->role);
    }

    public function test_role_assignment_hierarchy_enforcement(): void
    {
        $owner = User::factory()->create(['name' => 'Owner']);
        $admin = User::factory()->create(['name' => 'Admin']);
        $user1 = User::factory()->create(['name' => 'User One']);
        $user2 = User::factory()->create(['name' => 'User Two']);

        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Hierarchy Enforcement Group',
            'username' => 'hierarchy-group',
            'slug' => 'hierarchy-enforcement-group',
            'privacy' => 'public',
            'category' => 'Technology',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $admin->id,
            'role' => GroupMember::ROLE_ADMIN,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user1->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user2->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        // 1. Owner can assign Admin role
        Sanctum::actingAs($owner);
        $resp = $this->postJson("/api/v1/groups/{$group->id}/roles/assign", [
            'role' => 'admin',
            'user_ids' => [$user1->id],
        ]);
        $resp->assertStatus(200);
        $this->assertEquals('admin', GroupMember::where('group_id', $group->id)->where('user_id', $user1->id)->first()->role);

        // 2. Admin cannot appoint another Admin (Owner privilege)
        Sanctum::actingAs($admin);
        $failAdmin = $this->postJson("/api/v1/groups/{$group->id}/roles/assign", [
            'role' => 'admin',
            'user_ids' => [$user2->id],
        ]);
        $failAdmin->assertStatus(403);

        // 3. Admin can appoint a Moderator
        $successMod = $this->postJson("/api/v1/groups/{$group->id}/roles/assign", [
            'role' => 'moderator',
            'user_ids' => [$user2->id],
        ]);
        $successMod->assertStatus(200);
        $this->assertEquals('moderator', GroupMember::where('group_id', $group->id)->where('user_id', $user2->id)->first()->role);

        // 4. Regular member cannot assign roles
        Sanctum::actingAs($user2);
        $failMember = $this->postJson("/api/v1/groups/{$group->id}/roles/assign", [
            'role' => 'content_manager',
            'user_ids' => [$user1->id],
        ]);
        $failMember->assertStatus(403);
    }

    public function test_bulk_role_assignment_and_revocation(): void
    {
        $owner = User::factory()->create();
        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Bulk Role Group',
            'username' => 'bulk-role-group',
            'slug' => 'bulk-role-group',
            'privacy' => 'public',
            'category' => 'Technology',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        $users = User::factory()->count(3)->create();
        foreach ($users as $u) {
            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $u->id,
                'role' => GroupMember::ROLE_MEMBER,
                'status' => GroupMember::STATUS_ACTIVE,
            ]);
        }

        Sanctum::actingAs($owner);

        // Bulk assign content_manager to all 3 members
        $userIds = $users->pluck('id')->toArray();
        $bulkAssign = $this->postJson("/api/v1/groups/{$group->id}/roles/assign", [
            'role' => 'content_manager',
            'user_ids' => $userIds,
        ]);
        $bulkAssign->assertStatus(200);

        $assignedCount = GroupMember::where('group_id', $group->id)->where('role', 'content_manager')->count();
        $this->assertEquals(3, $assignedCount);

        // Bulk revoke back to member
        $bulkRevoke = $this->postJson("/api/v1/groups/{$group->id}/roles/revoke", [
            'user_ids' => $userIds,
        ]);
        $bulkRevoke->assertStatus(200);

        $memberCount = GroupMember::where('group_id', $group->id)->where('role', 'member')->count();
        $this->assertEquals(3, $memberCount);
    }

    public function test_role_audit_history_logging(): void
    {
        $owner = User::factory()->create(['name' => 'Auditor Owner']);
        $target = User::factory()->create(['name' => 'Audited Member']);

        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Role History Audit Group',
            'username' => 'role-audit-group',
            'slug' => 'role-history-audit-group',
            'privacy' => 'public',
            'category' => 'Technology',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $target->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($owner);

        // Assign role to generate history
        $this->postJson("/api/v1/groups/{$group->id}/roles/assign", [
            'role' => 'moderator',
            'user_ids' => [$target->id],
        ])->assertStatus(200);

        // Fetch history
        $response = $this->getJson("/api/v1/groups/{$group->id}/roles/history");
        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $history = $response->json('data');
        $this->assertNotEmpty($history);
        $this->assertEquals('assign_role', $history[0]['action']);
        $this->assertEquals($owner->id, $history[0]['moderator']['id']);
        $this->assertEquals($target->id, $history[0]['target_user']['id']);
    }

    public function test_permissions_enforced_by_laravel_policy(): void
    {
        $owner = User::factory()->create();
        $moderator = User::factory()->create();
        $restricted = User::factory()->create();

        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Policy Test Group',
            'username' => 'policy-group',
            'slug' => 'policy-test-group',
            'privacy' => 'public',
            'category' => 'Technology',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        $modMember = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $moderator->id,
            'role' => GroupMember::ROLE_MODERATOR,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        $restrictedMember = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $restricted->id,
            'role' => GroupMember::ROLE_RESTRICTED,
            'status' => GroupMember::STATUS_RESTRICTED,
        ]);

        // Moderator has manage_comments & manage_posts permissions
        $this->assertTrue($modMember->hasPermission('manage_comments'));
        $this->assertTrue($modMember->hasPermission('manage_posts'));
        $this->assertTrue($modMember->hasPermission('approve_members'));
        // But not manage_settings or manage_roles
        $this->assertFalse($modMember->hasPermission('manage_settings'));
        $this->assertFalse($modMember->hasPermission('manage_roles'));

        // Restricted member has no posting or management permissions
        $this->assertFalse($restrictedMember->hasPermission('publish_content'));
        $this->assertFalse($restrictedMember->hasPermission('manage_posts'));
        $this->assertFalse($group->canPost($restricted->id));
    }
}
