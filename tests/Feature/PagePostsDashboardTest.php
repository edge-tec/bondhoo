<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageEvent;
use App\Models\PageMember;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PagePostsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $editor;

    protected User $viewer;

    protected User $stranger;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::factory()->create([
            'name' => 'Tech Corp Owner',
            'username' => 'techowner',
            'email' => 'owner@techcorp.example.com',
        ]);

        $this->editor = User::factory()->create([
            'name' => 'Content Manager',
            'username' => 'editor_cm',
            'email' => 'editor@techcorp.example.com',
        ]);

        $this->viewer = User::factory()->create([
            'name' => 'Page Viewer',
            'username' => 'page_viewer',
            'email' => 'viewer@techcorp.example.com',
        ]);

        $this->stranger = User::factory()->create([
            'name' => 'Random Stranger',
            'username' => 'stranger',
            'email' => 'stranger@example.com',
        ]);

        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'name' => 'InnovateX Labs',
            'slug' => 'innovatex-labs',
            'username' => 'innovatex',
            'category' => 'Software & AI',
            'bio' => 'Next generation enterprise AI engineering.',
            'description' => 'Scalable platform engineering for global products.',
            'website' => 'https://innovatex.example.com',
            'email' => 'contact@innovatex.example.com',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 350,
            'posts_count' => 5,
            'is_verified' => true,
        ]);

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->editor->id,
            'role' => Page::ROLE_EDITOR,
            'status' => 'active',
        ]);

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->viewer->id,
            'role' => Page::ROLE_VIEWER,
            'status' => 'active',
        ]);
    }

    public function test_posts_dashboard_renders_for_owner_with_full_toolbar_and_metrics(): void
    {
        Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Exciting news: InnovateX Labs launches v2 platform today!',
            'type' => 'text',
            'status' => 'published',
            'audience' => 'public',
            'likes_count' => 45,
            'comments_count' => 12,
            'shares_count' => 8,
        ]);

        $response = $this->actingAs($this->owner)->get('/pages/innovatex-labs/posts');

        $response->assertStatus(200);
        $response->assertSee('Page Posts');
        $response->assertSee('All posts from your page');
        $response->assertSee('Search posts...');
        $response->assertSee('New Post');
        $response->assertSee('All Posts');
        $response->assertSee('Text');
        $response->assertSee('Photos');
        $response->assertSee('Videos');
        $response->assertSee('Links');
        $response->assertSee('Exciting news: InnovateX Labs launches v2 platform today!');
        $response->assertSee('Page Performance');
        $response->assertSee('Recent Activity');
        $response->assertSee('Quick Actions');
    }

    public function test_posts_dashboard_supports_search_and_type_filter(): void
    {
        Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'First post about Artificial Intelligence in DevOps',
            'type' => 'text',
            'status' => 'published',
        ]);

        Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Second post about Cooking recipes and food',
            'type' => 'text',
            'status' => 'published',
        ]);

        // Search for DevOps
        $searchResponse = $this->actingAs($this->owner)->get('/pages/innovatex-labs/posts?q=DevOps');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Artificial Intelligence in DevOps');
        $this->assertCount(1, $searchResponse->viewData('posts'));
        $this->assertEquals('First post about Artificial Intelligence in DevOps', $searchResponse->viewData('posts')->first()->content);

        // Type filter for links when none exist
        $typeResponse = $this->actingAs($this->owner)->get('/pages/innovatex-labs/posts?type=links');
        $typeResponse->assertStatus(200);
        $this->assertCount(0, $typeResponse->viewData('posts'));
        $typeResponse->assertSee('No Posts Found');
    }

    public function test_authorized_owner_and_editor_can_create_post(): void
    {
        // Owner create text post
        $response = $this->actingAs($this->owner)->postJson('/pages/innovatex-labs/posts', [
            'content' => 'Our first official announcement on the Page Posts dashboard.',
            'type' => 'text',
            'audience' => 'public',
            'status' => 'published',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Post published successfully.',
        ]);

        $this->assertDatabaseHas('posts', [
            'page_id' => $this->page->id,
            'content' => 'Our first official announcement on the Page Posts dashboard.',
            'type' => 'text',
            'status' => 'published',
        ]);

        // Editor create link post
        $editorResponse = $this->actingAs($this->editor)->postJson('/pages/innovatex-labs/posts', [
            'content' => 'Check out our latest enterprise release notes.',
            'link_url' => 'https://innovatex.example.com/changelog',
            'audience' => 'followers',
            'status' => 'published',
        ]);

        $editorResponse->assertStatus(200);
        $editorResponse->assertJson(['success' => true]);

        $this->assertDatabaseHas('posts', [
            'page_id' => $this->page->id,
            'type' => 'link',
            'audience' => 'followers',
        ]);
    }

    public function test_can_upload_media_with_post_creation(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('launch-hero.jpg', 600, 400);

        $response = $this->actingAs($this->owner)->postJson('/pages/innovatex-labs/posts', [
            'content' => 'Visual launch of our newly updated platform!',
            'type' => 'photo',
            'media' => $file,
            'audience' => 'public',
            'status' => 'published',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('posts', [
            'page_id' => $this->page->id,
            'content' => 'Visual launch of our newly updated platform!',
            'type' => 'media',
        ]);

        $this->assertDatabaseHas('media', [
            'collection' => 'posts',
            'disk' => 'public',
        ]);
    }

    public function test_viewer_or_unauthorized_user_cannot_create_page_post(): void
    {
        $viewerResponse = $this->actingAs($this->viewer)->postJson('/pages/innovatex-labs/posts', [
            'content' => 'Viewer trying to publish without permission.',
        ]);

        $viewerResponse->assertStatus(403);

        $strangerResponse = $this->actingAs($this->stranger)->postJson('/pages/innovatex-labs/posts', [
            'content' => 'Stranger attempting illegal page post.',
        ]);

        $strangerResponse->assertStatus(403);
    }

    public function test_authorized_user_can_update_post(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Initial post text to be edited.',
            'type' => 'text',
            'status' => 'published',
            'audience' => 'public',
        ]);

        $response = $this->actingAs($this->owner)->putJson('/pages/innovatex-labs/posts/'.$post->id, [
            'content' => 'Updated post text with polished wording.',
            'audience' => 'followers',
            'status' => 'published',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Post updated successfully.',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'content' => 'Updated post text with polished wording.',
            'audience' => 'followers',
        ]);
    }

    public function test_authorized_user_can_toggle_pin_status(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Important announcement to pin at top.',
            'type' => 'text',
            'status' => 'published',
            'is_pinned' => false,
        ]);

        $response = $this->actingAs($this->owner)->postJson('/pages/innovatex-labs/posts/'.$post->id.'/pin');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_pinned' => true,
            'message' => 'Post pinned to top of page.',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'is_pinned' => true,
        ]);

        // Unpin
        $unpinResponse = $this->actingAs($this->owner)->postJson('/pages/innovatex-labs/posts/'.$post->id.'/pin');
        $unpinResponse->assertStatus(200);
        $unpinResponse->assertJson(['is_pinned' => false]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'is_pinned' => false,
        ]);
    }

    public function test_authorized_user_can_update_visibility(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Post to test visibility changes.',
            'type' => 'text',
            'audience' => 'public',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->owner)->postJson('/pages/innovatex-labs/posts/'.$post->id.'/visibility', [
            'audience' => 'only_me',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'audience' => 'only_me',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'audience' => 'only_me',
        ]);
    }

    public function test_authorized_user_can_delete_post(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Post destined to be deleted.',
            'type' => 'text',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->owner)->deleteJson('/pages/innovatex-labs/posts/'.$post->id);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);

        $this->assertSoftDeleted('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_post_actions(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Protected post content.',
            'type' => 'text',
            'status' => 'published',
        ]);

        $response = $this->postJson('/pages/innovatex-labs/posts', ['content' => 'Unauthorized']);
        $response->assertStatus(401);

        $deleteResponse = $this->deleteJson('/pages/innovatex-labs/posts/'.$post->id);
        $deleteResponse->assertStatus(401);
    }

    public function test_owner_can_update_page_settings(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/pages/innovatex-labs/settings', [
            'name' => 'InnovateX Global Labs',
            'username' => 'innovatex-global',
            'category' => 'AI & Cloud Infrastructure',
            'bio' => 'Building autonomous enterprise AI applications.',
            'settings' => [
                'enable_auto_reply' => true,
                'auto_reply_message' => 'Thank you for contacting InnovateX Global Labs!',
                'allow_visitor_posts' => false,
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Page settings updated successfully.',
        ]);

        $this->assertDatabaseHas('pages', [
            'id' => $this->page->id,
            'name' => 'InnovateX Global Labs',
            'username' => 'innovatex-global',
            'category' => 'AI & Cloud Infrastructure',
            'bio' => 'Building autonomous enterprise AI applications.',
        ]);
    }

    public function test_owner_can_remove_page_branding(): void
    {
        $this->page->update([
            'avatar_url' => 'https://example.com/avatar.jpg',
            'cover_image_url' => 'https://example.com/cover.jpg',
        ]);

        // Remove avatar
        $avatarResponse = $this->actingAs($this->owner)->deleteJson('/pages/innovatex-labs/branding/avatar');
        $avatarResponse->assertStatus(200);
        $avatarResponse->assertJson(['success' => true]);
        $this->assertNull($this->page->fresh()->avatar_url);

        // Remove cover
        $coverResponse = $this->actingAs($this->owner)->deleteJson('/pages/innovatex-labs/branding/cover');
        $coverResponse->assertStatus(200);
        $coverResponse->assertJson(['success' => true]);
        $this->assertNull($this->page->fresh()->cover_image_url);
    }

    public function test_unauthorized_user_cannot_remove_page_branding(): void
    {
        $response = $this->actingAs($this->stranger)->deleteJson('/pages/innovatex-labs/branding/avatar');
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_create_and_delete_page_event(): void
    {
        $createResponse = $this->actingAs($this->owner)->postJson('/pages/innovatex-labs/events', [
            'title' => 'InnovateX Annual Tech Keynote 2026',
            'description' => 'Unveiling revolutionary multi-cloud orchestrations.',
            'start_time' => now()->addDays(7)->toDateTimeString(),
            'end_time' => now()->addDays(7)->addHours(3)->toDateTimeString(),
            'is_online' => true,
            'location' => 'https://stream.innovatex.example.com',
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'success' => true,
            'message' => 'Event created successfully.',
        ]);

        $eventId = $createResponse->json('event.id');
        $this->assertDatabaseHas('page_events', [
            'id' => $eventId,
            'page_id' => $this->page->id,
            'title' => 'InnovateX Annual Tech Keynote 2026',
        ]);

        // Delete event
        $deleteResponse = $this->actingAs($this->owner)->deleteJson('/pages/innovatex-labs/events/'.$eventId);
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJson([
            'success' => true,
            'message' => 'Event deleted successfully.',
        ]);

        $this->assertDatabaseMissing('page_events', [
            'id' => $eventId,
        ]);
    }

    public function test_user_can_rsvp_to_page_event(): void
    {
        $event = PageEvent::create([
            'page_id' => $this->page->id,
            'title' => 'Developer Meetup and Hackathon',
            'slug' => 'developer-meetup-hackathon',
            'description' => 'Hands on AI app building.',
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHours(4),
            'location' => 'Banani Dhaka',
            'is_online' => false,
            'rsvp_count' => 0,
            'status' => 'scheduled',
        ]);

        // First RSVP -> Going
        $rsvpResponse1 = $this->actingAs($this->stranger)->postJson('/pages/innovatex-labs/events/'.$event->id.'/rsvp');
        $rsvpResponse1->assertStatus(200);
        $rsvpResponse1->assertJson([
            'success' => true,
            'status' => 'going',
            'rsvp_count' => 1,
        ]);
        $this->assertEquals(1, $event->fresh()->rsvp_count);

        // Second RSVP -> Cancel
        $rsvpResponse2 = $this->actingAs($this->stranger)->postJson('/pages/innovatex-labs/events/'.$event->id.'/rsvp');
        $rsvpResponse2->assertStatus(200);
        $rsvpResponse2->assertJson([
            'success' => true,
            'status' => 'none',
            'rsvp_count' => 0,
        ]);
        $this->assertEquals(0, $event->fresh()->rsvp_count);
    }

    public function test_stranger_cannot_create_or_delete_page_events(): void
    {
        $event = PageEvent::create([
            'page_id' => $this->page->id,
            'title' => 'Protected Event',
            'slug' => 'protected-event',
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
            'is_online' => true,
            'rsvp_count' => 0,
            'status' => 'scheduled',
        ]);

        $createResponse = $this->actingAs($this->stranger)->postJson('/pages/innovatex-labs/events', [
            'title' => 'Malicious Event',
            'start_time' => now()->addDays(5)->toDateTimeString(),
        ]);
        $createResponse->assertStatus(403);

        $deleteResponse = $this->actingAs($this->stranger)->deleteJson('/pages/innovatex-labs/events/'.$event->id);
        $deleteResponse->assertStatus(403);
    }
}
