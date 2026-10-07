<?php

namespace Tests\Feature;

use App\Http\Middleware\ActiveUserMiddleware;
use App\Http\Middleware\VerifiedUserMiddleware;
use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Step01AuthFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register temporary test routes to verify middleware in isolation
        Route::middleware(['auth:sanctum', ActiveUserMiddleware::class])->get('/test/active-user-only', function () {
            return response()->json(['success' => true, 'message' => 'Active user allowed']);
        });

        Route::middleware(['auth:sanctum', VerifiedUserMiddleware::class])->get('/test/verified-user-only', function () {
            return response()->json(['success' => true, 'message' => 'Verified user allowed']);
        });

        Route::middleware(['guest'])->get('/test/guest-only', function () {
            return response()->json(['success' => true, 'message' => 'Guest allowed']);
        });
    }

    public function test_required_database_tables_exist_with_proper_schemas(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('sessions'));
        $this->assertTrue(Schema::hasTable('login_histories'));
        $this->assertTrue(Schema::hasTable('audit_logs'));
        $this->assertTrue(Schema::hasTable('trusted_devices'));

        // Verify key columns
        $this->assertTrue(Schema::hasColumns('users', ['id', 'username', 'email', 'phone', 'password', 'status']));
        $this->assertTrue(Schema::hasColumns('login_histories', ['user_id', 'ip_address', 'status']));
        $this->assertTrue(Schema::hasColumns('trusted_devices', ['user_id', 'device_fingerprint', 'trusted_until']));
        $this->assertTrue(Schema::hasColumns('audit_logs', ['user_id', 'action']));
    }

    public function test_user_relationships_with_foundation_models(): void
    {
        $user = User::factory()->create();

        LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'status' => 'success',
        ]);

        TrustedDevice::create([
            'user_id' => $user->id,
            'device_fingerprint' => 'test-fingerprint-123',
            'device_name' => 'MacBook Pro',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'FOUNDATION_TEST',
        ]);

        $this->assertCount(1, $user->loginHistories);
        $this->assertCount(1, $user->trustedDevices);
        $this->assertCount(1, $user->auditLogs);
    }

    public function test_sanctum_authentication_login_me_refresh_logout_flow(): void
    {
        $user = User::factory()->create([
            'email' => 'rahim@jugajug.com',
            'username' => 'rahim_test',
            'password' => Hash::make('SecretPass@123'),
            'status' => 'active',
        ]);

        // 1. Login via Sanctum API
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'rahim@jugajug.com',
            'password' => 'SecretPass@123',
            'device_name' => 'Mobile App',
        ]);

        $loginRes->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'token', 'token_type'],
            ]);

        $token = $loginRes->json('data.token');
        $this->assertNotEmpty($token);

        // 2. Access Me endpoint
        $meRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $meRes->assertStatus(200)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'rahim@jugajug.com');

        // 3. Refresh token
        $refreshRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/refresh');

        $refreshRes->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['token', 'token_type'],
            ]);

        $newToken = $refreshRes->json('data.token');
        $this->assertNotEquals($token, $newToken);

        // 4. Logout
        $logoutRes = $this->withHeader('Authorization', "Bearer {$newToken}")
            ->postJson('/api/v1/auth/logout');

        $logoutRes->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Reset in-memory guard cache to ensure fresh token authentication
        app('auth')->forgetGuards();

        // 5. Revoked token should no longer be authorized
        $unauthRes = $this->withHeader('Authorization', "Bearer {$newToken}")
            ->getJson('/api/v1/auth/me');

        $unauthRes->assertStatus(401);
    }

    public function test_web_session_based_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'karim@jugajug.com',
            'username' => 'karim_test',
            'password' => Hash::make('WebPass@12345'),
            'status' => 'active',
        ]);

        // Web login
        $loginResponse = $this->post('/login', [
            'identifier' => 'karim_test',
            'password' => 'WebPass@12345',
            'remember' => true,
        ]);

        $loginResponse->assertRedirect('/feed');
        $this->assertAuthenticatedAs($user, 'web');

        // Session Refresh
        $refreshRes = $this->postJson('/session/refresh');
        $refreshRes->assertStatus(200)
            ->assertJsonStructure(['success', 'csrf_token']);

        // Web logout
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/login');
        $this->assertGuest('web');
    }

    public function test_active_user_middleware_blocks_suspended_users(): void
    {
        $activeUser = User::factory()->create(['status' => 'active']);
        $suspendedUser = User::factory()->create(['status' => 'suspended']);

        // Active user can access
        $this->actingAs($activeUser, 'sanctum')
            ->getJson('/test/active-user-only')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        // Suspended user is blocked with 403
        $this->actingAs($suspendedUser, 'sanctum')
            ->getJson('/test/active-user-only')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_verified_user_middleware_checks_email_or_phone_verification(): void
    {
        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);

        $verifiedEmailUser = User::factory()->create([
            'email_verified_at' => now(),
            'phone_verified_at' => null,
        ]);

        $verifiedPhoneUser = User::factory()->create([
            'email_verified_at' => null,
            'phone_verified_at' => now(),
        ]);

        // Unverified blocked
        $this->actingAs($unverifiedUser, 'sanctum')
            ->getJson('/test/verified-user-only')
            ->assertStatus(403)
            ->assertJsonPath('needs_verification', true);

        // Email verified allowed
        $this->actingAs($verifiedEmailUser, 'sanctum')
            ->getJson('/test/verified-user-only')
            ->assertStatus(200);

        // Phone verified allowed
        $this->actingAs($verifiedPhoneUser, 'sanctum')
            ->getJson('/test/verified-user-only')
            ->assertStatus(200);
    }

    public function test_redirect_if_authenticated_middleware(): void
    {
        $user = User::factory()->create();

        // Guest can access
        $this->getJson('/test/guest-only')
            ->assertStatus(200);

        // Authenticated user redirected
        $this->actingAs($user, 'web')
            ->get('/test/guest-only')
            ->assertRedirect('/feed');
    }

    public function test_password_hashing_configuration_and_support(): void
    {
        $this->assertEquals('bcrypt', Config::get('hashing.driver'));
        $hash = Hash::make('FoundationPass@2026');
        $this->assertTrue(Hash::check('FoundationPass@2026', $hash));
    }
}
