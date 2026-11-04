<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6CompletePagesSystemTest extends TestCase
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
     * 1. Test Page Creation with Metadata, Contact Info, and Owner Role
     */
    public function test_page_creation_with_metadata_and_owner_role(): void
    {
        $owner = $this->createUser('page_founder', 'Page Founder');

        $payload = [
            'name' => 'Jugajug Technologies',
            'category' => 'Technology',
            'bio' => 'Building next-generation open social computing.',
            'short_description' => 'Enterprise Social Networking Solutions',
            'username' => 'jugajug-tech',
            'website' => 'https://jugajug.example.com',
            'email' => 'contact@jugajug.example.com',
            'phone' => '+8801700000000',
            'address' => 'Gulshan Avenue',
            'city' => 'Dhaka',
            'visibility' => 'public',
        ];

        $res = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/pages', $payload);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Jugajug Technologies')
            ->assertJsonPath('data.category', 'Technology');

        $this->assertDatabaseHas('pages', [
            'name' => 'Jugajug Technologies',
            'username' => 'jugajug-tech',
            'owner_id' => $owner->id,
            'category' => 'Technology',
        ]);

        $page = Page::where('username', 'jugajug-tech')->first();
        $this->assertNotNull($page);

        // Verify owner is registered in page_members
        $this->assertDatabaseHas('page_members', [
            'page_id' => $page->id,
            'user_id' => $owner->id,
            'role' => Page::ROLE_OWNER,
            'status' => 'active',
        ]);
    }

    /**
     * 2. Test Team Member Invitation & Role Assignment
     */
    public function test_page_team_invitation_and_role_management(): void
    {
        $owner = $this->createUser('team_owner', 'Team Owner');
        $editor = $this->createUser('editor_user', 'Editor User');

        $pageRes = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/pages', [
            'name' => 'Design Collective',
            'category' => 'Design & Art',
        ]);
        $pageId = $pageRes->json('data.id');

        // Owner invites team member as editor
        $inviteRes = $this->actingAs($owner, 'sanctum')->postJson("/api/v2/pages/{$pageId}/team/invite", [
            'identifier' => $editor->email,
            'role' => 'editor',
        ]);

        $inviteRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('page_members', [
            'page_id' => $pageId,
            'user_id' => $editor->id,
            'role' => 'editor',
        ]);
    }

    /**
     * 3. Test Follow and Unfollow Page
     */
    public function test_page_follow_and_unfollow_lifecycle(): void
    {
        $owner = $this->createUser('brand_owner', 'Brand Owner');
        $follower = $this->createUser('loyal_fan', 'Loyal Fan');

        $pageRes = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/pages', [
            'name' => 'Artisan Coffee Roasters',
            'category' => 'Food & Beverage',
        ]);
        $pageId = $pageRes->json('data.id');

        // 1. Follow page
        $followRes = $this->actingAs($follower, 'sanctum')->postJson("/api/v1/pages/{$pageId}/follow");
        $followRes->assertStatus(200);

        $page = Page::find($pageId);
        $this->assertEquals(1, $page->followers_count);

        // 2. Unfollow page (toggle off)
        $unfollowRes = $this->actingAs($follower, 'sanctum')->postJson("/api/v1/pages/{$pageId}/follow");
        $unfollowRes->assertStatus(200);

        $page->refresh();
        $this->assertEquals(0, $page->followers_count);
    }

    /**
     * 4. Test Page Official Post Creation and Feed
     */
    public function test_page_post_publishing_and_feed(): void
    {
        $owner = $this->createUser('publisher_owner', 'Publisher Owner');
        $visitor = $this->createUser('visitor_reader', 'Visitor Reader');

        $pageRes = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/pages', [
            'name' => 'Daily Tech Dispatch',
            'category' => 'Media & News',
        ]);
        $pageId = $pageRes->json('data.id');

        // Owner publishes post as page
        $postRes = $this->actingAs($owner, 'sanctum')->postJson("/api/v1/pages/{$pageId}/posts", [
            'content' => 'Exciting updates on our upcoming developer conference! 🚀',
            'type' => 'text',
        ]);
        $postRes->assertStatus(201);

        $this->assertDatabaseHas('posts', [
            'page_id' => $pageId,
            'user_id' => $owner->id,
            'content' => 'Exciting updates on our upcoming developer conference! 🚀',
        ]);

        // Visitor views page feed
        $feedRes = $this->actingAs($visitor, 'sanctum')->getJson("/api/v1/pages/{$pageId}/posts");
        $feedRes->assertStatus(200);
        $this->assertNotEmpty($feedRes->json('data'));
    }

    /**
     * 5. Test Page Deletion and Ownership Security
     */
    public function test_page_deletion_authorization_security(): void
    {
        $owner = $this->createUser('creator_owner', 'Creator Owner');
        $intruder = $this->createUser('malicious_actor', 'Malicious Actor');

        $pageRes = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/pages', [
            'name' => 'Secure Enterprise Hub',
            'category' => 'Business',
        ]);
        $pageId = $pageRes->json('data.id');

        // 1. Intruder attempts to delete page -> 403 Forbidden
        $intruderRes = $this->actingAs($intruder, 'sanctum')->deleteJson("/api/v2/pages/{$pageId}");
        $intruderRes->assertStatus(403);
        $this->assertDatabaseHas('pages', ['id' => $pageId, 'deleted_at' => null]);

        // 2. Owner deletes page -> 200 OK & Soft Deleted
        $ownerRes = $this->actingAs($owner, 'sanctum')->deleteJson("/api/v2/pages/{$pageId}");
        $ownerRes->assertStatus(200);

        $this->assertSoftDeleted('pages', ['id' => $pageId]);
    }

    /**
     * 6. Test Web Route Resolution for Pages
     */
    public function test_page_web_routes_and_navigation(): void
    {
        $user = $this->createUser('web_page_user', 'Web Page User');

        // 1. Pages directory index
        $indexRes = $this->actingAs($user, 'web')->get('/pages');
        $indexRes->assertStatus(200);

        // 2. Create page view
        $createRes = $this->actingAs($user, 'web')->get('/pages/create');
        $createRes->assertStatus(200);
    }
}
