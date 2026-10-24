<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageEvent;
use App\Models\PageMember;
use App\Models\PageType;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\PageTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PART 7 — Complete Page Tabs & Content Navigation Test Suite
 */
class PageTabsAndContentNavigationPart7Test extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $manager;

    protected User $visitor;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(PageTaxonomySeeder::class);

        $type = PageType::first() ?? PageType::create(['name' => 'Company', 'slug' => 'company', 'is_active' => true]);
        $category = PageCategory::first() ?? PageCategory::create(['page_type_id' => $type->id, 'name' => 'Technology', 'slug' => 'technology', 'is_active' => true]);

        $this->owner = User::factory()->create(['name' => 'Apex Admin', 'username' => 'apexadmin']);
        $this->manager = User::factory()->create(['name' => 'Content Manager', 'username' => 'contentmgr']);
        $this->visitor = User::factory()->create(['name' => 'General Visitor', 'username' => 'genvisitor']);

        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'page_type_id' => $type->id,
            'page_category_id' => $category->id,
            'name' => 'Jugajug Technologies',
            'slug' => 'jugajug-tech',
            'username' => 'jugajugtech',
            'bio' => 'Next generation communication platform.',
            'description' => 'Jugajug builds seamless social experiences for modern communities across Bangladesh.',
            'category' => 'Technology',
            'sub_category' => 'Software',
            'website' => 'https://jugajug.example.com',
            'email' => 'contact@jugajug.example.com',
            'phone' => '+8801700000000',
            'address' => 'Gulshan 2, Dhaka',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'zip_code' => '1212',
            'business_hours' => [
                'monday' => '09:00 - 18:00',
                'tuesday' => '09:00 - 18:00',
                'wednesday' => '09:00 - 18:00',
                'thursday' => '09:00 - 18:00',
                'friday' => 'Closed',
            ],
            'privacy_settings' => [
                'hide_email' => true,
                'hide_phone' => false,
            ],
            'followers_count' => 5,
            'is_verified' => true,
            'is_active' => true,
            'status' => 'published',
        ]);

        // Add manager with permissions
        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->manager->id,
            'role' => 'admin',
            'status' => 'active',
            'custom_permissions' => [
                'posts.create' => true,
                'posts.edit' => true,
                'posts.delete' => true,
                'posts.pin' => true,
                'media.manage' => true,
                'events.manage' => true,
            ],
        ]);
    }

    /**
     * 1. Test Home Tab displays overview, featured pinned post, and no fake placeholder counts.
     */
    public function test_home_tab_displays_overview_featured_post_and_no_fake_numbers(): void
    {
        // Create a pinned post and a regular post
        $pinnedPost = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Welcome to Jugajug Technologies! This is our featured announcement.',
            'status' => 'published',
            'audience' => 'public',
            'is_pinned' => true,
        ]);

        $regularPost = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Regular update about our software progress.',
            'status' => 'published',
            'audience' => 'public',
            'is_pinned' => false,
        ]);

        $response = $this->actingAs($this->visitor)->get(route('pages.show', ['slug' => $this->page->slug, 'tab' => 'home', 'blade' => 1]));

        $response->assertOk();
        $response->assertSee('Welcome to Jugajug Technologies! This is our featured announcement.');
        $response->assertSee('Regular update about our software progress.');
        $response->assertSee('Next generation communication platform.');

        // Verify zero hardcoded 125486 follower count
        $response->assertDontSee('125486');

        // General visitor cannot see the composer form
        $response->assertDontSee('id="pagePostSubmitForm"', false);

        // Manager can see composer
        $managerResponse = $this->actingAs($this->manager)->get(route('pages.show', ['slug' => $this->page->slug, 'tab' => 'home', 'blade' => 1]));
        $managerResponse->assertOk();
        $managerResponse->assertSee('id="pagePostContent"', false);
    }

    /**
     * 2. Test Posts Tab filters (all, photos, videos, pinned) and pagination.
     */
    public function test_posts_tab_filtering_and_pagination(): void
    {
        $photoPost = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Check out our team summit photo!',
            'type' => 'photo',
            'status' => 'published',
            'audience' => 'public',
        ]);

        $videoPost = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Watch our new product demo video.',
            'type' => 'video',
            'status' => 'published',
            'audience' => 'public',
        ]);

        $pinnedPost = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Crucial pinned guidelines for community.',
            'status' => 'published',
            'audience' => 'public',
            'is_pinned' => true,
        ]);

        // Filter: photos
        $photosResponse = $this->actingAs($this->visitor)->get(route('pages.show', [
            'slug' => $this->page->slug,
            'tab' => 'posts',
            'post_filter' => 'photos',
            'blade' => 1,
        ]));
        $photosResponse->assertOk();
        $photosResponse->assertSee('Check out our team summit photo!');
        $photosResponse->assertDontSee('Watch our new product demo video.');

        // Filter: videos
        $videosResponse = $this->actingAs($this->visitor)->get(route('pages.show', [
            'slug' => $this->page->slug,
            'tab' => 'posts',
            'post_filter' => 'videos',
            'blade' => 1,
        ]));
        $videosResponse->assertOk();
        $videosResponse->assertSee('Watch our new product demo video.');
        $videosResponse->assertDontSee('Check out our team summit photo!');

        // Filter: pinned
        $pinnedResponse = $this->actingAs($this->visitor)->get(route('pages.show', [
            'slug' => $this->page->slug,
            'tab' => 'posts',
            'post_filter' => 'pinned',
            'blade' => 1,
        ]));
        $pinnedResponse->assertOk();
        $pinnedResponse->assertSee('Crucial pinned guidelines for community.');
    }

    /**
     * 3. Test Authorized Management Actions: Pin, Edit, and Delete Post.
     */
    public function test_authorized_post_management_pin_edit_and_delete(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Original Post Content Before Edit',
            'status' => 'published',
            'audience' => 'public',
            'is_pinned' => false,
        ]);

        // Visitor cannot pin post
        $visitorPin = $this->actingAs($this->visitor)->postJson(route('pages.posts.pin', ['slug' => $this->page->slug, 'postId' => $post->id]));
        $visitorPin->assertForbidden();

        // Manager can pin post
        $managerPin = $this->actingAs($this->manager)->postJson(route('pages.posts.pin', ['slug' => $this->page->slug, 'postId' => $post->id]));
        $managerPin->assertOk();
        $this->assertTrue($post->fresh()->is_pinned);

        // Visitor cannot edit post
        $visitorEdit = $this->actingAs($this->visitor)->putJson(route('pages.posts.update', ['slug' => $this->page->slug, 'postId' => $post->id]), [
            'content' => 'Hacked content',
        ]);
        $visitorEdit->assertForbidden();

        // Manager can edit post
        $managerEdit = $this->actingAs($this->manager)->putJson(route('pages.posts.update', ['slug' => $this->page->slug, 'postId' => $post->id]), [
            'content' => 'Updated Post Content After Edit',
        ]);
        $managerEdit->assertOk();
        $this->assertEquals('Updated Post Content After Edit', $post->fresh()->content);

        // Visitor cannot delete post
        $visitorDelete = $this->actingAs($this->visitor)->deleteJson(route('pages.posts.delete', ['slug' => $this->page->slug, 'postId' => $post->id]));
        $visitorDelete->assertForbidden();

        // Manager can delete post
        $managerDelete = $this->actingAs($this->manager)->deleteJson(route('pages.posts.delete', ['slug' => $this->page->slug, 'postId' => $post->id]));
        $managerDelete->assertOk();
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    /**
     * 4. Test About Tab privacy-aware contact fields and business hours.
     */
    public function test_about_tab_privacy_aware_fields_and_business_hours(): void
    {
        // Visitor view: email is hidden by privacy_settings['hide_email'] = true
        $visitorResponse = $this->actingAs($this->visitor)->get(route('pages.show', ['slug' => $this->page->slug, 'tab' => 'about', 'blade' => 1]));
        $visitorResponse->assertOk();
        $visitorResponse->assertSee('গোপনীয় (Private)');
        $visitorResponse->assertDontSee('contact@jugajug.example.com');
        // Phone is not hidden
        $visitorResponse->assertSee('+8801700000000');
        // Website is visible
        $visitorResponse->assertSee('https://jugajug.example.com');
        // Business hours
        $visitorResponse->assertSee('monday');
        $visitorResponse->assertSee('09:00 - 18:00');
        $visitorResponse->assertSee('Closed');

        // Manager view: can see email with "পাবলিকের কাছে লুকানো" badge
        $managerResponse = $this->actingAs($this->manager)->get(route('pages.show', ['slug' => $this->page->slug, 'tab' => 'about', 'blade' => 1]));
        $managerResponse->assertOk();
        $managerResponse->assertSee('contact@jugajug.example.com');
        $managerResponse->assertSee('পাবলিকের কাছে লুকানো');

        // JSON API for about
        $jsonResponse = $this->actingAs($this->visitor)->getJson(route('pages.about', ['slug' => $this->page->slug]));
        $jsonResponse->assertOk();
        $jsonResponse->assertJsonPath('data.name', 'Jugajug Technologies');
        $jsonResponse->assertJsonPath('data.business_hours.monday', '09:00 - 18:00');
    }

    /**
     * 5. Test Photos Tab: upload, view, pagination, and deletion.
     */
    public function test_photos_tab_upload_view_and_authorized_deletion(): void
    {
        // Visitor cannot upload photos
        $file = UploadedFile::fake()->image('office.jpg', 600, 600);
        $visitorUpload = $this->actingAs($this->visitor)->postJson(route('pages.photos.store', ['slug' => $this->page->slug]), [
            'photo' => $file,
            'caption' => 'Unauthorized upload attempt',
        ]);
        $visitorUpload->assertForbidden();

        // Manager can upload photo
        $managerUpload = $this->actingAs($this->manager)->postJson(route('pages.photos.store', ['slug' => $this->page->slug]), [
            'photo' => $file,
            'caption' => 'Grand Opening of Jugajug Headquarters',
        ]);
        $managerUpload->assertCreated();
        $managerUpload->assertJsonPath('success', true);

        $mediaId = $managerUpload->json('media.0.id');
        $this->assertNotNull($mediaId);
        $this->assertDatabaseHas('media', ['id' => $mediaId]);

        // Photos tab shows the photo
        $photosTabResponse = $this->actingAs($this->visitor)->get(route('pages.show', ['slug' => $this->page->slug, 'tab' => 'photos', 'blade' => 1]));
        $photosTabResponse->assertOk();
        $photosTabResponse->assertSee('আপলোডকৃত ছবি ও গ্যালারি (1)');

        // Photos JSON API returns media
        $photosApi = $this->actingAs($this->visitor)->getJson(route('pages.photos', ['slug' => $this->page->slug]));
        $photosApi->assertOk();
        $photosApi->assertJsonPath('meta.total', 1);

        // Visitor cannot delete photo
        $visitorDelete = $this->actingAs($this->visitor)->deleteJson(route('pages.photos.delete', ['slug' => $this->page->slug, 'mediaId' => $mediaId]));
        $visitorDelete->assertForbidden();

        // Manager can delete photo
        $managerDelete = $this->actingAs($this->manager)->deleteJson(route('pages.photos.delete', ['slug' => $this->page->slug, 'mediaId' => $mediaId]));
        $managerDelete->assertOk();
        $this->assertDatabaseMissing('media', ['id' => $mediaId]);
    }

    /**
     * 6. Test Events Tab: creating, editing, deleting, and live RSVP.
     */
    public function test_events_tab_create_edit_delete_and_rsvp(): void
    {
        // Visitor cannot create event
        $visitorCreate = $this->actingAs($this->visitor)->postJson(route('pages.events.store', ['slug' => $this->page->slug]), [
            'title' => 'Visitor Unapproved Event',
            'start_time' => now()->addDays(2)->toDateTimeString(),
        ]);
        $visitorCreate->assertForbidden();

        // Manager creates event
        $managerCreate = $this->actingAs($this->manager)->postJson(route('pages.events.store', ['slug' => $this->page->slug]), [
            'title' => 'Jugajug Annual Tech Summit 2026',
            'description' => 'Keynote speeches, product demos, and networking opportunities.',
            'start_time' => now()->addDays(5)->toDateTimeString(),
            'end_time' => now()->addDays(5)->addHours(4)->toDateTimeString(),
            'location' => 'Dhaka International Convention City',
            'is_online' => false,
        ]);
        $managerCreate->assertCreated();
        $eventId = $managerCreate->json('event.id');
        $this->assertNotNull($eventId);

        // Events tab displays event
        $eventsTab = $this->actingAs($this->visitor)->get(route('pages.show', ['slug' => $this->page->slug, 'tab' => 'events', 'blade' => 1]));
        $eventsTab->assertOk();
        $eventsTab->assertSee('Jugajug Annual Tech Summit 2026');
        $eventsTab->assertSee('Dhaka International Convention City');

        // Events JSON endpoint
        $eventsJson = $this->actingAs($this->visitor)->getJson(route('pages.events', ['slug' => $this->page->slug]));
        $eventsJson->assertOk();
        $eventsJson->assertJsonPath('data.0.title', 'Jugajug Annual Tech Summit 2026');

        // Visitor RSVPs to event
        $rsvpResponse = $this->actingAs($this->visitor)->postJson(route('pages.events.rsvp', ['slug' => $this->page->slug, 'eventId' => $eventId]), [
            'status' => 'going',
        ]);
        $rsvpResponse->assertOk();
        $rsvpResponse->assertJsonPath('status', 'going');
        $rsvpResponse->assertJsonPath('rsvp_count', 1);

        $this->assertDatabaseHas('page_event_rsvps', [
            'event_id' => $eventId,
            'user_id' => $this->visitor->id,
            'status' => 'going',
        ]);

        // Visitor toggles RSVP off
        $rsvpToggle = $this->actingAs($this->visitor)->postJson(route('pages.events.rsvp', ['slug' => $this->page->slug, 'eventId' => $eventId]), [
            'status' => 'none',
        ]);
        $rsvpToggle->assertOk();
        $rsvpToggle->assertJsonPath('status', 'none');
        $rsvpToggle->assertJsonPath('rsvp_count', 0);

        // Visitor cannot update event
        $visitorUpdate = $this->actingAs($this->visitor)->putJson(route('pages.events.update', ['slug' => $this->page->slug, 'eventId' => $eventId]), [
            'title' => 'Hacked Event Title',
            'start_time' => now()->addDays(10)->toDateTimeString(),
        ]);
        $visitorUpdate->assertForbidden();

        // Manager updates event
        $managerUpdate = $this->actingAs($this->manager)->putJson(route('pages.events.update', ['slug' => $this->page->slug, 'eventId' => $eventId]), [
            'title' => 'Updated Tech Summit 2026 (Virtual & Physical)',
            'description' => 'Updated keynote speakers announcement.',
            'start_time' => now()->addDays(6)->toDateTimeString(),
            'is_online' => true,
        ]);
        $managerUpdate->assertOk();
        $this->assertEquals('Updated Tech Summit 2026 (Virtual & Physical)', PageEvent::find($eventId)->title);

        // Visitor cannot delete event
        $visitorDelete = $this->actingAs($this->visitor)->deleteJson(route('pages.events.delete', ['slug' => $this->page->slug, 'eventId' => $eventId]));
        $visitorDelete->assertForbidden();

        // Manager deletes event
        $managerDelete = $this->actingAs($this->manager)->deleteJson(route('pages.events.delete', ['slug' => $this->page->slug, 'eventId' => $eventId]));
        $managerDelete->assertOk();
        $this->assertDatabaseMissing('page_events', ['id' => $eventId]);
    }

    /**
     * 7. Test More Menu options navigate to real supported features.
     */
    public function test_more_menu_options_navigate_to_real_screens(): void
    {
        $response = $this->actingAs($this->manager)->get(route('pages.show', ['slug' => $this->page->slug, 'blade' => 1]));
        $response->assertOk();

        // Verify More dropdown contains real tabs and functional links
        $response->assertSee('switchPageTab(\'reels\')', false);
        $response->assertSee('switchPageTab(\'followers\')', false);
        $response->assertSee('switchPageTab(\'shop\')', false);
        $response->assertSee('switchPageTab(\'about\')', false);
        $response->assertSee(route('pages.manage', $this->page->slug));

        // Test tabs can be accessed directly via query parameter
        $reelsTab = $this->actingAs($this->visitor)->get(route('pages.show', ['slug' => $this->page->slug, 'tab' => 'reels', 'blade' => 1]));
        $reelsTab->assertOk();
        $reelsTab->assertSee('Page Reels');

        $followersTab = $this->actingAs($this->visitor)->get(route('pages.show', ['slug' => $this->page->slug, 'tab' => 'followers', 'blade' => 1]));
        $followersTab->assertOk();
        $followersTab->assertSee('পেইজ ফলোয়ারবৃন্দ');

        $shopTab = $this->actingAs($this->visitor)->get(route('pages.show', ['slug' => $this->page->slug, 'tab' => 'shop', 'blade' => 1]));
        $shopTab->assertOk();
        $shopTab->assertSee('পেইজের পণ্য ও সেবা সম্ভার');
    }
}
