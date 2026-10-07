<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Post;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionProfileTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_can_fetch_profile_v2_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Mizanur Rahman',
            'username' => 'mizanur',
            'email' => 'mizan@jugajug.com',
            'status' => 'active',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'bio' => 'Software Architect building scalable systems.',
            'work' => 'Senior Architect @ Tech',
            'education' => 'BUET CSE',
            'city' => 'Dhaka',
            'hometown' => 'Mymensingh',
            'relationship_status' => 'Married',
            'website' => 'https://jugajug.com',
        ]);

        $response = $this->getJson('/api/v2/profile/mizanur');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'mizanur',
                        'name' => 'Mizanur Rahman',
                    ],
                    'profile' => [
                        'bio' => 'Software Architect building scalable systems.',
                        'work' => 'Senior Architect @ Tech',
                        'education' => 'BUET CSE',
                        'city' => 'Dhaka',
                        'hometown' => 'Mymensingh',
                        'relationship_status' => 'Married',
                        'website' => 'https://jugajug.com',
                    ],
                    'is_locked_view' => false,
                ],
            ]);
    }

    public function test_locked_profile_restricts_non_friend_viewer(): void
    {
        $target = User::factory()->create([
            'name' => 'Private Person',
            'username' => 'privateperson',
            'status' => 'active',
        ]);

        UserSetting::create([
            'user_id' => $target->id,
            'is_profile_locked' => true,
        ]);

        $stranger = User::factory()->create([
            'username' => 'stranger',
            'status' => 'active',
        ]);

        // Stranger views locked profile
        $response = $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/v2/profile/privateperson');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'privateperson',
                    ],
                    'is_profile_locked' => true,
                    'is_locked_view' => true,
                    'is_friend' => false,
                ],
            ]);

        // Timeline should return empty collection
        $timelineResponse = $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/v2/profile/privateperson/timeline');

        $timelineResponse->assertStatus(200);
        $this->assertEmpty($timelineResponse->json('data'));
    }

    public function test_locked_profile_allows_friend_viewer(): void
    {
        $target = User::factory()->create([
            'name' => 'Locked User',
            'username' => 'lockeduser',
            'status' => 'active',
        ]);

        UserSetting::create([
            'user_id' => $target->id,
            'is_profile_locked' => true,
        ]);

        $friend = User::factory()->create([
            'username' => 'bestfriend',
            'status' => 'active',
        ]);

        // Create accepted friendship
        Friendship::create([
            'user_id' => $target->id,
            'friend_id' => $friend->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Friend views locked profile
        $response = $this->actingAs($friend, 'sanctum')
            ->getJson('/api/v2/profile/lockeduser');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'lockeduser',
                    ],
                    'is_profile_locked' => true,
                    'is_locked_view' => false,
                    'is_friend' => true,
                ],
            ]);
    }

    public function test_profile_owner_always_has_full_access_even_when_locked(): void
    {
        $user = User::factory()->create([
            'username' => 'owneruser',
            'status' => 'active',
        ]);

        UserSetting::create([
            'user_id' => $user->id,
            'is_profile_locked' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/profile/owneruser');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'owneruser',
                    ],
                    'is_owner' => true,
                    'is_locked_view' => false,
                ],
            ]);
    }

    public function test_authenticated_user_can_update_profile_details(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'username' => 'updateuser',
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'New Full Name',
            'bio' => 'Updated bio text here.',
            'work' => 'Staff Engineer @ Jugajug',
            'education' => 'Dhaka University',
            'city' => 'Dhaka',
            'hometown' => 'Rajshahi',
            'relationship_status' => 'In a relationship',
            'website' => 'https://newdomain.com',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'প্রোফাইল সফলভাবে আপডেট করা হয়েছে।',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Full Name',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'Updated bio text here.',
            'work' => 'Staff Engineer @ Jugajug',
            'education' => 'Dhaka University',
            'location' => 'Dhaka',
            'hometown' => 'Rajshahi',
            'relationship_status' => 'in_a_relationship',
            'website' => 'https://newdomain.com',
        ]);
    }

    public function test_authenticated_user_can_toggle_profile_lock(): void
    {
        $user = User::factory()->create([
            'username' => 'locktester',
            'status' => 'active',
        ]);

        // Toggle from false to true
        $response1 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/lock');

        $response1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_profile_locked' => true,
                ],
            ]);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'is_profile_locked' => true,
        ]);

        // Toggle back from true to false
        $response2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/lock');

        $response2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_profile_locked' => false,
                ],
            ]);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'is_profile_locked' => false,
        ]);
    }

    public function test_authenticated_user_can_upload_avatar_and_generates_timeline_post(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Avatar Tester',
            'username' => 'avatartester',
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->image('my_avatar.png', 400, 400);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/avatar', [
                'file' => $file,
                'caption' => 'আমার নতুন প্রোফাইল ছবি!',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'প্রোফাইল ছবি সফলভাবে আপডেট করা হয়েছে।',
            ])
            ->assertJsonStructure([
                'data' => [
                    'avatar_url',
                    'post' => ['id', 'content', 'type'],
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'type' => 'avatar_update',
            'content' => 'আমার নতুন প্রোফাইল ছবি!',
        ]);
    }

    public function test_authenticated_user_can_upload_cover_and_generates_timeline_post(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Cover Tester',
            'username' => 'covertester',
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->image('my_cover.jpg', 1200, 400);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/cover', [
                'file' => $file,
                'caption' => 'নতুন কভার ছবি প্রকাশ করলাম।',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'কভার ছবি সফলভাবে আপডেট করা হয়েছে।',
            ])
            ->assertJsonStructure([
                'data' => [
                    'cover_url',
                    'post' => ['id', 'content', 'type'],
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'type' => 'cover_update',
            'content' => 'নতুন কভার ছবি প্রকাশ করলাম।',
        ]);
    }

    public function test_can_fetch_user_friends_with_mutual_count(): void
    {
        $user = User::factory()->create(['username' => 'frienduser']);
        $friend = User::factory()->create(['username' => 'paluser', 'name' => 'Pal Friend']);

        Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $response = $this->getJson('/api/v2/profile/frienduser/friends');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'username', 'name', 'mutual_friends_count'],
                    ],
                ],
            ]);
    }

    public function test_can_fetch_user_photos(): void
    {
        $user = User::factory()->create(['username' => 'photouser']);
        UserProfile::create([
            'user_id' => $user->id,
            'avatar_url' => 'https://example.com/avatar.jpg',
            'cover_url' => 'https://example.com/cover.jpg',
        ]);

        $response = $this->getJson('/api/v2/profile/photouser/photos');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['type', 'url'],
                ],
            ]);
    }

    public function test_can_fetch_user_timeline_feed(): void
    {
        $user = User::factory()->create(['username' => 'timelineuser']);
        Post::create([
            'user_id' => $user->id,
            'content' => 'Hello Jugajug timeline!',
            'type' => 'text',
            'audience' => 'public',
        ]);

        $response = $this->getJson('/api/v2/profile/timelineuser/timeline');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'content', 'type', 'audience'],
                    ],
                ],
            ]);
    }

    public function test_web_route_displays_profile_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Shahidul Alam',
            'username' => 'shahidul',
            'status' => 'active',
        ]);

        $response = $this->get('/user/shahidul');

        $response->assertStatus(200)
            ->assertViewIs('profile.show')
            ->assertSee('Shahidul Alam')
            ->assertSee('@shahidul');
    }

    public function test_web_profile_me_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }

    public function test_web_profile_me_redirects_authenticated_to_own_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'loggeduser',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertRedirect('/u/loggeduser');
    }

    public function test_can_fetch_structured_about_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Tanvir Ahmed',
            'username' => 'tanvir',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'bio' => 'Passionate Developer',
            'work' => 'Lead Engineer @ Jugajug',
            'education' => 'BUET',
            'location' => 'Dhaka, Bangladesh',
            'hometown' => 'Rajshahi',
            'relationship_status' => 'single',
            'website' => 'https://tanvir.dev',
            'social_links' => ['github' => 'https://github.com/tanvir', 'linkedin' => 'https://linkedin.com/in/tanvir'],
            'interests' => ['AI', 'Open Source'],
        ]);

        $response = $this->getJson('/api/v2/profile/tanvir/about');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'overview',
                    'work_and_education',
                    'places_lived',
                    'contact_and_basic_info',
                    'family_and_relationships',
                    'interests',
                ],
            ]);

        $this->assertEquals('Lead Engineer @ Jugajug', $response->json('data.work_and_education.work'));
        $this->assertEquals('https://github.com/tanvir', $response->json('data.contact_and_basic_info.social_links.github'));
    }

    public function test_can_update_profile_with_social_links_and_cover_position(): void
    {
        $user = User::factory()->create(['username' => 'devuser']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v2/profile', [
                'bio' => 'Updated bio with social links',
                'social_links' => [
                    'facebook' => 'https://facebook.com/devuser',
                    'twitter' => 'https://x.com/devuser',
                ],
                'cover_position_y' => 75,
            ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('Updated bio with social links', $user->profile->bio);
        $this->assertEquals('https://facebook.com/devuser', $user->profile->social_links['facebook']);
        $this->assertEquals(75, $user->profile->cover_position_y);
    }

    public function test_can_reposition_cover_photo(): void
    {
        $user = User::factory()->create(['username' => 'repositionuser']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v2/profile/cover/position', [
                'position_y' => 85,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['cover_position_y' => 85],
            ]);

        $this->assertEquals(85, $user->profile->fresh()->cover_position_y);
    }

    public function test_profile_completion_percentage_calculation(): void
    {
        $user = User::factory()->create(['name' => 'Farhana']);
        $profile = UserProfile::create([
            'user_id' => $user->id,
            'avatar_url' => 'https://example.com/avatar.jpg',
            'cover_url' => 'https://example.com/cover.jpg',
            'bio' => 'My awesome bio',
            'work' => 'Engineer',
            'location' => 'Dhaka',
        ]);

        $percentage = $profile->calculateCompletionPercentage();
        $this->assertGreaterThan(50, $percentage);

        $res = $this->getJson('/api/v2/profile/'.$user->username);
        $this->assertEquals($percentage, $res->json('data.stats.completion_percentage'));
    }

    public function test_friends_privacy_only_me_hides_friends_from_non_owner(): void
    {
        $target = User::factory()->create(['username' => 'privatefriends']);
        $target->settings()->create([
            'user_id' => $target->id,
            'who_can_see_friends' => 'only_me',
        ]);

        $friend = User::factory()->create();
        Friendship::create([
            'user_id' => $target->id,
            'friend_id' => $friend->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Non-owner requests friends list -> empty
        $response = $this->getJson('/api/v2/profile/privatefriends/friends');
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.data'));

        // Owner requests friends list -> returns friend
        $ownerToken = $target->createToken('owner')->plainTextToken;
        $ownerRes = $this->withHeader('Authorization', 'Bearer '.$ownerToken)
            ->getJson('/api/v2/profile/privatefriends/friends');
        $ownerRes->assertStatus(200);
        $this->assertCount(1, $ownerRes->json('data.data'));
    }

    public function test_user_can_create_life_event_post(): void
    {
        $user = User::factory()->create(['username' => 'eventuser', 'status' => 'active']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/posts', [
                'type' => 'life_event',
                'feeling_activity' => '💼 নতুন কোম্পানিতে সিনিয়র সফটওয়্যার ইঞ্জিনিয়ার হিসেবে যোগদান (2026-09-20)',
                'location' => 'Dhaka, Bangladesh',
                'content' => 'আজ আমার জীবনের একটি নতুন অধ্যায় শুরু হলো!',
                'audience' => 'public',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'type' => 'life_event',
            'location' => 'Dhaka, Bangladesh',
        ]);
    }

    public function test_user_can_pin_and_unpin_post_on_timeline(): void
    {
        $user = User::factory()->create(['username' => 'pinuser', 'status' => 'active']);
        $token = $user->createToken('test')->plainTextToken;

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Pinned post content',
            'audience' => 'public',
            'type' => 'text',
        ]);

        $pinRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/posts/'.$post->id.'/pin');

        $pinRes->assertStatus(200);
        $this->assertTrue($post->fresh()->is_pinned);

        // Unpin
        $unpinRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/posts/'.$post->id.'/pin');

        $unpinRes->assertStatus(200);
        $this->assertFalse($post->fresh()->is_pinned);
    }

    public function test_owner_can_simulate_view_as_public(): void
    {
        $user = User::factory()->create(['username' => 'viewasuser', 'name' => 'View As User', 'status' => 'active']);
        $user->profile()->create([
            'bio' => 'Sample bio',
        ]);

        $this->actingAs($user);

        $response = $this->get('/user/viewasuser?view_as=public');
        $response->assertStatus(200);
        $response->assertSee('আপনি এখন পাবলিক দৃষ্টিতে আপনার প্রোফাইল দেখছেন');
        $response->assertSee('ভিউ অ্যাজ বন্ধ করুন');
    }

    public function test_locked_profile_displays_locked_state_in_view_as_public(): void
    {
        $user = User::factory()->create(['username' => 'lockedviewas', 'name' => 'Locked User', 'status' => 'active']);
        $user->profile()->create(['bio' => 'Secret bio']);
        $user->settings()->create([
            'user_id' => $user->id,
            'is_profile_locked' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get('/user/lockedviewas?view_as=public');
        $response->assertStatus(200);
        $response->assertSee('প্রোফাইল লক করা আছে');
    }

    public function test_user_can_toggle_avatar_guard(): void
    {
        $user = User::factory()->create([
            'username' => 'guarduser',
            'status' => 'active',
        ]);

        // Toggle on
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/avatar/guard');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'has_avatar_guard' => true,
                ],
            ]);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'has_avatar_guard' => true,
        ]);

        // Toggle off
        $response2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/avatar/guard');

        $response2->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'has_avatar_guard' => false,
                ],
            ]);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'has_avatar_guard' => false,
        ]);
    }

    public function test_profile_page_renders_avatar_shield_and_photo_theater(): void
    {
        $user = User::factory()->create([
            'username' => 'guardprofile',
            'name' => 'Guard Profile User',
            'status' => 'active',
        ]);
        $user->profile()->create(['bio' => 'Hello guard']);
        $user->settings()->create([
            'user_id' => $user->id,
            'has_avatar_guard' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get('/user/guardprofile');
        $response->assertStatus(200);
        $response->assertSee('avatar-shield-badge');
        $response->assertSee('photoTheaterModal');
        $response->assertSee('প্রোফাইল পিকচার গার্ড');
    }

    public function test_user_can_reposition_cover_photo(): void
    {
        $user = User::factory()->create([
            'username' => 'repositionuser',
            'status' => 'active',
        ]);
        $user->profile()->create([
            'cover_photo_path' => 'covers/sample.jpg',
            'cover_position_y' => 50,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/cover/reposition', [
                'position_y' => 75,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'cover_position_y' => 75,
        ]);
    }

    public function test_profile_page_renders_cover_menu_and_live_reposition_banner(): void
    {
        $user = User::factory()->create([
            'username' => 'coverprofile',
            'name' => 'Cover Profile User',
            'status' => 'active',
        ]);
        $user->profile()->create([
            'bio' => 'Cover user bio',
            'cover_photo_path' => 'covers/cover.jpg',
            'cover_position_y' => 60,
        ]);

        $this->actingAs($user);

        $response = $this->get('/user/coverprofile');
        $response->assertStatus(200);
        $response->assertSee('cover-menu-dropdown');
        $response->assertSee('coverRepositionBanner');
        $response->assertSee('টেনে এনে কভারের অবস্থান নির্ধারণ করুন');
        $response->assertSee('কভার ফটো পরিবর্তন');
    }

    public function test_profile_renders_video_hub_and_video_theater_modal(): void
    {
        $user = User::factory()->create([
            'username' => 'videoprofile',
            'name' => 'Video Profile User',
            'status' => 'active',
        ]);
        $user->profile()->create(['bio' => 'Video profile bio']);

        $this->actingAs($user);

        $response = $this->get('/user/videoprofile');
        $response->assertStatus(200);
        $response->assertSee('video-cards-grid');
        $response->assertSee('videoTheaterModal');
        $response->assertSee('ভিডিও ও রিলস হাব');
        $response->assertSee('নতুন ভিডিও যোগ করুন');
    }

    public function test_profile_renders_creator_category_modal_and_tools(): void
    {
        $user = User::factory()->create([
            'username' => 'creatorprofile',
            'name' => 'Creator Profile User',
            'status' => 'active',
        ]);
        $user->profile()->create([
            'bio' => 'Creator profile bio',
            'is_professional_mode' => true,
            'category' => 'ডিজিটাল ক্রিয়েটর',
        ]);

        $this->actingAs($user);

        $response = $this->get('/user/creatorprofile');
        $response->assertStatus(200);
        $response->assertSee('tabContent-professional', false);
        $response->assertSee('creatorCategoryModal');
        $response->assertSee('ক্যাটাগরি নির্ধারণ');
        $response->assertSee('টুলস ও ফিচারস');
        $response->assertSee('স্টারস ও ক্রিয়েটর ব্যাজ');
        $response->assertSee('অডিয়েন্স ও ফলোয়ার গ্রোথ');
        $response->assertSee('ডিজিটাল ক্রিয়েটর');
    }

    public function test_user_can_update_creator_category_via_about_personal_api(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);
        $user->profile()->create(['bio' => 'Category update test bio']);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v1/profile/about/personal', [
            'category' => 'ভিডিও ক্রিয়েটর',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'category' => 'ভিডিও ক্রিয়েটর',
        ]);
    }

    public function test_profile_renders_saved_items_hub_and_filters(): void
    {
        $user = User::factory()->create([
            'username' => 'saveduser',
            'name' => 'Saved User',
            'status' => 'active',
        ]);
        $user->profile()->create(['bio' => 'Saved items test bio']);

        $this->actingAs($user);

        $response = $this->get('/user/saveduser');
        $response->assertStatus(200);
        $response->assertSee('tabContent-saved', false);
        $response->assertSee('savedSearchInput');
        $response->assertSee('savedCategoryFilterWrap');
        $response->assertSee('সব সংরক্ষিত');
        $response->assertSee('পোস্টসমূহ');
        $response->assertSee('ভিডিও ও রিলস');
    }

    public function test_profile_renders_activity_log_hub_and_filters(): void
    {
        $user = User::factory()->create([
            'username' => 'activityuser',
            'name' => 'Activity User',
            'status' => 'active',
        ]);
        $user->profile()->create(['bio' => 'Activity test bio']);

        $this->actingAs($user);

        $response = $this->get('/user/activityuser');
        $response->assertStatus(200);
        $response->assertSee('tabContent-activity', false);
        $response->assertSee('activitySearchInput');
        $response->assertSee('activityFilterWrap');
        $response->assertSee('সকল কার্যকলাপ');
        $response->assertSee('ছবি ও মিডিয়া');
        $response->assertSee('নিরাপত্তা ও সেটিংস');
    }

    public function test_profile_renders_more_tabs_dropdown_and_items(): void
    {
        $user = User::factory()->create([
            'username' => 'moreuser',
            'name' => 'More Tab User',
            'status' => 'active',
        ]);
        $user->profile()->create(['bio' => 'More tab test bio']);

        $this->actingAs($user);

        $response = $this->get('/user/moreuser');
        $response->assertStatus(200);
        $response->assertSee('tab-more-btn');
        $response->assertSee('profileMoreTabsDropdown');
        $response->assertSee('moreLink-saved');
        $response->assertSee('moreLink-activity');
        $response->assertSee('moreLink-professional');
        $response->assertSee('moreLink-analytics');
        $response->assertSee('সেকশন পরিচালনা');
    }

    public function test_profile_renders_manage_sections_modal_and_toggles(): void
    {
        $user = User::factory()->create([
            'username' => 'sectionsuser',
            'name' => 'Sections User',
            'status' => 'active',
        ]);
        $user->profile()->create(['bio' => 'Sections test bio']);

        $this->actingAs($user);

        $response = $this->get('/user/sectionsuser');
        $response->assertStatus(200);
        $response->assertSee('manageSectionsModal');
        $response->assertSee('sectionToggle-intro');
        $response->assertSee('sectionToggle-featured');
        $response->assertSee('sectionToggle-photos');
        $response->assertSee('sectionToggle-friends');
        $response->assertSee('sectionToggle-completion');
        $response->assertSee('introBioCard');
        $response->assertSee('sidebarPhotosCard');
        $response->assertSee('sidebarFriendsCard');
    }

    public function test_profile_renders_direct_connect_buttons_in_intro(): void
    {
        $user = User::factory()->create([
            'username' => 'socialuser',
            'name' => 'Social User',
            'status' => 'active',
        ]);
        $user->profile()->create([
            'bio' => 'Social user bio',
            'whatsapp' => '+8801712345678',
            'messenger' => 'jugajuguser',
            'telegram' => '@jugajuguser',
            'portfolio' => 'https://example.com/portfolio',
        ]);

        $response = $this->get('/user/socialuser');
        $response->assertStatus(200);
        $response->assertSee('introConnectSection');
        $response->assertSee('introWaLink');
        $response->assertSee('https://wa.me/8801712345678');
        $response->assertSee('introMessengerLink');
        $response->assertSee('https://m.me/jugajuguser');
        $response->assertSee('introTelegramLink');
        $response->assertSee('https://t.me/jugajuguser');
        $response->assertSee('introPortfolioLink');
    }

    public function test_profile_renders_owner_add_social_prompt_when_empty(): void
    {
        $user = User::factory()->create([
            'username' => 'emptysocialuser',
            'name' => 'Empty Social User',
            'status' => 'active',
        ]);
        $user->profile()->create([
            'bio' => 'Empty bio',
            'whatsapp' => null,
            'messenger' => null,
            'telegram' => null,
            'portfolio' => null,
            'social_links' => [],
        ]);

        $this->actingAs($user);

        $response = $this->get('/user/emptysocialuser');
        $response->assertStatus(200);
        $response->assertSee('+ সোশ্যাল ও যোগাযোগ মাধ্যম যোগ করুন');
    }
}
