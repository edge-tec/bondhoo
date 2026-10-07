<?php

namespace Tests\Feature;

use App\Events\ProfileUpdatedEvent;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Step08BioAndAboutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Test user can retrieve own profile via GET profile and GET profile/about.
     */
    public function test_user_can_view_own_profile_via_get_profile_and_about_endpoints(): void
    {
        $user = User::factory()->create([
            'username' => 'rakibdev',
            'name' => 'Rakib Hasan',
        ]);

        $user->profile()->create([
            'bio' => 'Building social tech for everyone.',
            'headline' => 'Lead Software Architect',
            'about' => '<p>Passionate about distributed systems, privacy, and scalable web apps.</p>',
        ]);

        // 1. GET /api/v2/profile
        $responseV2 = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/profile');

        $responseV2->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.profile.bio', 'Building social tech for everyone.')
            ->assertJsonPath('data.profile.headline', 'Lead Software Architect')
            ->assertJsonPath('data.profile.about', '<p>Passionate about distributed systems, privacy, and scalable web apps.</p>');

        // 2. GET /api/v2/profile/about
        $responseAboutV2 = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/profile/about');

        $responseAboutV2->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.bio', 'Building social tech for everyone.')
            ->assertJsonPath('data.headline', 'Lead Software Architect')
            ->assertJsonPath('data.about', '<p>Passionate about distributed systems, privacy, and scalable web apps.</p>');

        // 3. GET /api/v1/profile
        $responseV1 = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile');

        $responseV1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.profile.bio', 'Building social tech for everyone.');

        // 4. GET /api/v1/profile/about
        $responseAboutV1 = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile/about');

        $responseAboutV1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.bio', 'Building social tech for everyone.');
    }

    /**
     * Test user can update bio, about, and headline with valid data.
     */
    public function test_user_can_update_bio_about_and_headline(): void
    {
        Event::fake([ProfileUpdatedEvent::class]);

        $user = User::factory()->create(['username' => 'designer101']);

        $payload = [
            'bio' => 'Senior UI/UX Designer & Accessibility Advocate.',
            'headline' => 'Design Systems Lead @ Jugajug',
            'about' => '<p>Crafting accessible, human-centric user experiences across web and mobile platforms.</p>',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/about', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.bio', 'Senior UI/UX Designer & Accessibility Advocate.')
            ->assertJsonPath('data.headline', 'Design Systems Lead @ Jugajug')
            ->assertJsonPath('data.about', '<p>Crafting accessible, human-centric user experiences across web and mobile platforms.</p>');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'Senior UI/UX Designer & Accessibility Advocate.',
            'headline' => 'Design Systems Lead @ Jugajug',
        ]);

        Event::assertDispatched(ProfileUpdatedEvent::class, function ($event) use ($user) {
            return $event->user->id === $user->id && in_array('about', $event->updatedSections, true);
        });
    }

    /**
     * Test bio maximum length validation (max: 255 characters).
     */
    public function test_bio_character_limit_exceeded_fails_validation(): void
    {
        $user = User::factory()->create();

        // 256 characters long bio
        $longBio = str_repeat('A', 256);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/about', [
                'bio' => $longBio,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bio']);
    }

    /**
     * Test about maximum length validation (max: 5000 characters).
     */
    public function test_about_character_limit_exceeded_fails_validation(): void
    {
        $user = User::factory()->create();

        // 5001 characters long about text
        $longAbout = str_repeat('X', 5001);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/about', [
                'about' => $longAbout,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['about']);
    }

    /**
     * Test headline maximum length validation (max: 191 characters).
     */
    public function test_headline_character_limit_exceeded_fails_validation(): void
    {
        $user = User::factory()->create();

        // 192 characters long headline
        $longHeadline = str_repeat('H', 192);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/about', [
                'headline' => $longHeadline,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['headline']);
    }

    /**
     * Test empty and null values safely clear fields.
     */
    public function test_empty_and_null_values_clear_fields(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'bio' => 'Existing bio to be cleared',
            'headline' => 'Existing headline',
            'about' => 'Existing about to be cleared',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/about', [
                'bio' => '',
                'headline' => null,
                'about' => '',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.bio', null)
            ->assertJsonPath('data.headline', null)
            ->assertJsonPath('data.about', null);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => null,
            'headline' => null,
            'about' => null,
        ]);
    }

    /**
     * Test XSS payloads, script tags, event handlers, and malicious URLs are stripped and neutralized.
     */
    public function test_xss_and_javascript_injection_payloads_are_sanitized(): void
    {
        $user = User::factory()->create();

        $xssBio = '<script>alert("XSS Bio")</script>Normal bio text <img src=x onerror=alert(1)>';
        $xssHeadline = 'Lead Dev <script>window.location="http://evil.com"</script>';
        $xssAbout = '<p>Normal text <b>safe bold</b> <script>alert("XSS About")</script><img src="x" onerror="stealCookies()"><a href="javascript:alert(document.cookie)">Malicious Link</a> <a href="https://jugajug.com">Safe Link</a></p>';

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/about', [
                'bio' => $xssBio,
                'headline' => $xssHeadline,
                'about' => $xssAbout,
            ]);

        $response->assertOk();

        $profile = UserProfile::where('user_id', $user->id)->first();

        // 1. Verify Bio: plain text only, no tags or onerror
        $this->assertStringNotContainsString('<script>', $profile->bio);
        $this->assertStringNotContainsString('alert', $profile->bio);
        $this->assertStringNotContainsString('<img', $profile->bio);
        $this->assertStringNotContainsString('onerror', $profile->bio);
        $this->assertStringContainsString('Normal bio text', $profile->bio);

        // 2. Verify Headline: no script tag or evil domain
        $this->assertStringNotContainsString('<script>', $profile->headline);
        $this->assertStringNotContainsString('evil.com', $profile->headline);
        $this->assertStringContainsString('Lead Dev', $profile->headline);

        // 3. Verify About: script removed, onerror removed, javascript: neutralized, safe tags kept
        $this->assertStringNotContainsString('<script>', $profile->about);
        $this->assertStringNotContainsString('stealCookies', $profile->about);
        $this->assertStringNotContainsString('javascript:', $profile->about);
        $this->assertStringContainsString('<b>safe bold</b>', $profile->about);
        $this->assertStringContainsString('https://jugajug.com', $profile->about);
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $profile->about);
    }

    /**
     * Test Unicode and Bangla text are correctly saved and retrieved without corruption.
     */
    public function test_unicode_and_bangla_text_saved_and_retrieved_correctly(): void
    {
        $user = User::factory()->create(['username' => 'banglauser']);

        $banglaBio = 'যোগাযোগ সামাজিক যোগাযোগ প্ল্যাটফর্মের একজন নিয়মিত ব্যবহারকারী।';
        $banglaHeadline = 'সফটওয়্যার প্রকৌশলী ও প্রযুক্তি গবেষক';
        $banglaAbout = '<p>আমি বাংলাদেশের উন্মুক্ত সামাজিক যোগাযোগ ব্যবস্থা গড়ে তোলার কাজে নিয়োজিত। আমাদের লক্ষ্য একটি নিরাপদ এবং নির্ভরযোগ্য কমিউনিটি গঠন করা।</p>';

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/about', [
                'bio' => $banglaBio,
                'headline' => $banglaHeadline,
                'about' => $banglaAbout,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.bio', $banglaBio)
            ->assertJsonPath('data.headline', $banglaHeadline)
            ->assertJsonPath('data.about', $banglaAbout);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => $banglaBio,
            'headline' => $banglaHeadline,
        ]);
    }

    /**
     * Test Emoji characters (utf8mb4) are preserved and retrieved correctly.
     */
    public function test_emoji_support_saved_and_retrieved_correctly(): void
    {
        $user = User::factory()->create(['username' => 'emojilover']);

        $emojiBio = 'Hello World 🇧🇩 🚀 ✨ ❤️ 💻 🌟';
        $emojiHeadline = 'AI Researcher 🤖 ⚡ 🧠';
        $emojiAbout = '<p>Building future platforms with love and passion ❤️ 🌐 🚀 🇧🇩</p>';

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/about', [
                'bio' => $emojiBio,
                'headline' => $emojiHeadline,
                'about' => $emojiAbout,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.bio', $emojiBio)
            ->assertJsonPath('data.headline', $emojiHeadline)
            ->assertJsonPath('data.about', $emojiAbout);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => $emojiBio,
            'headline' => $emojiHeadline,
        ]);
    }

    /**
     * Test Redis cache invalidation upon updating bio and about.
     */
    public function test_cache_is_invalidated_upon_update(): void
    {
        $user = User::factory()->create(['username' => 'cachetester']);
        $username = strtolower($user->username);

        // Pre-populate cache keys
        Cache::put("profile:public:{$username}", ['cached' => true], 3600);
        Cache::put("profile:about:{$username}", ['cached' => true], 3600);
        Cache::put("profile:v2:{$username}", ['cached' => true], 3600);
        Cache::put("user_profile:{$username}", ['cached' => true], 3600);

        $this->assertTrue(Cache::has("profile:public:{$username}"));
        $this->assertTrue(Cache::has("profile:about:{$username}"));

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/about', [
                'bio' => 'Fresh newly updated bio for cache test',
            ])
            ->assertOk();

        // Assert caches are cleared
        $this->assertFalse(Cache::has("profile:public:{$username}"));
        $this->assertFalse(Cache::has("profile:about:{$username}"));
        $this->assertFalse(Cache::has("profile:v2:{$username}"));
        $this->assertFalse(Cache::has("user_profile:{$username}"));
    }

    /**
     * Test audit log is properly recorded with old and new values.
     */
    public function test_audit_log_is_recorded_with_old_and_new_values(): void
    {
        $user = User::factory()->create(['username' => 'audittester']);
        $user->profile()->create([
            'bio' => 'Old Bio',
            'headline' => 'Old Headline',
            'about' => 'Old About',
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/about', [
                'bio' => 'Updated Brand New Bio',
                'headline' => 'Updated Headline',
                'about' => '<p>Updated About</p>',
            ])
            ->assertOk();

        $log = AuditLog::where('user_id', $user->id)
            ->where('action', 'BIO_ABOUT_UPDATED')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Old Bio', $log->old_values['bio']);
        $this->assertEquals('Old Headline', $log->old_values['headline']);
        $this->assertEquals('Old About', $log->old_values['about']);
        $this->assertEquals('Updated Brand New Bio', $log->new_values['bio']);
        $this->assertEquals('Updated Headline', $log->new_values['headline']);
        $this->assertEquals('<p>Updated About</p>', $log->new_values['about']);
        $this->assertContains('bio', $log->new_values['updated_fields']);
        $this->assertContains('headline', $log->new_values['updated_fields']);
        $this->assertContains('about', $log->new_values['updated_fields']);
    }

    /**
     * Test v1 profile update about endpoint works as expected.
     */
    public function test_v1_profile_update_about_endpoint_works(): void
    {
        $user = User::factory()->create(['username' => 'v1tester']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/about', [
                'bio' => 'Updated through API v1',
                'about' => '<p>About through API v1</p>',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.bio', 'Updated through API v1')
            ->assertJsonPath('data.about', '<p>About through API v1</p>');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'Updated through API v1',
        ]);
    }
}
