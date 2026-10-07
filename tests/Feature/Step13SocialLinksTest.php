<?php

namespace Tests\Feature;

use App\Events\ProfileUpdatedEvent;
use App\Models\Friendship;
use App\Models\ProfileSocialLink;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Step13SocialLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Test user can add links for all requested providers with automatic URL normalization.
     */
    public function test_user_can_add_all_supported_social_platforms(): void
    {
        Event::fake([ProfileUpdatedEvent::class]);

        $user = User::factory()->create(['username' => 'social_pro']);

        $platforms = [
            ['platform' => 'Website', 'url' => 'https://mywebsite.com'],
            ['platform' => 'Facebook', 'url' => 'facebook.com/myprofile'],
            ['platform' => 'Instagram', 'url' => 'instagram.com/myhandle'],
            ['platform' => 'YouTube', 'url' => 'youtube.com/@mychannel'],
            ['platform' => 'LinkedIn', 'url' => 'linkedin.com/in/myname'],
            ['platform' => 'X', 'url' => 'x.com/myhandle'],
            ['platform' => 'TikTok', 'url' => 'tiktok.com/@myhandle'],
            ['platform' => 'GitHub', 'url' => 'github.com/mycode'],
        ];

        foreach ($platforms as $item) {
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/v2/profile/social-links', $item);

            $response->assertStatus(201)
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.platform', strtolower($item['platform']))
                ->assertJsonPath('data.is_visible', true)
                ->assertJsonPath('data.privacy', 'public');

            // Verify URL was auto-normalized with https://
            $this->assertStringStartsWith('https://', $response->json('data.url'));
        }

        $this->assertEquals(8, $user->socialLinks()->count());
        Event::assertDispatched(ProfileUpdatedEvent::class);
    }

    /**
     * Test malicious schemes like javascript:, data:, vbscript: are strictly rejected.
     */
    public function test_malicious_urls_are_strictly_rejected(): void
    {
        $user = User::factory()->create();

        $maliciousUrls = [
            'javascript:alert(document.cookie)',
            'JAVASCRIPT:void(0)',
            '  javascript:alert(1)  ',
            'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
            'vbscript:msgbox("hacked")',
            'file:///etc/passwd',
            'blob:https://example.com/uuid',
            'https://example.com/<script>alert(1)</script>',
        ];

        foreach ($maliciousUrls as $badUrl) {
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/v2/profile/social-links', [
                    'platform' => 'website',
                    'url' => $badUrl,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['url']);
        }
    }

    /**
     * Test user can edit their social link.
     */
    public function test_user_can_edit_social_link(): void
    {
        $user = User::factory()->create();
        $link = ProfileSocialLink::create([
            'user_id' => $user->id,
            'platform' => 'github',
            'url' => 'https://github.com/oldhandle',
            'is_visible' => true,
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v2/profile/social-links/{$link->id}", [
                'url' => 'https://github.com/newhandle',
                'is_visible' => false,
                'privacy' => 'friends',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.url', 'https://github.com/newhandle')
            ->assertJsonPath('data.is_visible', false)
            ->assertJsonPath('data.privacy', 'friends');

        $this->assertDatabaseHas('profile_social_links', [
            'id' => $link->id,
            'url' => 'https://github.com/newhandle',
            'is_visible' => 0,
            'privacy' => 'friends',
        ]);
    }

    /**
     * Test user can delete their social link.
     */
    public function test_user_can_delete_social_link(): void
    {
        $user = User::factory()->create();
        $link = ProfileSocialLink::create([
            'user_id' => $user->id,
            'platform' => 'linkedin',
            'url' => 'https://linkedin.com/in/myhandle',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v2/profile/social-links/{$link->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('profile_social_links', [
            'id' => $link->id,
        ]);
    }

    /**
     * Test reordering social links.
     */
    public function test_user_can_reorder_social_links(): void
    {
        $user = User::factory()->create();

        $l1 = ProfileSocialLink::create(['user_id' => $user->id, 'platform' => 'github', 'url' => 'https://github.com', 'display_order' => 1]);
        $l2 = ProfileSocialLink::create(['user_id' => $user->id, 'platform' => 'website', 'url' => 'https://myweb.com', 'display_order' => 2]);
        $l3 = ProfileSocialLink::create(['user_id' => $user->id, 'platform' => 'x', 'url' => 'https://x.com', 'display_order' => 3]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/social-links/reorder', [
                'ids' => [$l3->id, $l1->id, $l2->id],
            ]);

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $l3->id)
            ->assertJsonPath('data.1.id', $l1->id)
            ->assertJsonPath('data.2.id', $l2->id);

        $this->assertEquals(1, $l3->fresh()->display_order);
        $this->assertEquals(2, $l1->fresh()->display_order);
        $this->assertEquals(3, $l2->fresh()->display_order);
    }

    /**
     * Test privacy controls and is_visible flag are strictly enforced.
     */
    public function test_privacy_controls_and_visibility_flags_are_enforced(): void
    {
        $targetUser = User::factory()->create(['username' => 'privacy_social']);
        $friend = User::factory()->create();
        $follower = User::factory()->create();
        $stranger = User::factory()->create();

        // Friendship
        Friendship::create([
            'user_id' => $targetUser->id,
            'friend_id' => $friend->id,
            'status' => 'accepted',
        ]);

        // Follower
        $targetUser->followers()->attach($follower->id);

        // 1. Public and visible
        ProfileSocialLink::create([
            'user_id' => $targetUser->id,
            'platform' => 'github',
            'url' => 'https://github.com/public',
            'is_visible' => true,
            'privacy' => 'public',
        ]);

        // 2. Followers privacy
        ProfileSocialLink::create([
            'user_id' => $targetUser->id,
            'platform' => 'x',
            'url' => 'https://x.com/followers',
            'is_visible' => true,
            'privacy' => 'followers',
        ]);

        // 3. Friends privacy
        ProfileSocialLink::create([
            'user_id' => $targetUser->id,
            'platform' => 'facebook',
            'url' => 'https://facebook.com/friends',
            'is_visible' => true,
            'privacy' => 'friends',
        ]);

        // 4. Only Me privacy
        ProfileSocialLink::create([
            'user_id' => $targetUser->id,
            'platform' => 'linkedin',
            'url' => 'https://linkedin.com/onlyme',
            'is_visible' => true,
            'privacy' => 'only_me',
        ]);

        // 5. Hidden flag (is_visible = false)
        ProfileSocialLink::create([
            'user_id' => $targetUser->id,
            'platform' => 'tiktok',
            'url' => 'https://tiktok.com/hidden',
            'is_visible' => false,
            'privacy' => 'public',
        ]);

        // Stranger view: sees only github (public & visible)
        $strangerRes = $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v2/profile/{$targetUser->username}/social-links");
        $strangerRes->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals('github', $strangerRes->json('data.0.platform'));

        // Follower view: sees github + x (followers) = 2
        $followerRes = $this->actingAs($follower, 'sanctum')
            ->getJson("/api/v2/profile/{$targetUser->username}/social-links");
        $followerRes->assertOk()->assertJsonCount(2, 'data');

        // Friend view: sees github + x + facebook = 3
        $friendRes = $this->actingAs($friend, 'sanctum')
            ->getJson("/api/v2/profile/{$targetUser->username}/social-links");
        $friendRes->assertOk()->assertJsonCount(3, 'data');

        // Owner view: sees all 5
        $ownerRes = $this->actingAs($targetUser, 'sanctum')
            ->getJson('/api/v2/profile/social-links');
        $ownerRes->assertOk()->assertJsonCount(5, 'data');
    }

    /**
     * Test cross-user authorization is strictly enforced (403 Forbidden).
     */
    public function test_cross_user_authorization_is_enforced(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $link = ProfileSocialLink::create([
            'user_id' => $owner->id,
            'platform' => 'github',
            'url' => 'https://github.com/original',
        ]);

        // Attacker attempts update
        $this->actingAs($attacker, 'sanctum')
            ->putJson("/api/v2/profile/social-links/{$link->id}", [
                'url' => 'https://github.com/hacked',
            ])->assertStatus(403);

        // Attacker attempts delete
        $this->actingAs($attacker, 'sanctum')
            ->deleteJson("/api/v2/profile/social-links/{$link->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('profile_social_links', [
            'id' => $link->id,
            'url' => 'https://github.com/original',
        ]);
    }

    /**
     * Test audit logging records all social link actions.
     */
    public function test_audit_logging_records_social_link_events(): void
    {
        $user = User::factory()->create();

        // Add
        $res = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/social-links', [
                'platform' => 'github',
                'url' => 'github.com/auditdev',
            ]);
        $res->assertStatus(201);
        $linkId = $res->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'SOCIAL_LINK_ADDED',
            'entity_type' => ProfileSocialLink::class,
            'entity_id' => $linkId,
        ]);

        // Update
        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v2/profile/social-links/{$linkId}", [
                'url' => 'https://github.com/auditdev-updated',
            ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'SOCIAL_LINK_UPDATED',
            'entity_type' => ProfileSocialLink::class,
            'entity_id' => $linkId,
        ]);

        // Reorder
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/social-links/reorder', [
                'ids' => [$linkId],
            ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'SOCIAL_LINK_REORDERED',
            'entity_type' => ProfileSocialLink::class,
        ]);

        // Delete
        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v2/profile/social-links/{$linkId}")
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'SOCIAL_LINK_DELETED',
            'entity_type' => ProfileSocialLink::class,
            'entity_id' => $linkId,
        ]);
    }

    /**
     * Test platforms endpoint returns list of supported platforms.
     */
    public function test_platforms_endpoint_returns_supported_platforms_list(): void
    {
        $response = $this->getJson('/api/v2/profile/social-links/platforms');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $keys = collect($response->json('data'))->pluck('key')->toArray();
        $this->assertContains('website', $keys);
        $this->assertContains('facebook', $keys);
        $this->assertContains('instagram', $keys);
        $this->assertContains('youtube', $keys);
        $this->assertContains('linkedin', $keys);
        $this->assertContains('x', $keys);
        $this->assertContains('tiktok', $keys);
        $this->assertContains('github', $keys);
    }
}
