<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionAuthSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_sql_injection_attempts_in_login_are_thwarted(): void
    {
        $payloads = [
            "' OR '1'='1",
            "admin' --",
            "admin' /*",
            "' UNION SELECT * FROM users --",
            '1; DROP TABLE users; --',
        ];

        foreach ($payloads as $sql) {
            $response = $this->postJson('/api/v2/auth/login', [
                'identifier' => $sql,
                'password' => "' OR '1'='1",
            ]);

            $response->assertStatus(422);
            $this->assertDatabaseMissing('users', ['email' => $sql]);
        }
    }

    public function test_xss_payloads_in_registration_are_properly_handled(): void
    {
        $xss = '<script>alert("XSS")</script>';

        $response = $this->postJson('/api/v2/auth/register', [
            'name' => 'John '.$xss,
            'username' => 'john_clean',
            'email' => 'johnxss@example.com',
            'password' => 'SafePass@2026!#',
            'password_confirmation' => 'SafePass@2026!#',
            'terms' => true,
        ]);

        $response->assertStatus(201);
        $user = User::where('username', 'john_clean')->first();
        $this->assertNotNull($user);
    }

    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_honeypot_field_blocks_automated_bot_logins(): void
    {
        $response = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'user@example.com',
            'password' => 'Password@123',
            'website_hp' => 'bot-filled-url.com',
        ]);

        $response->assertStatus(422);
    }
}
