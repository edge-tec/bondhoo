<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Media;
use App\Models\Page;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReelsSharingMessagingAndDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;

    protected User $viewer;

    protected User $friend;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->creator = User::factory()->create([
            'name' => 'Creator User',
            'username' => 'creatoruser',
        ]);

        $this->viewer = User::factory()->create([
            'name' => 'Viewer User',
            'username' => 'vieweruser',
        ]);

        $this->friend = User::factory()->create([
            'name' => 'Best Friend',
            'username' => 'bestfriend',
        ]);
    }

    protected function createReel(User $user): Reel
    {
        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => 'reels/pending/test_video.mp4',
            'thumbnail_path' => 'reels/pending/test_thumb.webp',
            'mime_type' => 'video/mp4',
            'size' => 2048000,
            'processing_status' => 'ready',
        ]);

        $reel = Reel::create([
            'user_id' => $user->id,
            'caption' => 'Awesome Reel for testing #social #reels',
            'audio_title' => 'Original Audio',
            'audio_artist' => $user->name,
            'duration' => 15.0,
            'width' => 720,
            'height' => 1280,
            'status' => Reel::STATUS_READY,
            'privacy' => 'public',
            'shares_count' => 0,
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'is_expired' => false,
            'is_draft' => false,
        ]);

        ReelMedia::create([
            'reel_id' => $reel->id,
            'media_id' => $media->id,
            'quality' => 'original',
            'video_path' => $media->original_path,
            'thumbnail_path' => $media->thumbnail_path,
            'mime_type' => 'video/mp4',
            'size' => $media->size,
        ]);

        return $reel;
    }

    public function test_user_can_send_reel_via_direct_message_to_friend(): void
    {
        $reel = $this->createReel($this->creator);

        $response = $this->actingAs($this->viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/message", [
                'recipient_ids' => [$this->friend->id],
                'message' => 'Check out this awesome reel!',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'sent_count' => 1,
                    'shares_count' => 1,
                ],
            ]);

        $this->assertEquals(1, $reel->fresh()->shares_count);
        $this->assertDatabaseHas('reel_shares', [
            'reel_id' => $reel->id,
            'user_id' => $this->viewer->id,
            'platform' => 'message',
        ]);
        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->viewer->id,
            'type' => 'reel',
        ]);
    }

    public function test_cannot_send_reel_via_message_to_blocked_user(): void
    {
        $reel = $this->createReel($this->creator);

        // Block relationship
        Friendship::create([
            'user_id' => $this->friend->id,
            'friend_id' => $this->viewer->id,
            'status' => Friendship::STATUS_BLOCKED,
        ]);

        $response = $this->actingAs($this->viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/message", [
                'recipient_ids' => [$this->friend->id],
                'message' => 'Hey',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_user_can_share_reel_to_profile_wall(): void
    {
        $reel = $this->createReel($this->creator);

        $response = $this->actingAs($this->viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/share-post", [
                'caption' => 'Sharing this incredible reel with everyone!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'shares_count' => 1,
                ],
            ]);

        $this->assertEquals(1, $reel->fresh()->shares_count);
        $this->assertDatabaseHas('posts', [
            'user_id' => $this->viewer->id,
            'content' => 'Sharing this incredible reel with everyone!',
            'type' => 'reel',
        ]);
    }

    public function test_user_can_share_reel_to_group_they_belong_to(): void
    {
        $reel = $this->createReel($this->creator);

        $group = Group::create([
            'name' => 'Photography Club',
            'slug' => 'photography-club',
            'creator_id' => $this->creator->id,
            'privacy' => 'public',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->viewer->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/share-group", [
                'group_id' => $group->id,
                'caption' => 'Inspirational reel for our club!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'shares_count' => 1,
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $this->viewer->id,
            'group_id' => $group->id,
            'content' => 'Inspirational reel for our club!',
            'type' => 'reel',
        ]);
    }

    public function test_unauthorized_user_cannot_share_reel_to_group_they_do_not_belong_to(): void
    {
        $reel = $this->createReel($this->creator);

        $group = Group::create([
            'name' => 'Secret Society',
            'slug' => 'secret-society',
            'creator_id' => $this->creator->id,
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($this->viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/share-group", [
                'group_id' => $group->id,
                'caption' => 'Trying to post without membership',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_share_reel_to_page_they_manage(): void
    {
        $reel = $this->createReel($this->creator);

        $page = Page::create([
            'name' => 'Tech Talks Daily',
            'slug' => 'tech-talks-daily',
            'owner_id' => $this->viewer->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/share-page", [
                'page_id' => $page->id,
                'caption' => 'Highlight of today!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'shares_count' => 1,
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $this->viewer->id,
            'page_id' => $page->id,
            'content' => 'Highlight of today!',
            'type' => 'reel',
        ]);
    }

    public function test_unauthorized_user_cannot_share_reel_to_page_they_do_not_manage(): void
    {
        $reel = $this->createReel($this->creator);

        $page = Page::create([
            'name' => 'Other Brand',
            'slug' => 'other-brand',
            'owner_id' => $this->creator->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/share-page", [
                'page_id' => $page->id,
                'caption' => 'Unauthorized page share attempt',
            ]);

        $response->assertStatus(403);
    }

    public function test_creator_can_delete_their_own_reel(): void
    {
        $reel = $this->createReel($this->creator);

        $response = $this->actingAs($this->creator, 'sanctum')
            ->deleteJson("/api/v2/reels/{$reel->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reel deleted successfully.',
            ]);

        $this->assertDatabaseMissing('reels', [
            'id' => $reel->id,
        ]);
    }

    public function test_non_owner_cannot_delete_another_users_reel(): void
    {
        $reel = $this->createReel($this->creator);

        $response = $this->actingAs($this->viewer, 'sanctum')
            ->deleteJson("/api/v2/reels/{$reel->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('reels', [
            'id' => $reel->id,
        ]);
    }

    public function test_admin_can_delete_any_reel(): void
    {
        $reel = $this->createReel($this->creator);

        $admin = User::factory()->create([
            'name' => 'Platform Admin',
            'status' => 'admin',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v2/reels/{$reel->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('reels', [
            'id' => $reel->id,
        ]);
    }

    public function test_share_targets_endpoint_returns_user_groups_pages_and_friends(): void
    {
        $group = Group::create([
            'name' => 'My Group',
            'slug' => 'my-group',
            'creator_id' => $this->viewer->id,
            'privacy' => 'public',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->viewer->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        $page = Page::create([
            'name' => 'My Page',
            'slug' => 'my-page',
            'owner_id' => $this->viewer->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->viewer, 'sanctum')
            ->getJson('/api/v2/reels/share-targets');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'groups',
                    'pages',
                    'friends',
                ],
            ]);

        $this->assertCount(1, $response->json('data.groups'));
        $this->assertCount(1, $response->json('data.pages'));
    }
}
