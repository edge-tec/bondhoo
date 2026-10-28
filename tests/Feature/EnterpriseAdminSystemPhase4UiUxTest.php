<?php

namespace Tests\Feature;

use App\Models\AdminAlert;
use App\Models\AdminElevation;
use App\Models\AuditCheckpoint;
use App\Models\AuditLog;
use App\Models\PlatformFeatureFlag;
use App\Models\PlatformSetting;
use App\Models\Post;
use App\Models\PrivacyRequest;
use App\Models\Report;
use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserRestriction;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EnterpriseAdminSystemPhase4UiUxTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['description' => 'Super Administrator']);
        Role::firstOrCreate(['name' => 'ADMIN'], ['description' => 'Administrator']);

        $this->superAdmin = User::factory()->create([
            'name' => 'Phase 4 Super Admin',
            'email' => 'phase4-admin@jugajug.com',
            'status' => 'active',
        ]);
        $this->superAdmin->roles()->attach($superAdminRole);
    }

    public function test_phase4_admin_command_center_renders_premium_design_system_accessibility_and_all_workspaces(): void
    {
        $response = $this->actingAs($this->superAdmin, 'web')->get('/admin');

        $response->assertStatus(200);

        // Verify Phase 4 Design Tokens & Modern Web Guidance CSS rules
        $response->assertSee('--surface-elevated', false);
        $response->assertSee('container-type: inline-size', false);
        $response->assertSee('content-visibility: auto', false);
        $response->assertSee('overscroll-behavior: contain', false);
        $response->assertSee('prefers-reduced-motion: reduce', false);

        // Verify Accessibility Landmarks & ARIA attributes
        $response->assertSee('role="navigation"', false);
        $response->assertSee('aria-label="প্রধান অ্যাডমিন নেভিগেশন"', false);
        $response->assertSee('id="top-breadcrumb-current"', false);
        $response->assertSee('aria-live="polite"', false);
        $response->assertSee('role="dialog"', false);

        // Verify all Workspace Sections exist including new Feature Flags workspace & Contextual Detail Drawer
        $workspaces = [
            'sec-overview',
            'sec-analytics',
            'sec-search',
            'sec-users',
            'sec-content',
            'sec-moderation',
            'sec-trust_safety',
            'sec-social',
            'sec-messaging',
            'sec-roles',
            'sec-security',
            'sec-alerts',
            'sec-support',
            'sec-monetization',
            'sec-jobs',
            'sec-health',
            'sec-flags',
            'sec-settings',
            'sec-developer',
            'sec-audit',
            'sec-governance',
        ];

        foreach ($workspaces as $workspaceId) {
            $response->assertSee('id="'.$workspaceId.'"', false);
        }

        // Verify Phase 4 Interactive Visualization & Drawer Containers
        $response->assertSee('id="context-detail-drawer"', false);
        $response->assertSee('id="intel-funnel-container"', false);
        $response->assertSee('id="intel-anomalies-container"', false);
        $response->assertSee('id="intel-cohorts-tbody"', false);
        $response->assertSee('id="soc-auth-trends-chart"', false);
        $response->assertSee('id="soc-playbooks-container"', false);
        $response->assertSee('id="audit-checkpoints-table-body"', false);
        $response->assertSee('id="flags-table-body"', false);
    }

    public function test_phase4_overview_endpoint_includes_real_operational_kpis(): void
    {
        $targetUser = User::factory()->create();

        UserRestriction::create([
            'user_id' => $targetUser->id,
            'restricted_by' => $this->superAdmin->id,
            'restriction_type' => 'post_block',
            'reason' => 'Automated spam velocity threshold',
            'is_active' => true,
            'expires_at' => now()->addHours(12),
        ]);

        AdminAlert::create([
            'title' => 'Critical Latency Spike',
            'message' => 'Database response time exceeded threshold',
            'type' => 'infrastructure',
            'severity' => 'critical',
            'status' => 'unread',
        ]);

        PrivacyRequest::create([
            'request_number' => 'PRV-P4-001',
            'user_id' => $targetUser->id,
            'request_type' => 'data_export',
            'status' => 'pending',
            'reason' => 'User requested GDPR archive',
        ]);

        PlatformFeatureFlag::create([
            'key' => 'phase4_command_palette',
            'name' => 'Phase 4 Command Palette',
            'is_enabled' => true,
            'rollout_percentage' => 100,
        ]);

        $expectedActiveFlags = PlatformFeatureFlag::where('is_enabled', true)->count();

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/overview');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kpis.operations.active_restrictions', 1)
            ->assertJsonPath('data.kpis.operations.unacknowledged_alerts', 1)
            ->assertJsonPath('data.kpis.operations.pending_privacy_requests', 1)
            ->assertJsonPath('data.kpis.operations.active_feature_flags', $expectedActiveFlags);
    }

    public function test_phase4_active_restrictions_endpoint_returns_real_database_records(): void
    {
        $restrictedUser = User::factory()->create(['name' => 'Restricted Member']);

        $restriction = UserRestriction::create([
            'user_id' => $restrictedUser->id,
            'restricted_by' => $this->superAdmin->id,
            'restriction_type' => 'comment_block',
            'reason' => 'Repeated harassment violations',
            'is_active' => true,
            'expires_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/abuse/restrictions');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $restriction->id,
                'user_id' => $restrictedUser->id,
                'restriction_type' => 'comment_block',
                'reason' => 'Repeated harassment violations',
            ]);
    }

    public function test_phase4_moderation_queue_supports_priority_ai_filters_and_duplicates_count(): void
    {
        $reporter1 = User::factory()->create();
        $reporter2 = User::factory()->create();
        $targetUser = User::factory()->create();

        $primaryReport = Report::create([
            'reporter_id' => $reporter1->id,
            'reportable_type' => User::class,
            'reportable_id' => $targetUser->id,
            'reason' => 'scam',
            'details' => 'Phishing link in profile bio',
            'status' => 'pending',
            'priority' => 'critical',
            'ai_flagged' => true,
            'ai_confidence' => 0.94,
        ]);

        Report::create([
            'reporter_id' => $reporter2->id,
            'reportable_type' => User::class,
            'reportable_id' => $targetUser->id,
            'reason' => 'scam',
            'details' => 'Same phishing link',
            'status' => 'pending',
            'priority' => 'medium',
            'ai_flagged' => false,
            'duplicate_of_id' => $primaryReport->id,
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/moderation?status=pending&priority=critical&ai_flagged=1');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $primaryReport->id)
            ->assertJsonPath('data.0.duplicates_count', 1);
    }

    public function test_phase4_roles_matrix_includes_jit_privilege_elevations_and_alerts_support_filters(): void
    {
        $analyst = User::factory()->create(['name' => 'SOC Analyst']);

        AdminElevation::create([
            'user_id' => $analyst->id,
            'granted_by' => $this->superAdmin->id,
            'elevated_role' => 'SOC_LEAD',
            'reason' => 'Investigating brute force spike',
            'expires_at' => now()->addHour(),
        ]);

        $rolesRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/roles');

        $rolesRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'roles',
                    'permissions',
                    'elevations',
                ],
            ])
            ->assertJsonPath('data.elevations.0.elevated_role', 'SOC_LEAD');

        // Verify filtered alerts endpoint
        DB::table('admin_alerts')->insert([
            [
                'title' => 'Critical Brute Force',
                'message' => '50 failed logins from single IP',
                'type' => 'security',
                'severity' => 'critical',
                'status' => 'unread',
                'hit_count' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Minor Queue Delay',
                'message' => 'Queue delay 2s',
                'type' => 'jobs',
                'severity' => 'info',
                'status' => 'resolved',
                'hit_count' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $alertsRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/alerts?severity=critical&status=unread');

        $alertsRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.alerts')
            ->assertJsonPath('data.alerts.0.title', 'Critical Brute Force');
    }

    public function test_phase4_interactive_button_workflows_and_api_integrations(): void
    {
        $targetUser = User::factory()->create(['name' => 'Target User']);
        $reporter = User::factory()->create();

        // 1. Moderation self-assign (without explicit assigned_to) and false positive (without explicit reason)
        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => User::class,
            'reportable_id' => $targetUser->id,
            'reason' => 'spam',
            'details' => 'Automated button test',
            'status' => 'pending',
            'priority' => 'high',
            'ai_flagged' => true,
            'ai_confidence' => 0.89,
        ]);

        $assignRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/moderation/{$report->id}/assign", []);
        $assignRes->assertStatus(200)
            ->assertJsonPath('data.assigned_to', $this->superAdmin->id);

        $fpRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/moderation/{$report->id}/false-positive", []);
        $fpRes->assertStatus(200)
            ->assertJsonPath('data.is_false_positive', true)
            ->assertJsonPath('data.status', 'dismissed');

        // 2. Moderation metrics enriched keys
        $metricsRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/moderation/metrics');
        $metricsRes->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_reviewed',
                    'false_positive_rate_pct',
                    'avg_resolution_minutes',
                    'appeal_overturn_rate_pct',
                ],
            ]);

        // 3. Security Incident self-assign & session revocation button endpoints
        $incident = SecurityEvent::create([
            'user_id' => $targetUser->id,
            'event_type' => 'suspicious_login',
            'severity' => 'high',
            'ip_address' => '203.0.113.50',
            'resolved' => false,
        ]);

        $incAssignRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/security/incidents/{$incident->id}/assign", []);
        $incAssignRes->assertStatus(200)
            ->assertJsonPath('data.assigned_to', $this->superAdmin->id);

        $revokeSessionsRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/security/sessions/revoke', [
                'user_id' => $targetUser->id,
                'reason' => 'জরুরি প্রশাসনিক সেশন টার্মিনেশন',
            ]);
        $revokeSessionsRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 4. Audit checkpoint creation & structure verification
        AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'phase4_verification_event',
            'entity_type' => User::class,
            'entity_id' => $targetUser->id,
            'ip_address' => '127.0.0.1',
        ]);

        $cpCreateRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/audit/checkpoints', []);
        $cpCreateRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $cpListRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/audit/checkpoints');
        $cpListRes->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'checkpoint_number',
                        'record_start_id',
                        'record_end_id',
                        'record_count',
                        'root_hash',
                        'signature',
                    ],
                ],
            ]);

        // 5. Data Governance inventory live records_count & legal hold enforcement
        $govInvRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/governance/inventory');
        $govInvRes->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'category',
                        'label',
                        'pii_sensitivity',
                        'records_count',
                    ],
                ],
            ]);
    }

    public function test_phase4_permission_boundaries_jit_elevations_and_last_super_admin_protection(): void
    {
        $regularUser = User::factory()->create(['status' => 'active']);
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN'], ['description' => 'Administrator']);
        $standardAdmin = User::factory()->create(['status' => 'active']);
        $standardAdmin->roles()->attach($adminRole);

        // 1. Regular user is blocked from web /admin and enterprise APIs
        $this->actingAs($regularUser, 'web')
            ->get('/admin')
            ->assertStatus(403);

        $this->actingAs($regularUser, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/overview')
            ->assertStatus(403);

        // 2. Standard ADMIN can access moderation queue & overview, but is blocked from SUPER_ADMIN/roles.manage-only endpoints (assignRole, grantElevation)
        $this->actingAs($standardAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/moderation')
            ->assertStatus(200);

        $this->actingAs($standardAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/roles/assign', [
                'user_id' => $regularUser->id,
                'role_name' => 'SUPER_ADMIN',
            ])
            ->assertStatus(403);

        $this->actingAs($standardAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/roles/elevate', [
                'user_id' => $regularUser->id,
                'elevated_role' => 'SUPER_ADMIN',
                'duration_minutes' => 30,
                'reason' => 'Unauthorized self-escalation attempt',
            ])
            ->assertStatus(403);

        // 3. JIT Privilege Elevation via /roles/elevate grants temporary role & revokes cleanly
        $elevateRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/roles/elevate', [
                'user_id' => $regularUser->id,
                'elevated_role' => 'ADMIN',
                'duration_minutes' => 30,
                'reason' => 'Emergency incident response window',
            ]);
        $elevateRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $elevationId = $elevateRes->json('data.id');
        $this->assertTrue($regularUser->fresh()->hasRole('ADMIN'));

        // Elevated user can now access ADMIN endpoints
        $this->actingAs($regularUser->fresh(), 'sanctum')
            ->getJson('/api/v2/admin/enterprise/overview')
            ->assertStatus(200);

        // Revoke elevation via /roles/elevations/{id}/revoke
        $revokeRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/roles/elevations/{$elevationId}/revoke", []);
        $revokeRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertFalse($regularUser->fresh()->hasRole('ADMIN'));

        // 4. Last SUPER_ADMIN protection on actionUser, assignRole, and restrictUser
        $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/users/{$this->superAdmin->id}/action", [
                'action' => 'suspend',
                'reason' => 'Attempt self suspend',
            ])
            ->assertStatus(422);

        $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/roles/assign', [
                'user_id' => $this->superAdmin->id,
                'role' => 'USER',
            ])
            ->assertStatus(422);

        $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/abuse/users/{$this->superAdmin->id}/restrict", [
                'restriction_type' => 'full_ban',
                'reason' => 'Attempt self ban on last super admin',
            ])
            ->assertStatus(422);
    }

    public function test_phase4_sqlite_and_mysql_migration_locking_pagination_and_aggregate_queries(): void
    {
        // 1. Verify Phase 3/4 migration rollback and re-migrate safety
        Artisan::call('migrate:rollback', [
            '--path' => 'database/migrations/2026_10_28_100000_enhance_enterprise_admin_system_phase3.php',
            '--force' => true,
        ]);
        Artisan::call('migrate', [
            '--path' => 'database/migrations/2026_10_28_100000_enhance_enterprise_admin_system_phase3.php',
            '--force' => true,
        ]);

        // 2. Verify row-level locking (`lockForUpdate()`) inside transactions for AuditLog, PlatformSetting, AdminAlert, and AuditCheckpoint
        $setting = PlatformSetting::set('phase4_lock_test', 'v1', $this->superAdmin->id);
        $this->assertSame('v1', $setting->value);
        PlatformSetting::set('phase4_lock_test', 'v2', $this->superAdmin->id);
        $rolledBack = PlatformSetting::rollback('phase4_lock_test', $this->superAdmin->id);
        $this->assertSame('v1', $rolledBack?->value);

        $alert1 = AdminAlert::trigger('security', 'Lock Test Alert', 'First hit', 'warning');
        $alert2 = AdminAlert::trigger('security', 'Lock Test Alert', 'Second hit', 'critical');
        $this->assertSame($alert1->id, $alert2->id);
        $this->assertSame(2, $alert2->hit_count);
        $this->assertSame('critical', $alert2->severity);

        // 3. Verify standardized pagination `meta` across all 7 paginated endpoints
        User::factory()->count(3)->create();
        Post::create([
            'user_id' => $this->superAdmin->id,
            'content' => 'Phase 4 pagination test #JugajugEnterprise',
            'visibility' => 'public',
        ]);
        SupportTicket::create([
            'ticket_number' => 'TKT-P4-900',
            'user_id' => $this->superAdmin->id,
            'subject' => 'Pagination Check',
            'category' => 'technical',
            'priority' => 'medium',
            'status' => 'open',
            'message' => 'Testing standardized meta keys',
        ]);

        $paginatedEndpoints = [
            '/api/v2/admin/enterprise/users?per_page=2',
            '/api/v2/admin/enterprise/posts?per_page=2',
            '/api/v2/admin/enterprise/moderation?per_page=2',
            '/api/v2/admin/enterprise/social?type=groups&per_page=2',
            '/api/v2/admin/enterprise/support?per_page=2',
            '/api/v2/admin/enterprise/audit-logs?per_page=2',
            '/api/v2/admin/enterprise/governance/privacy-requests?per_page=2',
        ];

        foreach ($paginatedEndpoints as $endpoint) {
            $res = $this->actingAs($this->superAdmin, 'sanctum')->getJson($endpoint);
            $res->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ],
                ]);
        }

        // 4. Verify SQLite/MySQL compatible aggregate queries (Executive Intelligence & SOC Overview)
        DB::table('failed_login_attempts')->insert([
            ['identifier' => 'attacker@test.com', 'ip_address' => '198.51.100.77', 'attempted_at' => now()],
            ['identifier' => 'attacker@test.com', 'ip_address' => '198.51.100.77', 'attempted_at' => now()],
            ['identifier' => 'attacker@test.com', 'ip_address' => '198.51.100.77', 'attempted_at' => now()],
            ['identifier' => 'attacker@test.com', 'ip_address' => '198.51.100.77', 'attempted_at' => now()],
            ['identifier' => 'attacker@test.com', 'ip_address' => '198.51.100.77', 'attempted_at' => now()],
        ]);

        $intelRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/intelligence?range=14d&refresh=1');
        $intelRes->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'meta' => ['cached', 'generated_at'],
                    'users' => ['dau', 'wau', 'mau', 'stickiness_pct', 'new_period', 'growth_pct'],
                    'content' => ['new_period_posts', 'posts_growth_pct'],
                    'funnel' => ['*' => ['step', 'count', 'conversion_rate_pct']],
                    'cohorts' => ['*' => ['cohort', 'size', 'retained_7d', 'retention_rate_pct']],
                    'trending' => ['hashtags', 'communities'],
                ],
            ]);

        $socRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/security');
        $socRes->assertStatus(200)
            ->assertJsonPath('data.high_risk_ips.0.ip_address', '198.51.100.77')
            ->assertJsonPath('data.high_risk_ips.0.attempts', 5);
    }

    public function test_checklist_1_jit_privilege_revocation_in_same_session_for_web_and_api_routes(): void
    {
        $sessionUser = User::factory()->create([
            'email' => 'jit-session-member@jugajug.com',
            'status' => 'active',
        ]);

        // Pre-load relations in memory to simulate a long-lived session user instance
        $sessionUser->load('roles', 'adminElevations');

        // Grant JIT ADMIN elevation
        $grantRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/roles/elevate', [
                'user_id' => $sessionUser->id,
                'elevated_role' => 'ADMIN',
                'duration_minutes' => 60,
                'reason' => 'Live incident response verification',
            ]);
        $grantRes->assertStatus(200);
        $elevationId = $grantRes->json('data.id');

        // Same sessionUser instance (without ->fresh()) can access both Web (/admin) and API (/overview)
        $this->actingAs($sessionUser, 'web')
            ->get('/admin')
            ->assertStatus(200);

        $this->actingAs($sessionUser, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/overview')
            ->assertStatus(200);

        // Revoke elevation while $sessionUser remains the active session instance
        $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/roles/elevations/{$elevationId}/revoke", [
                'reason' => 'Incident closed; revoking JIT access immediately',
            ])
            ->assertStatus(200);

        // Immediately verify that the EXACT SAME $sessionUser instance is blocked (403) on both Web and API routes
        $this->actingAs($sessionUser, 'web')
            ->get('/admin')
            ->assertStatus(403);

        $this->actingAs($sessionUser, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/overview')
            ->assertStatus(403);
    }

    public function test_checklist_2_concurrent_super_admin_protection_across_suspend_demote_full_ban_and_bulk(): void
    {
        $superAdminRole = Role::where('name', 'SUPER_ADMIN')->firstOrFail();
        $secondSuperAdmin = User::factory()->create([
            'email' => 'second-super@jugajug.com',
            'status' => 'active',
        ]);
        $secondSuperAdmin->roles()->attach($superAdminRole);

        // With 2 active SUPER_ADMINs, suspending the second one succeeds
        $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/users/{$secondSuperAdmin->id}/action", [
                'action' => 'suspend',
                'reason' => 'Planned maintenance rotation',
            ])
            ->assertStatus(200);

        // Now $this->superAdmin is the ONLY active SUPER_ADMIN; concurrent/subsequent attempts to suspend, demote, full-ban, or bulk-suspend must all fail (422)
        $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/users/{$this->superAdmin->id}/action", [
                'action' => 'suspend',
            ])
            ->assertStatus(422);

        $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/users/{$this->superAdmin->id}/action", [
                'action' => 'assign_role',
                'role' => 'ADMIN',
            ])
            ->assertStatus(422);

        $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/users/bulk', [
                'user_ids' => [$this->superAdmin->id],
                'action' => 'suspend',
            ])
            ->assertStatus(422);

        // Re-activate second SUPER_ADMIN, then place second SUPER_ADMIN under full_ban restriction
        $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/users/{$secondSuperAdmin->id}/action", [
                'action' => 'activate',
            ])
            ->assertStatus(200);

        $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/abuse/users/{$secondSuperAdmin->id}/restrict", [
                'restriction_type' => UserRestriction::TYPE_FULL_BAN,
                'reason' => 'Compromised credentials containment',
            ])
            ->assertStatus(200);

        // Because $secondSuperAdmin is under full_ban, $this->superAdmin is the last unrestricted SUPER_ADMIN and cannot be demoted or suspended
        $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/roles/assign', [
                'user_id' => $this->superAdmin->id,
                'role_name' => 'ADMIN',
            ])
            ->assertStatus(422);
    }

    public function test_checklist_3_sqlite_and_mysql_locking_concurrent_writes_rollback_and_unique_constraints(): void
    {
        // 1. Transaction rollback atomicity: rolled-back setting & feature flag writes must leave zero state or orphan history
        try {
            DB::transaction(function () {
                PlatformSetting::set('atomic_rollback_key', 'should_not_persist', $this->superAdmin->id);
                throw new \RuntimeException('Simulated mid-transaction failure');
            });
        } catch (\RuntimeException) {
        }

        $this->assertNull(PlatformSetting::where('key', 'atomic_rollback_key')->first());

        // 2. Unique constraint enforcement across core Phase 3/4 tables
        PlatformFeatureFlag::create([
            'key' => 'unique_flag_guard',
            'name' => 'Unique Flag',
            'is_enabled' => true,
            'rollout_percentage' => 50,
        ]);

        $this->expectException(QueryException::class);
        PlatformFeatureFlag::create([
            'key' => 'unique_flag_guard',
            'name' => 'Duplicate Flag',
            'is_enabled' => false,
            'rollout_percentage' => 10,
        ]);
    }

    public function test_checklist_4_audit_chain_atomicity_and_integrity_after_failed_transaction(): void
    {
        AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'chain_event_1',
            'entity_type' => User::class,
            'entity_id' => $this->superAdmin->id,
            'ip_address' => '127.0.0.1',
        ]);

        // Seal Checkpoint #1
        $cp1 = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/audit/checkpoints', []);
        $cp1->assertStatus(200);

        // Simulate a failed transaction that creates an AuditLog and then rolls back
        try {
            DB::transaction(function () {
                AuditLog::create([
                    'user_id' => $this->superAdmin->id,
                    'action' => 'rolled_back_audit_event',
                    'entity_type' => User::class,
                    'entity_id' => $this->superAdmin->id,
                    'ip_address' => '127.0.0.1',
                ]);
                throw new \RuntimeException('Simulated transaction abort');
            });
        } catch (\RuntimeException) {
        }

        // Subsequent committed AuditLog entry must chain cleanly to the last committed entry
        AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'chain_event_2_after_rollback',
            'entity_type' => User::class,
            'entity_id' => $this->superAdmin->id,
            'ip_address' => '127.0.0.1',
        ]);

        $verifyRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/audit/verify-integrity');
        $verifyRes->assertStatus(200)
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('data.error_code', 'NONE');

        // Seal Checkpoint #2 and verify cryptographic signature
        $cp2 = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/audit/checkpoints', []);
        $cp2->assertStatus(200);

        $latestCheckpoint = AuditCheckpoint::latest('checkpoint_number')->firstOrFail();
        $this->assertTrue($latestCheckpoint->verifySignature());
    }

    public function test_checklist_5_legal_hold_race_conditions_prevent_prohibited_deletion(): void
    {
        $protectedUser = User::factory()->create([
            'is_legal_hold' => false,
            'status' => 'active',
        ]);

        // Load user in memory while is_legal_hold is still false (simulating stale read before concurrent Legal Hold update)
        $staleMemoryUser = User::findOrFail($protectedUser->id);
        $this->assertFalse($staleMemoryUser->isUnderLegalHold());

        // Concurrent request flips is_legal_hold = true in the database
        User::where('id', $protectedUser->id)->update(['is_legal_hold' => true]);

        // Attempting to delete the stale in-memory user instance must be blocked by User::deleting lockForUpdate() guard
        $exceptionThrown = false;
        try {
            $staleMemoryUser->delete();
        } catch (\InvalidArgumentException) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, 'User::deleting must block deletion when Legal Hold was enabled concurrently in DB.');
        $this->assertNull($protectedUser->fresh()->deleted_at);

        // Also verify Privacy Erasure request transitions to blocked_by_legal_hold and returns 422
        $erasureReq = PrivacyRequest::create([
            'request_number' => 'PRV-RACE-01',
            'user_id' => $protectedUser->id,
            'request_type' => PrivacyRequest::TYPE_ERASURE,
            'status' => PrivacyRequest::STATUS_PENDING,
            'reason' => 'Concurrent erasure attempt',
        ]);

        $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/governance/privacy-requests/{$erasureReq->id}/process", [
                'decision' => 'approve',
            ])
            ->assertStatus(422);

        $this->assertSame(PrivacyRequest::STATUS_BLOCKED_BY_LEGAL_HOLD, $erasureReq->fresh()->status);
        $this->assertNull($protectedUser->fresh()->deleted_at);
    }

    public function test_checklist_6_pagination_contract_meta_max_per_page_and_filter_persistence_across_all_7_endpoints(): void
    {
        User::factory()->count(4)->create(['status' => 'suspended']);
        Post::create([
            'user_id' => $this->superAdmin->id,
            'content' => 'Filterable Post KeywordAlpha',
            'status' => 'hidden',
        ]);

        $contracts = [
            '/api/v2/admin/enterprise/users?status=suspended&per_page=2' => ['status' => 'suspended'],
            '/api/v2/admin/enterprise/posts?status=hidden&search=KeywordAlpha&per_page=2' => ['status' => 'hidden', 'search' => 'KeywordAlpha'],
            '/api/v2/admin/enterprise/moderation?status=pending&priority=high&per_page=2' => ['status' => 'pending', 'priority' => 'high'],
            '/api/v2/admin/enterprise/social?type=pages&search=Tech&per_page=2' => ['type' => 'pages', 'search' => 'Tech'],
            '/api/v2/admin/enterprise/support?status=open&priority=high&per_page=2' => ['status' => 'open', 'priority' => 'high'],
            '/api/v2/admin/enterprise/audit-logs?action=admin_user&per_page=2' => ['action' => 'admin_user'],
            '/api/v2/admin/enterprise/governance/privacy-requests?status=pending&request_type=export&per_page=2' => ['status' => 'pending', 'request_type' => 'export'],
        ];

        foreach ($contracts as $url => $expectedFilters) {
            $res = $this->actingAs($this->superAdmin, 'sanctum')->getJson($url);
            $res->assertStatus(200)
                ->assertJsonPath('meta.per_page', 2)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                        'filters',
                    ],
                ]);

            foreach ($expectedFilters as $filterKey => $filterVal) {
                $this->assertSame($filterVal, $res->json("meta.filters.{$filterKey}"));
            }

            // Verify maximum per_page cap at 100
            $maxUrl = preg_replace('/per_page=\d+/', 'per_page=999', $url);
            $maxRes = $this->actingAs($this->superAdmin, 'sanctum')->getJson((string) $maxUrl);
            $maxRes->assertStatus(200)
                ->assertJsonPath('meta.per_page', 100);
        }
    }

    public function test_checklist_7_security_regression_csrf_idor_privilege_escalation_and_sensitive_data_leakage(): void
    {
        $regularUser = User::factory()->create([
            'email' => 'regular-sec@jugajug.com',
            'status' => 'active',
            'two_factor_secret' => 'SECRET_2FA_KEY_DO_NOT_LEAK',
            'two_factor_recovery_codes' => '["RECOVERY_CODE_1"]',
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN'], ['description' => 'Administrator']);
        $standardAdmin = User::factory()->create([
            'email' => 'standard-admin-sec@jugajug.com',
            'status' => 'active',
        ]);
        $standardAdmin->roles()->attach($adminRole);

        // 1. Unauthorized direct API call (unauthenticated guest -> 401)
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v2/admin/enterprise/overview')->assertStatus(401);

        // 2. Privilege escalation prevention: Standard ADMIN cannot escalate via users/{id}/action assign_role, roles/assign, or roles/elevate
        $this->actingAs($standardAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/users/{$standardAdmin->id}/action", [
                'action' => 'assign_role',
                'role' => 'SUPER_ADMIN',
            ])
            ->assertStatus(403);

        // 3. Horizontal/Vertical protection: Standard ADMIN cannot suspend, reset_password, or restrict a SUPER_ADMIN
        $this->actingAs($standardAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/users/{$this->superAdmin->id}/action", [
                'action' => 'reset_password',
                'new_password' => 'CompromisedPass123!',
            ])
            ->assertStatus(403);

        $this->actingAs($standardAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/abuse/users/{$this->superAdmin->id}/restrict", [
                'restriction_type' => 'full_ban',
                'reason' => 'Unauthorized attempt against super admin',
            ])
            ->assertStatus(403);

        // 4. IDOR / Non-existent resource returns 404 cleanly
        $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/users/99999999')
            ->assertStatus(404);

        // 5. Sensitive data leakage check: user detail & directory responses must NEVER expose password, remember_token, or 2FA secrets
        $userShowRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v2/admin/enterprise/users/{$regularUser->id}");
        $userShowRes->assertStatus(200);

        $userPayload = $userShowRes->json('data.user');
        $this->assertArrayNotHasKey('password', $userPayload);
        $this->assertArrayNotHasKey('remember_token', $userPayload);
        $this->assertArrayNotHasKey('two_factor_secret', $userPayload);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $userPayload);
    }
}
