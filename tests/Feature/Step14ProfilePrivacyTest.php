<?php

namespace Tests\Feature;

use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\Permission;
use App\Models\PrivacySetting;
use App\Models\ProfileEducation;
use App\Models\ProfileExperience;
use App\Models\ProfileInterest;
use App\Models\ProfileLanguage;
use App\Models\ProfileSkill;
use App\Models\ProfileSocialLink;
use App\Models\Role;
use App\Models\User;
use App\Models\UserFollower;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Step14ProfilePrivacyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to create a user with complete profile data and settings.
     */
    protected function createCompleteUser(string $username = 'targetuser', array $privacyOverrides = []): User
    {
        static $phoneCounter = 1000;
        $phoneCounter++;

        $user = User::factory()->create([
            'username' => $username,
            'name' => 'John Doe',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => "{$username}@example.com",
            'phone' => "+880170000{$phoneCounter}",
            'gender' => 'male',
            'country' => 'Bangladesh',
            'birth_date' => '1995-08-20',
            'status' => 'active',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'display_name' => 'John Doe',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'cover_url' => 'https://example.com/cover.jpg',
            'bio' => 'Senior Software Architect and open source enthusiast.',
            'headline' => 'Tech Lead @ JugaJug',
            'about' => 'Extensive background in scalable systems and distributed databases.',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'address' => 'House 12, Road 5, Dhanmondi',
            'location' => 'Dhaka, Bangladesh',
            'gender' => 'male',
            'birth_date' => '1995-08-20',
            'website' => 'https://johndoe.dev',
        ]);

        UserSetting::create([
            'user_id' => $user->id,
            'is_profile_locked' => false,
        ]);

        $defaultPrivacy = [
            'user_id' => $user->id,
            'profile_visibility' => 'public',
            'avatar_privacy' => 'public',
            'cover_privacy' => 'public',
            'bio_privacy' => 'public',
            'about_privacy' => 'public',
            'location_privacy' => 'public',
            'education_privacy' => 'public',
            'work_privacy' => 'public',
            'skills_privacy' => 'public',
            'interests_privacy' => 'public',
            'languages_privacy' => 'public',
            'social_links_privacy' => 'public',
            'first_name_privacy' => 'public',
            'last_name_privacy' => 'public',
            'gender_privacy' => 'public',
            'dob_privacy' => 'friends',
            'dob_display_format' => 'month_day',
            'country_privacy' => 'public',
            'city_privacy' => 'public',
            'address_privacy' => 'only_me',
            'phone_privacy' => 'only_me',
            'email_privacy' => 'only_me',
        ];

        PrivacySetting::create(array_merge($defaultPrivacy, $privacyOverrides));

        // Add child section records
        ProfileEducation::create([
            'user_id' => $user->id,
            'institution_name' => 'University of Dhaka',
            'degree' => 'B.Sc. in CSE',
            'privacy' => 'public',
        ]);

        ProfileExperience::create([
            'user_id' => $user->id,
            'company_name' => 'Tech Innovations Ltd',
            'job_title' => 'Lead Engineer',
            'privacy' => 'public',
        ]);

        ProfileSkill::create([
            'user_id' => $user->id,
            'name' => 'Laravel Framework',
            'level' => 'expert',
        ]);

        ProfileInterest::create([
            'user_id' => $user->id,
            'name' => 'Distributed Systems',
            'category' => 'Technology',
        ]);

        ProfileLanguage::create([
            'user_id' => $user->id,
            'language' => 'Bengali',
            'proficiency' => 'native',
        ]);

        ProfileSocialLink::create([
            'user_id' => $user->id,
            'platform' => 'github',
            'platform_name' => 'GitHub',
            'url' => 'https://github.com/johndoe',
            'privacy' => 'public',
            'is_visible' => true,
        ]);

        return $user;
    }

    /**
     * 1. Test Owner has complete visibility into everything.
     */
    public function test_owner_can_see_full_profile_and_all_sensitive_fields(): void
    {
        $owner = $this->createCompleteUser('owneruser', [
            'profile_visibility' => 'only_me',
            'avatar_privacy' => 'only_me',
            'cover_privacy' => 'only_me',
            'bio_privacy' => 'only_me',
            'about_privacy' => 'only_me',
            'location_privacy' => 'only_me',
            'education_privacy' => 'only_me',
            'work_privacy' => 'only_me',
            'skills_privacy' => 'only_me',
            'interests_privacy' => 'only_me',
            'languages_privacy' => 'only_me',
            'social_links_privacy' => 'only_me',
            'email_privacy' => 'only_me',
            'phone_privacy' => 'only_me',
            'dob_privacy' => 'only_me',
            'dob_display_format' => 'hidden',
        ]);

        $res = $this->actingAs($owner, 'sanctum')->getJson("/api/v2/profile/{$owner->username}");

        $res->assertOk()
            ->assertJsonPath('data.is_owner', true)
            ->assertJsonPath('data.profile.avatar_url', 'https://example.com/avatar.jpg')
            ->assertJsonPath('data.profile.cover_url', 'https://example.com/cover.jpg')
            ->assertJsonPath('data.profile.bio', 'Senior Software Architect and open source enthusiast.')
            ->assertJsonPath('data.profile.about', 'Extensive background in scalable systems and distributed databases.')
            ->assertJsonPath('data.user.email', 'owneruser@example.com')
            ->assertJsonPath('data.user.phone', $owner->phone)
            ->assertJsonPath('data.profile.country', 'Bangladesh')
            ->assertJsonPath('data.profile.city', 'Dhaka')
            ->assertJsonPath('data.profile.address', 'House 12, Road 5, Dhanmondi');

        $this->assertNotEmpty($res->json('data.sections.educations'));
        $this->assertNotEmpty($res->json('data.sections.experiences'));
        $this->assertNotEmpty($res->json('data.sections.skills'));
        $this->assertNotEmpty($res->json('data.sections.interests'));
        $this->assertNotEmpty($res->json('data.sections.languages'));
        $this->assertNotEmpty($res->json('data.sections.social_links'));
    }

    /**
     * 2. Test Blocked user is completely denied from viewing profile and child endpoints.
     */
    public function test_blocked_user_is_strictly_forbidden_from_viewing_profile(): void
    {
        $target = $this->createCompleteUser('blockedtarget');
        $blockedUser = User::factory()->create(['username' => 'blockedviewer']);

        // Establish Friendship block
        Friendship::create([
            'user_id' => $target->id,
            'friend_id' => $blockedUser->id,
            'status' => Friendship::STATUS_BLOCKED,
        ]);

        // Access main profile endpoint
        $res = $this->actingAs($blockedUser, 'sanctum')->getJson("/api/v2/profile/{$target->username}");
        $res->assertStatus(403);

        // Access about endpoint
        $aboutRes = $this->actingAs($blockedUser, 'sanctum')->getJson("/api/v2/profile/{$target->username}/about");
        $aboutRes->assertStatus(403);

        // Access personal info endpoint
        $personalRes = $this->actingAs($blockedUser, 'sanctum')->getJson("/api/v2/profile/{$target->username}/personal");
        $personalRes->assertStatus(403);

        // Access education endpoint
        $eduRes = $this->actingAs($blockedUser, 'sanctum')->getJson("/api/v2/profile/{$target->username}/education");
        $eduRes->assertStatus(403);

        // Access work endpoint
        $workRes = $this->actingAs($blockedUser, 'sanctum')->getJson("/api/v2/profile/{$target->username}/work");
        $workRes->assertStatus(403);

        // Access skills endpoint
        $skillsRes = $this->actingAs($blockedUser, 'sanctum')->getJson("/api/v2/profile/{$target->username}/skills");
        $skillsRes->assertStatus(403);

        // Access social links endpoint
        $socialRes = $this->actingAs($blockedUser, 'sanctum')->getJson("/api/v2/profile/{$target->username}/social-links");
        $socialRes->assertStatus(403);

        // Target user also cannot view the blocked user (mutual restriction)
        $targetViewingBlocked = $this->actingAs($target, 'sanctum')->getJson("/api/v2/profile/{$blockedUser->username}");
        $targetViewingBlocked->assertStatus(403);
    }

    /**
     * 3. Test active block in blocked_users table also denies access.
     */
    public function test_active_record_in_blocked_users_table_enforces_blocking(): void
    {
        $target = $this->createCompleteUser('activeblocktarget');
        $viewer = User::factory()->create(['username' => 'securityblockedviewer']);

        BlockedUser::create([
            'user_id' => $target->id,
            'identifier' => (string) $viewer->id,
            'reason' => 'Violation of terms',
            'blocked_type' => 'user',
            'blocked_at' => now(),
            'is_active' => true,
        ]);

        $res = $this->actingAs($viewer, 'sanctum')->getJson("/api/v2/profile/{$target->username}");
        $res->assertStatus(403);
    }

    /**
     * 4. Test Anonymous (unauthenticated) users with profile visibility rules.
     */
    public function test_anonymous_user_profile_visibility_enforcement(): void
    {
        // Public profile -> Anonymous can view public fields
        $publicTarget = $this->createCompleteUser('publicuser', [
            'profile_visibility' => 'public',
            'email_privacy' => 'only_me',
            'phone_privacy' => 'only_me',
            'address_privacy' => 'only_me',
            'dob_privacy' => 'friends',
        ]);

        $publicRes = $this->getJson("/api/v2/profile/{$publicTarget->username}");
        $publicRes->assertOk();
        $this->assertNull($publicRes->json('data.user.email'));
        $this->assertNull($publicRes->json('data.user.phone'));
        $this->assertNull($publicRes->json('data.profile.address'));
        $this->assertNull($publicRes->json('data.profile.birth_date'));

        // Followers-only profile -> Anonymous gets 403 Forbidden
        $followersTarget = $this->createCompleteUser('followersonlyuser', [
            'profile_visibility' => 'followers',
        ]);
        $this->getJson("/api/v2/profile/{$followersTarget->username}")->assertStatus(403);

        // Friends-only profile -> Anonymous gets 403 Forbidden
        $friendsTarget = $this->createCompleteUser('friendsonlyprofile', [
            'profile_visibility' => 'friends',
        ]);
        $this->getJson("/api/v2/profile/{$friendsTarget->username}")->assertStatus(403);

        // Only_me profile -> Anonymous gets 403 Forbidden
        $onlyMeTarget = $this->createCompleteUser('onlymeprofile', [
            'profile_visibility' => 'only_me',
        ]);
        $this->getJson("/api/v2/profile/{$onlyMeTarget->username}")->assertStatus(403);
    }

    /**
     * 5. Test Followers vs Friends vs Strangers on Followers-level privacy.
     */
    public function test_follower_level_privacy_permits_followers_and_friends_but_not_strangers(): void
    {
        $target = $this->createCompleteUser('influencer', [
            'profile_visibility' => 'followers',
            'avatar_privacy' => 'followers',
            'bio_privacy' => 'followers',
            'education_privacy' => 'followers',
            'skills_privacy' => 'followers',
        ]);

        $follower = User::factory()->create(['username' => 'myfollower']);
        UserFollower::create([
            'user_id' => $target->id,
            'follower_id' => $follower->id,
        ]);

        $stranger = User::factory()->create(['username' => 'randomstranger']);

        $friend = User::factory()->create(['username' => 'goodfriend']);
        Friendship::create([
            'user_id' => $target->id,
            'friend_id' => $friend->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // 1. Stranger gets 403 on profile
        $this->actingAs($stranger, 'sanctum')->getJson("/api/v2/profile/{$target->username}")
            ->assertStatus(403);

        // 2. Follower gets 200 and sees follower-level sections
        $followerRes = $this->actingAs($follower, 'sanctum')->getJson("/api/v2/profile/{$target->username}");
        $followerRes->assertOk();
        $this->assertEquals('https://example.com/avatar.jpg', $followerRes->json('data.profile.avatar_url'));
        $this->assertEquals('Senior Software Architect and open source enthusiast.', $followerRes->json('data.profile.bio'));
        $this->assertNotEmpty($followerRes->json('data.sections.educations'));
        $this->assertNotEmpty($followerRes->json('data.sections.skills'));

        // 3. Friend also gets 200 (Friends inherit follower access)
        $friendRes = $this->actingAs($friend, 'sanctum')->getJson("/api/v2/profile/{$target->username}");
        $friendRes->assertOk();
        $this->assertEquals('https://example.com/avatar.jpg', $friendRes->json('data.profile.avatar_url'));
    }

    /**
     * 6. Test Friends-level privacy permits friends but hides from followers and public.
     */
    public function test_friends_level_privacy_permits_friends_but_hides_from_followers(): void
    {
        $target = $this->createCompleteUser('friendlytarget', [
            'profile_visibility' => 'public',
            'avatar_privacy' => 'friends',
            'cover_privacy' => 'friends',
            'bio_privacy' => 'friends',
            'about_privacy' => 'friends',
            'education_privacy' => 'friends',
            'work_privacy' => 'friends',
            'skills_privacy' => 'friends',
            'interests_privacy' => 'friends',
            'languages_privacy' => 'friends',
            'social_links_privacy' => 'friends',
        ]);

        $follower = User::factory()->create(['username' => 'justafollower']);
        UserFollower::create([
            'user_id' => $target->id,
            'follower_id' => $follower->id,
        ]);

        $friend = User::factory()->create(['username' => 'trustedfriend']);
        Friendship::create([
            'user_id' => $target->id,
            'friend_id' => $friend->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Follower view: profile is 200, but friends-only sections are masked/empty
        $follRes = $this->actingAs($follower, 'sanctum')->getJson("/api/v2/profile/{$target->username}");
        $follRes->assertOk();
        $this->assertNull($follRes->json('data.profile.avatar_url'));
        $this->assertNull($follRes->json('data.profile.cover_url'));
        $this->assertNull($follRes->json('data.profile.bio'));
        $this->assertNull($follRes->json('data.profile.about'));
        $this->assertEmpty($follRes->json('data.sections.educations'));
        $this->assertEmpty($follRes->json('data.sections.experiences'));
        $this->assertEmpty($follRes->json('data.sections.skills'));
        $this->assertEmpty($follRes->json('data.sections.interests'));
        $this->assertEmpty($follRes->json('data.sections.languages'));
        $this->assertEmpty($follRes->json('data.sections.social_links'));

        // Friend view: all friends-only sections are visible
        $friendRes = $this->actingAs($friend, 'sanctum')->getJson("/api/v2/profile/{$target->username}");
        $friendRes->assertOk();
        $this->assertEquals('https://example.com/avatar.jpg', $friendRes->json('data.profile.avatar_url'));
        $this->assertEquals('https://example.com/cover.jpg', $friendRes->json('data.profile.cover_url'));
        $this->assertEquals('Senior Software Architect and open source enthusiast.', $friendRes->json('data.profile.bio'));
        $this->assertEquals('Extensive background in scalable systems and distributed databases.', $friendRes->json('data.profile.about'));
        $this->assertNotEmpty($friendRes->json('data.sections.educations'));
        $this->assertNotEmpty($friendRes->json('data.sections.experiences'));
        $this->assertNotEmpty($friendRes->json('data.sections.skills'));
        $this->assertNotEmpty($friendRes->json('data.sections.interests'));
        $this->assertNotEmpty($friendRes->json('data.sections.languages'));
        $this->assertNotEmpty($friendRes->json('data.sections.social_links'));
    }

    /**
     * 7. Test Admin access follows RBAC and bypasses all privacy filters.
     */
    public function test_admin_with_manage_users_permission_bypasses_privacy_restrictions(): void
    {
        $target = $this->createCompleteUser('secretiveuser', [
            'profile_visibility' => 'only_me',
            'avatar_privacy' => 'only_me',
            'cover_privacy' => 'only_me',
            'bio_privacy' => 'only_me',
            'about_privacy' => 'only_me',
            'location_privacy' => 'only_me',
            'education_privacy' => 'only_me',
            'work_privacy' => 'only_me',
            'skills_privacy' => 'only_me',
            'interests_privacy' => 'only_me',
            'languages_privacy' => 'only_me',
            'social_links_privacy' => 'only_me',
            'email_privacy' => 'only_me',
            'phone_privacy' => 'only_me',
            'dob_privacy' => 'only_me',
            'dob_display_format' => 'full',
        ]);

        // Create Admin User with manage.users permission
        $admin = User::factory()->create(['username' => 'systemadmin']);
        $adminRole = Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['label' => 'Super Administrator']);
        $admin->roles()->sync([$adminRole->id]);

        $res = $this->actingAs($admin, 'sanctum')->getJson("/api/v2/profile/{$target->username}");
        $res->assertOk();
        $this->assertEquals('https://example.com/avatar.jpg', $res->json('data.profile.avatar_url'));
        $this->assertEquals('https://example.com/cover.jpg', $res->json('data.profile.cover_url'));
        $this->assertEquals('Senior Software Architect and open source enthusiast.', $res->json('data.profile.bio'));
        $this->assertEquals('Extensive background in scalable systems and distributed databases.', $res->json('data.profile.about'));
        $this->assertEquals('secretiveuser@example.com', $res->json('data.user.email'));
        $this->assertEquals($target->phone, $res->json('data.user.phone'));
        $this->assertEquals('1995-08-20', $res->json('data.profile.birth_date'));
        $this->assertNotEmpty($res->json('data.sections.educations'));
        $this->assertNotEmpty($res->json('data.sections.experiences'));
        $this->assertNotEmpty($res->json('data.sections.skills'));
    }

    /**
     * 8. Test updating all new section privacy settings via PUT /api/v2/profile/privacy/settings.
     */
    public function test_updating_complete_privacy_settings(): void
    {
        $user = $this->createCompleteUser('settingsowner');

        $payload = [
            'profile_visibility' => 'friends',
            'avatar_privacy' => 'followers',
            'cover_privacy' => 'only_me',
            'bio_privacy' => 'followers',
            'about_privacy' => 'friends',
            'location_privacy' => 'followers',
            'education_privacy' => 'friends',
            'work_privacy' => 'followers',
            'skills_privacy' => 'friends',
            'interests_privacy' => 'followers',
            'languages_privacy' => 'friends',
            'social_links_privacy' => 'followers',
            'first_name_privacy' => 'friends',
            'last_name_privacy' => 'friends',
            'gender_privacy' => 'friends',
            'dob_privacy' => 'followers',
            'dob_display_format' => 'age',
            'country_privacy' => 'followers',
            'city_privacy' => 'friends',
            'address_privacy' => 'only_me',
            'phone_privacy' => 'only_me',
            'email_privacy' => 'only_me',
        ];

        $res = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/privacy/settings', $payload);

        $res->assertOk()
            ->assertJsonPath('data.profile_visibility', 'friends')
            ->assertJsonPath('data.avatar_privacy', 'followers')
            ->assertJsonPath('data.cover_privacy', 'only_me')
            ->assertJsonPath('data.bio_privacy', 'followers')
            ->assertJsonPath('data.about_privacy', 'friends')
            ->assertJsonPath('data.location_privacy', 'followers')
            ->assertJsonPath('data.education_privacy', 'friends')
            ->assertJsonPath('data.work_privacy', 'followers')
            ->assertJsonPath('data.skills_privacy', 'friends')
            ->assertJsonPath('data.interests_privacy', 'followers')
            ->assertJsonPath('data.languages_privacy', 'friends')
            ->assertJsonPath('data.social_links_privacy', 'followers')
            ->assertJsonPath('data.dob_display_format', 'age');

        $this->assertDatabaseHas('privacy_settings', [
            'user_id' => $user->id,
            'avatar_privacy' => 'followers',
            'cover_privacy' => 'only_me',
            'about_privacy' => 'friends',
            'skills_privacy' => 'friends',
        ]);
    }
}
