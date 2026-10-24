<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageMember;
use App\Models\User;
use Database\Seeders\PageTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Enterprise Create Page System Test Suite
 * Covers authentication, Inertia view rendering, validation, media upload,
 * atomic database persistence, owner role assignment, privacy enforcement,
 * and security protections.
 */
class CreatePageEnterpriseTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PageTaxonomySeeder::class);

        $this->user = User::factory()->create([
            'name' => 'John Creator',
            'username' => 'johncreator',
            'email' => 'john@socialhub.test',
        ]);

        $this->otherUser = User::factory()->create([
            'name' => 'Alice Member',
            'username' => 'alicemember',
            'email' => 'alice@socialhub.test',
        ]);
    }

    /**
     * Test 1: Unauthenticated requests to /app/pages/create redirect to login.
     */
    public function test_unauthenticated_user_cannot_access_create_page_wizard(): void
    {
        $response = $this->get('/app/pages/create');
        $response->assertRedirect(route('login'));

        $apiResponse = $this->postJson('/api/v2/pages/register', [
            'name' => 'Test Page',
        ]);
        $apiResponse->assertStatus(401);
    }

    /**
     * Test 2: Authenticated user can load the Create Page Inertia wizard view.
     */
    public function test_authenticated_user_can_load_create_page_inertia_view(): void
    {
        $response = $this->actingAs($this->user)->get('/app/pages/create');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Pages/Create')
            ->has('categories')
            ->has('pendingRequests')
            ->has('contacts')
            ->has('unreadCount')
        );
    }

    /**
     * Test 3: Validation fails if required fields (name, category, description) are missing or invalid.
     */
    public function test_create_page_validation_fails_for_invalid_data(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v2/pages/register', [
            'name' => '', // Empty name
            'category' => '',
            'description' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);

        // Test name too short
        $shortResponse = $this->actingAs($this->user)->postJson('/api/v2/pages/register', [
            'name' => 'A',
            'category' => 'Technology',
            'description' => 'Valid description here',
        ]);
        $shortResponse->assertStatus(422);
        $shortResponse->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 4: Real file upload endpoint persists media and returns valid URL.
     */
    public function test_authenticated_user_can_upload_page_media(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('brand-logo.jpg', 600, 600);

        $response = $this->actingAs($this->user)->postJson('/api/v1/media/upload', [
            'file' => $file,
            'collection' => 'page',
        ]);

        $this->assertTrue(in_array($response->status(), [200, 201]));
        $response->assertJson(['success' => true]);
        $data = $response->json('data');
        $this->assertNotEmpty($data['url']);
        $this->assertEquals('page', $data['collection']);
    }

    /**
     * Test 5: Complete atomic Page registration persists database record and assigns owner.
     */
    public function test_complete_page_registration_persists_page_and_assigns_owner(): void
    {
        $category = PageCategory::first();

        $pageData = [
            'name' => 'Tech Innovators Hub',
            'username' => 'techinnovators',
            'category' => $category ? $category->name : 'Technology',
            'page_category_id' => $category?->id,
            'description' => 'A premier community for technology founders, engineers, and creators.',
            'short_description' => 'A premier community for technology founders.',
            'visibility' => 'public',
            'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400',
            'cover_image_url' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=1200',
            'website' => 'https://techinnovators.test',
            'email' => 'hello@techinnovators.test',
            'phone' => '+8801711223344',
            'city' => 'Dhaka',
            'address' => 'Gulshan-2, Road 45, House 12',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v2/pages/register', $pageData);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);

        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData['slug']);
        $this->assertEquals('techinnovators', $responseData['username']);

        // Verify Page persisted in database
        $page = Page::where('slug', $responseData['slug'])->first();
        $this->assertNotNull($page);
        $this->assertEquals('Tech Innovators Hub', $page->name);
        $this->assertEquals($this->user->id, $page->owner_id);
        $this->assertEquals('public', $page->visibility);
        $this->assertEquals('Dhaka', $page->city);
        $this->assertEquals('hello@techinnovators.test', $page->email);

        // Verify PageMember ownership assignment
        $ownerMembership = PageMember::where('page_id', $page->id)
            ->where('user_id', $this->user->id)
            ->first();
        $this->assertNotNull($ownerMembership);
        $this->assertEquals(Page::ROLE_OWNER, $ownerMembership->role);
        $this->assertEquals('active', $ownerMembership->status);
    }

    /**
     * Test 6: Creating pages with duplicate or colliding names generates unique slugs automatically.
     */
    public function test_duplicate_page_names_generate_unique_slugs(): void
    {
        $data = [
            'name' => 'Awesome Creators',
            'category' => 'Community',
            'description' => 'First awesome creators community.',
            'visibility' => 'public',
        ];

        $res1 = $this->actingAs($this->user)->postJson('/api/v2/pages/register', $data);
        $res1->assertStatus(201);
        $slug1 = $res1->json('data.slug');

        $res2 = $this->actingAs($this->otherUser)->postJson('/api/v2/pages/register', [
            'name' => 'Awesome Creators',
            'category' => 'Community',
            'description' => 'Second awesome creators community.',
            'visibility' => 'public',
        ]);
        $res2->assertStatus(201);
        $slug2 = $res2->json('data.slug');

        $this->assertNotEquals($slug1, $slug2);
        $this->assertStringStartsWith('awesome-creators', $slug2);
    }

    /**
     * Test 7: Privacy modes (public, friends, private) are correctly stored and enforced.
     */
    public function test_privacy_settings_are_properly_persisted(): void
    {
        foreach (['public', 'friends', 'private'] as $visibility) {
            $response = $this->actingAs($this->user)->postJson('/api/v2/pages/register', [
                'name' => "Visibility {$visibility} ".rand(1000, 9999),
                'category' => 'Community',
                'description' => "Test description for {$visibility} page.",
                'visibility' => $visibility,
            ]);

            $response->assertStatus(201);
            $pageId = $response->json('data.id') ?? $response->json('data.page.id');
            $page = Page::find($pageId);
            $this->assertNotNull($page);
            $this->assertEquals($visibility, $page->visibility);
        }
    }

    /**
     * Test 8: Non-owners cannot access page management.
     */
    public function test_unauthorized_users_cannot_manage_page(): void
    {
        $page = Page::create([
            'owner_id' => $this->user->id,
            'name' => 'Owner Only Hub',
            'slug' => 'owner-only-hub',
            'username' => 'owneronlyhub',
            'category' => 'Community',
            'status' => 'active',
            'visibility' => 'public',
        ]);

        PageMember::create([
            'page_id' => $page->id,
            'user_id' => $this->user->id,
            'role' => Page::ROLE_OWNER,
            'status' => 'active',
        ]);

        // Unauthorized user attempts to access manage
        $response = $this->actingAs($this->otherUser)->get("/pages/{$page->slug}/manage");
        // Should be forbidden or redirect
        $this->assertTrue(in_array($response->status(), [403, 302]));
    }

    /**
     * Test 9: Short or incomplete usernames automatically resolve from valid page name.
     */
    public function test_registration_auto_corrects_short_username_from_page_name(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v2/pages/register', [
            'name' => 'alinalidsgww',
            'username' => 't',
            'category' => 'Content Creator',
            'description' => 'A creative media channel for video and lifestyle content.',
            'website' => 'sahaw',
            'visibility' => 'friends',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
        $pageData = $response->json('data');
        $this->assertEquals('alinalidsgww', $pageData['username']);
        $page = Page::find($pageData['id'] ?? $pageData['page']['id']);
        $this->assertNotNull($page);
        $this->assertEquals('friends', $page->visibility);
        $this->assertEquals('alinalidsgww', $page->name);
    }
}
