<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\SocSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AuditLogTest — অডিট লগ ও সিকিউরিটি ট্র্যাকিং টেস্ট
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * এসওসি সার্ভিস অডিট লগ ডাটাবেজে সংরক্ষণ করে।
     */
    public function test_soc_service_records_audit_log_entry(): void
    {
        $user = User::factory()->create();
        $service = app(SocSecurityService::class);

        $log = $service->logAction(
            userId: $user->id,
            action: 'password_change',
            entityType: 'User',
            entityId: $user->id,
            oldValues: ['email' => 'old@jugajug.com'],
            newValues: ['email' => 'new@jugajug.com'],
            ip: '192.168.1.50',
            userAgent: 'TestBrowser'
        );

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals('password_change', $log->action);
        $this->assertEquals($user->id, $log->user_id);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'action' => 'password_change',
        ]);
    }

    /**
     * সিকিউরিটি ইভেন্ট এপিআই সফলভাবে ইভেন্ট ডাটা রিটার্ন করে।
     */
    public function test_admin_can_view_security_events(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/security/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ]);
    }
}
