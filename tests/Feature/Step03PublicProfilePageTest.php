<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\ProfileEducation;
use App\Models\ProfileExperience;
use App\Models\ProfileInterest;
use App\Models\ProfileLanguage;
use App\Models\ProfileSkill;
use App\Models\ProfileSocialLink;
use App\Models\ProfileView;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Step03PublicProfilePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_can_view_public_profile_via_at_username_route(): void
    {
        $user = User::factory()->create([
            'name' => 'Tariq Islam',
            'username' => 'tariqislam',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'bio' => 'Open source contributor & Laravel lover',
            'work' => 'Lead Engineer',
            'education' => 'DU CSE',
            'location' => 'Dhaka',
            'website' => 'https://tariq.dev',
        ]);

        ProfileSkill::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
            'level' => 'expert',
        ]);

        ProfileSocialLink::create([
            'user_id' => $user->id,
            'platform' => 'github',
            'url' => 'https://github.com/tariq',
            'is_visible' => true,
        ]);

        // Access via /@username
        $response = $this->get('/@tariqislam');
        $response->assertStatus(200);
        $response->assertSee('Tariq Islam');
        $response->assertSee('@tariqislam');
        $response->assertSee('Open source contributor & Laravel lover');
        $response->assertSee('Laravel');
        $response->assertSee('https://github.com/tariq');
    }

    public function test_can_view_public_profile_via_user_username_route(): void
    {
        $user = User::factory()->create([
            'name' => 'Anisul Hoque',
            'username' => 'anisul',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'bio' => 'Writer & journalist',
        ]);

        $response = $this->get('/user/anisul');
        $response->assertStatus(200);
        $response->assertSee('Anisul Hoque');
        $response->assertSee('@anisul');
    }

    public function test_public_profile_page_tracks_views_for_non_owners(): void
    {
        $target = User::factory()->create(['username' => 'author1']);
        $viewer = User::factory()->create(['username' => 'reader1']);

        // Guest view
        $this->get('/@author1');
        $this->assertDatabaseHas('profile_views', [
            'user_id' => $target->id,
            'viewer_id' => null,
        ]);

        // Authenticated non-owner view
        $this->actingAs($viewer)->get('/@author1');
        $this->assertDatabaseHas('profile_views', [
            'user_id' => $target->id,
            'viewer_id' => $viewer->id,
        ]);

        $initialCount = ProfileView::where('user_id', $target->id)->count();

        // Own view should not increase profile views count
        $this->actingAs($target)->get('/@author1');
        $this->assertEquals($initialCount, ProfileView::where('user_id', $target->id)->count());
    }

    public function test_public_profile_api_returns_all_23_components_and_sections(): void
    {
        $user = User::factory()->create([
            'name' => 'Karim Ahmed',
            'username' => 'karimahmed',
            'email_verified_at' => now(),
        ]);

        $profile = UserProfile::create([
            'user_id' => $user->id,
            'bio' => 'Tech enthusiast and educator.',
            'about' => 'More detailed about me section describing passions and background.',
            'location' => 'Chittagong',
            'website' => 'https://karim.me',
            'avatar_url' => 'https://cdn.jugajug.com/avatar.jpg',
            'cover_url' => 'https://cdn.jugajug.com/cover.jpg',
        ]);

        ProfileEducation::create([
            'user_id' => $user->id,
            'institution_name' => 'Chittagong University',
            'degree' => 'B.Sc.',
            'field_of_study' => 'Physics',
            'privacy' => 'public',
        ]);

        ProfileExperience::create([
            'user_id' => $user->id,
            'company_name' => 'Data Soft',
            'job_title' => 'Software Engineer',
            'privacy' => 'public',
        ]);

        ProfileSkill::create([
            'user_id' => $user->id,
            'name' => 'Python',
            'level' => 'expert',
        ]);

        ProfileInterest::create([
            'user_id' => $user->id,
            'name' => 'Robotics',
            'category' => 'Technology',
        ]);

        ProfileLanguage::create([
            'user_id' => $user->id,
            'language' => 'English',
            'proficiency' => 'fluent',
        ]);

        ProfileSocialLink::create([
            'user_id' => $user->id,
            'platform' => 'linkedin',
            'url' => 'https://linkedin.com/in/karim',
            'is_visible' => true,
        ]);

        Post::create([
            'user_id' => $user->id,
            'content' => 'First public post by Karim',
            'audience' => 'public',
        ]);

        Media::create([
            'user_id' => $user->id,
            'collection' => 'general',
            'disk' => 'public',
            'original_path' => 'media/sample.mp4',
            'mime_type' => 'video/mp4',
            'size' => 5242880,
            'processing_status' => 'ready',
        ]);

        $response = $this->getJson('/api/v2/profile/karimahmed');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'karimahmed',
                        'name' => 'Karim Ahmed',
                    ],
                    'profile' => [
                        'bio' => 'Tech enthusiast and educator.',
                        'about' => 'More detailed about me section describing passions and background.',
                        'location' => 'Chittagong',
                        'website' => 'https://karim.me',
                    ],
                    'sections' => [
                        'educations' => [
                            ['institution_name' => 'Chittagong University'],
                        ],
                        'experiences' => [
                            ['company_name' => 'Data Soft'],
                        ],
                        'skills' => [
                            ['name' => 'Python'],
                        ],
                        'interests' => [
                            ['name' => 'Robotics'],
                        ],
                        'languages' => [
                            ['language' => 'English'],
                        ],
                        'social_links' => [
                            ['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/karim'],
                        ],
                    ],
                ],
            ]);

        // Check videos endpoint
        $videosRes = $this->getJson('/api/v2/profile/karimahmed/videos');
        $videosRes->assertStatus(200);
        $this->assertCount(1, $videosRes->json('data'));

        // Check about endpoint
        $aboutRes = $this->getJson('/api/v2/profile/karimahmed/about');
        $aboutRes->assertStatus(200);
        $this->assertEquals('More detailed about me section describing passions and background.', $aboutRes->json('data.overview.about'));
    }

    public function test_locked_profile_enforces_server_side_privacy(): void
    {
        $target = User::factory()->create(['username' => 'secretive']);
        UserSetting::create([
            'user_id' => $target->id,
            'is_profile_locked' => true,
            'who_can_see_friends' => 'only_me',
        ]);

        ProfileEducation::create([
            'user_id' => $target->id,
            'institution_name' => 'Secret University',
            'privacy' => 'friends',
        ]);

        ProfileExperience::create([
            'user_id' => $target->id,
            'company_name' => 'Secret Intelligence',
            'job_title' => 'Agent',
            'privacy' => 'only_me',
        ]);

        $stranger = User::factory()->create(['username' => 'curious']);

        // Stranger requesting profile API
        $response = $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/v2/profile/secretive');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertTrue($data['is_locked_view']);
        $this->assertEmpty($data['sections']['educations']);
        $this->assertEmpty($data['sections']['experiences']);
        $this->assertNull($data['profile']['about']);
        $this->assertNull($data['profile']['work']);
        $this->assertNull($data['profile']['education']);
    }

    public function test_cache_is_invalidated_when_profile_updates(): void
    {
        $user = User::factory()->create(['username' => 'cacheuser']);
        $token = $user->createToken('test')->plainTextToken;

        UserProfile::create([
            'user_id' => $user->id,
            'bio' => 'Initial bio',
        ]);

        // Warm cache
        Cache::put('profile:public:cacheuser', ['cached' => true], 3600);
        $this->assertTrue(Cache::has('profile:public:cacheuser'));

        // Update profile
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v2/profile', [
                'bio' => 'New bio after update',
            ]);

        $response->assertStatus(200);

        // Cache should be invalidated
        $this->assertFalse(Cache::has('profile:public:cacheuser'));
    }
}
