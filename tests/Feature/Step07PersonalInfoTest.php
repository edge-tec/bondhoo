<?php

namespace Tests\Feature;

use App\Events\ProfileUpdatedEvent;
use App\Models\Friendship;
use App\Models\PrivacySetting;
use App\Models\User;
use App\Models\UserFollower;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Step07PersonalInfoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_update_personal_information_and_granular_privacy(): void
    {
        Event::fake([ProfileUpdatedEvent::class]);

        $user = User::factory()->create([
            'username' => 'personaluser',
            'first_name' => 'OriginalFirst',
            'last_name' => 'OriginalLast',
            'email' => 'original@jugajug.com',
            'phone' => '+8801700000001',
        ]);

        $cacheKey = "profile:public:{$user->username}";
        Cache::put($cacheKey, ['dummy' => 'cached'], 3600);

        $payload = [
            'first_name' => 'Tanvir',
            'last_name' => 'Hasan',
            'display_name' => 'Tanvir Hasan (Dev)',
            'gender' => 'male',
            'birth_date' => '1996-08-20',
            'phone' => '+8801712345678',
            'email' => 'tanvir@jugajug.social',
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'address' => 'House 42, Road 11, Banani',
            'dob_display_format' => 'month_day',
            'privacy' => [
                'first_name' => 'public',
                'last_name' => 'public',
                'gender' => 'public',
                'birth_date' => 'friends',
                'phone' => 'only_me',
                'email' => 'only_me',
                'country' => 'public',
                'city' => 'public',
                'address' => 'only_me',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/personal', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ব্যক্তিগত তথ্য ও গোপনীয়তা সফলভাবে আপডেট করা হয়েছে।',
            ])
            ->assertJsonPath('data.first_name', 'Tanvir')
            ->assertJsonPath('data.last_name', 'Hasan')
            ->assertJsonPath('data.display_name', 'Tanvir Hasan (Dev)')
            ->assertJsonPath('data.gender', 'male')
            ->assertJsonPath('data.country', 'Bangladesh')
            ->assertJsonPath('data.city', 'Dhaka')
            ->assertJsonPath('data.address', 'House 42, Road 11, Banani')
            ->assertJsonPath('data.privacy.phone', 'only_me')
            ->assertJsonPath('data.privacy.address', 'only_me')
            ->assertJsonPath('data.privacy.dob_display_format', 'month_day');

        // Verify User Table Updates
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Tanvir',
            'last_name' => 'Hasan',
            'email' => 'tanvir@jugajug.social',
            'phone' => '+8801712345678',
            'country' => 'Bangladesh',
            'gender' => 'male',
            'birth_date' => '1996-08-20 00:00:00',
        ]);

        // Verify UserProfiles Table Updates
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'first_name' => 'Tanvir',
            'last_name' => 'Hasan',
            'display_name' => 'Tanvir Hasan (Dev)',
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'address' => 'House 42, Road 11, Banani',
            'gender' => 'male',
        ]);

        // Verify PrivacySettings Table Updates
        $this->assertDatabaseHas('privacy_settings', [
            'user_id' => $user->id,
            'phone_privacy' => 'only_me',
            'email_privacy' => 'only_me',
            'address_privacy' => 'only_me',
            'dob_privacy' => 'friends',
            'dob_display_format' => 'month_day',
        ]);

        // Verify Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'PERSONAL_INFO_UPDATED',
            'entity_type' => UserProfile::class,
        ]);

        // Verify Cache Invalidation
        $this->assertFalse(Cache::has($cacheKey));

        // Verify Event
        Event::assertDispatched(ProfileUpdatedEvent::class);
    }

    public function test_privacy_level_only_me_hides_fields_from_public_and_friends(): void
    {
        $target = User::factory()->create([
            'username' => 'privacyman',
            'first_name' => 'PrivateFirst',
            'last_name' => 'PrivateLast',
            'phone' => '+8801999999999',
            'email' => 'private@jugajug.com',
        ]);

        UserProfile::create([
            'user_id' => $target->id,
            'first_name' => 'PrivateFirst',
            'last_name' => 'PrivateLast',
            'address' => 'Secret Bunker, Dhaka',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
        ]);

        PrivacySetting::create([
            'user_id' => $target->id,
            'phone_privacy' => 'only_me',
            'email_privacy' => 'only_me',
            'address_privacy' => 'only_me',
            'city_privacy' => 'public',
        ]);

        // 1. A stranger views the profile
        $stranger = User::factory()->create(['username' => 'stranger']);
        $publicRes = $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/v2/profile/privacyman/personal');

        $publicRes->assertStatus(200);
        $this->assertNull($publicRes->json('data.phone'));
        $this->assertNull($publicRes->json('data.email'));
        $this->assertNull($publicRes->json('data.address'));
        $this->assertEquals('Dhaka', $publicRes->json('data.city'));

        // Also verify main profile endpoint masks private fields
        $mainRes = $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/v2/profile/privacyman');
        $this->assertNull($mainRes->json('data.user.phone'));
        $this->assertNull($mainRes->json('data.user.email'));
        $this->assertNull($mainRes->json('data.profile.address'));

        // 2. A friend views the profile (should still NOT see ONLY_ME fields)
        $friend = User::factory()->create(['username' => 'bestfriend']);
        Friendship::create([
            'user_id' => $target->id,
            'friend_id' => $friend->id,
            'status' => 'accepted',
        ]);

        $friendRes = $this->actingAs($friend, 'sanctum')
            ->getJson('/api/v2/profile/privacyman/personal');
        $this->assertNull($friendRes->json('data.phone'));
        $this->assertNull($friendRes->json('data.email'));
        $this->assertNull($friendRes->json('data.address'));

        // 3. The owner themselves views the profile (should see all their own fields)
        $ownerRes = $this->actingAs($target, 'sanctum')
            ->getJson('/api/v2/profile/privacyman/personal');
        $this->assertEquals('+8801999999999', $ownerRes->json('data.phone'));
        $this->assertEquals('private@jugajug.com', $ownerRes->json('data.email'));
        $this->assertEquals('Secret Bunker, Dhaka', $ownerRes->json('data.address'));
    }

    public function test_privacy_level_friends_visible_to_friends_but_hidden_from_followers_and_public(): void
    {
        $target = User::factory()->create(['username' => 'friendsonlyuser']);

        UserProfile::create([
            'user_id' => $target->id,
            'city' => 'Sylhet',
            'country' => 'Bangladesh',
        ]);

        PrivacySetting::create([
            'user_id' => $target->id,
            'city_privacy' => 'friends',
            'country_privacy' => 'friends',
        ]);

        $follower = User::factory()->create(['username' => 'followeruser']);
        UserFollower::create([
            'user_id' => $target->id,
            'follower_id' => $follower->id,
        ]);

        $friend = User::factory()->create(['username' => 'realfriend']);
        Friendship::create([
            'user_id' => $target->id,
            'friend_id' => $friend->id,
            'status' => 'accepted',
        ]);

        $stranger = User::factory()->create(['username' => 'purepublic']);

        // Public viewer -> Hidden
        $pubRes = $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/v2/profile/friendsonlyuser/personal');
        $this->assertNull($pubRes->json('data.city'));
        $this->assertNull($pubRes->json('data.country'));

        // Follower viewer -> Hidden (because privacy is friends, not followers)
        $follRes = $this->actingAs($follower, 'sanctum')
            ->getJson('/api/v2/profile/friendsonlyuser/personal');
        $this->assertNull($follRes->json('data.city'));
        $this->assertNull($follRes->json('data.country'));

        // Friend viewer -> Visible
        $friendRes = $this->actingAs($friend, 'sanctum')
            ->getJson('/api/v2/profile/friendsonlyuser/personal');
        $this->assertEquals('Sylhet', $friendRes->json('data.city'));
        $this->assertEquals('Bangladesh', $friendRes->json('data.country'));
    }

    public function test_privacy_level_followers_visible_to_followers_and_friends_but_not_public(): void
    {
        $target = User::factory()->create(['username' => 'followeronlyuser']);

        UserProfile::create([
            'user_id' => $target->id,
            'city' => 'Rajshahi',
        ]);

        PrivacySetting::create([
            'user_id' => $target->id,
            'city_privacy' => 'followers',
        ]);

        $follower = User::factory()->create(['username' => 'myfan']);
        UserFollower::create([
            'user_id' => $target->id,
            'follower_id' => $follower->id,
        ]);

        $stranger = User::factory()->create(['username' => 'stranger2']);

        // Public -> Hidden
        $pubRes = $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/v2/profile/followeronlyuser/personal');
        $this->assertNull($pubRes->json('data.city'));

        // Follower -> Visible
        $fanRes = $this->actingAs($follower, 'sanctum')
            ->getJson('/api/v2/profile/followeronlyuser/personal');
        $this->assertEquals('Rajshahi', $fanRes->json('data.city'));
    }

    public function test_date_of_birth_display_formats_full_month_day_age_and_hidden(): void
    {
        $target = User::factory()->create([
            'username' => 'birthdayguy',
            'birth_date' => '1990-05-15',
        ]);

        UserProfile::create([
            'user_id' => $target->id,
            'birth_date' => '1990-05-15',
        ]);

        $viewer = User::factory()->create(['username' => 'birthdayviewer']);
        // Make them friends so dob_privacy='friends' passes
        Friendship::create([
            'user_id' => $target->id,
            'friend_id' => $viewer->id,
            'status' => 'accepted',
        ]);

        $privacy = PrivacySetting::create([
            'user_id' => $target->id,
            'dob_privacy' => 'friends',
            'dob_display_format' => 'month_day',
        ]);

        // 1. Format: month_day (e.g. May 15, no year exposed!)
        $res1 = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v2/profile/birthdayguy/personal');
        $this->assertEquals('May 15', $res1->json('data.birth_date'));
        $this->assertStringNotContainsString('1990', $res1->json('data.birth_date'));

        // 2. Format: full (1990-05-15)
        $privacy->update(['dob_display_format' => 'full']);
        Cache::flush();
        $res2 = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v2/profile/birthdayguy/personal');
        $this->assertEquals('1990-05-15', $res2->json('data.birth_date'));

        // 3. Format: age (e.g. 36 years old)
        $privacy->update(['dob_display_format' => 'age']);
        Cache::flush();
        $expectedAge = Carbon::parse('1990-05-15')->age;
        $res3 = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v2/profile/birthdayguy/personal');
        $this->assertEquals("{$expectedAge} years old", $res3->json('data.birth_date'));

        // 4. Format: hidden (null for viewer)
        $privacy->update(['dob_display_format' => 'hidden']);
        Cache::flush();
        $res4 = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v2/profile/birthdayguy/personal');
        $this->assertNull($res4->json('data.birth_date'));

        // But Owner always has access
        $ownerRes = $this->actingAs($target, 'sanctum')
            ->getJson('/api/v2/profile/birthdayguy/personal');
        $this->assertNotNull($ownerRes->json('data.birth_date'));
    }

    public function test_locked_profile_strictly_masks_personal_fields_for_strangers(): void
    {
        $target = User::factory()->create([
            'username' => 'lockeduser',
            'first_name' => 'Secret',
            'last_name' => 'Agent',
        ]);

        UserProfile::create([
            'user_id' => $target->id,
            'first_name' => 'Secret',
            'last_name' => 'Agent',
            'city' => 'Coxs Bazar',
            'country' => 'Bangladesh',
        ]);

        UserSetting::create([
            'user_id' => $target->id,
            'is_profile_locked' => true,
        ]);

        PrivacySetting::create([
            'user_id' => $target->id,
            'first_name_privacy' => 'public',
            'city_privacy' => 'public',
        ]);

        $stranger = User::factory()->create(['username' => 'curiousperson']);

        $res = $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/v2/profile/lockeduser/personal');

        $res->assertStatus(200);
        $this->assertTrue($res->json('data.meta.is_locked_view'));
        $this->assertNull($res->json('data.first_name'));
        $this->assertNull($res->json('data.last_name'));
        $this->assertNull($res->json('data.city'));
    }

    public function test_validates_birth_date_cannot_be_in_future(): void
    {
        $user = User::factory()->create(['username' => 'time_traveler']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/personal', [
                'birth_date' => Carbon::tomorrow()->format('Y-m-d'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['birth_date']);
    }

    public function test_user_can_read_and_update_privacy_settings_endpoint(): void
    {
        $user = User::factory()->create(['username' => 'settingsuser']);

        // GET privacy settings
        $getRes = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/profile/privacy/settings');

        $getRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                ],
            ]);

        // PUT privacy settings
        $putRes = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/privacy/settings', [
                'phone_visibility' => 'only_me',
                'dob_privacy' => 'followers',
                'dob_display_format' => 'age',
                'country_privacy' => 'friends',
            ]);

        $putRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'phone_visibility' => 'only_me',
                    'dob_privacy' => 'followers',
                    'dob_display_format' => 'age',
                    'country_privacy' => 'friends',
                ],
            ]);

        $this->assertDatabaseHas('privacy_settings', [
            'user_id' => $user->id,
            'phone_visibility' => 'only_me',
            'dob_privacy' => 'followers',
            'dob_display_format' => 'age',
            'country_privacy' => 'friends',
        ]);
    }
}
