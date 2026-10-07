<?php

namespace Tests\Feature;

use App\Models\ProfileCompletion;
use App\Models\ProfileEducation;
use App\Models\ProfileExperience;
use App\Models\ProfileInterest;
use App\Models\ProfileLanguage;
use App\Models\ProfileSkill;
use App\Models\ProfileSocialLink;
use App\Models\ProfileVerification;
use App\Models\ProfileView;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Step02ProfileDatabaseArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_profile_tables_exist(): void
    {
        $tables = [
            'user_profiles',
            'user_settings',
            'privacy_settings',
            'profile_educations',
            'profile_experiences',
            'profile_skills',
            'profile_interests',
            'profile_languages',
            'profile_social_links',
            'profile_verifications',
            'profile_views',
            'profile_completions',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected table [{$table}] to exist.");
        }
    }

    public function test_user_profiles_table_has_slug_and_about_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('user_profiles', 'slug'));
        $this->assertTrue(Schema::hasColumn('user_profiles', 'about'));
        $this->assertTrue(Schema::hasColumn('user_profiles', 'social_links'));
        $this->assertTrue(Schema::hasColumn('user_profiles', 'cover_position_y'));
    }

    public function test_user_relationships_to_extended_profile_models(): void
    {
        $user = User::factory()->create();

        // 1. Education
        $edu = ProfileEducation::create([
            'user_id' => $user->id,
            'institution_name' => 'University of Dhaka',
            'degree' => 'B.Sc.',
            'field_of_study' => 'Computer Science & Engineering',
            'start_date' => '2018-01-01',
            'end_date' => '2022-12-31',
            'is_current' => false,
            'privacy' => 'public',
        ]);
        $this->assertCount(1, $user->educations);
        $this->assertEquals('University of Dhaka', $user->educations->first()->institution_name);

        // 2. Experience
        $exp = ProfileExperience::create([
            'user_id' => $user->id,
            'company_name' => 'Tech Corp',
            'job_title' => 'Software Engineer',
            'employment_type' => 'full_time',
            'location' => 'Dhaka, Bangladesh',
            'is_remote' => true,
            'start_date' => '2023-01-01',
            'is_current' => true,
            'privacy' => 'public',
        ]);
        $this->assertCount(1, $user->experiences);
        $this->assertEquals('Tech Corp', $user->experiences->first()->company_name);

        // 3. Skill
        $skill = ProfileSkill::create([
            'user_id' => $user->id,
            'name' => 'PHP',
            'level' => 'expert',
            'endorsements_count' => 10,
        ]);
        $this->assertCount(1, $user->skills);
        $this->assertEquals('PHP', $user->skills->first()->name);

        // 4. Interest
        $interest = ProfileInterest::create([
            'user_id' => $user->id,
            'name' => 'Open Source',
            'category' => 'Technology',
        ]);
        $this->assertCount(1, $user->interests);

        // 5. Language
        $lang = ProfileLanguage::create([
            'user_id' => $user->id,
            'language' => 'Bengali',
            'proficiency' => 'native',
        ]);
        $this->assertCount(1, $user->languages);

        // 6. Social Link
        $social = ProfileSocialLink::create([
            'user_id' => $user->id,
            'platform' => 'github',
            'url' => 'https://github.com/developer',
        ]);
        $this->assertCount(1, $user->socialLinks);

        // 7. Verification (Media in object storage, only keys and meta stored)
        $verification = ProfileVerification::create([
            'user_id' => $user->id,
            'verification_type' => 'nid',
            'document_number' => '1234567890',
            'document_front_key' => 'verifications/front_1.jpg',
            'media_meta' => [
                'mime_type' => 'image/jpeg',
                'size' => 1048576,
                'dimensions' => ['width' => 1920, 'height' => 1080],
                'cdn_url' => 'https://cdn.jugajug.com/verifications/front_1.jpg',
            ],
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
        $this->assertCount(1, $user->verifications);
        $this->assertEquals('verifications/front_1.jpg', $user->verifications->first()->document_front_key);
        $this->assertEquals(1048576, $user->verifications->first()->media_meta['size']);

        // 8. Profile View (Analytics)
        $viewer = User::factory()->create();
        $view = ProfileView::create([
            'user_id' => $user->id,
            'viewer_id' => $viewer->id,
            'ip_address' => '127.0.0.1',
            'device_type' => 'desktop',
            'referer' => 'https://jugajug.com/explore',
        ]);
        $this->assertCount(1, $user->profileViews);
        $this->assertEquals($viewer->id, $user->profileViews->first()->viewer_id);

        // 9. Profile Completion
        $completion = ProfileCompletion::create([
            'user_id' => $user->id,
            'completion_percentage' => 85,
            'has_avatar' => true,
            'has_bio' => true,
            'has_education' => true,
            'has_experience' => true,
            'has_skills' => true,
            'missing_sections' => ['cover_photo'],
            'last_calculated_at' => now(),
        ]);
        $this->assertNotNull($user->profileCompletion);
        $this->assertEquals(85, $user->profileCompletion->completion_percentage);
    }

    public function test_unique_constraints_are_enforced(): void
    {
        $user = User::factory()->create();

        ProfileSkill::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
            'level' => 'expert',
        ]);

        $this->expectException(QueryException::class);
        ProfileSkill::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
            'level' => 'intermediate',
        ]);
    }

    public function test_soft_deletes_work_on_educations_and_experiences(): void
    {
        $user = User::factory()->create();

        $edu = ProfileEducation::create([
            'user_id' => $user->id,
            'institution_name' => 'Test University',
        ]);

        $exp = ProfileExperience::create([
            'user_id' => $user->id,
            'company_name' => 'Test Corp',
            'job_title' => 'Engineer',
        ]);

        $edu->delete();
        $exp->delete();

        $this->assertSoftDeleted('profile_educations', ['id' => $edu->id]);
        $this->assertSoftDeleted('profile_experiences', ['id' => $exp->id]);

        $this->assertCount(0, $user->educations);
        $this->assertCount(0, $user->experiences);
        $this->assertCount(1, ProfileEducation::withTrashed()->where('user_id', $user->id)->get());
    }

    public function test_cascade_delete_removes_all_profile_related_data(): void
    {
        $user = User::factory()->create();

        $profile = UserProfile::create([
            'user_id' => $user->id,
            'display_name' => 'Test User',
            'slug' => 'test-user-slug-1',
            'about' => 'Detailed about section for the user.',
        ]);

        ProfileEducation::create([
            'user_id' => $user->id,
            'institution_name' => 'Uni',
        ]);

        ProfileSkill::create([
            'user_id' => $user->id,
            'name' => 'React',
        ]);

        ProfileSocialLink::create([
            'user_id' => $user->id,
            'platform' => 'twitter',
            'url' => 'https://twitter.com/test',
        ]);

        ProfileCompletion::create([
            'user_id' => $user->id,
            'completion_percentage' => 50,
        ]);

        $this->assertDatabaseHas('user_profiles', ['id' => $profile->id]);
        $this->assertDatabaseHas('profile_educations', ['user_id' => $user->id]);
        $this->assertDatabaseHas('profile_skills', ['user_id' => $user->id]);
        $this->assertDatabaseHas('profile_social_links', ['user_id' => $user->id]);
        $this->assertDatabaseHas('profile_completions', ['user_id' => $user->id]);

        // Force delete user to trigger database foreign key cascades
        $user->forceDelete();

        $this->assertDatabaseMissing('user_profiles', ['id' => $profile->id]);
        $this->assertDatabaseMissing('profile_educations', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('profile_skills', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('profile_social_links', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('profile_completions', ['user_id' => $user->id]);
    }
}
