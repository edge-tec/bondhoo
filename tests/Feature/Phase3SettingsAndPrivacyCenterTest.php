<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase3SettingsAndPrivacyCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['label' => 'Super Admin', 'description' => 'Full access']);
        Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Admin', 'description' => 'Staff administrator']);
        Role::firstOrCreate(['name' => 'USER'], ['label' => 'Regular User', 'description' => 'Platform user']);
        Role::firstOrCreate(['name' => 'VERIFIED_USER'], ['label' => 'Verified User', 'description' => 'Identity verified citizen']);
    }

    protected function createUser(array $attributes = []): User
    {
        static $counter = 800;
        $counter++;

        $user = User::factory()->create(array_merge([
            'username' => "phase3_user_{$counter}",
            'name' => "Phase3 User {$counter}",
            'email' => "phase3_user_{$counter}@example.com",
            'phone' => "+880172200{$counter}",
            'password' => Hash::make('CurrentPassword123!'),
            'status' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ], $attributes));

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => 'Phase 3 Settings bio',
                'about' => 'Phase 3 Settings about',
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'who_can_see_posts' => 'public',
                'who_can_send_friend_requests' => 'everyone',
                'is_profile_locked' => false,
            ]
        );

        return $user;
    }

    /**
     * Test web hub /settings and tabs render properly.
     */
    public function test_settings_hub_web_page_renders_successfully(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'web')->get('/settings');
        $response->assertStatus(200);
        $response->assertSee('সেটিংস ও নিয়ন্ত্রণ');
        $response->assertSee('অ্যাকাউন্ট সাধারণ তথ্য');

        // Test with tab query parameter
        $tabResponse = $this->actingAs($user, 'web')->get('/settings?tab=privacy');
        $tabResponse->assertStatus(200);
        $tabResponse->assertSee('প্রাইভেসি ও অডিয়েন্স সেটিংস');
    }

    /**
     * Test legal compliance views: /terms and /cookies.
     */
    public function test_legal_pages_terms_and_cookies_render_successfully(): void
    {
        $termsRes = $this->get('/terms');
        $termsRes->assertStatus(200);
        $termsRes->assertSee('ব্যবহারের শর্তাবলী (Terms of Service)');

        $cookiesRes = $this->get('/cookies');
        $cookiesRes->assertStatus(200);
        $cookiesRes->assertSee('কুকিজ ও ডেটা পলিসি', false);
        $cookiesRes->assertSee('Cookie & Data Policy', false);
    }

    /**
     * Test overview API endpoint returns comprehensive status.
     */
    public function test_settings_overview_api_returns_complete_profile_and_privacy_data(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $res = $this->getJson('/api/v1/settings/overview');
        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.account.id', $user->id)
            ->assertJsonPath('data.account.name', $user->name)
            ->assertJsonPath('data.account.username', $user->username)
            ->assertJsonPath('data.security.two_factor_enabled', false)
            ->assertJsonPath('data.blocking.blocked_count', 0);
    }

    /**
     * Test account information retrieval and updating.
     */
    public function test_account_settings_can_be_retrieved_and_updated(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        // 1. Get account
        $getRes = $this->getJson('/api/v1/settings/account');
        $getRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', $user->name);

        // 2. Update account
        $updateRes = $this->putJson('/api/v1/settings/account', [
            'name' => 'Updated Setting Name',
            'username' => 'updated_setting_nick',
            'phone' => '+8801999888777',
            'gender' => 'male',
            'birth_date' => '1996-05-15',
            'bio' => 'My updated professional bio for testing',
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Setting Name')
            ->assertJsonPath('data.username', 'updated_setting_nick');

        $user->refresh();
        $this->assertSame('Updated Setting Name', $user->name);
        $this->assertSame('updated_setting_nick', $user->username);
        $this->assertSame('My updated professional bio for testing', $user->profile->bio);
    }

    /**
     * Test password update validation and successful modification.
     */
    public function test_password_update_validates_current_password_and_changes_password(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        // Invalid current password should fail with 422
        $failRes = $this->putJson('/api/v1/settings/password', [
            'current_password' => 'WrongPassword999!',
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);
        $failRes->assertStatus(422)
            ->assertJsonPath('success', false);

        // Correct current password succeeds
        $successRes = $this->putJson('/api/v1/settings/password', [
            'current_password' => 'CurrentPassword123!',
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);
        $successRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $user->refresh();
        $this->assertTrue(Hash::check('BrandNewPassword123!', $user->password));
    }

    /**
     * Test privacy settings retrieval and update.
     */
    public function test_privacy_settings_can_be_retrieved_and_updated(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $getRes = $this->getJson('/api/v1/settings/privacy');
        $getRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $putRes = $this->putJson('/api/v1/settings/privacy', [
            'who_can_see_posts' => 'friends',
            'who_can_send_friend_requests' => 'friends_of_friends',
            'who_can_see_friends' => 'only_me',
            'is_profile_locked' => true,
            'has_avatar_guard' => true,
            'show_online_status' => false,
            'search_engine_indexing' => false,
            'notification_email' => true,
            'notification_push' => false,
        ]);

        $putRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.who_can_see_posts', 'friends')
            ->assertJsonPath('data.who_can_send_friend_requests', 'friends_of_friends')
            ->assertJsonPath('data.is_profile_locked', true)
            ->assertJsonPath('data.has_avatar_guard', true)
            ->assertJsonPath('data.show_online_status', false);

        $user->settings->refresh();
        $this->assertSame('friends', $user->settings->who_can_see_posts);
        $this->assertTrue($user->settings->is_profile_locked);
    }

    /**
     * Test 2FA quick toggle.
     */
    public function test_two_factor_quick_toggle_endpoint(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        // Enable 2FA
        $enableRes = $this->postJson('/api/v1/settings/2fa/toggle', [
            'enabled' => true,
        ]);
        $enableRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.two_factor_enabled', true);

        $user->refresh();
        $this->assertTrue((bool) $user->two_factor_enabled);

        // Disable 2FA
        $disableRes = $this->postJson('/api/v1/settings/2fa/toggle', [
            'enabled' => false,
        ]);
        $disableRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.two_factor_enabled', false);

        $user->refresh();
        $this->assertFalse((bool) $user->two_factor_enabled);
    }

    /**
     * Test user blocking and unblocking flow.
     */
    public function test_user_blocking_and_unblocking_flow(): void
    {
        $user = $this->createUser();
        $targetUser = $this->createUser();

        Sanctum::actingAs($user);

        // Block target user
        $blockRes = $this->postJson("/api/v1/settings/blocking/{$targetUser->id}");
        $blockRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $targetUser->id);

        // List blocked users
        $listRes = $this->getJson('/api/v1/settings/blocking');
        $listRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.blocked_users');

        $this->assertSame($targetUser->id, $listRes->json('data.blocked_users.0.id'));

        // Unblock target user
        $unblockRes = $this->deleteJson("/api/v1/settings/blocking/{$targetUser->id}");
        $unblockRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify block list is empty
        $afterListRes = $this->getJson('/api/v1/settings/blocking');
        $afterListRes->assertStatus(200)
            ->assertJsonCount(0, 'data.blocked_users');
    }
}
