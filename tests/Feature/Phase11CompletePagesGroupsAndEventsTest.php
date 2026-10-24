<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\GroupService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase11CompletePagesGroupsAndEventsTest extends TestCase
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
            'first_name' => $user->first_name ?? 'Community',
            'last_name' => $user->last_name ?? 'User',
        ]);

        return $user;
    }

    /**
     * Requirement 11.1: Pages Creation, Follow/Unfollow, and Publishing Posts.
     */
    public function test_pages_creation_follow_and_publishing(): void
    {
        $owner = $this->createUser(['name' => 'Page Creator']);
        $follower = $this->createUser(['name' => 'Active Follower']);

        // 1. Create page
        $createRes = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/pages', [
                'name' => 'Dhaka Tech Hub',
                'category' => 'Technology & Innovation',
                'bio' => 'The leading tech ecosystem page in Bangladesh.',
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Dhaka Tech Hub')
            ->assertJsonPath('data.slug', 'dhaka-tech-hub')
            ->assertJsonPath('data.is_owner', true);

        $pageId = $createRes->json('data.id');
        $page = Page::findOrFail($pageId);

        // 2. Follow page
        $followRes = $this->actingAs($follower, 'sanctum')
            ->postJson("/api/v1/pages/{$pageId}/follow");
        $followRes->assertStatus(200)
            ->assertJsonPath('data.is_following', true)
            ->assertJsonPath('data.followers_count', 1);

        $this->assertEquals(1, $page->fresh()->followers_count);

        // 3. Publish post to page feed
        $postRes = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/pages/{$pageId}/posts", [
                'content' => 'Excited to announce our upcoming Tech Expo!',
                'type' => 'text',
            ]);

        $postRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.page_id', $pageId);

        $this->assertEquals(1, $page->fresh()->posts_count);

        // 4. Follower views page feed
        $feedRes = $this->actingAs($follower, 'sanctum')
            ->getJson("/api/v1/pages/{$pageId}/posts");
        $feedRes->assertStatus(200);
        $this->assertCount(1, $feedRes->json('data'));
        $this->assertEquals('Excited to announce our upcoming Tech Expo!', $feedRes->json('data.0.content'));

        // 5. Unfollow page
        $unfollowRes = $this->actingAs($follower, 'sanctum')
            ->postJson("/api/v1/pages/{$pageId}/follow");
        $unfollowRes->assertStatus(200)
            ->assertJsonPath('data.is_following', false)
            ->assertJsonPath('data.followers_count', 0);
    }

    /**
     * Requirement 11.2: Public vs Private Group Membership & Approval Workflow.
     */
    public function test_group_creation_and_membership_approval_workflow(): void
    {
        $admin = $this->createUser(['name' => 'Group Admin']);
        $applicant = $this->createUser(['name' => 'Applicant']);
        $publicMember = $this->createUser(['name' => 'Public Member']);

        // 1. Create Public Group
        $publicGroupRes = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/groups', [
                'name' => 'Open Source Enthusiasts',
                'description' => 'Public group for open source',
                'privacy' => 'public',
                'membership_approval_mode' => 'anyone',
            ]);
        $publicGroupRes->assertStatus(201);
        $publicGroupId = $publicGroupRes->json('data.id');

        // Public member joins instantly
        $joinPublicRes = $this->actingAs($publicMember, 'sanctum')
            ->postJson("/api/v1/groups/{$publicGroupId}/join");
        $joinPublicRes->assertStatus(200)
            ->assertJsonPath('data.status', 'joined');

        $this->assertDatabaseHas('group_members', [
            'group_id' => $publicGroupId,
            'user_id' => $publicMember->id,
            'status' => 'active',
        ]);

        // 2. Create Private Group
        $privateGroupRes = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/groups', [
                'name' => 'Executive Mastermind',
                'description' => 'Private invite/approval group',
                'privacy' => 'private',
                'membership_approval_mode' => 'approval_required',
            ]);
        $privateGroupRes->assertStatus(201);
        $privateGroupId = $privateGroupRes->json('data.id');

        // Applicant applies to join private group -> status is pending
        $joinPrivateRes = $this->actingAs($applicant, 'sanctum')
            ->postJson("/api/v1/groups/{$privateGroupId}/join");
        $joinPrivateRes->assertStatus(200)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('group_members', [
            'group_id' => $privateGroupId,
            'user_id' => $applicant->id,
            'status' => 'pending',
        ]);

        // Admin approves applicant
        $approveRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$privateGroupId}/approve/{$applicant->id}");
        $approveRes->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $privateGroupId,
            'user_id' => $applicant->id,
            'status' => 'active',
        ]);
    }

    /**
     * Requirement 11.3: Group Moderation (Role update, ban, and member removal).
     */
    public function test_group_moderation_roles_and_banning(): void
    {
        $admin = $this->createUser();
        $member = $this->createUser();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($admin, [
            'name' => 'Cyber Security Guild',
            'privacy' => 'public',
        ]);

        // Member joins
        $this->actingAs($member, 'sanctum')->postJson("/api/v1/groups/{$group->id}/join");

        // 1. Admin promotes member to moderator
        $roleRes = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/groups/{$group->id}/members/{$member->id}/role", [
                'role' => 'moderator',
            ]);
        $roleRes->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => 'moderator',
        ]);

        // 2. Admin bans member for policy violation
        $banRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/members/{$member->id}/ban", [
                'reason' => 'Spamming affiliate links',
            ]);
        $banRes->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $member->id,
            'status' => 'banned',
        ]);

        // 3. Admin unbans member
        $unbanRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/members/{$member->id}/unban");
        $unbanRes->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $member->id,
            'status' => 'active',
        ]);
    }

    /**
     * Requirement 11.4: Group Posts and Moderation Queue.
     */
    public function test_group_posts_and_moderation_queue(): void
    {
        $admin = $this->createUser();
        $member = $this->createUser();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($admin, [
            'name' => 'Curated Literature Society',
            'privacy' => 'public',
            'post_approval_mode' => 'admin_approval', // requires approval
        ]);

        $this->actingAs($member, 'sanctum')->postJson("/api/v1/groups/{$group->id}/join");

        // Member posts in group -> post is held in pending status
        $postRes = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/posts", [
                'content' => 'Review of the latest Nobel Prize winner.',
            ]);
        $postRes->assertStatus(201);
        $postId = $postRes->json('data.id');

        // Post should not be visible in public group feed yet
        $feedRes = $this->actingAs($member, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/posts");
        $feedIds = collect($feedRes->json('data'))->pluck('id')->all();
        $this->assertNotContains($postId, $feedIds);

        // Admin views moderation queue
        $queueRes = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/moderation/posts");
        $queueRes->assertStatus(200);
        $queueIds = collect($queueRes->json('data'))->pluck('id')->all();
        $this->assertContains($postId, $queueIds);

        // Admin approves post
        $approveRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/moderation/posts/{$postId}/approve");
        $approveRes->assertStatus(200);

        // Now post is visible in group feed
        $updatedFeedRes = $this->actingAs($member, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/posts");
        $updatedFeedIds = collect($updatedFeedRes->json('data'))->pluck('id')->all();
        $this->assertContains($postId, $updatedFeedIds);
    }

    /**
     * Requirement 11.5: Group Events Creation and RSVP Response.
     */
    public function test_group_events_and_rsvp_responses(): void
    {
        $admin = $this->createUser();
        $member = $this->createUser();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($admin, [
            'name' => 'Developers Meetup Group',
            'privacy' => 'public',
        ]);

        $this->actingAs($member, 'sanctum')->postJson("/api/v1/groups/{$group->id}/join");

        // 1. Admin creates group event
        $eventRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/events", [
                'title' => 'Annual Developer Conference 2026',
                'description' => 'Keynotes and networking sessions',
                'location' => 'Dhaka International Convention City',
                'start_time' => now()->addDays(10)->toDateTimeString(),
                'end_time' => now()->addDays(11)->toDateTimeString(),
            ]);

        $eventRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Annual Developer Conference 2026');

        $eventId = $eventRes->json('data.id');

        // 2. Member RSVPs 'going'
        $rsvpRes = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/events/{$eventId}/rsvp", [
                'status' => 'going',
            ]);
        $rsvpRes->assertStatus(200)->assertJsonPath('success', true);

        // 3. Member retrieves events list and verifies participation
        $listRes = $this->actingAs($member, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/events");
        $listRes->assertStatus(200);
        $this->assertCount(1, $listRes->json('data'));
        $this->assertEquals('Annual Developer Conference 2026', $listRes->json('data.0.title'));
    }
}
