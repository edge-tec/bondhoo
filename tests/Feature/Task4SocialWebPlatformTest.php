<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\Story;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Task4SocialWebPlatformTest extends TestCase
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
            'first_name' => $user->first_name ?? 'Bondhoo',
            'last_name' => $user->last_name ?? 'User',
            'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150',
            'bio' => 'Building the next-gen social network.',
        ]);

        return $user;
    }

    /**
     * 1. Unauthenticated users visiting /app are redirected to login.
     */
    public function test_guest_is_redirected_to_login_from_app(): void
    {
        $response = $this->get('/app');
        $response->assertRedirect(route('login'));
    }

    /**
     * 2. Authenticated user can view feed rendering Inertia 'Feed' with real posts.
     */
    public function test_authenticated_user_can_view_feed_with_posts(): void
    {
        $user = $this->createUser(['name' => 'Feed Tester', 'username' => 'feed_tester']);

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'content' => 'First post on Bondhoo Vue 3 platform!',
            'audience' => 'public',
        ]);

        $response = $this->actingAs($user, 'web')->get('/app');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Feed')
            ->has('posts.data', 1)
            ->where('posts.data.0.content', 'First post on Bondhoo Vue 3 platform!')
            ->has('pendingRequests')
            ->has('contacts')
            ->has('unreadCount')
            ->has('stories')
            ->has('suggestions')
            ->has('upcomingEvents')
            ->has('unreadMessagesCount')
        );
    }

    /**
     * 3. Private posts from other users do not leak into the viewer's feed.
     */
    public function test_private_posts_do_not_leak_into_unauthorized_user_feed(): void
    {
        $viewer = $this->createUser(['username' => 'viewer_user']);
        $stranger = $this->createUser(['username' => 'stranger_user']);

        // Stranger creates a private post (only_me) and friends-only post
        Post::factory()->create([
            'user_id' => $stranger->id,
            'content' => 'Top secret private thoughts',
            'audience' => 'only_me',
        ]);
        Post::factory()->create([
            'user_id' => $stranger->id,
            'content' => 'Friends only update',
            'audience' => 'friends',
        ]);
        // And a public post
        Post::factory()->create([
            'user_id' => $stranger->id,
            'content' => 'Public announcement to everyone',
            'audience' => 'public',
        ]);

        $response = $this->actingAs($viewer, 'web')->get('/app');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Feed')
            ->has('posts.data', 1)
            ->where('posts.data.0.content', 'Public announcement to everyone')
        );
    }

    /**
     * 4. User can view their own and another user's profile rendering Inertia 'Profile'.
     */
    public function test_user_can_view_social_profile_page(): void
    {
        $alice = $this->createUser(['name' => 'Alice Profile', 'username' => 'alice_profile']);
        $bob = $this->createUser(['name' => 'Bob Profile', 'username' => 'bob_profile']);

        Post::factory()->create([
            'user_id' => $bob->id,
            'content' => 'Bob greeting the world!',
            'audience' => 'public',
        ]);

        // Alice views Bob's profile
        $response = $this->actingAs($alice, 'web')->get("/app/profile/{$bob->username}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Profile')
            ->where('user.username', 'bob_profile')
            ->where('user.name', 'Bob Profile')
            ->where('relationship', 'none')
            ->has('posts.data', 1)
            ->where('posts.data.0.content', 'Bob greeting the world!')
            ->has('stats')
            ->has('friends')
        );
    }

    /**
     * 5. Friends management page renders Inertia 'Friends' with requests, friends, and suggestions.
     */
    public function test_user_can_view_friends_management_page(): void
    {
        $user = $this->createUser(['username' => 'main_user']);
        $requester = $this->createUser(['username' => 'requester_user', 'name' => 'Requester']);
        $friend = $this->createUser(['username' => 'already_friend', 'name' => 'Friend User']);
        $suggestion = $this->createUser(['username' => 'suggested_user', 'name' => 'Suggested User']);

        // Requester sent friend request to user
        Friendship::create([
            'user_id' => $requester->id,
            'friend_id' => $user->id,
            'status' => 'pending',
        ]);

        // Friend is already accepted friend
        Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($user, 'web')->get('/app/friends');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Friends')
            ->has('requests', 1)
            ->where('requests.0.sender.username', 'requester_user')
            ->has('friends', 1)
            ->where('friends.0.username', 'already_friend')
            ->has('suggestions')
        );
    }

    /**
     * 6. Notifications center renders Inertia 'Notifications' with notifications list.
     */
    public function test_user_can_view_notifications_page(): void
    {
        $user = $this->createUser(['username' => 'notif_user']);
        $actor = $this->createUser(['username' => 'actor_user', 'name' => 'Actor User']);

        // Create a notification for user
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'type' => 'reaction.post',
            'data' => json_encode([
                'message' => 'reacted with ❤️ to your post.',
                'sender' => [
                    'id' => $actor->id,
                    'name' => $actor->name,
                    'username' => $actor->username,
                ],
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user, 'web')->get('/app/notifications');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Notifications')
            ->has('notifications.data', 1)
            ->where('notifications.data.0.message', 'reacted with ❤️ to your post.')
            ->has('unreadCount')
        );
    }

    /**
     * 7. Profile page renders all SocialHub redesign fields: profile_details, mutual_friends_count, photos.
     */
    public function test_profile_renders_complete_socialhub_redesign_data(): void
    {
        $viewer = $this->createUser(['username' => 'social_viewer']);
        $profileUser = $this->createUser(['username' => 'alex_carter', 'name' => 'Alex Carter']);

        $profileUser->profile->update([
            'work' => 'Software Engineer at Tech Solutions',
            'education' => 'Computer Science at University',
            'city' => 'Dhaka, Bangladesh',
            'hometown' => 'Chittagong, Bangladesh',
            'relationship_status' => 'single',
            'birth_date' => '1995-05-12',
            'gender' => 'male',
            'interests' => ['Travel', 'Photography', 'Technology'],
        ]);

        $response = $this->actingAs($viewer, 'web')->get('/app/profile/alex_carter');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Profile')
            ->where('user.name', 'Alex Carter')
            ->where('user.username', 'alex_carter')
            ->has('profile_details')
            ->where('profile_details.work', 'Software Engineer at Tech Solutions')
            ->where('profile_details.city', 'Dhaka, Bangladesh')
            ->where('profile_details.relationship_status', 'single')
            ->has('photos')
            ->has('mutual_friends_count')
        );
    }

    /**
     * 8. Feed renders real active stories from current user and friends.
     */
    public function test_feed_provides_real_stories_tray_data(): void
    {
        $user = $this->createUser(['username' => 'story_author', 'name' => 'Story Author']);
        $friend = $this->createUser(['username' => 'story_friend', 'name' => 'Story Friend']);

        Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'status' => 'accepted',
        ]);

        Story::create([
            'user_id' => $friend->id,
            'type' => Story::TYPE_TEXT,
            'status' => Story::STATUS_READY,
            'content' => 'Hello from friend story!',
            'background_color' => '#1877F2',
            'privacy' => 'public',
            'expires_at' => now()->addHours(20),
            'is_expired' => false,
        ]);

        $response = $this->actingAs($user, 'web')->get('/app');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Feed')
            ->has('stories')
            ->where('stories.0.user.username', 'story_friend')
            ->where('stories.0.stories.0.content', 'Hello from friend story!')
        );
    }

    /**
     * 9. Feed provides real upcoming events and friend suggestions.
     */
    public function test_feed_provides_real_suggestions_and_events(): void
    {
        $user = $this->createUser(['username' => 'main_event_user']);
        $otherUser = $this->createUser(['username' => 'suggested_peer', 'name' => 'Suggested Peer']);

        Event::create([
            'creator_id' => $otherUser->id,
            'title' => 'Tech Summit 2026',
            'description' => 'Annual developer summit',
            'start_time' => now()->addDays(5),
            'address' => 'Dhaka City Convention Center',
            'location_type' => 'physical',
        ]);

        $response = $this->actingAs($user, 'web')->get('/app');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Feed')
            ->has('upcomingEvents', 1)
            ->where('upcomingEvents.0.title', 'Tech Summit 2026')
            ->has('suggestions')
        );
    }
}
