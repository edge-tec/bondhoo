<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\Post;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Admin and Regular Users
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN'], ['description' => 'Administrator']);
        Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['description' => 'Super Administrator']);
        Role::firstOrCreate(['name' => 'USER'], ['description' => 'Regular User']);

        $this->adminUser = User::factory()->create([
            'email' => 'admin@jugajug.com',
            'status' => 'active',
        ]);
        $this->adminUser->roles()->attach($adminRole);

        $this->regularUser = User::factory()->create([
            'email' => 'user@jugajug.com',
            'status' => 'active',
        ]);
    }

    /**
     * Unauthenticated guest is redirected to admin login.
     */
    public function test_unauthenticated_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect(route('admin.login'));
    }

    /**
     * Regular user without admin privileges gets 403 Forbidden.
     */
    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->regularUser, 'web')->get('/admin');
        $response->assertStatus(403);
    }

    /**
     * Authorized administrator can view the Enterprise Admin Dashboard.
     */
    public function test_admin_can_access_enterprise_admin_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser, 'web')->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Bondhoo Admin');
        $response->assertSee('এক্সিকিউটিভ ওভারভিউ');
        $response->assertSee('ব্যবহারকারী ব্যবস্থাপনা');
        $response->assertSee('কন্টেন্ট ও পোস্টসমূহ');
        $response->assertSee('মডারেশন কিউ');
        $response->assertSee('প্ল্যাটফর্ম কনফিগারেশন');
    }

    /**
     * Executive Overview API returns real KPIs and analytics time series.
     */
    public function test_executive_overview_api_returns_kpis_and_analytics(): void
    {
        // Create sample posts
        Post::factory()->count(3)->create(['user_id' => $this->regularUser->id]);

        $response = $this->actingAs($this->adminUser, 'sanctum')->getJson('/api/v2/admin/enterprise/overview');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'kpis' => [
                    'users' => ['total', 'dau', 'mau'],
                    'content' => ['total_posts', 'posts_today'],
                    'system',
                ],
                'analytics' => [
                    'dates',
                    'registrations',
                    'content',
                    'engagement',
                ],
                'server_time',
            ],
        ]);

        $this->assertGreaterThanOrEqual(2, $response->json('data.kpis.users.total'));
        $this->assertGreaterThanOrEqual(3, $response->json('data.kpis.content.total_posts'));
    }

    /**
     * User Directory API supports filtering and search.
     */
    public function test_users_api_returns_paginated_and_filtered_users(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'Mizanur Rahman Special',
            'username' => 'mizanur_special',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')->getJson('/api/v2/admin/enterprise/users?search=mizanur_special');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.username', 'mizanur_special');
    }

    /**
     * Admin can perform administrative actions on users with audit logging.
     */
    public function test_admin_can_suspend_and_verify_user_with_audit_trail(): void
    {
        $targetUser = User::factory()->create(['status' => 'active', 'email_verified_at' => null]);

        // 1. Suspend User
        $suspendResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/users/{$targetUser->id}/action", [
                'action' => 'suspend',
                'duration_days' => 14,
                'reason' => 'Violation of community guidelines',
            ]);

        $suspendResponse->assertStatus(200);
        $this->assertEquals('suspended', $targetUser->fresh()->status);
        $this->assertNotNull($targetUser->fresh()->locked_until);

        // Check Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'admin_user_suspend',
            'entity_id' => $targetUser->id,
        ]);

        // 2. Verify User
        $verifyResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/users/{$targetUser->id}/action", [
                'action' => 'verify',
            ]);

        $verifyResponse->assertStatus(200);
        $this->assertTrue($targetUser->fresh()->isVerified());
    }

    /**
     * Admin can perform bulk actions on multiple users.
     */
    public function test_admin_can_execute_bulk_user_actions(): void
    {
        $users = User::factory()->count(3)->create(['email_verified_at' => null]);
        $userIds = $users->pluck('id')->toArray();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/users/bulk', [
                'user_ids' => $userIds,
                'action' => 'verify',
            ]);

        $response->assertStatus(200);
        foreach ($userIds as $id) {
            $this->assertNotNull(User::find($id)->email_verified_at);
        }
    }

    /**
     * Moderation queue returns reports and allows resolution.
     */
    public function test_moderation_queue_and_resolution(): void
    {
        $reportedPost = Post::factory()->create(['user_id' => $this->regularUser->id]);
        $report = Report::create([
            'reporter_id' => $this->adminUser->id,
            'reportable_type' => Post::class,
            'reportable_id' => $reportedPost->id,
            'reason' => 'spam',
            'details' => 'Repeated spam links',
            'status' => Report::STATUS_PENDING,
        ]);

        // Get moderation queue
        $queueResponse = $this->actingAs($this->adminUser, 'sanctum')->getJson('/api/v2/admin/enterprise/moderation?status=pending');
        $queueResponse->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $queueResponse->json('meta.total'));

        // Resolve report by deleting content
        $resolveResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/moderation/{$report->id}/resolve", [
                'action' => 'delete_content',
                'admin_notes' => 'Confirmed spam content, removed.',
            ]);

        $resolveResponse->assertStatus(200);
        $this->assertEquals(Report::STATUS_ACTIONED, $report->fresh()->status);
        $this->assertSoftDeleted('posts', ['id' => $reportedPost->id]);
    }

    /**
     * Platform Configuration settings can be retrieved and updated.
     */
    public function test_platform_settings_management(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/settings', [
                'settings' => [
                    ['key' => 'site_name', 'value' => 'Bondhoo Enterprise Supernet'],
                    ['key' => 'max_upload_size_mb', 'value' => '250'],
                ],
            ]);

        $response->assertStatus(200);
        $this->assertEquals('Bondhoo Enterprise Supernet', PlatformSetting::get('site_name'));
        $this->assertEquals(250, PlatformSetting::get('max_upload_size_mb'));
    }

    /**
     * Global Search returns multi-entity search results.
     */
    public function test_global_search_across_entities(): void
    {
        $searchUser = User::factory()->create(['name' => 'Searchable Hero User', 'username' => 'searchable_hero']);

        $response = $this->actingAs($this->adminUser, 'sanctum')->getJson('/api/v2/admin/enterprise/search?q=Searchable');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['users', 'posts', 'groups', 'tickets'],
        ]);
        $this->assertEquals('Searchable Hero User', $response->json('data.users.0.name'));
    }
}
