<?php

namespace Tests\Feature;

use App\Events\ProfileUpdatedEvent;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SocialHubProfileFullFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
    }

    protected function createUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => $user->first_name ?? 'Social',
            'last_name' => $user->last_name ?? 'User',
            'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150',
            'cover_url' => 'https://images.unsplash.com/photo-1707343843437-caacff5cfa74?w=1200',
            'bio' => 'Software Engineer & Tech Creator',
            'work' => 'Senior Developer at Tech Hub',
            'education' => 'BSc in CSE at University of Dhaka',
            'city' => 'Dhaka, Bangladesh',
            'hometown' => 'Chittagong, Bangladesh',
            'relationship_status' => 'single',
            'birth_date' => '1996-08-20',
            'gender' => 'male',
            'website' => 'https://socialhub.example.com',
            'interests' => ['Coding', 'Photography', 'Travel'],
        ]);

        return $user;
    }

    /**
     * 1. Profile Page loads correctly with all required SocialHub fields and props.
     */
    public function test_profile_page_renders_complete_data_structures(): void
    {
        $viewer = $this->createUser(['username' => 'viewer_profile_test']);
        $owner = $this->createUser(['username' => 'profile_owner', 'name' => 'Profile Owner']);

        Post::factory()->create([
            'user_id' => $owner->id,
            'content' => 'First post on SocialHub profile timeline!',
            'audience' => 'public',
        ]);

        $response = $this->actingAs($viewer, 'web')->get("/app/profile/{$owner->username}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Profile')
            ->where('user.username', 'profile_owner')
            ->where('user.name', 'Profile Owner')
            ->has('profile_details')
            ->where('profile_details.work', 'Senior Developer at Tech Hub')
            ->where('profile_details.education', 'BSc in CSE at University of Dhaka')
            ->where('profile_details.city', 'Dhaka, Bangladesh')
            ->where('profile_details.hometown', 'Chittagong, Bangladesh')
            ->where('profile_details.relationship_status', 'single')
            ->where('profile_details.birth_date', 'Aug 20, 1996')
            ->where('profile_details.gender', 'male')
            ->where('profile_details.website', 'https://socialhub.example.com')
            ->where('profile_details.interests', ['Coding', 'Photography', 'Travel'])
            ->has('stats')
            ->has('photos')
            ->has('videos')
            ->has('mutual_friends_count')
            ->has('is_following')
            ->has('posts.data', 1)
            ->where('posts.data.0.content', 'First post on SocialHub profile timeline!')
        );
    }

    /**
     * 2. User can update profile details via PUT /api/v1/profile (Basic Info, Work/Edu, Places, Interests).
     */
    public function test_user_can_update_profile_details_via_api(): void
    {
        $user = $this->createUser(['username' => 'updater_user', 'name' => 'Original Name']);

        $payload = [
            'name' => 'Updated Name',
            'bio' => 'New bio written from Edit Profile modal',
            'gender' => 'female',
            'birth_date' => '1998-11-25',
            'work' => 'Staff Architect at Global Systems',
            'education' => 'MSc in Software Engineering',
            'city' => 'Sylhet, Bangladesh',
            'hometown' => 'Rajshahi, Bangladesh',
            'relationship_status' => 'married',
            'interests' => ['Open Source', 'AI', 'Reading', 'Coffee'],
        ];

        $response = $this->actingAs($user, 'web')
            ->putJson('/api/v1/profile', $payload);

        $response->assertOk();

        // Verify users table updated
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);

        // Verify user_profiles table updated
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'New bio written from Edit Profile modal',
            'gender' => 'female',
            'work' => 'Staff Architect at Global Systems',
            'education' => 'MSc in Software Engineering',
            'city' => 'Sylhet, Bangladesh',
            'hometown' => 'Rajshahi, Bangladesh',
            'relationship_status' => 'married',
        ]);
    }

    /**
     * 3. User can upload avatar via POST /api/v1/profile/avatar.
     */
    public function test_user_can_upload_avatar(): void
    {
        Queue::fake();
        Event::fake([ProfileUpdatedEvent::class]);

        $user = $this->createUser(['username' => 'avatar_uploader']);
        $file = UploadedFile::fake()->image('my_new_avatar.jpg', 500, 500);

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/v1/profile/avatar', [
                'file' => $file,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    /**
     * 4. User can upload cover photo via POST /api/v1/profile/cover.
     */
    public function test_user_can_upload_cover(): void
    {
        Queue::fake();
        Event::fake([ProfileUpdatedEvent::class]);

        $user = $this->createUser(['username' => 'cover_uploader']);
        $file = UploadedFile::fake()->image('my_cover.jpg', 1200, 450);

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/v1/profile/cover', [
                'file' => $file,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    /**
     * 5. Friendship lifecycle actions from profile (request, cancel, accept, unfriend).
     */
    public function test_friendship_lifecycle_endpoints_from_profile(): void
    {
        $alice = $this->createUser(['username' => 'alice_friend']);
        $bob = $this->createUser(['username' => 'bob_friend']);

        // 1. Alice sends friend request to Bob
        $reqResponse = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");
        $reqResponse->assertStatus(201);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => 'pending',
        ]);

        // 2. Alice cancels the request
        $cancelResponse = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/cancel");
        $cancelResponse->assertOk();

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => 'pending',
        ]);

        // 3. Alice sends request again
        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request")
            ->assertStatus(201);

        // 4. Bob accepts the request
        $acceptResponse = $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/friends/{$alice->id}/accept");
        $acceptResponse->assertOk();

        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => 'accepted',
        ]);

        // 5. Alice unfriends Bob
        $unfriendResponse = $this->actingAs($alice, 'sanctum')
            ->deleteJson("/api/v1/friends/{$bob->id}");
        $unfriendResponse->assertOk();

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => 'accepted',
        ]);
    }

    /**
     * 6. User can follow and unfollow profile owner.
     */
    public function test_follow_and_unfollow_toggle(): void
    {
        $follower = $this->createUser(['username' => 'profile_follower']);
        $celebrity = $this->createUser(['username' => 'profile_star']);

        // Follow
        $followResponse = $this->actingAs($follower, 'sanctum')
            ->postJson("/api/v1/friends/{$celebrity->id}/follow");
        $followResponse->assertStatus(201);

        // Unfollow
        $unfollowResponse = $this->actingAs($follower, 'sanctum')
            ->deleteJson("/api/v1/friends/{$celebrity->id}/follow");
        $unfollowResponse->assertOk();
    }

    /**
     * 7. Direct messaging button resolves conversation with target user.
     */
    public function test_messaging_button_resolves_direct_conversation(): void
    {
        $userA = $this->createUser(['username' => 'chat_initiator']);
        $userB = $this->createUser(['username' => 'chat_target']);

        $response = $this->actingAs($userA, 'web')->get("/messages?user={$userB->id}");

        $response->assertOk();
        $response->assertViewHas('activeConversation');

        $activeConv = $response->viewData('activeConversation');
        $this->assertInstanceOf(Conversation::class, $activeConv);
        $this->assertEquals('direct', $activeConv->type);
    }

    /**
     * 8. User can block and unblock target profile.
     */
    public function test_user_can_block_and_unblock_profile(): void
    {
        $actor = $this->createUser(['username' => 'actor_blocker']);
        $target = $this->createUser(['username' => 'target_blocked']);

        // Block target user
        $blockResponse = $this->actingAs($actor, 'web')
            ->postJson("/api/v1/profile/{$target->username}/block");
        $blockResponse->assertOk()
            ->assertJsonPath('success', true);

        // Unblock target user
        $unblockResponse = $this->actingAs($actor, 'web')
            ->postJson("/api/v1/profile/{$target->username}/unblock");
        $unblockResponse->assertOk()
            ->assertJsonPath('success', true);
    }

    /**
     * 9. User can report profile and post.
     */
    public function test_user_can_report_profile_and_post(): void
    {
        $reporter = $this->createUser(['username' => 'reporter_user']);
        $suspect = $this->createUser(['username' => 'suspect_user']);
        $post = Post::factory()->create([
            'user_id' => $suspect->id,
            'content' => 'Suspicious content here',
            'audience' => 'public',
        ]);

        // Report user profile
        $reportUserResponse = $this->actingAs($reporter, 'web')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'user',
                'reportable_id' => $suspect->id,
                'reason' => 'harassment',
                'details' => 'Harassing behavior on profile',
            ]);
        $reportUserResponse->assertStatus(201);

        // Report post
        $reportPostResponse = $this->actingAs($reporter, 'web')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'spam',
                'details' => 'Spam link posted on feed',
            ]);
        $reportPostResponse->assertStatus(201);
    }

    /**
     * 10. User can share a post from profile timeline with custom caption.
     */
    public function test_user_can_share_post_with_caption(): void
    {
        $sharer = $this->createUser(['username' => 'post_sharer']);
        $author = $this->createUser(['username' => 'post_author']);

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'content' => 'Inspiring announcement from profile owner!',
            'audience' => 'public',
        ]);

        $response = $this->actingAs($sharer, 'web')
            ->postJson("/api/v1/posts/{$post->id}/share", [
                'caption' => 'Check out this awesome update from my friend!',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('post_shares', [
            'user_id' => $sharer->id,
            'post_id' => $post->id,
            'caption' => 'Check out this awesome update from my friend!',
        ]);

        $this->assertEquals(1, $post->fresh()->shares_count);
    }

    /**
     * 11. User can delete avatar and cover photo via DELETE endpoints.
     */
    public function test_user_can_delete_avatar_and_cover_photo(): void
    {
        $user = $this->createUser(['username' => 'photo_remover']);

        // Delete cover
        $coverResponse = $this->actingAs($user, 'web')
            ->deleteJson('/api/v1/profile/cover');
        $coverResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull($user->fresh()->profile->cover_url);

        // Delete avatar
        $avatarResponse = $this->actingAs($user, 'web')
            ->deleteJson('/api/v1/profile/avatar');
        $avatarResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull($user->fresh()->profile->avatar_url);
    }

    /**
     * 12. User can toggle save and unsave a post.
     */
    public function test_user_can_toggle_save_post(): void
    {
        $user = $this->createUser(['username' => 'post_saver']);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'content' => 'A post to save for later reading',
            'audience' => 'public',
        ]);

        // Save post
        $saveResponse = $this->actingAs($user, 'web')
            ->postJson('/api/v1/profile/saved-items/toggle', [
                'item_type' => 'post',
                'item_id' => $post->id,
            ]);

        $saveResponse->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('saved_items', [
            'user_id' => $user->id,
            'item_type' => 'post',
            'item_id' => $post->id,
        ]);

        // Unsave post
        $unsaveResponse = $this->actingAs($user, 'web')
            ->postJson('/api/v1/profile/saved-items/toggle', [
                'item_type' => 'post',
                'item_id' => $post->id,
            ]);

        $unsaveResponse->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('saved_items', [
            'user_id' => $user->id,
            'item_type' => 'post',
            'item_id' => $post->id,
        ]);
    }

    /**
     * 13. Sidebar navigation items resolve properly without 404s.
     */
    public function test_sidebar_navigation_destinations_resolve_successfully(): void
    {
        $user = $this->createUser(['username' => 'nav_tester']);

        $this->actingAs($user, 'web')->get('/saved')->assertRedirect('/dashboard?tab=saved');
        $this->actingAs($user, 'web')->get('/memories')->assertRedirect('/dashboard?tab=memories');
        $this->actingAs($user, 'web')->get('/events')->assertRedirect('/dashboard?tab=events');
        $this->actingAs($user, 'web')->get('/settings/profile')->assertRedirect("/app/profile/{$user->username}");
        $this->actingAs($user, 'web')->get('/settings/privacy')->assertOk();
    }

    /**
     * 14. User can update website field via profile update endpoint.
     */
    public function test_user_can_update_website_in_profile(): void
    {
        $user = $this->createUser(['username' => 'website_updater']);

        $response = $this->actingAs($user, 'web')
            ->putJson('/api/v1/profile', [
                'website' => 'https://newportfolio.dev',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals('https://newportfolio.dev', $user->fresh()->profile->website);
    }
}
