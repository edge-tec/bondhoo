<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterpriseSocialHubGroupSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_endpoint_returns_expected_taxonomy(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/groups/categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(12, 'data');
    }

    public function test_group_media_upload_endpoint_handles_avatar_and_cover(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $avatarFile = UploadedFile::fake()->image('group_avatar.png', 500, 500);

        $res = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/groups/media/upload', [
                'avatar' => $avatarFile,
                'type' => 'avatar',
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'avatar');

        $avatarUrl = $res->json('data.url');
        $this->assertStringContainsString('/storage/groups/avatars/', $avatarUrl);
    }

    public function test_full_group_creation_with_wizard_data_and_invitations(): void
    {
        $creator = User::factory()->create();
        $friend1 = User::factory()->create();
        $friend2 = User::factory()->create();

        $response = $this->actingAs($creator, 'sanctum')
            ->postJson('/api/v1/groups', [
                'name' => 'Tech Enthusiasts Hub',
                'category' => 'Technology',
                'description' => 'A premier community for tech lovers and software developers.',
                'privacy' => 'public',
                'allow_member_invites' => true,
                'allow_member_posts' => true,
                'require_admin_approval' => false,
                'invited_user_ids' => [$friend1->id, $friend2->id],
                'rules' => [
                    ['title' => 'Be respectful and kind', 'description' => 'Treat everyone nicely.'],
                    ['title' => 'No spam', 'description' => 'No unsolicited ads.'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Tech Enthusiasts Hub')
            ->assertJsonPath('data.category', 'Technology')
            ->assertJsonPath('data.privacy', 'public');

        $groupId = $response->json('data.id');

        // Creator should be admin member and group owner
        $this->assertDatabaseHas('group_members', [
            'group_id' => $groupId,
            'user_id' => $creator->id,
            'role' => GroupMember::ROLE_ADMIN,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('groups', [
            'id' => $groupId,
            'creator_id' => $creator->id,
        ]);

        // Invitations should be created for friends
        $this->assertDatabaseHas('group_invitations', [
            'group_id' => $groupId,
            'inviter_id' => $creator->id,
            'invitee_id' => $friend1->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('group_invitations', [
            'group_id' => $groupId,
            'inviter_id' => $creator->id,
            'invitee_id' => $friend2->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);
    }

    public function test_invitation_lifecycle_accept_and_decline(): void
    {
        $creator = User::factory()->create();
        $invitee1 = User::factory()->create();
        $invitee2 = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($creator, [
            'name' => 'Design Circle',
            'privacy' => 'private',
        ]);

        // Creator invites friends
        $inviteRes = $this->actingAs($creator, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/invitations", [
                'user_ids' => [$invitee1->id, $invitee2->id],
            ]);
        $inviteRes->assertStatus(200)
            ->assertJsonPath('data.invited_count', 2);

        // Invitee 1 checks myInvitations
        $myInvitesRes = $this->actingAs($invitee1, 'sanctum')
            ->getJson('/api/v1/groups/invitations');
        $myInvitesRes->assertStatus(200)
            ->assertJsonCount(1, 'data');
        $invitationId1 = $myInvitesRes->json('data.0.id');

        // Invitee 1 accepts
        $acceptRes = $this->actingAs($invitee1, 'sanctum')
            ->postJson("/api/v1/groups/invitations/{$invitationId1}/accept");
        $acceptRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $invitee1->id,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('group_invitations', [
            'id' => $invitationId1,
            'status' => GroupInvitation::STATUS_ACCEPTED,
        ]);

        // Invitee 2 declines
        $myInvitesRes2 = $this->actingAs($invitee2, 'sanctum')
            ->getJson('/api/v1/groups/invitations');
        $invitationId2 = $myInvitesRes2->json('data.0.id');

        $declineRes = $this->actingAs($invitee2, 'sanctum')
            ->postJson("/api/v1/groups/invitations/{$invitationId2}/decline");
        $declineRes->assertStatus(200);

        $this->assertDatabaseHas('group_invitations', [
            'id' => $invitationId2,
            'status' => GroupInvitation::STATUS_DECLINED,
        ]);
    }

    public function test_group_notification_preferences_flow(): void
    {
        $creator = User::factory()->create();
        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($creator, ['name' => 'Notification Test Group']);

        // Fetch default preferences
        $getRes = $this->actingAs($creator, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/notification-preferences");

        $getRes->assertStatus(200)
            ->assertJsonPath('data.settings.new_posts', true)
            ->assertJsonPath('data.settings.comments', true);

        // Update preferences
        $putRes = $this->actingAs($creator, 'sanctum')
            ->putJson("/api/v1/groups/{$group->id}/notification-preferences", [
                'preference' => 'highlights',
                'settings' => [
                    'new_posts' => false,
                    'comments' => true,
                    'member_requests' => true,
                    'mentions' => true,
                    'events' => false,
                ],
            ]);

        $putRes->assertStatus(200)
            ->assertJsonPath('data.preference', 'highlights')
            ->assertJsonPath('data.settings.new_posts', false)
            ->assertJsonPath('data.settings.events', false);

        $this->assertDatabaseHas('group_notification_preferences', [
            'group_id' => $group->id,
            'user_id' => $creator->id,
            'preference' => 'highlights',
        ]);
    }

    public function test_group_discussion_posts_and_comments(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($creator, ['name' => 'Discussion Group']);
        $groupService->joinGroup($group, $member);

        // Member creates a post
        $postRes = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/posts", [
                'content' => 'Hello everyone in the discussion group!',
            ]);

        $postRes->assertStatus(201)
            ->assertJsonPath('data.content', 'Hello everyone in the discussion group!');
        $postId = $postRes->json('data.id');

        // Feed lists the post
        $feedRes = $this->actingAs($creator, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/posts");
        $feedRes->assertStatus(200)
            ->assertJsonPath('data.0.id', $postId);

        // Creator can delete the post
        $delRes = $this->actingAs($creator, 'sanctum')
            ->deleteJson("/api/v1/groups/{$group->id}/posts/{$postId}");
        $delRes->assertStatus(200);

        $this->assertSoftDeleted('posts', ['id' => $postId]);
    }

    public function test_web_and_app_routes_render_cleanly(): void
    {
        $user = User::factory()->create();
        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($user, ['name' => 'Frontend Verification Group']);

        // Create Group wizard page
        $createRes = $this->actingAs($user, 'web')->get('/groups/create');
        $createRes->assertStatus(200);

        // App create group route
        $appCreateRes = $this->actingAs($user, 'web')->get('/app/groups/create');
        $appCreateRes->assertStatus(200);

        // App groups discovery route
        $appGroupsRes = $this->actingAs($user, 'web')->get('/app/groups');
        $appGroupsRes->assertStatus(200);

        // App group profile route
        $appGroupShowRes = $this->actingAs($user, 'web')->get("/app/groups/{$group->slug}");
        $appGroupShowRes->assertStatus(200);

        // Blade routes backward compatibility
        $bladeIndexRes = $this->actingAs($user, 'web')->get('/groups');
        $bladeIndexRes->assertStatus(200);

        $bladeShowRes = $this->actingAs($user, 'web')->get("/groups/{$group->slug}?tab=about");
        $bladeShowRes->assertStatus(200);
    }
}
