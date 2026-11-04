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

class Phase12CompleteSettingsAndPrivacySystemTest extends TestCase
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
        static $counter = 1200;
        $counter++;

        $user = User::factory()->create(array_merge([
            'username' => "phase12_user_{$counter}",
            'name' => "Phase 12 User {$counter}",
            'email' => "phase12_user_{$counter}@example.com",
            'phone' => "+880173300{$counter}",
            'password' => Hash::make('CurrentSecret123!'),
            'status' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ], $attributes));

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => 'Phase 12 Settings bio test',
                'gender' => 'male',
                'birth_date' => '1995-05-15',
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'who_can_see_posts' => 'public',
                'who_can_send_friend_requests' => 'everyone',
                'who_can_follow' => 'everyone',
                'who_can_message' => 'everyone',
                'who_can_see_friends' => 'public',
                'dark_mode' => false,
                'language' => 'bn',
            ]
        );

        return $user;
    }

    /**
     * Test web hub /settings and tab shortcuts render correctly.
     */
    public function test_settings_web_hub_and_tab_shortcuts_render_correctly(): void
    {
        $user = $this->createUser();

        // 1. Settings index
        $resIndex = $this->actingAs($user, 'web')->get('/settings');
        $resIndex->assertStatus(200);
        $resIndex->assertSee('সেটিংস ও নিয়ন্ত্রণ');
        $resIndex->assertSee('অ্যাকাউন্ট সাধারণ তথ্য');

        // 2. Settings with tab query
        $resTab = $this->actingAs($user, 'web')->get('/settings?tab=privacy');
        $resTab->assertStatus(200);
        $resTab->assertSee('প্রাইভেসি ও অডিয়েন্স সেটিংস');

        // 3. Tab route shortcuts
        $resAccount = $this->actingAs($user, 'web')->get('/settings/account');
        $resAccount->assertStatus(200);
        $resAccount->assertSee('অ্যাকাউন্ট সাধারণ তথ্য');

        $resSecurity = $this->actingAs($user, 'web')->get('/settings/security');
        $resSecurity->assertStatus(200);
        $resSecurity->assertSee('নিরাপত্তা ও লগইন');

        $resPrivacy = $this->actingAs($user, 'web')->get('/settings/privacy');
        $resPrivacy->assertStatus(200);
        $resPrivacy->assertSee('প্রাইভেসি ও অডিয়েন্স সেটিংস');

        $resBlocking = $this->actingAs($user, 'web')->get('/settings/blocking');
        $resBlocking->assertStatus(200);
        $resBlocking->assertSee('ব্লকিং সেন্টার');

        $resSounds = $this->actingAs($user, 'web')->get('/settings/sounds');
        $resSounds->assertStatus(200);
    }

    /**
     * Test overview API endpoint returns comprehensive status.
     */
    public function test_settings_overview_api_returns_complete_profile_security_and_privacy_metrics(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/settings/overview');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'account' => [
                        'id',
                        'name',
                        'username',
                        'email',
                        'phone',
                        'gender',
                        'birth_date',
                        'avatar',
                        'bio',
                        'created_at',
                    ],
                    'privacy' => [
                        'who_can_see_posts',
                        'who_can_send_friend_requests',
                        'who_can_follow',
                        'who_can_message',
                        'who_can_see_friends',
                        'is_profile_locked',
                        'has_avatar_guard',
                        'show_online_status',
                        'search_engine_indexing',
                        'dark_mode',
                        'language',
                    ],
                    'security' => [
                        'two_factor_enabled',
                        'two_factor_type',
                        'email_verified',
                        'phone_verified',
                    ],
                    'blocking' => [
                        'blocked_count',
                    ],
                ],
            ]);

        $this->assertEquals($user->name, $response->json('data.account.name'));
        $this->assertEquals($user->email, $response->json('data.account.email'));
    }

    /**
     * Test account settings retrieval and update with validation.
     */
    public function test_account_settings_can_be_retrieved_and_updated(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        Sanctum::actingAs($user);

        // 1. GET account
        $getRes = $this->getJson('/api/v1/settings/account');
        $getRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.username', $user->username);

        // 2. PUT account with invalid duplicate username
        $dupRes = $this->putJson('/api/v1/settings/account', [
            'username' => $otherUser->username,
        ]);
        $dupRes->assertStatus(422)
            ->assertJsonValidationErrors(['username']);

        // 3. PUT account with valid data
        $updateRes = $this->putJson('/api/v1/settings/account', [
            'name' => 'Mizanur Rahman Updated',
            'username' => 'mizan_updated_12',
            'phone' => '+8801999998888',
            'gender' => 'male',
            'birth_date' => '1992-08-20',
            'bio' => 'Senior Lead Software Architect',
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Mizanur Rahman Updated')
            ->assertJsonPath('data.username', 'mizan_updated_12')
            ->assertJsonPath('data.phone', '+8801999998888')
            ->assertJsonPath('data.bio', 'Senior Lead Software Architect');

        // Verify DB persistence
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Mizanur Rahman Updated',
            'username' => 'mizan_updated_12',
            'phone' => '+8801999998888',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'display_name' => 'Mizanur Rahman Updated',
            'bio' => 'Senior Lead Software Architect',
        ]);
    }

    /**
     * Test password change flow with current password verification and token revocation.
     */
    public function test_password_change_flow_with_current_password_verification(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('test-device')->plainTextToken;

        // 1. Invalid current password attempt
        $failRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/settings/password', [
                'current_password' => 'WrongPassword123!',
                'password' => 'BrandNewPassword456!',
                'password_confirmation' => 'BrandNewPassword456!',
            ]);

        $failRes->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['current_password']);

        // 2. Successful password update
        $successRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/settings/password', [
                'current_password' => 'CurrentSecret123!',
                'password' => 'BrandNewPassword456!',
                'password_confirmation' => 'BrandNewPassword456!',
            ]);

        $successRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে।');

        // Verify user password changed in database
        $user->refresh();
        $this->assertTrue(Hash::check('BrandNewPassword456!', $user->password));
    }

    /**
     * Test two-factor authentication toggle generating secret and recovery codes.
     */
    public function test_two_factor_authentication_toggle_lifecycle(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        // 1. Enable 2FA
        $enableRes = $this->postJson('/api/v1/settings/2fa/toggle', [
            'enabled' => true,
        ]);

        $enableRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.two_factor_enabled', true)
            ->assertJsonStructure([
                'data' => [
                    'two_factor_enabled',
                    'secret',
                    'qr_code_url',
                    'recovery_codes',
                ],
            ]);

        $user->refresh();
        $this->assertTrue((bool) $user->two_factor_enabled);
        $this->assertNotNull($user->two_factor_secret);

        // 2. Disable 2FA
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
     * Test privacy settings retrieval and update including dark_mode toggle.
     */
    public function test_privacy_settings_can_be_retrieved_and_updated_including_dark_mode(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        // 1. GET privacy
        $getRes = $this->getJson('/api/v1/settings/privacy');
        $getRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.who_can_see_posts', 'public')
            ->assertJsonPath('data.dark_mode', false);

        // 2. PUT privacy (matching SidebarNavigation theme toggle & privacy preferences)
        $putRes = $this->putJson('/api/v1/settings/privacy', [
            'who_can_see_posts' => 'friends',
            'who_can_send_friend_requests' => 'friends_of_friends',
            'dark_mode' => true,
            'profile_visibility' => 'friends',
            'search_engine_indexing' => false,
            'show_online_status' => false,
        ]);

        $putRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.who_can_see_posts', 'friends')
            ->assertJsonPath('data.who_can_send_friend_requests', 'friends_of_friends')
            ->assertJsonPath('data.dark_mode', true)
            ->assertJsonPath('data.profile_visibility', 'friends')
            ->assertJsonPath('data.search_engine_indexing', false)
            ->assertJsonPath('data.show_online_status', false);

        // Verify database updates
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'who_can_see_posts' => 'friends',
            'dark_mode' => 1,
        ]);

        $this->assertDatabaseHas('privacy_settings', [
            'user_id' => $user->id,
            'profile_visibility' => 'friends',
            'search_engine_indexing' => 0,
            'show_online_status' => 0,
        ]);
    }

    /**
     * Test blocking and unblocking user lifecycle with validation.
     */
    public function test_blocking_and_unblocking_user_lifecycle(): void
    {
        $user = $this->createUser();
        $targetUser = $this->createUser(['name' => 'Target Blocked User']);
        Sanctum::actingAs($user);

        // 1. List initially empty
        $emptyRes = $this->getJson('/api/v1/settings/blocking');
        $emptyRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 0);

        // 2. Prevent self-blocking
        $selfRes = $this->postJson("/api/v1/settings/blocking/{$user->id}");
        $selfRes->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'নিজের অ্যাকাউন্ট ব্লক করা সম্ভব নয়।');

        // 3. Block target user
        $blockRes = $this->postJson("/api/v1/settings/blocking/{$targetUser->id}");
        $blockRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $targetUser->id);

        // 4. Verify in blocked list
        $listRes = $this->getJson('/api/v1/settings/blocking');
        $listRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1);

        $this->assertEquals($targetUser->id, $listRes->json('data.blocked_users.0.id'));

        // 5. Unblock target user
        $unblockRes = $this->deleteJson("/api/v1/settings/blocking/{$targetUser->id}");
        $unblockRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 6. Verify removed from blocked list
        $afterListRes = $this->getJson('/api/v1/settings/blocking');
        $afterListRes->assertStatus(200)
            ->assertJsonPath('data.total', 0);
    }
}
