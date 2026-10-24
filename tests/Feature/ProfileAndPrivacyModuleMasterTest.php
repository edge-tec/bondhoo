<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Permission;
use App\Models\PrivacySetting;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAndPrivacyModuleMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        Queue::fake();
    }

    /**
     * Helper to create a user with a profile and default privacy settings.
     */
    protected function createTestUser(string $username, array $profileData = [], array $privacyOverrides = []): User
    {
        static $counter = 100;
        $counter++;

        $user = User::factory()->create([
            'username' => $username,
            'name' => ucfirst($username).' User',
            'email' => "{$username}{$counter}@example.com",
            'phone' => "+880170000{$counter}",
            'status' => 'active',
        ]);

        UserProfile::create(array_merge([
            'user_id' => $user->id,
            'first_name' => ucfirst($username),
            'last_name' => 'User',
            'display_name' => ucfirst($username).' User',
            'bio' => 'Professional developer & tech lover',
            'headline' => 'Software Engineer',
            'location' => 'Dhaka, Bangladesh',
            'website' => 'https://example.com',
            'gender' => 'male',
        ], $profileData));

        UserSetting::create([
            'user_id' => $user->id,
            'is_profile_locked' => false,
        ]);

        PrivacySetting::create(array_merge([
            'user_id' => $user->id,
            'profile_visibility' => 'public',
            'avatar_privacy' => 'public',
            'cover_privacy' => 'public',
            'bio_privacy' => 'public',
            'location_privacy' => 'public',
            'friends_list_visibility' => 'public',
            'post_default_privacy' => 'public',
        ], $privacyOverrides));

        return $user;
    }

    /**
     * Test 1: Authenticated user can view own profile and public profile details.
     */
    public function test_user_can_retrieve_own_profile_and_public_profile(): void
    {
        $user = $this->createTestUser('testuser');

        // Test authenticated /api/v1/profile endpoint
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User profile retrieved.',
            ])
            ->assertJsonPath('data.user.username', 'testuser')
            ->assertJsonPath('data.profile.headline', 'Software Engineer');

        // Test public /api/v1/users/{username} endpoint
        $publicResponse = $this->getJson("/api/v1/users/{$user->username}");
        $publicResponse->assertStatus(200)
            ->assertJsonPath('data.user.username', 'testuser')
            ->assertJsonPath('data.profile.location', 'Dhaka, Bangladesh');
    }

    /**
     * Test 2: User can edit profile details and about info via API v1.
     */
    public function test_user_can_edit_profile_details(): void
    {
        $user = $this->createTestUser('editoruser');

        $payload = [
            'headline' => 'Principal Systems Architect',
            'bio' => 'Building scalable web applications.',
            'location' => 'Sylhet, Bangladesh',
            'website' => 'https://sylhet-dev.com',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully.',
            ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'headline' => 'Principal Systems Architect',
            'location' => 'Sylhet, Bangladesh',
            'website' => 'https://sylhet-dev.com',
        ]);
    }

    /**
     * Test 3: Avatar upload accepts valid image and rejects malicious / invalid files.
     */
    public function test_avatar_upload_security_and_validation(): void
    {
        $user = $this->createTestUser('avataruser');

        // 1. Valid avatar upload (JPEG, 300x300)
        $validImage = UploadedFile::fake()->image('avatar.jpg', 300, 300);
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/avatar', [
                'file' => $validImage,
                'caption' => 'Updated my new avatar',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'প্রোফাইল ছবি সফলভাবে আপডেট করা হয়েছে।',
            ]);

        $user->refresh();
        $this->assertNotNull($user->profile->avatar_url);

        // 2. Reject SVG file to prevent SVG XSS
        $svgFile = UploadedFile::fake()->createWithContent('malicious.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $svgResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/avatar', [
                'file' => $svgFile,
            ]);

        $svgResponse->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        // 3. Reject embedded executable PHP script
        $phpPayload = "GIF89a\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;<?php echo 'danger'; ?>";
        $fakeFile = UploadedFile::fake()->createWithContent('exploit.jpg', $phpPayload);

        $exploitResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/avatar', [
                'file' => $fakeFile,
            ]);

        $exploitResponse->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        // 4. Reject tiny image (<100x100)
        $tinyImage = UploadedFile::fake()->image('tiny.jpg', 50, 50);
        $tinyResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/avatar', [
                'file' => $tinyImage,
            ]);

        $tinyResponse->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * Test 4: Cover photo upload accepts valid dimensions and rejects invalid ones.
     */
    public function test_cover_photo_upload_security_and_validation(): void
    {
        $user = $this->createTestUser('coveruser');

        // 1. Valid cover photo (800x300, JPEG)
        $validCover = UploadedFile::fake()->image('cover.jpg', 800, 300);
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/cover', [
                'file' => $validCover,
                'caption' => 'Scenic view of Sylhet',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'কভার ছবি সফলভাবে আপডেট করা হয়েছে।',
            ]);

        $user->refresh();
        $this->assertNotNull($user->profile->cover_url);

        // 2. Reject undersized cover photo (<400x150)
        $undersizedCover = UploadedFile::fake()->image('undersized.png', 200, 100);
        $undersizedResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/cover', [
                'file' => $undersizedCover,
            ]);

        $undersizedResponse->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * Test 5: Delete avatar and delete cover photo reset to default.
     */
    public function test_delete_avatar_and_cover_resets_defaults(): void
    {
        $user = $this->createTestUser('deleteuser');

        // Upload avatar
        $validImage = UploadedFile::fake()->image('avatar.jpg', 250, 250);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/avatar', ['file' => $validImage])
            ->assertStatus(200);

        // Upload cover
        $validCover = UploadedFile::fake()->image('cover.jpg', 600, 250);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/cover', ['file' => $validCover])
            ->assertStatus(200);

        // Delete Avatar
        $deleteAvatarResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile/avatar');

        $deleteAvatarResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'প্রোফাইল ছবি সফলভাবে মুছে ফেলা হয়েছে।',
            ]);

        // Delete Cover
        $deleteCoverResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile/cover');

        $deleteCoverResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'কভার ছবি সফলভাবে মুছে ফেলা হয়েছে।',
            ]);
    }

    /**
     * Test 6: Granular privacy settings update and profile visibility enforcement.
     */
    public function test_privacy_settings_update_and_visibility_rules(): void
    {
        $owner = $this->createTestUser('privacyme');
        $viewer = $this->createTestUser('vieweruser');

        // Update privacy settings via PUT /api/v1/profile/privacy
        $privacyPayload = [
            'profile_visibility' => 'friends',
            'friends_list_visibility' => 'only_me',
            'bio_privacy' => 'only_me',
            'location_privacy' => 'friends',
        ];

        $response = $this->actingAs($owner, 'sanctum')
            ->putJson('/api/v1/profile/privacy', $privacyPayload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'গোপনীয়তা সেটিংস সফলভাবে সংরক্ষিত হয়েছে।',
            ]);

        $this->assertDatabaseHas('privacy_settings', [
            'user_id' => $owner->id,
            'profile_visibility' => 'friends',
            'bio_privacy' => 'only_me',
        ]);

        // Viewer is not a friend yet -> Profile visibility is 'friends' -> Should receive 403 Forbidden
        $blockedView = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/v1/users/{$owner->username}");
        $blockedView->assertStatus(403);

        // Make viewer and owner accepted friends
        Friendship::create([
            'user_id' => $owner->id,
            'friend_id' => $viewer->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Now viewer is a friend -> Can view profile
        $friendView = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/v1/users/{$owner->username}");
        $friendView->assertStatus(200);

        // But bio_privacy is 'only_me', so bio should be hidden/null for friend
        $friendView->assertJsonPath('data.profile.bio', null);
    }

    /**
     * Test 7: Block and unblock flow, list blocked users, and enforce complete profile isolation.
     */
    public function test_blocked_users_management_and_isolation(): void
    {
        $userA = $this->createTestUser('usera');
        $userB = $this->createTestUser('userb');

        // 1. User cannot block themselves
        $selfBlock = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/profile/{$userA->username}/block");
        $selfBlock->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'আপনি নিজেকে ব্লক করতে পারবেন না।',
            ]);

        // 2. User A blocks User B
        $blockResponse = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/profile/{$userB->username}/block");

        $blockResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'blocked' => true,
                    'target_id' => $userB->id,
                ],
            ]);

        // 3. User B appears in User A's blocked users list
        $blockedList = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v1/profile/blocked-users');

        $blockedList->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonFragment(['username' => 'userb']);

        // 4. Block isolation: User B cannot view User A's profile
        $userBView = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/v1/users/{$userA->username}");
        $userBView->assertStatus(403);

        // 5. Bidirectional isolation: User A also cannot view User B's profile
        $userAView = $this->actingAs($userA, 'sanctum')
            ->getJson("/api/v1/users/{$userB->username}");
        $userAView->assertStatus(403);

        // 6. User A unblocks User B
        $unblockResponse = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/profile/{$userB->username}/unblock");

        $unblockResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'unblocked' => true,
                    'target_id' => $userB->id,
                ],
            ]);

        // 7. Access is restored: User B can view User A's public profile again
        $restoredView = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/v1/users/{$userA->username}");
        $restoredView->assertStatus(200)
            ->assertJsonPath('data.user.username', 'usera');
    }

    /**
     * Test 8: Authorization policies prevent unauthorized editing of another user's profile.
     */
    public function test_authorization_policy_prevents_unauthorized_profile_edit(): void
    {
        $victim = $this->createTestUser('victim');
        $attacker = $this->createTestUser('attacker');

        // Attacker attempts to update Victim's profile via PUT /api/v2/profile/{user}
        $unauthorizedResponse = $this->actingAs($attacker, 'sanctum')
            ->putJson("/api/v2/profile/{$victim->id}", [
                'headline' => 'Defaced by attacker',
                'bio' => 'Hacked bio',
            ]);

        $unauthorizedResponse->assertStatus(403);

        $victim->refresh();
        $this->assertNotEquals('Defaced by attacker', $victim->profile->headline);

        // Super Admin or admin with manage.users permission can edit
        $admin = $this->createTestUser('adminuser');
        $role = Role::firstOrCreate(['name' => 'SUPER_ADMIN', 'guard_name' => 'web']);
        $admin->roles()->sync([$role->id]);

        $adminResponse = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v2/profile/{$victim->id}", [
                'headline' => 'Verified by Admin',
            ]);

        $adminResponse->assertStatus(200);

        $victim->refresh();
        $this->assertEquals('Verified by Admin', $victim->profile->headline);
    }
}
