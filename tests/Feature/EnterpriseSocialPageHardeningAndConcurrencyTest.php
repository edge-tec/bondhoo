<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use App\Models\PageEvent;
use App\Models\PageFollower;
use App\Models\PageMember;
use App\Models\Post;
use App\Models\User;
use App\Services\PageService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseSocialPageHardeningAndConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $adminMember;

    protected User $moderatorMember;

    protected User $followerUser;

    protected User $strangerUser;

    protected Page $page;

    protected PageService $pageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->pageService = app(PageService::class);

        $this->owner = User::factory()->create([
            'name' => 'Enterprise Page Owner',
            'username' => 'enterpriseowner',
            'email' => 'owner@enterprise.test',
        ]);

        $this->adminMember = User::factory()->create([
            'name' => 'Enterprise Page Admin',
            'username' => 'enterpriseadmin',
            'email' => 'admin@enterprise.test',
        ]);

        $this->moderatorMember = User::factory()->create([
            'name' => 'Enterprise Moderator',
            'username' => 'enterprisemod',
            'email' => 'mod@enterprise.test',
        ]);

        $this->followerUser = User::factory()->create([
            'name' => 'Follower User',
            'username' => 'followeruser',
            'email' => 'follower@enterprise.test',
        ]);

        $this->strangerUser = User::factory()->create([
            'name' => 'Stranger User',
            'username' => 'strangeruser',
            'email' => 'stranger@enterprise.test',
        ]);

        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'name' => 'Apex Cloud Technologies',
            'slug' => 'apex-cloud-technologies',
            'username' => 'apexcloud',
            'category' => 'Technology',
            'bio' => 'Scalable enterprise cloud systems.',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 0,
            'posts_count' => 0,
            'is_verified' => true,
        ]);

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->adminMember->id,
            'role' => Page::ROLE_ADMIN,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->moderatorMember->id,
            'role' => Page::ROLE_MODERATOR,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    public function test_concurrent_follow_requests_do_not_duplicate_records_or_corrupt_counter(): void
    {
        // Simulate rapid sequential/concurrent follow calls
        for ($i = 0; $i < 3; $i++) {
            $response = $this->actingAs($this->followerUser)->postJson("/api/v1/pages/{$this->page->id}/follow");
            $response->assertStatus(200);
        }

        // Odd number of toggles (1st follow -> 2nd unfollow -> 3rd follow)
        $this->page->refresh();
        $this->assertEquals(1, $this->page->followers_count);
        $this->assertEquals(1, PageFollower::where('page_id', $this->page->id)->where('user_id', $this->followerUser->id)->count());

        // 4th call toggles back to unfollow
        $unfollowRes = $this->actingAs($this->followerUser)->postJson("/api/v1/pages/{$this->page->id}/follow");
        $unfollowRes->assertStatus(200);

        $this->page->refresh();
        $this->assertEquals(0, $this->page->followers_count);
        $this->assertEquals(0, PageFollower::where('page_id', $this->page->id)->where('user_id', $this->followerUser->id)->count());
    }

    public function test_page_post_cannot_hijack_media_belonging_to_another_user(): void
    {
        // Media owned by stranger
        $strangerMedia = Media::create([
            'user_id' => $this->strangerUser->id,
            'disk' => 'public',
            'original_path' => 'media/private_photo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 204800,
        ]);

        // Owner tries to attach stranger's media to page post
        $response = $this->actingAs($this->owner)->postJson("/api/v1/pages/{$this->page->id}/posts", [
            'content' => 'Trying to use someone else media',
            'media_ids' => [$strangerMedia->id],
        ]);

        $response->assertStatus(201);
        $postId = $response->json('data.id') ?? $response->json('data.post.id');

        // Verify stranger's media is untouched and NOT attached to this post
        $strangerMedia->refresh();
        $this->assertNull($strangerMedia->mediable_id);
        $this->assertNotEquals($postId, $strangerMedia->mediable_id);
    }

    public function test_page_post_cannot_steal_media_already_attached_to_another_post(): void
    {
        $media = Media::create([
            'user_id' => $this->owner->id,
            'disk' => 'public',
            'original_path' => 'media/first_post.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 102400,
        ]);

        // Post 1 legitimately attaches the media
        $post1 = $this->pageService->createPagePost($this->page, $this->owner, [
            'content' => 'First post',
            'media_ids' => [$media->id],
        ]);

        $media->refresh();
        $this->assertEquals($post1->id, $media->mediable_id);

        // Post 2 attempts to attach the same media
        $post2 = $this->pageService->createPagePost($this->page, $this->owner, [
            'content' => 'Second post attempting to steal media',
            'media_ids' => [$media->id],
        ]);

        // Media remains bound to Post 1
        $media->refresh();
        $this->assertEquals($post1->id, $media->mediable_id);
        $this->assertNotEquals($post2->id, $media->mediable_id);
    }

    public function test_privilege_escalation_non_owner_cannot_invite_admin(): void
    {
        $candidate = User::factory()->create();

        // Admin member (who has team.manage) attempts to invite someone as ADMIN
        $response = $this->actingAs($this->adminMember)->postJson("/api/v2/pages/{$this->page->id}/team/invite", [
            'identifier' => $candidate->email,
            'role' => Page::ROLE_ADMIN,
        ]);

        // Must be rejected by server-side validation
        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('Only the page owner can invite an admin', $response->json('message'));
    }

    public function test_privilege_escalation_non_owner_cannot_promote_member_to_admin(): void
    {
        // Admin tries to promote moderator to admin
        $modMember = PageMember::where('page_id', $this->page->id)
            ->where('user_id', $this->moderatorMember->id)
            ->firstOrFail();

        $response = $this->actingAs($this->adminMember)->putJson("/api/v2/pages/{$this->page->id}/team/{$modMember->id}", [
            'role' => Page::ROLE_ADMIN,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Only the page owner can promote to or modify an admin role', $response->json('message'));
    }

    public function test_non_owner_cannot_remove_an_admin(): void
    {
        $adminRecord = PageMember::where('page_id', $this->page->id)
            ->where('user_id', $this->adminMember->id)
            ->firstOrFail();

        // Stranger or moderator cannot remove admin
        $unauthResponse = $this->actingAs($this->moderatorMember)->deleteJson("/api/v2/pages/{$this->page->id}/team/{$adminRecord->id}");
        $unauthResponse->assertStatus(403);

        // And if an admin tries to remove another admin, server blocks it
        $anotherAdmin = User::factory()->create();
        $anotherAdminMember = PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $anotherAdmin->id,
            'role' => Page::ROLE_ADMIN,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $adminRemoveResponse = $this->actingAs($this->adminMember)->deleteJson("/api/v2/pages/{$this->page->id}/team/{$anotherAdminMember->id}");
        $adminRemoveResponse->assertStatus(422);
        $this->assertStringContainsString('Only the page owner can remove an admin', $adminRemoveResponse->json('message'));
    }

    public function test_trashed_page_cannot_be_viewed_by_strangers_or_guests(): void
    {
        // Move page to trash
        $this->page->update(['status' => Page::STATUS_TRASH]);

        // Guest visitor
        $guestRes = $this->get("/pages/{$this->page->slug}");
        $guestRes->assertStatus(404);

        // Stranger visitor
        $strangerRes = $this->actingAs($this->strangerUser)->get("/pages/{$this->page->slug}");
        $strangerRes->assertStatus(404);
    }

    public function test_private_page_blocks_unauthorized_visitors(): void
    {
        $this->page->update(['visibility' => 'private']);

        // Guest visitor
        $guestRes = $this->get("/pages/{$this->page->slug}");
        $guestRes->assertStatus(403);

        // Stranger visitor
        $strangerRes = $this->actingAs($this->strangerUser)->get("/pages/{$this->page->slug}");
        $strangerRes->assertStatus(403);

        // Authorized admin member can view
        $adminRes = $this->actingAs($this->adminMember)->get("/pages/{$this->page->slug}");
        $adminRes->assertStatus(200);

        // Page owner can view
        $ownerRes = $this->actingAs($this->owner)->get("/pages/{$this->page->slug}");
        $ownerRes->assertStatus(200);
    }

    public function test_event_rsvp_idempotency_and_accurate_counts(): void
    {
        $event = PageEvent::create([
            'page_id' => $this->page->id,
            'title' => 'Cloud Summit 2026',
            'slug' => 'cloud-summit-2026',
            'start_time' => now()->addDays(7),
            'status' => 'active',
            'rsvp_count' => 0,
        ]);

        // 1. Follower RSVPs as going
        $res1 = $this->actingAs($this->followerUser)->postJson("/api/v2/pages/{$this->page->id}/events/{$event->id}/rsvp", [
            'status' => 'going',
        ]);
        $res1->assertStatus(200);
        $event->refresh();
        $this->assertEquals(1, $event->rsvp_count);

        // 2. Duplicate RSVP with same status does not duplicate count
        $res2 = $this->actingAs($this->followerUser)->postJson("/api/v2/pages/{$this->page->id}/events/{$event->id}/rsvp", [
            'status' => 'going',
        ]);
        $res2->assertStatus(200);
        $event->refresh();
        $this->assertEquals(1, $event->rsvp_count);

        // 3. User changes status to not_going -> rsvp_count updates to 0
        $res3 = $this->actingAs($this->followerUser)->postJson("/api/v2/pages/{$this->page->id}/events/{$event->id}/rsvp", [
            'status' => 'not_going',
        ]);
        $res3->assertStatus(200);
        $event->refresh();
        $this->assertEquals(0, $event->rsvp_count);
    }
}
