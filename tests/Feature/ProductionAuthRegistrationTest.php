<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionAuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_register_with_all_production_fields(): void
    {
        $payload = [
            'name' => 'Tariqul Islam',
            'username' => 'tariqul_bd',
            'email' => 'tariqul@jugajug.com',
            'phone' => '+8801712345678',
            'country' => 'BD',
            'birth_date' => '1995-05-12',
            'gender' => 'male',
            'password' => 'StrongPass@2026!',
            'password_confirmation' => 'StrongPass@2026!',
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

        $this->assertDatabaseHas('users', [
            'username' => 'tariqul_bd',
            'email' => 'tariqul@jugajug.com',
            'phone' => '+8801712345678',
            'country' => 'BD',
            'gender' => 'male',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'display_name' => 'Tariqul Islam',
            'gender' => 'male',
        ]);

        $this->assertDatabaseHas('user_settings', [
            'who_can_see_posts' => 'public',
        ]);

        // Verify OTPs were generated and hashed in otp_codes table
        $this->assertDatabaseHas('otp_codes', [
            'identifier' => 'tariqul@jugajug.com',
            'purpose' => 'verify_email',
            'is_used' => false,
        ]);

        $this->assertDatabaseHas('otp_codes', [
            'identifier' => '+8801712345678',
            'purpose' => 'verify_phone',
            'is_used' => false,
        ]);

        // Verify password history was initialized
        $user = User::where('username', 'tariqul_bd')->first();
        $this->assertCount(1, $user->passwordHistories);
    }

    public function test_registration_with_referral_username_links_referrer(): void
    {
        $referrer = User::factory()->create(['username' => 'lead_promoter']);

        $payload = [
            'name' => 'Kamal Hossain',
            'username' => 'kamal99',
            'email' => 'kamal@example.com',
            'password' => 'SecurePass@1234',
            'password_confirmation' => 'SecurePass@1234',
            'referred_by' => 'lead_promoter',
            'terms' => true,
        ];

        $response = $this->postJson('/api/v2/auth/register', $payload);
        $response->assertStatus(201);

        $user = User::where('username', 'kamal99')->first();
        $this->assertNotNull($user);
        $this->assertEquals('lead_promoter', $user->referred_by);
        $this->assertEquals($referrer->id, $user->referrer->id);
    }

    public function test_registration_blocks_disposable_email_domains(): void
    {
        $payload = [
            'name' => 'Fake Spammer',
            'username' => 'fakespam',
            'email' => 'badactor@mailinator.com',
            'password' => 'StrongPass@2026!',
            'password_confirmation' => 'StrongPass@2026!',
            'terms' => true,
        ];

        $response = $this->postJson('/api/v2/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_enforces_strong_password_policy(): void
    {
        // 1. Password under 12 characters
        $res1 = $this->postJson('/api/v2/auth/register', [
            'name' => 'Test User',
            'username' => 'testuser1',
            'email' => 'test1@example.com',
            'password' => 'Short@1',
            'password_confirmation' => 'Short@1',
            'terms' => true,
        ]);
        $res1->assertStatus(422)->assertJsonValidationErrors(['password']);

        // 2. Password without special character or digits
        $res2 = $this->postJson('/api/v2/auth/register', [
            'name' => 'Test User',
            'username' => 'testuser2',
            'email' => 'test2@example.com',
            'password' => 'AllLettersWithoutSymbols',
            'password_confirmation' => 'AllLettersWithoutSymbols',
            'terms' => true,
        ]);
        $res2->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_registration_rejects_bot_honeypot_field(): void
    {
        $payload = [
            'name' => 'Automated Bot',
            'username' => 'robot123',
            'email' => 'bot@example.com',
            'password' => 'StrongPass@2026!',
            'password_confirmation' => 'StrongPass@2026!',
            'terms' => true,
            'website_hp' => 'http://spamlink.com', // Honeypot filled by bot
        ];

        $response = $this->postJson('/api/v2/auth/register', $payload);
        $response->assertStatus(422);
    }

    public function test_registration_requires_terms_acceptance(): void
    {
        $payload = [
            'name' => 'No Terms',
            'username' => 'noterms',
            'email' => 'noterms@example.com',
            'password' => 'StrongPass@2026!',
            'password_confirmation' => 'StrongPass@2026!',
            'terms' => false,
        ];

        $response = $this->postJson('/api/v2/auth/register', $payload);
        $response->assertStatus(422)->assertJsonValidationErrors(['terms']);
    }
}
