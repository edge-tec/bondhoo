<?php

namespace Tests\Feature;

use App\Models\LoginHistory;
use App\Models\User;
use App\Models\UserSession;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Step10DeviceLoginHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_device_creation_records_all_fields_on_login(): void
    {
        $password = 'SecretPassword@123';
        $user = User::factory()->create([
            'email' => 'device.tracker@jugajug.com',
            'password' => Hash::make($password),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            'CF-IPCountry' => 'BD',
            'CF-IPCity' => 'Dhaka',
        ])->post('/login', [
            'identifier' => $user->email,
            'password' => $password,
        ]);

        $response->assertRedirect('/feed');
        $this->assertAuthenticatedAs($user, 'web');

        // Verify LoginHistory table contains all required fields
        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'browser' => 'Apple Safari',
            'device' => 'Apple iPhone',
            'os' => 'iOS (iPhone)',
            'country' => 'BD',
            'city' => 'Dhaka',
            'status' => 'success',
            'logout_at' => null,
        ]);

        $history = LoginHistory::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertNotNull($history->login_at);
        $this->assertNull($history->logout_at);
        $this->assertEquals('Apple iPhone', $history->device);
        $this->assertEquals('Dhaka', $history->city);
        $this->assertEquals('BD', $history->country);
    }

    public function test_device_logout_updates_logout_time(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $session = UserSession::create([
            'user_id' => $user->id,
            'device_name' => 'Secondary Android',
            'device_fingerprint' => 'fp-android-test',
            'ip_address' => '103.205.71.1',
            'is_current' => false,
            'last_active_at' => now(),
        ]);

        $history = LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => '103.205.71.1',
            'device' => 'Android Device',
            'browser' => 'Google Chrome',
            'os' => 'Android',
            'country' => 'BD',
            'city' => 'Chittagong',
            'status' => 'success',
            'login_at' => now()->subHours(1),
            'logout_at' => null,
        ]);

        $this->actingAs($user, 'web');

        // Revoke the individual device session
        $response = $this->delete('/devices/'.$session->id);
        $response->assertRedirect();

        $history->refresh();
        $this->assertNotNull($history->logout_at, 'Logout time should be updated when the device is logged out.');
    }

    public function test_api_device_destroy_updates_logout_time(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $session = UserSession::create([
            'user_id' => $user->id,
            'device_name' => 'Tablet',
            'ip_address' => '192.168.1.55',
            'is_current' => false,
            'last_active_at' => now(),
        ]);

        $history = LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => '192.168.1.55',
            'device' => 'Apple iPad',
            'browser' => 'Apple Safari',
            'os' => 'iPadOS',
            'country' => 'BD',
            'city' => 'Sylhet',
            'status' => 'success',
            'login_at' => now()->subHours(2),
            'logout_at' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/auth/device/'.$session->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ডিভাইস সেশন সফলভাবে লগআউট করা হয়েছে।',
            ]);

        $history->refresh();
        $this->assertNotNull($history->logout_at);
    }

    public function test_history_retrieval_api_returns_complete_login_history(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'device' => 'Macintosh',
            'browser' => 'Google Chrome',
            'os' => 'macOS',
            'device_type' => 'desktop',
            'country' => 'BD',
            'city' => 'Dhaka',
            'status' => 'success',
            'login_at' => now()->subMinutes(30),
            'logout_at' => now()->subMinutes(10),
        ]);

        LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'device' => 'Apple iPhone',
            'browser' => 'Apple Safari',
            'os' => 'iOS (iPhone)',
            'device_type' => 'mobile',
            'country' => 'BD',
            'city' => 'Dhaka',
            'status' => 'success',
            'login_at' => now()->subMinutes(5),
            'logout_at' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/login-history');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ডিভাইস লগইন হিস্টোরি লোড হয়েছে।',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'ip_address',
                        'device',
                        'browser',
                        'os',
                        'country',
                        'city',
                        'status',
                        'login_at',
                        'logout_at',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('Apple iPhone', $data[0]['device']);
        $this->assertEquals('Macintosh', $data[1]['device']);
    }

    public function test_dashboard_displays_active_devices_and_login_history(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserSession::create([
            'user_id' => $user->id,
            'device_name' => 'Workstation Mac',
            'device_fingerprint' => 'fp-mac',
            'ip_address' => '127.0.0.1',
            'is_current' => true,
            'last_active_at' => now(),
        ]);

        UserSession::create([
            'user_id' => $user->id,
            'device_name' => 'Phone Mobile',
            'device_fingerprint' => 'fp-phone',
            'ip_address' => '103.205.71.9',
            'is_current' => false,
            'last_active_at' => now()->subHours(1),
        ]);

        LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'device' => 'Workstation Mac',
            'browser' => 'Google Chrome',
            'os' => 'macOS',
            'country' => 'BD',
            'city' => 'Dhaka',
            'status' => 'success',
            'login_at' => now()->subMinutes(45),
            'logout_at' => null,
        ]);

        $this->actingAs($user, 'web');

        $response = $this->get('/devices');
        $response->assertStatus(200);
        $response->assertSee('আমার ডিভাইসসমূহ');
        $response->assertSee('Workstation Mac');
        $response->assertSee('Phone Mobile');
        $response->assertSee('ডিভাইস লগইন হিস্টোরি');
        $response->assertSee('Dhaka');
        $response->assertSee('সক্রিয় (Active)');
    }
}
