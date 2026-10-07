<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BackupSystemTest — ব্যাকআপ তৈরি ও ম্যানেজমেন্ট টেস্ট
 */
class BackupSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ব্যাকআপ সার্ভিস এনক্রিপ্টেড ব্যাকআপ তৈরি ও ডাটাবেজে সংরক্ষণ নিশ্চিত করে।
     */
    public function test_backup_service_creates_encrypted_backup_record(): void
    {
        $service = app(BackupService::class);
        $backup = $service->createBackup('full', 'local');

        $this->assertInstanceOf(Backup::class, $backup);
        $this->assertEquals('completed', $backup->status);
        $this->assertTrue($backup->encrypted);
        $this->assertNotEmpty($backup->checksum);
        $this->assertGreaterThan(0, $backup->size_bytes);

        $this->assertDatabaseHas('backups', [
            'id' => $backup->id,
            'status' => 'completed',
        ]);
    }

    /**
     * এপিআই এর মাধ্যমে অ্যাডমিন ব্যাকআপ ট্রিগার করা যাচাই।
     */
    public function test_admin_can_run_backup_via_api(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/admin/backups/run', [
            'type' => 'db',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'type' => 'db',
                    'status' => 'completed',
                ],
            ]);
    }

    /**
     * ব্যাকআপ আর্ট্টিজান কমান্ড নির্বিঘ্নে সম্পন্ন হওয়া যাচাই।
     */
    public function test_backup_artisan_command_executes_successfully(): void
    {
        $this->artisan('jugajug:backup', [
            '--type' => 'media',
            '--disk' => 'local',
        ])
            ->expectsOutputToContain('media ব্যাকআপ তৈরি শুরু হচ্ছে')
            ->expectsOutputToContain('ব্যাকআপ সফল!')
            ->assertExitCode(0);
    }
}
