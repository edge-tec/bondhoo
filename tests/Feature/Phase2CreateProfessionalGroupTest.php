<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2CreateProfessionalGroupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_step1_and_step6_create_group_stores_basic_info_and_assigns_owner(): void
    {
        $creator = User::factory()->create();

        $payload = [
            'name' => 'Silicon Valley AI Founders Hub',
            'username' => 'sv-ai-founders',
            'description' => 'A premier professional community for AI startup founders, angel investors, and researchers.',
            'category' => 'Startups',
            'industry' => 'Artificial Intelligence & SaaS',
            'website' => 'https://sv-ai-founders.example.com',
            'location' => 'San Francisco, CA, USA',
            'contact_email' => 'contact@sv-ai-founders.example.com',
            'contact_phone' => '+1-415-555-0199',
            'privacy' => 'public',
        ];

        $response = $this->actingAs($creator, 'sanctum')
            ->postJson('/api/v1/groups', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Silicon Valley AI Founders Hub')
            ->assertJsonPath('data.username', 'sv-ai-founders')
            ->assertJsonPath('data.category', 'Startups')
            ->assertJsonPath('data.industry', 'Artificial Intelligence & SaaS')
            ->assertJsonPath('data.website', 'https://sv-ai-founders.example.com')
            ->assertJsonPath('data.contact_email', 'contact@sv-ai-founders.example.com')
            ->assertJsonPath('data.privacy', 'public');

        $groupId = $response->json('data.id');
        $group = Group::findOrFail($groupId);

        // Verify creator assigned as Group Owner
        $this->assertTrue($group->isOwner($creator->id));
        $this->assertTrue($group->isAdmin($creator->id));

        $membership = GroupMember::where('group_id', $groupId)->where('user_id', $creator->id)->first();
        $this->assertNotNull($membership);
        $this->assertEquals(GroupMember::ROLE_OWNER, $membership->role);
        $this->assertTrue($membership->isActive());
        $this->assertContains('delete_group', $membership->permissions ?? []);
        $this->assertContains('manage_roles', $membership->permissions ?? []);

        // Verify URLs generated
        $this->assertStringContainsString('/groups/sv-ai-founders', $response->json('data.url'));
        $this->assertStringContainsString('/groups/sv-ai-founders/dashboard', $response->json('data.dashboard_url'));
    }

    public function test_step2_supports_public_private_and_hidden_group_visibility(): void
    {
        $creator = User::factory()->create();
        $outsider = User::factory()->create();

        // 1. Public Group
        $resPublic = $this->actingAs($creator, 'sanctum')->postJson('/api/v1/groups', [
            'name' => 'Open Tech Global Guild',
            'privacy' => 'public',
        ]);
        $resPublic->assertCreated();
        $pubGroup = Group::findOrFail($resPublic->json('data.id'));
        $this->assertEquals('public', $pubGroup->privacy);
        $this->assertTrue($pubGroup->canView($outsider));

        // 2. Private Group
        $resPrivate = $this->actingAs($creator, 'sanctum')->postJson('/api/v1/groups', [
            'name' => 'Executive Fintech Circle',
            'privacy' => 'private',
            'membership_approval_mode' => 'approval_required',
        ]);
        $resPrivate->assertCreated();
        $privGroup = Group::findOrFail($resPrivate->json('data.id'));
        $this->assertEquals('private', $privGroup->privacy);
        $this->assertEquals('approval_required', $privGroup->membership_approval_mode);

        // 3. Hidden Group
        $resHidden = $this->actingAs($creator, 'sanctum')->postJson('/api/v1/groups', [
            'name' => 'Secret Board of Directors',
            'privacy' => 'hidden',
        ]);
        $resHidden->assertCreated();
        $hiddenGroup = Group::findOrFail($resHidden->json('data.id'));
        $this->assertEquals('hidden', $hiddenGroup->privacy);
        $this->assertFalse($hiddenGroup->canView($outsider));
        $this->assertTrue($hiddenGroup->canView($creator));
    }

    public function test_step3_and_step4_membership_and_content_settings(): void
    {
        $creator = User::factory()->create();

        $payload = [
            'name' => 'Enterprise Product Leaders',
            'privacy' => 'private',
            // Membership Settings
            'membership_approval_mode' => 'admin_only',
            'allow_member_invitations' => false,
            'allow_member_recommendations' => true,
            'enable_membership_screening' => true,
            'max_members_limit' => 500,
            'auto_approve_trusted' => true,
            // Content Settings
            'who_can_post' => 'admins_moderators_only',
            'who_can_comment' => 'anyone_in_group',
            'who_can_upload_media' => 'admins_only',
            'post_approval_mode' => 'admin_approval',
            'enable_comment_moderation' => true,
            'allow_anonymous_posts' => false,
            'allow_scheduled_posts' => true,
        ];

        $response = $this->actingAs($creator, 'sanctum')
            ->postJson('/api/v1/groups', $payload);

        $response->assertCreated();
        $groupId = $response->json('data.id');
        $group = Group::findOrFail($groupId);

        $this->assertEquals('admin_only', $group->membership_approval_mode);
        $this->assertEquals('admin_approval', $group->post_approval_mode);

        $settings = $group->settings;
        $this->assertFalse($settings['allow_member_invitations']);
        $this->assertTrue($settings['allow_member_recommendations']);
        $this->assertTrue($settings['enable_membership_screening']);
        $this->assertEquals(500, $settings['max_members_limit']);
        $this->assertTrue($settings['auto_approve_trusted']);
        $this->assertEquals('admins_moderators_only', $settings['who_can_post']);
        $this->assertEquals('admins_only', $settings['who_can_upload_media']);
        $this->assertTrue($settings['enable_comment_moderation']);
        $this->assertFalse($settings['allow_anonymous_posts']);
        $this->assertTrue($settings['allow_scheduled_posts']);
    }

    public function test_step5_group_rules_auto_seeds_defaults_and_supports_crud_and_reorder(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();

        // 1. Group creation without custom rules automatically seeds 5 professional rules
        $resGroup = $this->actingAs($creator, 'sanctum')->postJson('/api/v1/groups', [
            'name' => 'Dhaka Freelancers Community',
            'privacy' => 'public',
        ]);
        $resGroup->assertCreated();
        $groupId = $resGroup->json('data.id');
        $group = Group::findOrFail($groupId);

        $this->assertCount(5, $group->rules);
        $ruleTitles = $group->rules->pluck('title')->all();
        $this->assertContains('Respect all members', $ruleTitles);
        $this->assertContains('No spam or misleading information', $ruleTitles);
        $this->assertContains('Professional discussions only', $ruleTitles);

        // 2. Add custom rule via API
        $resAddRule = $this->actingAs($creator, 'sanctum')
            ->postJson("/api/v1/groups/{$groupId}/rules", [
                'title' => 'No Unapproved Affiliate Links',
                'description' => 'Posting affiliate links without moderator approval is subject to post removal.',
                'enforcement_level' => 'strike',
            ]);
        $resAddRule->assertCreated()
            ->assertJsonPath('data.title', 'No Unapproved Affiliate Links');
        $newRuleId = $resAddRule->json('data.id');

        // 3. Regular member cannot edit rule
        $resForbiddenEdit = $this->actingAs($member, 'sanctum')
            ->putJson("/api/v1/groups/{$groupId}/rules/{$newRuleId}", [
                'title' => 'Hacked Rule Title',
            ]);
        $resForbiddenEdit->assertForbidden();

        // 4. Owner can edit rule
        $resEditRule = $this->actingAs($creator, 'sanctum')
            ->putJson("/api/v1/groups/{$groupId}/rules/{$newRuleId}", [
                'title' => 'Strictly No Affiliate or Referral Links',
                'description' => 'All affiliate links will result in immediate strike.',
                'enforcement_level' => 'removal',
            ]);
        $resEditRule->assertOk()
            ->assertJsonPath('data.title', 'Strictly No Affiliate or Referral Links');

        $this->assertDatabaseHas('group_rules', [
            'id' => $newRuleId,
            'title' => 'Strictly No Affiliate or Referral Links',
            'enforcement_level' => 'removal',
        ]);

        // 5. Reorder rules
        $allRules = GroupRule::where('group_id', $groupId)->pluck('id')->all();
        $reversed = array_reverse($allRules);

        $resReorder = $this->actingAs($creator, 'sanctum')
            ->postJson("/api/v1/groups/{$groupId}/rules/reorder", [
                'rule_ids' => $reversed,
            ]);
        $resReorder->assertOk();

        $firstRule = GroupRule::where('group_id', $groupId)->orderBy('sort_order')->first();
        $this->assertEquals($reversed[0], $firstRule->id);

        // 6. Delete rule
        $resDelete = $this->actingAs($creator, 'sanctum')
            ->deleteJson("/api/v1/groups/{$groupId}/rules/{$newRuleId}");
        $resDelete->assertOk();

        $this->assertDatabaseMissing('group_rules', [
            'id' => $newRuleId,
        ]);
    }
}
