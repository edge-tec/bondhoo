<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageFollower;
use App\Models\PageType;
use App\Models\User;
use App\Services\PageService;
use Database\Seeders\PageTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PART 2 — Fully Functional Page Profile, Creation & Core Settings Test Suite
 */
class PageProfileAndCoreSettingsPart2Test extends TestCase
{
    use RefreshDatabase;

    protected User $creator;

    protected User $otherUser;

    protected PageType $pageType;

    protected PageCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(PageTaxonomySeeder::class);

        $this->pageType = PageType::first() ?? PageType::create([
            'name' => 'Business',
            'slug' => 'business',
            'is_active' => true,
        ]);

        $this->category = PageCategory::first() ?? PageCategory::create([
            'page_type_id' => $this->pageType->id,
            'name' => 'Technology Company',
            'slug' => 'tech-company',
            'is_active' => true,
        ]);

        $this->creator = User::factory()->create([
            'name' => 'Sara Founder',
            'username' => 'sara_founder',
            'email' => 'sara@example.com',
        ]);

        $this->otherUser = User::factory()->create([
            'name' => 'Bob Visitor',
            'username' => 'bob_visitor',
            'email' => 'bob@example.com',
        ]);
    }

    /**
     * Test A1: Page creation succeeds with all fields, assigns ownership, and persists to DB.
     */
    public function test_page_creation_succeeds_with_all_fields_and_assigns_owner(): void
    {
        $avatarFile = UploadedFile::fake()->image('avatar.jpg', 300, 300);
        $coverFile = UploadedFile::fake()->image('cover.jpg', 1200, 400);

        $payload = [
            'name' => 'TechCorp Global',
            'username' => 'techcorp_global',
            'slug' => 'techcorp-global',
            'page_type_id' => $this->pageType->id,
            'page_category_id' => $this->category->id,
            'category' => 'Technology Company',
            'short_description' => 'Global innovation engine.',
            'bio' => 'Empowering modern workflows with cutting edge tech.',
            'description' => 'Comprehensive about story for TechCorp Global enterprise.',
            'website' => 'https://techcorp.example.com',
            'email' => 'contact@techcorp.example.com',
            'phone' => '+15551234567',
            'address' => '100 Silicon Way',
            'city' => 'San Francisco',
            'country' => 'USA',
            'zip_code' => '94105',
            'cta_type' => 'learn_more',
            'cta_url' => 'https://techcorp.example.com/about',
            'visibility' => 'public',
            'avatar' => $avatarFile,
            'cover_image' => $coverFile,
            'business_hours' => [
                'monday' => ['mode' => 'open', 'open' => '09:00', 'close' => '18:00'],
                'tuesday' => ['mode' => 'open', 'open' => '09:00', 'close' => '18:00'],
            ],
        ];

        $response = $this->actingAs($this->creator)->post('/pages', $payload, [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'TechCorp Global');

        $this->assertDatabaseHas('pages', [
            'name' => 'TechCorp Global',
            'slug' => 'techcorp-global',
            'owner_id' => $this->creator->id,
            'email' => 'contact@techcorp.example.com',
            'city' => 'San Francisco',
            'visibility' => 'public',
        ]);

        $page = Page::where('slug', 'techcorp-global')->first();
        $this->assertNotNull($page);
        $this->assertNotNull($page->avatar_url);
        $this->assertNotNull($page->cover_image_url);

        // Creator is also an active owner in page_members
        $this->assertDatabaseHas('page_members', [
            'page_id' => $page->id,
            'user_id' => $this->creator->id,
            'role' => Page::ROLE_OWNER,
            'status' => 'active',
        ]);
    }

    /**
     * Test A2: Duplicate names and slugs are handled cleanly without collision errors.
     */
    public function test_duplicate_slug_is_automatically_disambiguated(): void
    {
        $payload = [
            'name' => 'Apex Solutions',
            'slug' => 'apex-solutions',
            'category' => 'Technology Company',
        ];

        $res1 = $this->actingAs($this->creator)->postJson('/api/v2/pages/register', $payload);
        $res1->assertStatus(201);
        $slug1 = $res1->json('data.slug');
        $this->assertEquals('apex-solutions', $slug1);

        // Second page with identical name and slug
        $res2 = $this->actingAs($this->otherUser)->postJson('/api/v2/pages/register', $payload);
        $res2->assertStatus(201);
        $slug2 = $res2->json('data.slug');

        $this->assertNotEquals($slug1, $slug2);
        $this->assertStringStartsWith('apex-solutions', $slug2);
        $this->assertDatabaseCount('pages', 2);
    }

    /**
     * Test A3: Creation validates required fields and rejects invalid files.
     */
    public function test_creation_validation_requires_name_and_valid_image_types(): void
    {
        // Missing name
        $response = $this->actingAs($this->creator)->postJson('/api/v2/pages/register', [
            'category' => 'Tech',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);

        // Invalid file type for avatar (executable / php disguised as txt)
        $invalidFile = UploadedFile::fake()->create('malicious.php', 20, 'application/x-php');
        $response2 = $this->actingAs($this->creator)->post('/pages', [
            'name' => 'Faulty Page',
            'avatar' => $invalidFile,
        ], ['Accept' => 'application/json']);

        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['avatar']);
    }

    /**
     * Test B1: Page Header elements connect to actual records, follower count from real relationship.
     */
    public function test_page_header_displays_real_followers_and_verified_badge(): void
    {
        $page = Page::create([
            'name' => 'Verified Alpha Brand',
            'slug' => 'verified-alpha-brand',
            'category' => 'Technology Company',
            'owner_id' => $this->creator->id,
            'is_verified' => true,
            'visibility' => 'public',
            'status' => Page::STATUS_ACTIVE,
        ]);

        // Add 2 real followers
        PageFollower::create(['page_id' => $page->id, 'user_id' => $this->creator->id]);
        PageFollower::create(['page_id' => $page->id, 'user_id' => $this->otherUser->id]);

        $viewResponse = $this->actingAs($this->otherUser)->get('/pages/'.$page->slug.'?blade=1');
        $viewResponse->assertStatus(200);

        // Check verification badge
        $viewResponse->assertSee('Verified Page');
        // Check follower count reflects real relationships
        $viewResponse->assertSee('2</strong> followers', false);
        // Check action buttons: Message button, Follow/Following button
        $viewResponse->assertSee('id="btnPageMessage"', false);
        $viewResponse->assertSee('id="btnPageFollow"', false);
    }

    /**
     * Test B2: Follow / Unfollow toggles database relationship and updates follower count.
     */
    public function test_follow_and_unfollow_toggle_real_relationship(): void
    {
        $page = Page::create([
            'name' => 'Innovate Hub',
            'slug' => 'innovate-hub',
            'category' => 'Technology Company',
            'owner_id' => $this->creator->id,
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
        ]);

        // 1. Follow
        $followRes = $this->actingAs($this->otherUser)->postJson("/pages/{$page->id}/follow");
        $followRes->assertStatus(200);
        $followRes->assertJsonPath('is_following', true);
        $followRes->assertJsonPath('followers_count', 1);

        $this->assertDatabaseHas('page_followers', [
            'page_id' => $page->id,
            'user_id' => $this->otherUser->id,
        ]);

        // 2. Unfollow
        $unfollowRes = $this->actingAs($this->otherUser)->postJson("/pages/{$page->id}/follow");
        $unfollowRes->assertStatus(200);
        $unfollowRes->assertJsonPath('is_following', false);
        $unfollowRes->assertJsonPath('followers_count', 0);

        $this->assertDatabaseMissing('page_followers', [
            'page_id' => $page->id,
            'user_id' => $this->otherUser->id,
        ]);
    }

    /**
     * Test C1: Edit page updates permitted fields and persists them.
     */
    public function test_edit_page_updates_permitted_fields(): void
    {
        $page = Page::create([
            'name' => 'Old Title',
            'slug' => 'old-title',
            'category' => 'Technology Company',
            'owner_id' => $this->creator->id,
            'status' => Page::STATUS_ACTIVE,
        ]);

        $updatePayload = [
            'name' => 'New Upgraded Title',
            'username' => 'new_upgraded_handle',
            'category' => 'Enterprise Software',
            'sub_category' => 'Cloud Platforms',
            'bio' => 'Next-gen cloud architecture.',
            'short_description' => 'Fast and agile.',
            'description' => 'Long deep-dive about our software services.',
            'website' => 'https://newupgraded.example.com',
            'email' => 'info@newupgraded.example.com',
            'phone' => '+19998887777',
            'address' => '500 Innovation Boulevard',
            'city' => 'Austin',
            'country' => 'USA',
            'zip_code' => '78701',
            'cta_type' => 'contact_us',
            'cta_url' => 'https://newupgraded.example.com/contact',
            'visibility' => 'public',
        ];

        $response = $this->actingAs($this->creator)->postJson("/pages/{$page->slug}/details", $updatePayload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'New Upgraded Title');
        $response->assertJsonPath('data.category', 'Enterprise Software');

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'name' => 'New Upgraded Title',
            'category' => 'Enterprise Software',
            'city' => 'Austin',
            'email' => 'info@newupgraded.example.com',
        ]);
    }

    /**
     * Test C2: Unauthorized users cannot edit page details (403 Forbidden).
     */
    public function test_unauthorized_user_cannot_edit_page_details(): void
    {
        $page = Page::create([
            'name' => 'Protected Brand',
            'slug' => 'protected-brand',
            'category' => 'Technology Company',
            'owner_id' => $this->creator->id,
            'status' => Page::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->otherUser)->postJson("/pages/{$page->slug}/details", [
            'name' => 'Hacked Title',
            'category' => 'Technology Company',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'name' => 'Protected Brand',
        ]);
    }

    /**
     * Test C3: Safe image replacement and cleanup of unused media files.
     */
    public function test_safe_media_replacement_deletes_orphan_file_but_preserves_shared(): void
    {
        // 1. Upload initial avatar
        $avatar1 = UploadedFile::fake()->image('old_avatar.jpg', 200, 200);
        $pageService = app(PageService::class);

        $page = Page::create([
            'name' => 'Media Test Page',
            'slug' => 'media-test-page',
            'category' => 'Technology Company',
            'owner_id' => $this->creator->id,
            'status' => Page::STATUS_ACTIVE,
        ]);

        $uploadRes1 = $this->actingAs($this->creator)->postJson("/pages/{$page->slug}/branding", [
            'avatar' => $avatar1,
        ]);
        $uploadRes1->assertStatus(200);
        $firstAvatarUrl = $uploadRes1->json('url');
        $relativeOldPath = str_replace('/storage/', '', $firstAvatarUrl);
        $this->assertTrue(Storage::disk('public')->exists($relativeOldPath));

        // 2. Replace avatar with new one
        $avatar2 = UploadedFile::fake()->image('new_avatar.jpg', 200, 200);
        $uploadRes2 = $this->actingAs($this->creator)->postJson("/pages/{$page->slug}/branding", [
            'avatar' => $avatar2,
        ]);
        $uploadRes2->assertStatus(200);
        $newAvatarUrl = $uploadRes2->json('url');
        $relativeNewPath = str_replace('/storage/', '', $newAvatarUrl);

        // Old file was orphaned and cleaned up
        $this->assertFalse(Storage::disk('public')->exists($relativeOldPath));
        // New file exists
        $this->assertTrue(Storage::disk('public')->exists($relativeNewPath));

        // 3. Test shared asset protection: If another page shares a media file, cleanup does NOT delete it
        $sharedFile = UploadedFile::fake()->image('shared_asset.jpg', 300, 300);
        $storedSharedPath = $sharedFile->store('pages/avatars', 'public');
        $sharedUrl = '/storage/'.$storedSharedPath;

        $page2 = Page::create([
            'name' => 'Page 2',
            'slug' => 'page-2',
            'category' => 'Tech',
            'owner_id' => $this->creator->id,
            'avatar_url' => $sharedUrl,
        ]);

        $page3 = Page::create([
            'name' => 'Page 3',
            'slug' => 'page-3',
            'category' => 'Tech',
            'owner_id' => $this->creator->id,
            'avatar_url' => $sharedUrl,
        ]);

        // Attempting to cleanup $sharedUrl while page3 still points to it should preserve the file
        $cleaned = $pageService->cleanupUnusedMedia($sharedUrl, $page2->id);
        $this->assertFalse($cleaned);
        $this->assertTrue(Storage::disk('public')->exists($storedSharedPath));
    }

    /**
     * Test D1: Core page settings persistence for all 8 categories.
     */
    public function test_core_page_settings_persist_comprehensively(): void
    {
        $page = Page::create([
            'name' => 'Settings Lab Page',
            'slug' => 'settings-lab-page',
            'category' => 'Technology Company',
            'owner_id' => $this->creator->id,
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
        ]);

        $settingsPayload = [
            'name' => 'Settings Lab Page Updated',
            'visibility' => 'unlisted',
            'settings' => [
                'discoverability' => [
                    'search_engine_indexing' => false,
                    'show_in_directory' => false,
                ],
                'messaging' => [
                    'allow_messages' => true,
                    'auto_reply_enabled' => true,
                    'auto_reply_message' => 'Hello! We are currently away but will reply shortly.',
                ],
                'moderation' => [
                    'allow_comments' => false,
                    'profanity_filter' => true,
                    'blocked_keywords' => ['spam', 'phishing', 'promo'],
                ],
                'notifications' => [
                    'email_alerts' => false,
                    'in_app_follows' => true,
                    'in_app_comments' => false,
                    'in_app_messages' => true,
                ],
                'publishing' => [
                    'allowed_formats' => ['text', 'image', 'video'],
                ],
                'privacy' => [
                    'audience' => 'followers',
                    'show_contact_info' => false,
                ],
            ],
            'privacy_settings' => [
                'public_email' => false,
                'public_phone' => false,
                'public_address' => false,
                'default_post_audience' => 'followers',
            ],
        ];

        $response = $this->actingAs($this->creator)->putJson("/pages/{$page->slug}/settings", $settingsPayload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('settings.messaging.auto_reply_message', 'Hello! We are currently away but will reply shortly.');
        $response->assertJsonPath('settings.moderation.blocked_keywords', ['spam', 'phishing', 'promo']);

        $refreshed = $page->fresh();
        $this->assertEquals('unlisted', $refreshed->visibility);
        $merged = $refreshed->getMergedSettings();
        $this->assertFalse($merged['discoverability']['search_engine_indexing']);
        $this->assertFalse($merged['moderation']['allow_comments']);
        $this->assertEquals(['text', 'image', 'video'], $merged['publishing']['allowed_formats']);

        $mergedPrivacy = $refreshed->getMergedPrivacySettings();
        $this->assertFalse($mergedPrivacy['public_email']);
        $this->assertEquals('followers', $mergedPrivacy['default_post_audience']);
    }

    /**
     * Test D2: Archive, Soft-Delete, and Restore lifecycle.
     */
    public function test_page_archive_delete_and_restore_lifecycle(): void
    {
        $page = Page::create([
            'name' => 'Lifecycle Page',
            'slug' => 'lifecycle-page',
            'category' => 'Technology Company',
            'owner_id' => $this->creator->id,
            'status' => Page::STATUS_ACTIVE,
        ]);

        // 1. Archive page
        $archiveRes = $this->actingAs($this->creator)->postJson("/pages/{$page->slug}/archive");
        $archiveRes->assertStatus(200);
        $this->assertEquals(Page::STATUS_DISABLED, $page->fresh()->status);

        // 2. Reactivate via settings
        $reactivateRes = $this->actingAs($this->creator)->putJson("/pages/{$page->slug}/settings", [
            'status' => Page::STATUS_ACTIVE,
        ]);
        $reactivateRes->assertStatus(200);
        $this->assertEquals(Page::STATUS_ACTIVE, $page->fresh()->status);

        // 3. Move page to trash (soft-delete)
        $deleteRes = $this->actingAs($this->creator)->deleteJson("/pages/{$page->slug}");
        $deleteRes->assertStatus(200);
        $this->assertSoftDeleted('pages', ['id' => $page->id]);

        // 4. Restore page from trash
        $restoreRes = $this->actingAs($this->creator)->postJson("/pages/{$page->id}/restore");
        $restoreRes->assertStatus(200);
        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'status' => Page::STATUS_ACTIVE,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test E1: Changes survive page reload, fresh session, and logout/login.
     */
    public function test_page_data_survives_reload_logout_and_fresh_session(): void
    {
        $page = Page::create([
            'name' => 'Session Endurance Page',
            'slug' => 'session-endurance-page',
            'category' => 'Technology Company',
            'owner_id' => $this->creator->id,
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'bio' => 'Initial bio text.',
        ]);

        // Creator updates bio and category
        $this->actingAs($this->creator)->postJson("/pages/{$page->slug}/details", [
            'name' => 'Session Endurance Page',
            'category' => 'Media & News',
            'bio' => 'Persistent across sessions.',
        ])->assertStatus(200);

        // Logout
        auth('web')->logout();
        $this->flushSession();

        // Fresh login as a different user / fresh visitor session
        $this->actingAs($this->otherUser);

        // Follow the page
        $this->postJson("/pages/{$page->id}/follow")->assertStatus(200);

        // Load show page as visitor
        $showRes = $this->get('/pages/'.$page->slug.'?blade=1');
        $showRes->assertStatus(200);
        $showRes->assertSee('Media & News');
        $showRes->assertSee('Persistent across sessions.');
        $showRes->assertSee('1</strong> followers', false);
    }
}
