<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5CompleteGroupsSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(string $username, string $name, string $role = 'USER'): User
    {
        $user = User::factory()->create([
            'username' => $username,
            'name' => $name,
            'email' => "{$username}@example.com",
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'avatar_url' => "https://ui-avatars.com/api/?name={$username}",
                'bio' => "Bio of {$name}",
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'dark_mode' => true,
            ]
        );

        $roleModel = Role::where('name', $role)->first();
        if ($roleModel) {
            $user->roles()->syncWithoutDetaching([$roleModel->id]);
        }

        return $user;
    }

    /**
     * 1. Test Group Creation with Privacy, Categories, Rules, and Questions
     */
    public function test_group_creation_with_rules_and_categories(): void
    {
        $owner = $this->createUser('group_owner', 'Group Owner');

        $payload = [
            'name' => 'Laravel Artisans Dhaka',
            'username' => 'laravel-artisans-dhaka',
            'description' => 'Dedicated group for modern full-stack web and mobile artisans.',
            'category' => 'Technology',
            'privacy' => 'public',
            'membership_approval_mode' => 'anyone',
            'post_approval_mode' => 'auto',
            'rules' => [
                ['title' => 'Be Courteous', 'description' => 'Support and respect each artisan in the community.'],
                ['title' => 'No Direct Ads', 'description' => 'Keep job listings in the designated threads.'],
            ],
            'questions' => [
                ['question' => 'How many years have you built with Laravel?', 'type' => 'text'],
            ],
        ];

        $res = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/groups', $payload);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Laravel Artisans Dhaka')
            ->assertJsonPath('data.privacy', 'public');

        $this->assertDatabaseHas('groups', [
            'name' => 'Laravel Artisans Dhaka',
            'creator_id' => $owner->id,
            'category' => 'Technology',
        ]);

        $this->assertDatabaseHas('group_rules', [
            'title' => 'Be Courteous',
        ]);

        // Verify owner automatically has OWNER role in group_members and is creator
        $group = Group::where('username', 'laravel-artisans-dhaka')->first();
        $this->assertNotNull($group);
        $this->assertEquals($owner->id, $group->creator_id);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);
    }

    /**
     * 2. Test Membership Lifecycle: Join, Admin Approval, Roles, Ban, and Leave
     */
    public function test_group_membership_lifecycle_roles_and_moderation(): void
    {
        $owner = $this->createUser('owner_mod', 'Owner Mod');
        $applicant = $this->createUser('applicant_user', 'Applicant User');

        // Create Private Group requiring approval
        $groupRes = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/groups', [
            'name' => 'Secret Guild',
            'privacy' => 'private',
            'membership_approval_mode' => 'approval_required',
        ]);
        $groupId = $groupRes->json('data.id');

        // 1. Applicant requests to join
        $joinRes = $this->actingAs($applicant, 'sanctum')->postJson("/api/v1/groups/{$groupId}/join");
        $joinRes->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $groupId,
            'user_id' => $applicant->id,
            'status' => GroupMember::STATUS_PENDING,
        ]);

        // 2. Owner views pending requests
        $reqRes = $this->actingAs($owner, 'sanctum')->getJson("/api/v1/groups/{$groupId}/requests");
        $reqRes->assertStatus(200);
        $this->assertNotEmpty($reqRes->json('data'));

        // 3. Owner approves applicant
        $approveRes = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/groups/{$groupId}/requests/{$applicant->id}/approve");
        $approveRes->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $groupId,
            'user_id' => $applicant->id,
            'status' => GroupMember::STATUS_ACTIVE,
            'role' => GroupMember::ROLE_MEMBER,
        ]);

        // 4. Owner promotes applicant to Moderator
        $roleRes = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/groups/{$groupId}/members/{$applicant->id}/role", [
            'role' => 'moderator',
        ]);
        $roleRes->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $groupId,
            'user_id' => $applicant->id,
            'role' => GroupMember::ROLE_MODERATOR,
        ]);

        // 5. Owner bans applicant for violation
        $banRes = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/groups/{$groupId}/members/{$applicant->id}/ban", [
            'reason' => 'Repeated guideline breaches.',
        ]);
        $banRes->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $groupId,
            'user_id' => $applicant->id,
            'status' => GroupMember::STATUS_BANNED,
        ]);

        // 6. Owner unbans applicant
        $unbanRes = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/groups/{$groupId}/members/{$applicant->id}/unban");
        $unbanRes->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $groupId,
            'user_id' => $applicant->id,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);
    }

    /**
     * 3. Test Group Post Creation, Feed, and Post Deletion
     */
    public function test_group_posts_and_feed(): void
    {
        $owner = $this->createUser('group_poster', 'Group Poster');
        $groupRes = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/groups', [
            'name' => 'Open Source Enthusiasts',
            'privacy' => 'public',
            'post_approval_mode' => 'auto',
        ]);
        $groupId = $groupRes->json('data.id');

        // Post in group
        $postRes = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/groups/{$groupId}/posts", [
            'content' => 'Hello everyone! Excited to share our new Vue & Compose architectures.',
            'type' => 'text',
        ]);
        $postRes->assertStatus(201);
        $postId = $postRes->json('data.id');

        // Verify feed
        $feedRes = $this->actingAs($owner, 'sanctum')->getJson("/api/v1/groups/{$groupId}/posts");
        $feedRes->assertStatus(200);
        $posts = $feedRes->json('data');
        $this->assertNotEmpty($posts);

        // Delete post
        $deleteRes = $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/groups/{$groupId}/posts/{$postId}");
        $deleteRes->assertStatus(200);

        $this->assertSoftDeleted('posts', ['id' => $postId]);
    }

    /**
     * 4. Test Discovery, My Groups, and Search
     */
    public function test_group_discovery_and_user_groups(): void
    {
        $user = $this->createUser('discover_user', 'Discover User');

        // Create Public Group
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/groups', [
            'name' => 'Flutter & Jetpack Compose BD',
            'category' => 'Mobile',
            'privacy' => 'public',
        ]);

        // Discover groups
        $discRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/groups?q=Flutter');
        $discRes->assertStatus(200);
        $this->assertNotEmpty($discRes->json('data'));

        // My groups
        $myRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/groups/my');
        $myRes->assertStatus(200);
        $this->assertNotEmpty($myRes->json('data'));
    }

    /**
     * 5. Test Settings Update, Ownership Transfer, and Deletion Security
     */
    public function test_group_settings_ownership_transfer_and_deletion(): void
    {
        $owner = $this->createUser('alpha_owner', 'Alpha Owner');
        $successor = $this->createUser('successor_user', 'Successor User');
        $intruder = $this->createUser('intruder_user', 'Intruder User');

        $groupRes = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/groups', [
            'name' => 'Alpha Network',
            'privacy' => 'public',
        ]);
        $groupId = $groupRes->json('data.id');

        // Successor joins group
        $this->actingAs($successor, 'sanctum')->postJson("/api/v1/groups/{$groupId}/join");

        // 1. Intruder cannot update settings -> 403 Forbidden
        $intruderUpdate = $this->actingAs($intruder, 'sanctum')->putJson("/api/v1/groups/{$groupId}", [
            'name' => 'Hacked Name',
        ]);
        $intruderUpdate->assertStatus(403);

        // 2. Owner updates group settings
        $ownerUpdate = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/groups/{$groupId}", [
            'description' => 'Updated professional network description.',
        ]);
        $ownerUpdate->assertStatus(200);
        $this->assertDatabaseHas('groups', [
            'id' => $groupId,
            'description' => 'Updated professional network description.',
        ]);

        // 3. Owner transfers ownership to successor
        $transferRes = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/groups/{$groupId}/transfer-ownership", [
            'new_owner_id' => $successor->id,
        ]);
        $transferRes->assertStatus(200);

        $this->assertDatabaseHas('groups', [
            'id' => $groupId,
            'creator_id' => $successor->id,
        ]);

        // 4. Former owner cannot delete group anymore -> 403 Forbidden
        $failDelete = $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/groups/{$groupId}");
        $failDelete->assertStatus(403);

        // 5. New owner deletes group successfully
        $successDelete = $this->actingAs($successor, 'sanctum')->deleteJson("/api/v1/groups/{$groupId}");
        $successDelete->assertStatus(200);

        $this->assertSoftDeleted('groups', ['id' => $groupId]);
    }

    /**
     * 6. Test Web Route Resolution for Groups
     */
    public function test_group_web_routes_and_navigation(): void
    {
        $user = $this->createUser('web_groups_user', 'Web Groups User');

        // 1. Groups listing page
        $res = $this->actingAs($user, 'web')->get('/groups');
        $res->assertStatus(200);

        // 2. Group create page
        $createRes = $this->actingAs($user, 'web')->get('/groups/create');
        $createRes->assertStatus(200);
    }
}
