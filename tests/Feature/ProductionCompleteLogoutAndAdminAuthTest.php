<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ProductionCompleteLogoutAndAdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. Complete Server-Side Web Logout:
     * - Web session invalidated
     * - Token revoked from database
     * - Remember-me rotated
     * - Auth cookies expired
     * - No-cache headers returned
     */
    public function test_web_logout_completely_revokes_session_token_and_expires_cookies(): void
    {
        $user = User::factory()->create([
            'email' => 'user_logout_test@jugajug.com',
            'status' => 'active',
            'remember_token' => 'old_remember_token_value',
        ]);

        $token = $user->createToken('bondhoo_web')->plainTextToken;

        $response = $this->actingAs($user, 'web')
            ->withCookie('bondhoo_token', $token)
            ->withCookie('jugajug_token', $token)
            ->post('/logout');

        // Should redirect to /login
        $response->assertRedirect('/login');

        // Check no-cache headers
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        // Check auth cookies are expired (max-age <= 0 or expires in the past)
        $cookies = $response->headers->getCookies();
        $cookieNames = array_map(fn ($c) => $c->getName(), $cookies);

        $this->assertContains('bondhoo_token', $cookieNames);
        $this->assertContains('jugajug_token', $cookieNames);

        foreach ($cookies as $c) {
            if (in_array($c->getName(), ['bondhoo_token', 'jugajug_token'])) {
                $this->assertTrue($c->getMaxAge() <= 0 || $c->getExpiresTime() < time());
            }
        }

        // Web session must be logged out
        $this->assertGuest('web');

        // Remember token must be rotated/changed
        $this->assertNotEquals('old_remember_token_value', $user->fresh()->remember_token);

        // Access token must be deleted from personal_access_tokens
        $this->assertNull(PersonalAccessToken::findToken($token));
    }

    /**
     * 2. API v1 Logout revokes token and clears session & cookies.
     */
    public function test_api_v1_logout_revokes_token_and_invalidates_session(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $token = $user->createToken('jugajug_web')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->withCookie('jugajug_token', $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Token must be destroyed
        $this->assertNull(PersonalAccessToken::findToken($token));

        // Subsequent API call with this old token must fail with 401
        $subsequent = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $subsequent->assertStatus(401);
    }

    /**
     * 3. API v2 Logout revokes token and clears device session.
     */
    public function test_api_v2_logout_revokes_token(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $token = $user->createToken('bondhoo_device')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v2/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNull(PersonalAccessToken::findToken($token));
    }

    /**
     * 4. Back / Refresh Cache Prevention:
     * Dynamic pages return strict cache-control preventing bfcache restoration.
     */
    public function test_dynamic_pages_have_strict_no_cache_headers(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    /**
     * 5. Protected Dashboard redirects unauthenticated guest to /login.
     */
    public function test_dashboard_redirects_unauthenticated_guests_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * 6. User Login Page (GET /login) contains NO Admin options or demo buttons.
     */
    public function test_user_login_page_has_no_admin_options_or_demo_buttons(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);

        // Must NOT contain admin modes or demo buttons
        $response->assertDontSee('btnModeAdmin');
        $response->assertDontSee('সুপার অ্যাডমিন');
        $response->assertDontSee('আব্দুর রহিম');
        $response->assertDontSee('admin@jugajug.com');
        $response->assertDontSee('User@123456');

        // Must contain user login essentials
        $response->assertSee('loginIdentifier');
        $response->assertSee('loginPassword');
        $response->assertSee('rememberMe');
        $response->assertSee('submitBtn');
    }

    /**
     * 7. Separate Dedicated Admin Login Page (GET /admin/login) works.
     */
    public function test_separate_admin_login_page_renders_cleanly(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('অ্যাডমিন সিকিউরিটি কনসোল');
        $response->assertSee('adminLoginForm');
        $response->assertSee('identifier');
        $response->assertSee('password');
        $response->assertDontSee('নতুন অ্যাকাউন্ট তৈরি করুন');
    }

    /**
     * 8. Admin Login with valid credentials succeeds and redirects to admin dashboard.
     */
    public function test_admin_can_login_at_admin_login_route(): void
    {
        $admin = Admin::create([
            'username' => 'sec_admin',
            'name' => 'Security Admin',
            'email' => 'secadmin@jugajug.com',
            'password' => 'AdminSecret@123',
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $response = $this->post('/admin/login', [
            'identifier' => 'secadmin@jugajug.com',
            'password' => 'AdminSecret@123',
        ]);

        $response->assertRedirect('/admin/auth-management');
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    /**
     * 9. Regular user CANNOT login through /admin/login.
     */
    public function test_regular_user_cannot_login_through_admin_login(): void
    {
        User::factory()->create([
            'email' => 'regular_citizen@jugajug.com',
            'username' => 'citizen',
            'password' => 'Password@123456',
            'status' => 'active',
        ]);

        $response = $this->post('/admin/login', [
            'identifier' => 'regular_citizen@jugajug.com',
            'password' => 'Password@123456',
        ]);

        $this->assertGuest('admin');
        $response->assertSessionHasErrors(['identifier']);
    }

    /**
     * 10. Normal user is FORBIDDEN (403) from accessing admin routes.
     */
    public function test_normal_user_receives_403_on_admin_routes(): void
    {
        $regularUser = User::factory()->create([
            'status' => 'active',
        ]);

        $response = $this->actingAs($regularUser, 'web')
            ->get('/admin/auth-management');

        $response->assertStatus(403);
    }

    /**
     * 11. Guest is redirected to /admin/login when accessing admin routes.
     */
    public function test_guest_is_redirected_to_admin_login_on_admin_routes(): void
    {
        $response = $this->get('/admin/auth-management');

        $response->assertRedirect('/admin/login');
    }

    /**
     * 12. Authenticated Admin CAN access admin routes.
     */
    public function test_authenticated_admin_can_access_admin_routes(): void
    {
        $admin = Admin::create([
            'username' => 'root_admin',
            'name' => 'Root Admin',
            'email' => 'root@jugajug.com',
            'password' => 'RootPass@123',
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/auth-management');

        $response->assertStatus(200);
        $response->assertSee('অথেন্টিকেশন ও সিকিউরিটি ম্যানেজমেন্ট');
        $response->assertSee('অ্যাডমিন লগআউট');
    }

    /**
     * 13. Admin Web Logout terminates admin session and redirects to /admin/login.
     */
    public function test_admin_web_logout_terminates_admin_session_and_expires_cookies(): void
    {
        $admin = Admin::create([
            'username' => 'auth_admin',
            'name' => 'Auth Admin',
            'email' => 'authadmin@jugajug.com',
            'password' => 'AdminPass@123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/logout');

        $response->assertRedirect('/admin/login');
        $this->assertGuest('admin');

        // Cookies expired
        $cookies = $response->headers->getCookies();
        $cookieNames = array_map(fn ($c) => $c->getName(), $cookies);
        $this->assertContains('bondhoo_admin', $cookieNames);
    }

    /**
     * 14. User with ADMIN role can login at /admin/login.
     */
    public function test_user_with_admin_role_can_login_through_admin_login(): void
    {
        $adminUser = User::factory()->create([
            'email' => 'system_admin@jugajug.com',
            'password' => 'AdminPass@987',
            'status' => 'active',
        ]);
        $role = Role::firstOrCreate(['name' => 'ADMIN']);
        $adminUser->roles()->sync([$role->id]);

        $this->assertTrue($adminUser->isAdmin());

        $response = $this->post('/admin/login', [
            'identifier' => 'system_admin@jugajug.com',
            'password' => 'AdminPass@987',
        ]);

        $response->assertRedirect('/admin/auth-management');
        $this->assertAuthenticated('admin');
    }

    /**
     * 15. Normal user receives 403 on /admin/live route.
     */
    public function test_normal_user_receives_403_on_admin_live_route(): void
    {
        $regularUser = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($regularUser, 'web')
            ->get('/admin/live');

        $response->assertStatus(403);
    }

    /**
     * 16. Views contain ZERO mock buttons and ZERO prefilled credentials.
     */
    public function test_views_contain_zero_mock_buttons_and_zero_prefilled_credentials(): void
    {
        $loginBlade = file_get_contents(resource_path('views/auth/login.blade.php'));
        $dashboardBlade = file_get_contents(resource_path('views/dashboard.blade.php'));

        $this->assertStringNotContainsString('btnModeAdmin', $loginBlade);
        $this->assertStringNotContainsString('admin@jugajug.com', $loginBlade);
        $this->assertStringNotContainsString('user@jugajug.com', $loginBlade);
        $this->assertStringNotContainsString('User@123456', $loginBlade);
        $this->assertStringNotContainsString('Admin@123456', $loginBlade);

        $this->assertStringNotContainsString('👤 ইউজার হিসেবে লগইন', $dashboardBlade);
        $this->assertStringNotContainsString('⚡ সুপার অ্যাডমিন হিসেবে লগইন', $dashboardBlade);
        $this->assertStringNotContainsString('user@jugajug.com', $dashboardBlade);
        $this->assertStringNotContainsString('admin@jugajug.com', $dashboardBlade);
        $this->assertStringNotContainsString('User@123456', $dashboardBlade);
        $this->assertStringNotContainsString('Admin@123456', $dashboardBlade);
        $this->assertStringNotContainsString('id="adminToggleText"', $dashboardBlade);
        $this->assertStringNotContainsString('ইউজার পরিবর্তন (Admin ↔ User)', $dashboardBlade);
    }
}
