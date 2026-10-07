<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\ProfileEnterpriseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileEnterpriseV3Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $friend;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Tanvir Rahman',
            'username' => 'tanvir',
            'email' => 'tanvir@jugajug.com',
        ]);

        UserProfile::create([
            'user_id' => $this->user->id,
            'bio' => 'Senior Software Architect',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'category' => 'Technology',
            'pronouns' => 'He/Him',
        ]);

        $this->friend = User::factory()->create([
            'name' => 'Fahim Ahmed',
            'username' => 'fahim',
            'email' => 'fahim@jugajug.com',
        ]);

        UserProfile::create([
            'user_id' => $this->friend->id,
            'bio' => 'Creative Designer',
            'city' => 'Chittagong',
            'country' => 'Bangladesh',
        ]);
    }

    public function test_avatar_and_cover_management_workflow(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Set avatar and verify history
        $service = app(ProfileEnterpriseService::class);
        $photo1 = $service->setAvatar($this->user, 'https://example.com/avatar1.jpg');
        $photo2 = $service->setAvatar($this->user, 'https://example.com/avatar2.jpg');

        $this->assertDatabaseHas('profile_photos', ['id' => $photo1->id, 'is_current' => false]);
        $this->assertDatabaseHas('profile_photos', ['id' => $photo2->id, 'is_current' => true]);

        // 2. Restore previous avatar
        $response = $this->postJson('/api/v1/profile/avatar/restore', [
            'photo_id' => $photo1->id,
        ]);
        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('profile_photos', ['id' => $photo1->id, 'is_current' => true]);

        // 3. Apply frame
        $frameResponse = $this->postJson('/api/v1/profile/avatar/frame', [
            'frame_id' => 'bd_flag',
        ]);
        $frameResponse->assertOk()
            ->assertJsonPath('data.frame_id', 'bd_flag');

        // 4. Set and reposition cover
        $cover = $service->setCover($this->user, 'https://example.com/cover1.jpg', 40);
        $this->assertDatabaseHas('cover_photos', ['id' => $cover->id, 'position_y' => 40]);

        $repoResponse = $this->postJson('/api/v1/profile/cover/reposition', [
            'position_y' => 75,
        ]);
        $repoResponse->assertOk();
        $this->assertDatabaseHas('cover_photos', ['id' => $cover->id, 'position_y' => 75]);
    }

    public function test_about_multi_sections_api(): void
    {
        Sanctum::actingAs($this->user);

        // Personal
        $resPersonal = $this->putJson('/api/v1/profile/about/personal', [
            'middle_name' => 'Hasan',
            'religion' => 'Islam',
            'blood_group' => 'B+',
            'pronouns' => 'He/Him',
            'category' => 'Engineering',
            'relationship_status' => 'Single',
        ]);
        $resPersonal->assertOk()
            ->assertJsonPath('data.middle_name', 'Hasan')
            ->assertJsonPath('data.blood_group', 'B+');

        // Contact
        $resContact = $this->putJson('/api/v1/profile/about/contact', [
            'portfolio' => 'https://tanvir.dev',
            'whatsapp' => '+8801700000000',
            'telegram' => '@tanvir',
        ]);
        $resContact->assertOk()
            ->assertJsonPath('data.portfolio', 'https://tanvir.dev')
            ->assertJsonPath('data.whatsapp', '+8801700000000');

        // Location
        $resLocation = $this->putJson('/api/v1/profile/about/location', [
            'division' => 'Dhaka',
            'district' => 'Dhaka',
            'upazila' => 'Dhanmondi',
            'city' => 'Dhaka',
            'hometown' => 'Mymensingh',
        ]);
        $resLocation->assertOk()
            ->assertJsonPath('data.division', 'Dhaka')
            ->assertJsonPath('data.upazila', 'Dhanmondi');

        // Interests & Favorites
        $resInterests = $this->putJson('/api/v1/profile/about/interests', [
            'hobbies' => ['Reading', 'Photography'],
            'favorite_music' => ['Rabindra Sangeet', 'Rock'],
            'favorite_books' => ['Shesher Kobita'],
        ]);
        $resInterests->assertOk()
            ->assertJsonPath('data.hobbies.0', 'Reading');
    }

    public function test_advanced_friends_management(): void
    {
        Sanctum::actingAs($this->user);

        // Establish Friendship
        Friendship::create([
            'user_id' => $this->user->id,
            'friend_id' => $this->friend->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Toggle Favorite
        $favRes = $this->postJson("/api/v1/profile/friends/{$this->friend->id}/favorite");
        $favRes->assertOk()->assertJsonPath('is_favorite', true);

        // Toggle Close Friend
        $closeRes = $this->postJson("/api/v1/profile/friends/{$this->friend->id}/close");
        $closeRes->assertOk()->assertJsonPath('is_close_friend', true);

        // Mute Friend
        $muteRes = $this->postJson("/api/v1/profile/friends/{$this->friend->id}/mute", ['hours' => 24]);
        $muteRes->assertOk();
        $this->assertDatabaseHas('friendships', [
            'user_id' => $this->user->id,
            'friend_id' => $this->friend->id,
            'is_muted' => true,
        ]);

        // Snooze Friend
        $snoozeRes = $this->postJson("/api/v1/profile/friends/{$this->friend->id}/snooze", ['days' => 30]);
        $snoozeRes->assertOk();

        // Get Advanced Friends
        $listRes = $this->getJson('/api/v1/profile/friends/advanced?filter=close_friends');
        $listRes->assertOk()->assertJsonPath('count', 1);

        // Friend Suggestions
        $sugRes = $this->getJson('/api/v1/profile/friends/suggestions');
        $sugRes->assertOk();
    }

    public function test_photo_albums_and_items_lifecycle(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Create Album
        $createRes = $this->postJson('/api/v1/profile/albums', [
            'title' => 'Sylhet Tour 2026',
            'description' => 'Memorable tour with friends',
            'privacy' => 'public',
        ]);
        $createRes->assertCreated();
        $albumId = $createRes->json('data.id');

        // 2. Add Item to Album
        $itemRes = $this->postJson("/api/v1/profile/albums/{$albumId}/items", [
            'media_path' => 'https://example.com/sylhet1.jpg',
            'caption' => 'Jaflong stone collection',
            'location' => 'Jaflong',
        ]);
        $itemRes->assertOk()
            ->assertJsonPath('data.caption', 'Jaflong stone collection');

        // 3. Get Albums
        $getRes = $this->getJson('/api/v1/profile/albums');
        $getRes->assertOk()
            ->assertJsonCount(1, 'data');

        // 4. Delete Album
        $delRes = $this->deleteJson("/api/v1/profile/albums/{$albumId}");
        $delRes->assertOk();
        $this->assertDatabaseMissing('photo_albums', ['id' => $albumId]);
    }

    public function test_saved_items_and_collections(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Toggle Save Item
        $saveRes = $this->postJson('/api/v1/profile/saved-items/toggle', [
            'item_type' => 'App\Models\Post',
            'item_id' => 99,
            'collection_name' => 'Tech Articles',
        ]);
        $saveRes->assertOk()
            ->assertJsonPath('data.saved', true);

        // 2. Get Saved Items
        $getRes = $this->getJson('/api/v1/profile/saved-items?collection=Tech Articles');
        $getRes->assertOk()
            ->assertJsonCount(1, 'data');

        // 3. Toggle Unsave Item
        $unsaveRes = $this->postJson('/api/v1/profile/saved-items/toggle', [
            'item_type' => 'App\Models\Post',
            'item_id' => 99,
        ]);
        $unsaveRes->assertOk()
            ->assertJsonPath('data.saved', false);
    }

    public function test_story_highlights_lifecycle(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Create Highlight
        $createRes = $this->postJson('/api/v1/profile/highlights', [
            'title' => 'Coding Moments',
            'cover_image_path' => 'https://example.com/code_highlight.jpg',
        ]);
        $createRes->assertCreated();
        $highlightId = $createRes->json('data.id');

        // 2. Get Highlights
        $getRes = $this->getJson('/api/v1/profile/highlights');
        $getRes->assertOk()
            ->assertJsonCount(1, 'data');

        // 3. Delete Highlight
        $delRes = $this->deleteJson("/api/v1/profile/highlights/{$highlightId}");
        $delRes->assertOk();
        $this->assertDatabaseMissing('story_highlights', ['id' => $highlightId]);
    }

    public function test_activity_logs_and_professional_mode(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Toggle Professional Mode
        $proToggle = $this->postJson('/api/v1/profile/professional-mode/toggle');
        $proToggle->assertOk()
            ->assertJsonPath('is_professional_mode', true);

        // 2. Get Professional Analytics
        $proAnalytics = $this->getJson('/api/v1/profile/professional-mode/analytics');
        $proAnalytics->assertOk()
            ->assertJsonPath('data.is_professional_mode', true)
            ->assertJsonStructure(['data' => ['profile_views_total', 'followers_count', 'monetization_ready']]);

        // 3. Query Activity Log
        $activityRes = $this->getJson('/api/v1/profile/activity-log');
        $activityRes->assertOk()
            ->assertJsonStructure(['data' => ['data']]);
    }

    public function test_profile_multi_search_api(): void
    {
        Sanctum::actingAs($this->user);

        $searchRes = $this->getJson('/api/v1/profile/search?query=Tanvir&city=Dhaka');
        $searchRes->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.username', 'tanvir');
    }

    public function test_web_profile_renders_all_enterprise_sections(): void
    {
        // View as owner
        $this->actingAs($this->user);
        $response = $this->get('/user/tanvir');

        $response->assertOk()
            ->assertSee('Tanvir Rahman')
            ->assertSee('Technology')
            ->assertSee('He/Him')
            ->assertSee('ছবি ও অ্যালবাম')
            ->assertSee('ভিডিও ও রিলস')
            ->assertSee('সংরক্ষিত')
            ->assertSee('অ্যাক্টিভিটি')
            ->assertSee('ড্যাশবোর্ড')
            ->assertSee('অ্যানালিটিক্স');

        // View as guest / viewer
        auth()->logout();
        $guestResponse = $this->get('/user/tanvir');
        $guestResponse->assertOk()
            ->assertSee('Tanvir Rahman')
            ->assertSee('পরিচিতি')
            ->assertSee('ছবি ও অ্যালবাম');
    }
}
