<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PhotoAlbum;
use App\Models\PhotoAlbumItem;
use App\Models\Report;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnterpriseUserProfileUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'name' => 'Abdur Rahim',
            'username' => 'rahim',
            'email' => 'rahim@jugajug.com',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        UserProfile::create([
            'user_id' => $this->userA->id,
            'bio' => 'Senior Developer at Jugajug',
            'headline' => 'Fullstack Artisan',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'website' => 'https://jugajug.com',
        ]);

        $this->userB = User::factory()->create([
            'name' => 'Nusrat Jahan',
            'username' => 'nusrat',
            'email' => 'nusrat@jugajug.com',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        UserProfile::create([
            'user_id' => $this->userB->id,
            'bio' => 'Product Designer',
            'city' => 'Sylhet',
            'country' => 'Bangladesh',
        ]);
    }

    /**
     * Requirement 30: User can export full profile data package.
     */
    public function test_user_can_export_complete_profile_data(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->getJson('/api/v2/profile/export');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'export_metadata' => ['app_name', 'exported_at', 'user_id', 'username', 'version'],
                    'user' => ['name', 'username', 'email', 'status'],
                    'profile' => ['bio', 'headline', 'city', 'country'],
                    'posts',
                    'friends',
                    'followers',
                    'following',
                    'activity_logs',
                ],
            ]);

        $this->assertSame('rahim', $response->json('data.user.username'));

        // Test download stream format
        $downloadResponse = $this->get('/api/v2/profile/export?download=1');
        $downloadResponse->assertStatus(200);
        $this->assertStringContainsString('attachment; filename="jugajug_export_rahim_', (string) $downloadResponse->headers->get('content-disposition'));
    }

    /**
     * Requirement 26: User cannot access or export another user's private data.
     */
    public function test_unauthenticated_user_cannot_export_data(): void
    {
        $response = $this->getJson('/api/v2/profile/export');
        $response->assertStatus(401);
    }

    /**
     * Requirement 20: User can view and update notification preferences.
     */
    public function test_user_can_view_and_update_notification_settings(): void
    {
        Sanctum::actingAs($this->userA);

        $getRes = $this->getJson('/api/v2/profile/notifications/settings');
        $getRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $this->userA->id);

        $updateRes = $this->putJson('/api/v2/profile/notifications/settings', [
            'email_notifications' => false,
            'sms_notifications' => true,
            'friend_request_alerts' => false,
            'security_alerts' => true,
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email_notifications', false)
            ->assertJsonPath('data.sms_notifications', true)
            ->assertJsonPath('data.friend_request_alerts', false)
            ->assertJsonPath('data.security_alerts', true);

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $this->userA->id,
            'email_notifications' => false,
            'sms_notifications' => true,
        ]);
    }

    /**
     * Requirement 13: Self-hosted dynamic SVG QR Code endpoint.
     */
    public function test_qr_code_endpoint_returns_svg_vector(): void
    {
        $response = $this->get("/api/v2/profile/{$this->userA->username}/qrcode");

        $response->assertStatus(200);
        $this->assertSame('image/svg+xml', $response->headers->get('content-type'));
        $this->assertStringContainsString('<svg', $response->getContent());
        $this->assertStringContainsString('</svg>', $response->getContent());
    }

    /**
     * Requirement 19: Block and unblock another user.
     */
    public function test_user_can_block_and_unblock_another_user(): void
    {
        Sanctum::actingAs($this->userA);

        // Block User B
        $blockRes = $this->postJson("/api/v2/profile/{$this->userB->username}/block");
        $blockRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.blocked', true);

        // Verify friendship status is blocked
        $this->assertDatabaseHas('friendships', [
            'user_id' => $this->userA->id,
            'friend_id' => $this->userB->id,
            'status' => 'blocked',
        ]);

        // Unblock User B
        $unblockRes = $this->postJson("/api/v2/profile/{$this->userB->username}/unblock");
        $unblockRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unblocked', true);
    }

    /**
     * Requirement 19: Restrict user.
     */
    public function test_user_can_restrict_another_user(): void
    {
        Sanctum::actingAs($this->userA);

        $restrictRes = $this->postJson("/api/v2/profile/{$this->userB->username}/restrict");
        $restrictRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_restricted', true);

        // Toggle back
        $unrestrictRes = $this->postJson("/api/v2/profile/{$this->userB->username}/restrict");
        $unrestrictRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_restricted', false);
    }

    /**
     * Requirement 19: Report a user profile.
     */
    public function test_user_can_report_another_user(): void
    {
        Sanctum::actingAs($this->userA);

        $reportRes = $this->postJson("/api/v2/profile/{$this->userB->username}/report", [
            'reason' => 'spam',
            'details' => 'Suspicious automated activity detected.',
        ]);

        $reportRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $this->userA->id,
            'reportable_id' => $this->userB->id,
            'reason' => 'spam',
            'status' => Report::STATUS_PENDING,
        ]);
    }

    /**
     * Requirement 29: Account Deactivation.
     */
    public function test_user_can_deactivate_account(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson('/api/v2/profile/deactivate', [
            'reason' => 'Taking a break',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'deactivated');

        $this->assertDatabaseHas('users', [
            'id' => $this->userA->id,
            'status' => 'deactivated',
        ]);
    }

    /**
     * Requirement 29: Account Deletion (Soft delete with password & confirmation).
     */
    public function test_user_can_delete_account_with_confirmation(): void
    {
        Sanctum::actingAs($this->userB);

        $response = $this->postJson('/api/v2/profile/delete', [
            'password' => 'Secret123!',
            'confirmation' => 'DELETE',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'deleted');

        $this->assertSoftDeleted('users', [
            'id' => $this->userB->id,
        ]);
    }

    /**
     * Requirement 8: Photo Album update and item deletion.
     */
    public function test_album_can_be_updated_and_photo_deleted(): void
    {
        Sanctum::actingAs($this->userA);

        $album = PhotoAlbum::create([
            'user_id' => $this->userA->id,
            'title' => 'Vacation 2026',
            'description' => 'Coxs Bazar trip',
            'privacy' => 'public',
        ]);

        $item = PhotoAlbumItem::create([
            'album_id' => $album->id,
            'media_url' => 'albums/photo1.jpg',
            'display_order' => 1,
        ]);

        // Update album
        $updateRes = $this->putJson("/api/v1/profile/albums/{$album->id}", [
            'title' => 'Updated Vacation 2026',
            'privacy' => 'friends',
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'Updated Vacation 2026')
            ->assertJsonPath('data.privacy', 'friends');

        // Delete photo item
        $deleteItemRes = $this->deleteJson("/api/v1/profile/albums/{$album->id}/items/{$item->id}");
        $deleteItemRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('photo_album_items', ['id' => $item->id]);
    }

    /**
     * Requirement 1 & 21: Profile web page renders properly with redesigned header.
     */
    public function test_profile_web_page_renders_with_redesigned_header(): void
    {
        $this->actingAs($this->userA, 'web');

        $response = $this->get("/user/{$this->userA->username}");

        $response->assertStatus(200);
        $response->assertSee('Abdur Rahim');
        $response->assertSee('@rahim');
        $response->assertSee('Senior Developer at Jugajug');
        $response->assertSee('Dhaka, Bangladesh');
        $response->assertSee('প্রোফাইল ডেটা এক্সপোর্ট');
        $response->assertSee('নোটিফিকেশন সেটিংস');
    }
}
