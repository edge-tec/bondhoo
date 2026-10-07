<?php

namespace Tests\Feature;

use App\Models\PrivacySetting;
use App\Models\ProfileEducation;
use App\Models\ProfileExperience;
use App\Models\ProfileInterest;
use App\Models\ProfileLanguage;
use App\Models\ProfileSkill;
use App\Models\ProfileSocialLink;
use App\Models\ProfileVerification;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Step18ProfileSecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
        Storage::fake('local');
    }

    protected function createUser(array $attributes = []): User
    {
        static $counter = 1000;
        $counter++;

        $user = User::factory()->create(array_merge([
            'username' => "sec_user_{$counter}",
            'name' => "Security User {$counter}",
            'email' => "sec_user_{$counter}@example.com",
            'phone' => "+880172200{$counter}",
            'status' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ], $attributes));

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => 'Original bio text',
                'about' => 'Original about description',
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'is_profile_locked' => false,
            ]
        );

        PrivacySetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'profile_visibility' => 'public',
                'email_privacy' => 'only_me',
                'phone_privacy' => 'only_me',
                'bio_privacy' => 'public',
                'about_privacy' => 'public',
                'education_privacy' => 'public',
                'work_privacy' => 'public',
                'skills_privacy' => 'public',
                'interests_privacy' => 'public',
                'languages_privacy' => 'public',
                'social_links_privacy' => 'public',
            ]
        );

        return $user;
    }

    // =========================================================================
    // 1. IDOR (Insecure Direct Object Reference) Prevention Tests
    // =========================================================================

    public function test_idor_prevented_updating_another_users_education(): void
    {
        $victim = $this->createUser();
        $attacker = $this->createUser();

        $edu = ProfileEducation::create([
            'user_id' => $victim->id,
            'institution_name' => 'Original University',
            'degree' => 'B.Sc.',
            'field_of_study' => 'Computer Science',
            'start_date' => '2018-01-01',
            'order' => 1,
        ]);

        $response = $this->actingAs($attacker, 'sanctum')->putJson("/api/v2/profile/education/{$edu->id}", [
            'institution' => 'Hacked University',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('profile_educations', [
            'id' => $edu->id,
            'institution_name' => 'Original University',
        ]);
    }

    public function test_idor_prevented_deleting_another_users_education(): void
    {
        $victim = $this->createUser();
        $attacker = $this->createUser();

        $edu = ProfileEducation::create([
            'user_id' => $victim->id,
            'institution_name' => 'Legit College',
            'degree' => 'HSC',
            'order' => 1,
        ]);

        $response = $this->actingAs($attacker, 'sanctum')->deleteJson("/api/v2/profile/education/{$edu->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('profile_educations', ['id' => $edu->id]);
    }

    public function test_idor_prevented_updating_another_users_work_experience(): void
    {
        $victim = $this->createUser();
        $attacker = $this->createUser();

        $exp = ProfileExperience::create([
            'user_id' => $victim->id,
            'company_name' => 'Original Tech Corp',
            'job_title' => 'Senior Engineer',
            'employment_type' => 'full_time',
            'start_date' => '2020-01-01',
            'is_current' => true,
            'order' => 1,
        ]);

        $response = $this->actingAs($attacker, 'sanctum')->putJson("/api/v2/profile/work/{$exp->id}", [
            'company' => 'Malicious Company Corp',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('profile_experiences', [
            'id' => $exp->id,
            'company_name' => 'Original Tech Corp',
        ]);
    }

    public function test_idor_prevented_deleting_another_users_work_experience(): void
    {
        $victim = $this->createUser();
        $attacker = $this->createUser();

        $exp = ProfileExperience::create([
            'user_id' => $victim->id,
            'company_name' => 'Established Ltd',
            'job_title' => 'Lead Architect',
            'employment_type' => 'full_time',
            'start_date' => '2021-01-01',
            'order' => 1,
        ]);

        $response = $this->actingAs($attacker, 'sanctum')->deleteJson("/api/v2/profile/work/{$exp->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('profile_experiences', ['id' => $exp->id]);
    }

    public function test_idor_prevented_removing_another_users_skill(): void
    {
        $victim = $this->createUser();
        $attacker = $this->createUser();

        $skill = ProfileSkill::create([
            'user_id' => $victim->id,
            'name' => 'Laravel Security',
            'order' => 1,
        ]);

        $response = $this->actingAs($attacker, 'sanctum')->deleteJson("/api/v2/profile/skills/{$skill->id}");

        $this->assertTrue(in_array($response->status(), [403, 404]));
        $this->assertDatabaseHas('profile_skills', [
            'id' => $skill->id,
            'user_id' => $victim->id,
        ]);
    }

    public function test_idor_prevented_removing_another_users_interest(): void
    {
        $victim = $this->createUser();
        $attacker = $this->createUser();

        $interest = ProfileInterest::create([
            'user_id' => $victim->id,
            'name' => 'Cybersecurity',
            'order' => 1,
        ]);

        $response = $this->actingAs($attacker, 'sanctum')->deleteJson("/api/v2/profile/interests/{$interest->id}");

        $this->assertTrue(in_array($response->status(), [403, 404]));
        $this->assertDatabaseHas('profile_interests', [
            'id' => $interest->id,
            'user_id' => $victim->id,
        ]);
    }

    public function test_idor_prevented_updating_another_users_language(): void
    {
        $victim = $this->createUser();
        $attacker = $this->createUser();

        $pLang = ProfileLanguage::create([
            'user_id' => $victim->id,
            'language' => 'Japanese',
            'proficiency' => 'basic',
            'order' => 1,
        ]);

        $response = $this->actingAs($attacker, 'sanctum')->putJson("/api/v2/profile/languages/{$pLang->id}", [
            'proficiency' => 'native',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('profile_languages', [
            'id' => $pLang->id,
            'proficiency' => 'basic',
        ]);
    }

    public function test_idor_prevented_deleting_another_users_language(): void
    {
        $victim = $this->createUser();
        $attacker = $this->createUser();

        $pLang = ProfileLanguage::create([
            'user_id' => $victim->id,
            'language' => 'German',
            'proficiency' => 'fluent',
            'order' => 1,
        ]);

        $response = $this->actingAs($attacker, 'sanctum')->deleteJson("/api/v2/profile/languages/{$pLang->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('profile_languages', ['id' => $pLang->id]);
    }

    public function test_idor_prevented_updating_another_users_social_link(): void
    {
        $victim = $this->createUser();
        $attacker = $this->createUser();

        $link = ProfileSocialLink::create([
            'user_id' => $victim->id,
            'platform' => 'github',
            'url' => 'https://github.com/victimuser',
            'username' => 'victimuser',
            'order' => 1,
        ]);

        $response = $this->actingAs($attacker, 'sanctum')->putJson("/api/v2/profile/social-links/{$link->id}", [
            'url' => 'https://github.com/attackerpwned',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('profile_social_links', [
            'id' => $link->id,
            'url' => 'https://github.com/victimuser',
        ]);
    }

    public function test_idor_prevented_deleting_another_users_social_link(): void
    {
        $victim = $this->createUser();
        $attacker = $this->createUser();

        $link = ProfileSocialLink::create([
            'user_id' => $victim->id,
            'platform' => 'linkedin',
            'url' => 'https://linkedin.com/in/victimuser',
            'username' => 'victimuser',
            'order' => 1,
        ]);

        $response = $this->actingAs($attacker, 'sanctum')->deleteJson("/api/v2/profile/social-links/{$link->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('profile_social_links', ['id' => $link->id]);
    }

    public function test_idor_prevented_reordering_another_users_items(): void
    {
        $victim = $this->createUser();
        $attacker = $this->createUser();

        $e1 = ProfileEducation::create([
            'user_id' => $victim->id,
            'institution_name' => 'School A',
            'display_order' => 1,
        ]);
        $e2 = ProfileEducation::create([
            'user_id' => $victim->id,
            'institution_name' => 'School B',
            'display_order' => 2,
        ]);

        $response = $this->actingAs($attacker, 'sanctum')->postJson('/api/v2/profile/education/reorder', [
            'ids' => [$e2->id, $e1->id],
        ]);

        // Victim's items must remain completely unchanged despite attacker's reorder request
        $this->assertEquals(1, $e1->fresh()->display_order);
        $this->assertEquals(2, $e2->fresh()->display_order);
    }

    // =========================================================================
    // 2. Broken Access Control & Privilege Escalation Tests
    // =========================================================================

    public function test_broken_access_control_regular_user_cannot_access_admin_verification_list(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v2/admin/verifications');

        $this->assertTrue(in_array($response->status(), [401, 403]));
    }

    public function test_broken_access_control_regular_user_cannot_review_verification_request(): void
    {
        $user = $this->createUser();
        $applicant = $this->createUser();

        $verification = ProfileVerification::create([
            'user_id' => $applicant->id,
            'verification_type' => ProfileVerification::TYPE_NID,
            'document_number' => '1234567890',
            'status' => ProfileVerification::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v2/admin/verifications/{$verification->id}/approve");

        $this->assertTrue(in_array($response->status(), [401, 403]));
        $this->assertEquals(ProfileVerification::STATUS_PENDING, $verification->fresh()->status);
    }

    public function test_unauthenticated_requests_blocked_from_all_profile_mutation_endpoints(): void
    {
        // Edit Profile
        $this->putJson('/api/v2/profile', ['first_name' => 'Ghost'])->assertStatus(401);

        // Avatar
        $this->postJson('/api/v2/profile/avatar', [])->assertStatus(401);

        // Cover
        $this->postJson('/api/v2/profile/cover', [])->assertStatus(401);

        // Username
        $this->putJson('/api/v2/profile/username', ['username' => 'ghostly'])->assertStatus(401);

        // About
        $this->putJson('/api/v2/profile/about', ['bio' => 'Hacked'])->assertStatus(401);

        // Privacy
        $this->putJson('/api/v2/profile/privacy/settings', ['profile_visibility' => 'only_me'])->assertStatus(401);

        // Verification
        $this->postJson('/api/v2/profile/verification/submit', [])->assertStatus(401);
    }

    // =========================================================================
    // 3. XSS (Cross-Site Scripting) Neutralization Tests
    // =========================================================================

    public function test_xss_payload_in_bio_is_neutralized_or_escaped(): void
    {
        $user = $this->createUser();
        $xssPayload = '<script>alert("XSS_BIO")</script><b>Injected</b>';

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v2/profile/about', [
            'bio' => $xssPayload,
        ]);

        $response->assertStatus(200);
        $savedBio = $user->profile->fresh()->bio;

        $this->assertStringNotContainsString('<script>', $savedBio);
    }

    public function test_xss_payload_in_about_section_is_sanitized(): void
    {
        $user = $this->createUser();
        $xssPayload = '<img src=x onerror=alert(document.cookie)>Dangerous Content';

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v2/profile/about', [
            'about' => $xssPayload,
        ]);

        $response->assertStatus(200);
        $savedAbout = $user->profile->fresh()->about;

        $this->assertStringNotContainsString('onerror=', $savedAbout);
    }

    public function test_xss_payload_in_education_institution_is_neutralized(): void
    {
        $user = $this->createUser();
        $xssPayload = '<svg onload=alert(1)>Oxford';

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/education', [
            'institution' => $xssPayload,
            'degree' => 'B.A.',
            'start_date' => '2020-01-01',
        ]);

        $response->assertStatus(201);
        $saved = ProfileEducation::where('user_id', $user->id)->first();
        $this->assertStringNotContainsString('<svg onload=', $saved->institution_name);
    }

    public function test_xss_payload_in_work_experience_company_is_neutralized(): void
    {
        $user = $this->createUser();
        $xssPayload = '<iframe src="javascript:alert(1)">Google';

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/work', [
            'company' => $xssPayload,
            'position' => 'Dev',
            'employment_type' => 'full_time',
            'start_date' => '2020-01-01',
        ]);

        $response->assertStatus(201);
        $saved = ProfileExperience::where('user_id', $user->id)->first();
        $this->assertStringNotContainsString('<iframe', $saved->company_name);
    }

    public function test_xss_payload_in_skill_name_is_neutralized(): void
    {
        $user = $this->createUser();
        $xssPayload = '<script>alert("SKILL")</script>ReactJS';

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/skills', [
            'name' => $xssPayload,
        ]);

        $response->assertStatus(201);
        $skill = ProfileSkill::where('user_id', $user->id)->first();
        $this->assertNotNull($skill);
        $this->assertStringNotContainsString('<script>', $skill->name);
    }

    public function test_xss_payload_in_interest_name_is_neutralized(): void
    {
        $user = $this->createUser();
        $xssPayload = '<b onmouseover=alert(1)>Photography</b>';

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/interests', [
            'name' => $xssPayload,
        ]);

        $response->assertStatus(201);
        $interest = ProfileInterest::where('user_id', $user->id)->first();
        $this->assertNotNull($interest);
        $this->assertStringNotContainsString('onmouseover=', $interest->name);
    }

    public function test_xss_payload_in_language_name_is_neutralized(): void
    {
        $user = $this->createUser();
        $xssPayload = '<script>alert(1)</script>French';

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/languages', [
            'language' => $xssPayload,
            'proficiency' => 'conversational',
        ]);

        $response->assertStatus(201);
        $lang = ProfileLanguage::where('user_id', $user->id)->first();
        $this->assertNotNull($lang);
        $this->assertStringNotContainsString('<script>', $lang->language);
    }

    // =========================================================================
    // 4. Malicious URL Scheme & Pseudoprotocol Blocks (Social Links)
    // =========================================================================

    public function test_social_link_blocks_javascript_pseudoprotocol_url(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/social-links', [
            'platform' => 'Website',
            'url' => 'javascript:alert("PWNED")',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('profile_social_links', [
            'user_id' => $user->id,
            'url' => 'javascript:alert("PWNED")',
        ]);
    }

    public function test_social_link_blocks_data_pseudoprotocol_url(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/social-links', [
            'platform' => 'Website',
            'url' => 'data:text/html,<script>alert(1)</script>',
        ]);

        $response->assertStatus(422);
    }

    public function test_social_link_blocks_vbscript_pseudoprotocol_url(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/social-links', [
            'platform' => 'Website',
            'url' => 'vbscript:msgbox("hello")',
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // 5. SQL Injection Resilience Tests
    // =========================================================================

    public function test_sql_injection_attempt_in_username_route_fails_safely(): void
    {
        $sqliPayload = "admin' OR '1'='1' --";

        $response = $this->getJson("/api/v1/users/{$sqliPayload}");

        $this->assertTrue(in_array($response->status(), [404, 422]));
    }

    public function test_sql_injection_attempt_in_skill_search_query_fails_safely(): void
    {
        $user = $this->createUser();
        $sqliQuery = "'; DROP TABLE profile_skills; --";

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v2/profile/skills?q='.urlencode($sqliQuery));

        $response->assertStatus(200);
        $this->assertTrue(Schema::hasTable('profile_skills'));
    }

    public function test_sql_injection_attempt_in_interest_search_query_fails_safely(): void
    {
        $user = $this->createUser();
        $sqliQuery = "' UNION SELECT password, email FROM users --";

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v2/profile/interests?q='.urlencode($sqliQuery));

        $response->assertStatus(200);
        $responseContent = $response->getContent();
        $this->assertStringNotContainsString('password', $responseContent);
    }

    // =========================================================================
    // 6. File Upload Vulnerability & Exploitation Protection Tests
    // =========================================================================

    public function test_file_upload_blocks_executable_file_extensions_for_avatar(): void
    {
        $user = $this->createUser();
        $maliciousFile = UploadedFile::fake()->create('shell.php', 100, 'application/x-php');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/avatar', [
            'avatar' => $maliciousFile,
        ]);

        $response->assertStatus(422);
    }

    public function test_file_upload_blocks_executable_file_extensions_for_cover(): void
    {
        $user = $this->createUser();
        $maliciousFile = UploadedFile::fake()->create('backdoor.sh', 50, 'text/x-shellscript');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/cover', [
            'cover' => $maliciousFile,
        ]);

        $response->assertStatus(422);
    }

    public function test_file_upload_enforces_maximum_file_size_limits(): void
    {
        $user = $this->createUser();
        $oversizedFile = UploadedFile::fake()->create('giant_photo.jpg', 25000, 'image/jpeg');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/avatar', [
            'avatar' => $oversizedFile,
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // 7. Mass Assignment Protection Tests
    // =========================================================================

    public function test_mass_assignment_prevented_cannot_inject_is_verified_via_profile_update(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v2/profile', [
            'first_name' => 'Honest',
            'is_verified' => true,
            'status' => 'verified',
        ]);

        $response->assertStatus(200);
        $this->assertFalse((bool) ($user->profile->fresh()->is_verified ?? false));
    }

    public function test_mass_assignment_prevented_cannot_inject_user_id_via_profile_update(): void
    {
        $victim = $this->createUser();
        $attacker = $this->createUser();

        $response = $this->actingAs($attacker, 'sanctum')->putJson('/api/v2/profile', [
            'user_id' => $victim->id,
            'id' => $victim->id,
            'first_name' => 'Name Takeover',
        ]);

        $response->assertStatus(200);
        $this->assertNotEquals('Name Takeover', $victim->fresh()->name);
    }

    // =========================================================================
    // 8. Rate Limiting Tests
    // =========================================================================

    public function test_rate_limiting_enforced_on_profile_view_recording(): void
    {
        $owner = $this->createUser(['username' => 'popular_star']);
        $viewer = $this->createUser();

        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/profile/{$owner->username}/view");
        }

        $throttledResponse = $this->actingAs($viewer, 'sanctum')->postJson("/api/v2/profile/{$owner->username}/view");
        $throttledResponse->assertStatus(429);
    }

    // =========================================================================
    // 9. Username Enumeration & Privacy Leakage Prevention Tests
    // =========================================================================

    public function test_username_enumeration_protection_on_private_profiles(): void
    {
        $user = $this->createUser(['username' => 'private_citizen']);
        $user->privacySettings()->update(['profile_visibility' => 'only_me']);

        $response = $this->getJson("/api/v2/profile/{$user->username}");

        // Public/guest viewing only_me profile returns 403 Forbidden with zero PII
        $response->assertStatus(403);
        $responseContent = $response->getContent();
        $this->assertStringNotContainsString($user->email, $responseContent);
        $this->assertStringNotContainsString($user->phone, $responseContent);
    }

    public function test_privacy_leakage_prevented_hidden_email_not_exposed_to_guests(): void
    {
        $user = $this->createUser(['username' => 'public_star']);
        $user->privacySettings()->update([
            'profile_visibility' => 'public',
            'email_privacy' => 'only_me',
        ]);

        $response = $this->getJson("/api/v2/profile/{$user->username}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNull($data['email'] ?? null);
    }

    public function test_privacy_leakage_prevented_hidden_phone_not_exposed_to_guests(): void
    {
        $user = $this->createUser(['username' => 'public_star2']);
        $user->privacySettings()->update([
            'profile_visibility' => 'public',
            'phone_privacy' => 'only_me',
        ]);

        $response = $this->getJson("/api/v2/profile/{$user->username}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNull($data['phone'] ?? null);
    }

    public function test_privacy_leakage_prevented_only_me_sections_hidden_from_public(): void
    {
        $user = $this->createUser(['username' => 'secretive_user']);
        $user->privacySettings()->update([
            'profile_visibility' => 'public',
            'education_privacy' => 'only_me',
            'work_privacy' => 'only_me',
            'skills_privacy' => 'only_me',
        ]);

        ProfileEducation::create([
            'user_id' => $user->id,
            'institution_name' => 'Classified University',
            'order' => 1,
        ]);

        $response = $this->getJson("/api/v2/profile/{$user->username}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEmpty($data['education'] ?? []);
        $this->assertEmpty($data['work'] ?? []);
        $this->assertEmpty($data['skills'] ?? []);
    }

    // =========================================================================
    // 10. Locked Profile Protection Tests
    // =========================================================================

    public function test_unauthorized_profile_modification_prevented_for_non_owner(): void
    {
        $victim = $this->createUser(['username' => 'target_victim']);
        $attacker = $this->createUser(['username' => 'malicious_actor']);

        $response = $this->actingAs($attacker, 'sanctum')->putJson("/api/v2/profile/{$victim->id}", [
            'first_name' => 'Attempted Takeover',
        ]);

        $response->assertStatus(403);
        $this->assertNotEquals('Attempted Takeover', $victim->fresh()->name);
    }
}
