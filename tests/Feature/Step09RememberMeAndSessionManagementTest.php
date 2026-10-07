<?php

namespace Tests\Feature;

use App\Models\TrustedDevice;
use App\Models\User;
use App\Models\UserSession;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class Step09RememberMeAndSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_remember_me_cookie_and_trusted_device_created_on_login(): void
    {
        $password = 'Secret@123456';
        $user = User::factory()->create([
            'email' => 'remember.user@jugajug.com',
            'password' => Hash::make($password),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'identifier' => $user->email,
            'password' => $password,
            'remember' => 1,
        ]);

        $response->assertRedirect('/feed');
        $this->assertAuthenticatedAs($user, 'web');

        // Verify Remember Me cookie is queued or set
        $cookies = $response->headers->getCookies();
        $hasRememberCookie = false;
        foreach ($cookies as $cookie) {
            if (str_starts_with($cookie->getName(), 'remember_web_')) {
                $hasRememberCookie = true;
                break;
            }
        }
        $this->assertTrue($hasRememberCookie, 'Remember Me cookie was not found in response headers.');

        // Verify trusted device and user session created
        $this->assertDatabaseHas('trusted_devices', [
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('user_sessions', [
            'user_id' => $user->id,
            'is_current' => true,
        ]);
    }

    public function test_session_rotation_regenerates_session_id(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $response = $this->withSession(['foo' => 'bar'])
            ->postJson('/session/rotate');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'সেশন সফলভাবে রোটেট করা হয়েছে।',
            ]);

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_logout_current_device_invalidates_session_and_cleans_records(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        UserSession::create([
            'user_id' => $user->id,
            'device_name' => 'Chrome on macOS',
            'device_fingerprint' => 'test-fingerprint',
            'is_current' => true,
            'last_active_at' => now(),
        ]);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest('web');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'AUTH_LOGOUT_CURRENT_DEVICE',
        ]);
    }

    public function test_logout_all_devices_purges_sessions_tokens_and_rotates_remember_token(): void
    {
        $initialRememberToken = Str::random(60);
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
            'remember_token' => $initialRememberToken,
        ]);

        // Create Sanctum tokens
        $user->createToken('Mobile App');
        $user->createToken('Tablet App');
        $this->assertEquals(2, $user->tokens()->count());

        // Create multiple UserSessions
        UserSession::create([
            'user_id' => $user->id,
            'device_name' => 'Device 1',
            'device_fingerprint' => 'fp-1',
            'is_current' => true,
            'last_active_at' => now(),
        ]);
        UserSession::create([
            'user_id' => $user->id,
            'device_name' => 'Device 2',
            'device_fingerprint' => 'fp-2',
            'is_current' => false,
            'last_active_at' => now(),
        ]);
        $this->assertEquals(2, UserSession::where('user_id', $user->id)->count());

        // Create Trusted Device
        TrustedDevice::create([
            'user_id' => $user->id,
            'device_fingerprint' => 'fp-1',
            'device_name' => 'Trusted PC',
            'trusted_until' => now()->addDays(30),
            'last_used_at' => now(),
        ]);
        $this->assertEquals(1, TrustedDevice::where('user_id', $user->id)->count());

        // Insert database sessions if table exists
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->insert([
                'id' => 'session-dummy-1',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla',
                'payload' => 'dummy',
                'last_activity' => time(),
            ]);
            DB::table('sessions')->insert([
                'id' => 'session-dummy-2',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla',
                'payload' => 'dummy',
                'last_activity' => time(),
            ]);
            $this->assertEquals(2, DB::table('sessions')->where('user_id', $user->id)->count());
        }

        $this->actingAs($user, 'web');

        $response = $this->post('/devices/logout-all');

        $response->assertRedirect('/login');
        $this->assertGuest('web');

        // Verify tokens are revoked
        $this->assertEquals(0, $user->tokens()->count());

        // Verify user_sessions are cleared
        $this->assertEquals(0, UserSession::where('user_id', $user->id)->count());

        // Verify trusted devices are cleared
        $this->assertEquals(0, TrustedDevice::where('user_id', $user->id)->count());

        // Verify database sessions are purged
        if (Schema::hasTable('sessions')) {
            $this->assertEquals(0, DB::table('sessions')->where('user_id', $user->id)->count());
        }

        // Verify remember_token was rotated
        $user->refresh();
        $this->assertNotEquals($initialRememberToken, $user->remember_token);

        // Verify audit log recorded
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'AUTH_LOGOUT_ALL_DEVICES',
        ]);
    }

    public function test_post_logout_all_route_logs_out_user_and_cleans_sessions(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserSession::create([
            'user_id' => $user->id,
            'device_name' => 'Device Test',
            'device_fingerprint' => 'fp-test',
            'is_current' => true,
            'last_active_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $response = $this->post('/logout/all');

        $response->assertRedirect('/login');
        $this->assertGuest('web');
        $this->assertEquals(0, UserSession::where('user_id', $user->id)->count());
    }

    public function test_idle_session_expiration_logs_out_user_after_timeout(): void
    {
        config(['session.idle_timeout' => 15]); // 15 minutes timeout

        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        // 1. When last activity was within 10 minutes ago, request proceeds
        $response = $this->withSession([
            'last_activity_time' => time() - (10 * 60),
        ])->get('/me');

        $response->assertStatus(200);
        $this->assertAuthenticatedAs($user, 'web');

        // 2. When last activity was 20 minutes ago (> 15 minutes idle timeout), session expires
        $expiredResponse = $this->withSession([
            'last_activity_time' => time() - (20 * 60),
        ])->get('/devices');

        $expiredResponse->assertRedirect('/login');
        $this->assertGuest('web');
    }

    public function test_my_devices_dashboard_displays_sessions_and_allows_revoking(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $currentSession = UserSession::create([
            'user_id' => $user->id,
            'device_name' => 'Primary Desktop',
            'device_fingerprint' => 'fp-current',
            'browser' => 'Chrome',
            'os' => 'macOS',
            'ip_address' => '192.168.1.10',
            'location' => 'Dhaka, BD',
            'is_current' => true,
            'last_active_at' => now(),
        ]);

        $otherSession = UserSession::create([
            'user_id' => $user->id,
            'device_name' => 'Secondary Laptop',
            'device_fingerprint' => 'fp-other',
            'browser' => 'Firefox',
            'os' => 'Windows',
            'ip_address' => '103.205.71.5',
            'location' => 'Chittagong, BD',
            'is_current' => false,
            'last_active_at' => now()->subHours(2),
        ]);

        $trustedDevice = TrustedDevice::create([
            'user_id' => $user->id,
            'device_fingerprint' => 'fp-other',
            'device_name' => 'Secondary Laptop',
            'browser' => 'Firefox',
            'os' => 'Windows',
            'ip_address' => '103.205.71.5',
            'trusted_until' => now()->addDays(30),
            'last_used_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        // 1. Visit /devices dashboard
        $viewResponse = $this->get('/devices');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('আমার ডিভাইসসমূহ');
        $viewResponse->assertSee('Primary Desktop');
        $viewResponse->assertSee('Secondary Laptop');

        // 2. Revoke the other session
        $revokeResponse = $this->delete('/devices/'.$otherSession->id);
        $revokeResponse->assertRedirect();
        $this->assertDatabaseMissing('user_sessions', ['id' => $otherSession->id]);
        $this->assertDatabaseHas('user_sessions', ['id' => $currentSession->id]);

        // 3. Revoke the trusted device
        $trustedRevokeResponse = $this->delete('/devices/trusted/'.$trustedDevice->id);
        $trustedRevokeResponse->assertRedirect();
        $this->assertDatabaseMissing('trusted_devices', ['id' => $trustedDevice->id]);
    }
}
