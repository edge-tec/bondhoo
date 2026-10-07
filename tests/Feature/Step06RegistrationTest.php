<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\NotificationSetting;
use App\Models\PrivacySetting;
use App\Models\Referral;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Wallet;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Step06RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_registration_blade_view_renders_successfully(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200)
            ->assertSee('নতুন অ্যাকাউন্ট তৈরি করুন')
            ->assertSee('নামের প্রথম অংশ')
            ->assertSee('নামের শেষ অংশ')
            ->assertSee('ইউজারনেম')
            ->assertSee('ইমেইল ঠিকানা')
            ->assertSee('মোবাইল নম্বর')
            ->assertSee('জন্ম তারিখ')
            ->assertSee('নিবন্ধন সম্পন্ন করুন');
    }

    public function test_successful_registration_creates_all_required_entities(): void
    {
        $payload = [
            'first_name' => 'মশিউর',
            'last_name' => 'রহমান',
            'username' => 'moshiur_bd',
            'email' => 'moshiur@example.com',
            'phone' => '+8801799887766',
            'country' => 'BD',
            'birth_date' => '1998-04-15',
            'gender' => 'male',
            'password' => 'StrongPass@2026#',
            'password_confirmation' => 'StrongPass@2026#',
            'terms' => true,
        ];

        $response = $this->postJson('/api/v2/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'requires_verification' => true,
                ],
            ]);

        // 1. Verify User
        $user = User::where('username', 'moshiur_bd')->first();
        $this->assertNotNull($user);
        $this->assertEquals('মশিউর', $user->first_name);
        $this->assertEquals('রহমান', $user->last_name);
        $this->assertEquals('মশিউর রহমান', $user->name);
        $this->assertEquals('moshiur@example.com', $user->email);
        $this->assertEquals('+8801799887766', $user->phone);
        $this->assertEquals('male', $user->gender);
        $this->assertEquals('pending', $user->status);
        $this->assertNotNull($user->terms_accepted_at);

        // 2. Verify Profile
        $profile = UserProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals('মশিউর রহমান', $profile->display_name);
        $this->assertEquals('মশিউর', $profile->first_name);
        $this->assertEquals('রহমান', $profile->last_name);
        $this->assertEquals('male', $profile->gender);

        // 3. Verify Privacy Settings
        $privacy = PrivacySetting::where('user_id', $user->id)->first();
        $this->assertNotNull($privacy);
        $this->assertEquals('public', $privacy->profile_visibility);
        $this->assertEquals('public', $privacy->post_default_privacy);
        $this->assertTrue($privacy->search_engine_indexing);

        // 4. Verify Notification Settings
        $notification = NotificationSetting::where('user_id', $user->id)->first();
        $this->assertNotNull($notification);
        $this->assertTrue($notification->email_notifications);
        $this->assertTrue($notification->push_notifications);
        $this->assertTrue($notification->security_alerts);

        // 5. Verify Wallet
        $wallet = Wallet::where('user_id', $user->id)->first();
        $this->assertNotNull($wallet);
        $this->assertEquals('0.00', $wallet->balance);
        $this->assertEquals('BDT', $wallet->currency);
        $this->assertEquals('active', $wallet->status);

        // 6. Verify Referral
        $referral = Referral::where('user_id', $user->id)->first();
        $this->assertNotNull($referral);
        $this->assertNotEmpty($referral->referral_code);
        $this->assertEquals('active', $referral->status);

        // 7. Verify Audit Log
        $auditLog = AuditLog::where('user_id', $user->id)
            ->where('action', 'auth.registered')
            ->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals(User::class, $auditLog->entity_type);
        $this->assertEquals($user->id, $auditLog->entity_id);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
            'username' => 'existing_user',
            'phone' => '+8801700000001',
        ]);

        $payload = [
            'first_name' => 'নতুন',
            'last_name' => 'ইউজার',
            'username' => 'new_user_1',
            'email' => 'existing@example.com',
            'phone' => '+8801700000002',
            'birth_date' => '2000-01-01',
            'gender' => 'female',
            'password' => 'StrongPass@2026!',
            'password_confirmation' => 'StrongPass@2026!',
            'terms' => true,
        ];

        $response = $this->postJson('/api/v2/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_with_duplicate_username(): void
    {
        User::factory()->create([
            'email' => 'user1@example.com',
            'username' => 'duplicate_user',
            'phone' => '+8801700000011',
        ]);

        $payload = [
            'first_name' => 'নতুন',
            'last_name' => 'ইউজার',
            'username' => 'duplicate_user',
            'email' => 'unique_user@example.com',
            'phone' => '+8801700000012',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'password' => 'StrongPass@2026!',
            'password_confirmation' => 'StrongPass@2026!',
            'terms' => true,
        ];

        $response = $this->postJson('/api/v2/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_registration_fails_with_duplicate_phone(): void
    {
        User::factory()->create([
            'email' => 'phone1@example.com',
            'username' => 'phone_user_1',
            'phone' => '+8801711223344',
        ]);

        $payload = [
            'first_name' => 'নতুন',
            'last_name' => 'ইউজার',
            'username' => 'phone_user_2',
            'email' => 'phone2@example.com',
            'phone' => '+8801711223344',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'password' => 'StrongPass@2026!',
            'password_confirmation' => 'StrongPass@2026!',
            'terms' => true,
        ];

        $response = $this->postJson('/api/v2/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_registration_fails_with_reserved_username(): void
    {
        $payload = [
            'first_name' => 'অ্যাডমিন',
            'last_name' => 'সাহেব',
            'username' => 'admin',
            'email' => 'admin_test@example.com',
            'phone' => '+8801700000099',
            'birth_date' => '1995-01-01',
            'gender' => 'male',
            'password' => 'StrongPass@2026!',
            'password_confirmation' => 'StrongPass@2026!',
            'terms' => true,
        ];

        $response = $this->postJson('/api/v2/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_registration_fails_with_disposable_email(): void
    {
        $payload = [
            'first_name' => 'টেস্ট',
            'last_name' => 'ইউজার',
            'username' => 'throwaway_usr',
            'email' => 'fake_person@mailinator.com',
            'phone' => '+8801700000088',
            'birth_date' => '1995-01-01',
            'gender' => 'male',
            'password' => 'StrongPass@2026!',
            'password_confirmation' => 'StrongPass@2026!',
            'terms' => true,
        ];

        $response = $this->postJson('/api/v2/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_when_user_is_under_13_years_old(): void
    {
        // 10 years old DOB
        $underageDob = now()->subYears(10)->format('Y-m-d');

        $payload = [
            'first_name' => 'শিশু',
            'last_name' => 'ইউজার',
            'username' => 'underage_kid',
            'email' => 'kid@example.com',
            'phone' => '+8801700000077',
            'birth_date' => $underageDob,
            'gender' => 'other',
            'password' => 'StrongPass@2026!',
            'password_confirmation' => 'StrongPass@2026!',
            'terms' => true,
        ];

        $response = $this->postJson('/api/v2/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['birth_date']);
    }

    public function test_registration_fails_when_password_does_not_meet_policy(): void
    {
        $payload = [
            'first_name' => 'টেস্ট',
            'last_name' => 'ইউজার',
            'username' => 'weak_pass_user',
            'email' => 'weak@example.com',
            'phone' => '+8801700000066',
            'birth_date' => '1996-01-01',
            'gender' => 'male',
            'password' => 'weak',
            'password_confirmation' => 'weak',
            'terms' => true,
        ];

        $response = $this->postJson('/api/v2/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_fails_when_terms_are_not_accepted(): void
    {
        $payload = [
            'first_name' => 'টেস্ট',
            'last_name' => 'ইউজার',
            'username' => 'noterms_user',
            'email' => 'noterms@example.com',
            'phone' => '+8801700000055',
            'birth_date' => '1996-01-01',
            'gender' => 'male',
            'password' => 'StrongPass@2026!',
            'password_confirmation' => 'StrongPass@2026!',
            'terms' => false,
        ];

        $response = $this->postJson('/api/v2/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['terms']);
    }

    public function test_registration_with_referral_links_referrer_and_creates_referral(): void
    {
        $referrer = User::factory()->create([
            'username' => 'top_influencer',
            'email' => 'influencer@example.com',
            'phone' => '+8801700000044',
        ]);

        $payload = [
            'first_name' => 'রেফার্ড',
            'last_name' => 'ইউজার',
            'username' => 'referred_person',
            'email' => 'referred@example.com',
            'phone' => '+8801700000033',
            'birth_date' => '1997-03-20',
            'gender' => 'female',
            'password' => 'StrongPass@2026!',
            'password_confirmation' => 'StrongPass@2026!',
            'referred_by' => 'top_influencer',
            'terms' => true,
        ];

        $response = $this->postJson('/api/v2/auth/register', $payload);

        $response->assertStatus(201);

        $newUser = User::where('username', 'referred_person')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('top_influencer', $newUser->referred_by);

        $referral = Referral::where('user_id', $newUser->id)->first();
        $this->assertNotNull($referral);
        $this->assertEquals($referrer->id, $referral->referrer_id);
    }
}
