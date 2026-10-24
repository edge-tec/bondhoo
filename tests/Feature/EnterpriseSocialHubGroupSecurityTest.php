<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupNotificationPreference;
use App\Models\Post;
use App\Models\User;
use App\Services\FeedService;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterpriseSocialHubGroupSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $admin;

    protected User $member;

    protected User $outsider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Owner Alice', 'username' => 'owner_alice']);
        $this->admin = User::factory()->create(['name' => 'Admin Bob', 'username' => 'admin_bob']);
        $this->member = User::factory()->create(['name' => 'Member Charlie', 'username' => 'member_charlie']);
        $this->outsider = User::factory()->create(['name' => 'Outsider Dave', 'username' => 'outsider_dave']);
    }

    /**
     * Helper to create a test group with memberships.
     */
    protected function createTestGroup(string $privacy = 'public', array $attributes = []): Group
    {
        $group = Group::create(array_merge([
            'name' => 'Cyber Security Guild',
            'slug' => 'cyber-security-guild-'.uniqid(),
            'description' => 'A group for enterprise security specialists.',
            'category' => 'Technology',
            'privacy' => $privacy,
            'creator_id' => $this->owner->id,
            'members_count' => 1,
            'active_members_count' => 1,
            'status' => Group::STATUS_ACTIVE,
        ], $attributes));

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->owner->id,
            'role' => GroupMember::ROLE_ADMIN,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        return $group;
    }

    public function test_private_group_content_is_strictly_inaccessible_to_non_members(): void
    {
        $group = $this->createTestGroup('private');

        // Create a post in this private group
        $post = Post::create([
            'user_id' => $this->owner->id,
            'group_id' => $group->id,
            'content' => 'Top secret private roadmap for Q4.',
            'audience' => 'public', // even if audience says public, group privacy must govern
            'status' => 'published',
        ]);

        // 1. Outsider cannot fetch group feed via API
        $response = $this->actingAs($this->outsider, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/posts");

        $response->assertStatus(403);

        // 2. Outsider cannot access group files
        $fileResponse = $this->actingAs($this->outsider, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/files");

        $fileResponse->assertStatus(403);

        // 3. Outsider cannot access group polls
        $pollResponse = $this->actingAs($this->outsider, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/polls");

        $pollResponse->assertStatus(403);

        // 4. Outsider cannot see the private post in global feed
        $feedService = app(FeedService::class);
        $globalFeed = $feedService->getFeed($this->outsider, 'smart', 20);
        $feedPostIds = collect($globalFeed->items())->pluck('id')->all();
        $this->assertNotContains($post->id, $feedPostIds);

        // 5. Outsider cannot see the private post in author's profile feed
        $userPosts = $feedService->getUserPosts($this->owner, $this->outsider, 20);
        $userPostIds = collect($userPosts->items())->pluck('id')->all();
        $this->assertNotContains($post->id, $feedPostIds);

        // 6. Outsider cannot discover the post via Global Search
        $searchService = app(SearchService::class);
        $searchResults = $searchService->search('Top secret', 'posts', $this->outsider);
        $this->assertEmpty($searchResults['results']['posts']);
    }

    public function test_role_based_access_control_and_privilege_escalation_guards(): void
    {
        $group = $this->createTestGroup('public');

        // Add admin and member
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->admin->id,
            'role' => GroupMember::ROLE_ADMIN,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->member->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        // 1. Regular member CANNOT promote themselves or anyone to admin
        $memberEscalation = $this->actingAs($this->member, 'sanctum')
            ->putJson("/api/v1/groups/{$group->id}/members/{$this->member->id}/role", [
                'role' => 'admin',
            ]);
        $memberEscalation->assertStatus(403);

        // 2. Regular admin CANNOT demote another admin (only owner can modify another admin's role)
        $adminDemoteAdmin = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/groups/{$group->id}/members/{$this->owner->id}/role", [
                'role' => 'member',
            ]);
        $adminDemoteAdmin->assertStatus(403);

        // 3. Regular admin CANNOT promote another user to admin (only owner can promote to admin)
        $adminPromoteToAdmin = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/groups/{$group->id}/members/{$this->member->id}/role", [
                'role' => 'admin',
            ]);
        $adminPromoteToAdmin->assertStatus(403);

        // 4. Owner CAN promote member to moderator or admin
        $ownerPromote = $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/v1/groups/{$group->id}/members/{$this->member->id}/role", [
                'role' => 'moderator',
            ]);
        $ownerPromote->assertStatus(200);
        $this->assertEquals('moderator', GroupMember::where('group_id', $group->id)->where('user_id', $this->member->id)->value('role'));

        // 5. Owner CANNOT leave the group without transferring ownership
        $ownerLeaveAttempt = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/leave");
        $ownerLeaveAttempt->assertStatus(422);

        // 6. Owner CAN successfully transfer ownership to another member
        $transferResponse = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/transfer-ownership", [
                'new_owner_id' => $this->admin->id,
            ]);
        $transferResponse->assertStatus(200);

        $this->assertEquals($this->admin->id, $group->fresh()->creator_id);

        // Now new owner (admin) can leave or manage, and old owner can leave
        $oldOwnerLeave = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/leave");
        $oldOwnerLeave->assertStatus(200);
    }

    public function test_join_request_lifecycle_and_cancellation(): void
    {
        $group = $this->createTestGroup('public', [
            'membership_approval_mode' => Group::APPROVAL_REQUIRED,
        ]);

        // 1. Outsider submits join request
        $joinResponse = $this->actingAs($this->outsider, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/join");

        $joinResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $this->outsider->id,
            'status' => GroupMember::STATUS_PENDING,
        ]);

        // 2. Admin can list pending requests
        $requestsList = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/requests");

        $requestsList->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // 3. Outsider can cancel their pending request
        $cancelResponse = $this->actingAs($this->outsider, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/requests/cancel");

        $cancelResponse->assertStatus(200);
        $this->assertDatabaseMissing('group_members', [
            'group_id' => $group->id,
            'user_id' => $this->outsider->id,
        ]);

        // 4. Outsider re-submits join request, and admin approves it
        $this->actingAs($this->outsider, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/join");

        $approveResponse = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/requests/{$this->outsider->id}/approve");

        $approveResponse->assertStatus(200);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $this->outsider->id,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);
        $this->assertEquals(2, $group->fresh()->members_count);
    }

    public function test_posting_controls_prevent_member_posts_when_restricted(): void
    {
        // Group with member posting disabled
        $group = $this->createTestGroup('public', [
            'features' => ['posts' => true, 'member_posts' => false, 'allow_member_posts' => false],
            'post_approval_mode' => 'admins_only',
        ]);

        // Add regular member
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->member->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        // Regular member attempt to post is blocked by backend
        $postAttempt = $this->actingAs($this->member, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/posts", [
                'content' => 'Unauthorized member post attempt.',
            ]);

        $postAttempt->assertStatus(403);

        // Owner CAN post despite the member restriction
        $ownerPost = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/posts", [
                'content' => 'Official announcement from the owner.',
            ]);

        $ownerPost->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'group_id' => $group->id,
            'user_id' => $this->owner->id,
            'content' => 'Official announcement from the owner.',
        ]);
    }

    public function test_media_upload_authorization_and_group_media_route(): void
    {
        Storage::fake('public');

        $group = $this->createTestGroup('public');

        $fakeImage = UploadedFile::fake()->image('cover.jpg', 1200, 400);

        // 1. Outsider cannot upload/modify someone else's group media
        $unauthorizedUpload = $this->actingAs($this->outsider, 'sanctum')
            ->postJson('/api/v1/groups/media/upload', [
                'image' => $fakeImage,
                'type' => 'cover',
                'group_id' => $group->id,
            ]);

        $unauthorizedUpload->assertStatus(403);

        // 2. Owner can upload media directly via dedicated /api/v1/groups/{id}/media endpoint
        $authorizedUpload = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/media", [
                'image' => $fakeImage,
                'type' => 'cover',
            ]);

        $authorizedUpload->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertNotNull($group->fresh()->cover_image_url);
    }

    public function test_granular_notification_preferences_are_honored_on_new_posts(): void
    {
        $group = $this->createTestGroup('public');

        // Add member with post notifications turned off
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->member->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        GroupNotificationPreference::create([
            'group_id' => $group->id,
            'user_id' => $this->member->id,
            'preference' => 'custom',
            'settings' => [
                'new_posts' => false,
                'comments' => true,
            ],
        ]);

        $this->assertFalse(GroupNotificationPreference::allowsNotification($this->member->id, $group->id, 'new_posts'));
        $this->assertTrue(GroupNotificationPreference::allowsNotification($this->member->id, $group->id, 'comments'));

        // Post something in group
        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/posts", [
                'content' => 'Notification test post for all members.',
            ]);

        // Member who turned off new_posts should NOT receive post notification
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $this->member->id,
            'type' => 'notification.group_post',
        ]);
    }
}
