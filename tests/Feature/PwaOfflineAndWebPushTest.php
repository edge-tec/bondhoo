<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaOfflineAndWebPushTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create([
            'name' => 'তানভীর ওয়েব ইউজার',
            'username' => 'tanvir_web',
        ]);
    }

    public function test_manifest_json_is_valid_and_has_standalone_and_shortcuts(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $content = file_get_contents($manifestPath);
        $json = json_decode($content, true);

        $this->assertIsArray($json);
        $this->assertEquals('Bondhoo', $json['short_name']);
        $this->assertEquals('standalone', $json['display']);
        $this->assertArrayHasKey('shortcuts', $json);
        $this->assertNotEmpty($json['shortcuts']);
        $this->assertArrayHasKey('icons', $json);
        $this->assertNotEmpty($json['icons']);
    }

    public function test_service_worker_file_contains_offline_caching_and_web_push_listeners(): void
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);

        $swContent = file_get_contents($swPath);
        $this->assertStringContainsString('bondhoo-pwa-v3.0', $swContent);
        $this->assertStringContainsString('/offline', $swContent);
        $this->assertStringContainsString("self.addEventListener('push'", $swContent);
        $this->assertStringContainsString("self.addEventListener('notificationclick'", $swContent);
        $this->assertStringContainsString('showNotification', $swContent);
    }

    public function test_offline_fallback_page_is_accessible_and_renders_bengali_ui(): void
    {
        $response = $this->get('/offline');

        $response->assertStatus(200)
            ->assertSee('ইন্টারনেট সংযোগ বিচ্ছিন্ন')
            ->assertSee('পুনরায় চেষ্টা করুন');
    }

    public function test_web_browser_device_can_register_push_notification_token(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/devices/register', [
                'device_id' => 'browser_chrome_desktop_102',
                'device_name' => 'Chrome on macOS',
                'platform' => 'web',
                'app_version' => '3.0.0',
                'push_token' => 'fcm_web_push_subscription_sample_token_xyz_123',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('device_sessions', [
            'user_id' => $this->user->id,
            'device_id' => 'browser_chrome_desktop_102',
            'platform' => 'web',
            'push_token' => 'fcm_web_push_subscription_sample_token_xyz_123',
            'is_revoked' => false,
        ]);
    }
}
