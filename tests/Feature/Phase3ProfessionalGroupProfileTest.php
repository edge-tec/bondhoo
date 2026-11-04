<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupAnnouncement;
use App\Models\GroupCategory;
use App\Models\GroupEvent;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\GroupRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase3ProfessionalGroupProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        GroupCategory::firstOrCreate(
            ['slug' => 'technology'],
            [
                'name' => 'Technology',
                'description' => 'Tech discussions, software and engineering.',
                'icon' => 'Cpu',
                'color' => '#3B82F6',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }

    public function test_user_can_fetch_full_group_profile_by_slug_with_all_9_tabs_data(): void
    {
        $creator = User::factory()->create([
            'name' => 'Mizanur Rahman',
            'username' => 'mizanur',
            'email' => 'mizan@connectly.test',
        ]);

        $group = Group::create([
            'creator_id' => $creator->id,
            'name' => 'AI & Cloud Architects Global',
            'username' => 'ai-cloud-architects',
            'slug' => 'ai-cloud-architects-global',
            'description' => 'A premier professional community for AI engineers and Cloud architects.',
            'category' => 'Technology',
            'subcategory' => 'Cloud Computing',
            'privacy' => 'public',
            'location' => 'Dhaka, Bangladesh',
            'cover_image' => 'https://connectly.test/covers/tech.jpg',
            'avatar_url' => 'https://connectly.test/avatars/tech.jpg',
            'members_count' => 1,
            'posts_count' => 0,
            'is_verified' => true,
            'settings' => [
                'website' => 'https://ai-architects.org',
                'industry' => 'Software & Artificial Intelligence',
                'contact_email' => 'contact@ai-architects.org',
                'contact_phone' => '+8801700000000',
            ],
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $creator->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        GroupRule::create([
            'group_id' => $group->id,
            'title' => 'Professional Courtesy',
            'description' => 'Respect differing technical opinions and avoid inflammatory remarks.',
            'enforcement_level' => 'strict',
            'sort_order' => 1,
        ]);

        GroupAnnouncement::create([
            'group_id' => $group->id,
            'author_id' => $creator->id,
            'title' => 'Welcome to AI Architects',
            'content' => 'Welcome all new engineers to the community! Please review the rules.',
            'is_pinned' => true,
        ]);

        GroupEvent::create([
            'group_id' => $group->id,
            'creator_id' => $creator->id,
            'title' => 'Cloud Native Meetup 2026',
            'description' => 'Discussing microservices, Kubernetes, and AI workloads.',
            'location' => 'Virtual Zoom',
            'start_time' => now()->addDays(7),
            'end_time' => now()->addDays(7)->addHours(2),
            'is_online' => true,
            'status' => 'published',
        ]);

        GroupFile::create([
            'group_id' => $group->id,
            'user_id' => $creator->id,
            'name' => 'Architecture_Guidelines_v1.pdf',
            'file_url' => 'https://connectly.test/groups/files/guidelines.pdf',
            'file_type' => 'pdf',
            'file_size' => 2048576,
        ]);

        Sanctum::actingAs($creator);

        $response = $this->getJson("/api/v1/groups/{$group->slug}/profile");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.group.name', 'AI & Cloud Architects Global')
            ->assertJsonPath('data.group.username', 'ai-cloud-architects')
            ->assertJsonPath('data.group.is_verified', true)
            ->assertJsonPath('data.group.category', 'Technology')
            ->assertJsonPath('data.group.privacy', 'public')
            ->assertJsonPath('data.group.actions.can_manage', true)
            ->assertJsonPath('data.tabs.dashboard_access', true)
            ->assertJsonPath('data.tabs.overview.pinned_announcement.content', 'Welcome all new engineers to the community! Please review the rules.')
            ->assertJsonPath('data.tabs.about.rules.0.title', 'Professional Courtesy')
            ->assertJsonPath('data.tabs.about.website', 'https://ai-architects.org');
    }

    public function test_group_profile_header_actions_join_cancel_leave_and_follow(): void
    {
        $owner = User::factory()->create(['name' => 'Group Owner']);
        $member = User::factory()->create(['name' => 'Prospective Member']);

        $privateGroup = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Private Executive Network',
            'username' => 'exec-network',
            'slug' => 'private-executive-network',
            'privacy' => 'private',
            'category' => 'Business',
            'membership_approval_mode' => 'admin_approval',
            'members_count' => 1,
        ]);

        GroupMember::create([
            'group_id' => $privateGroup->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($member);

        // ১. Request to Join private group
        $joinResponse = $this->postJson("/api/v1/groups/{$privateGroup->id}/join");
        $joinResponse->assertStatus(200);

        // Profile should show pending and can_cancel_request
        $profileResponse = $this->getJson("/api/v1/groups/{$privateGroup->slug}/profile");
        $profileResponse->assertStatus(200)
            ->assertJsonPath('data.group.is_pending', true)
            ->assertJsonPath('data.group.actions.can_cancel_request', true);

        // ২. Cancel join request
        $cancelResponse = $this->postJson("/api/v1/groups/{$privateGroup->id}/cancel-request");
        $cancelResponse->assertStatus(200);

        $profileAfterCancel = $this->getJson("/api/v1/groups/{$privateGroup->slug}/profile");
        $profileAfterCancel->assertStatus(200)
            ->assertJsonPath('data.group.is_pending', false)
            ->assertJsonPath('data.group.actions.can_request', true);

        // ৩. Public group join and leave
        $publicGroup = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Public Tech Club',
            'username' => 'tech-club',
            'slug' => 'public-tech-club',
            'privacy' => 'public',
            'category' => 'Technology',
            'membership_approval_mode' => 'anyone',
            'members_count' => 1,
        ]);

        $joinPublic = $this->postJson("/api/v1/groups/{$publicGroup->id}/join");
        $joinPublic->assertStatus(200);

        $profilePublic = $this->getJson("/api/v1/groups/{$publicGroup->slug}/profile");
        $profilePublic->assertStatus(200)
            ->assertJsonPath('data.group.is_member', true)
            ->assertJsonPath('data.group.actions.can_leave', true);

        // ৪. Toggle follow
        $followResponse1 = $this->postJson("/api/v1/groups/{$publicGroup->id}/follow");
        $followResponse1->assertStatus(200)
            ->assertJsonPath('data.is_following', true);

        $followResponse2 = $this->postJson("/api/v1/groups/{$publicGroup->id}/follow");
        $followResponse2->assertStatus(200)
            ->assertJsonPath('data.is_following', false);

        // ৫. Leave group
        $leaveResponse = $this->postJson("/api/v1/groups/{$publicGroup->id}/leave");
        $leaveResponse->assertStatus(200);

        $profileAfterLeave = $this->getJson("/api/v1/groups/{$publicGroup->slug}/profile");
        $profileAfterLeave->assertStatus(200)
            ->assertJsonPath('data.group.is_member', false)
            ->assertJsonPath('data.group.actions.can_join', true);
    }

    public function test_individual_group_tabs_endpoints(): void
    {
        $user = User::factory()->create();
        $group = Group::create([
            'creator_id' => $user->id,
            'name' => 'Full Tabs Testing Group',
            'username' => 'full-tabs-group',
            'slug' => 'full-tabs-testing-group',
            'privacy' => 'public',
            'category' => 'Technology',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/groups/{$group->id}/overview")->assertStatus(200)->assertJsonPath('success', true);
        $this->getJson("/api/v1/groups/{$group->id}/announcements")->assertStatus(200)->assertJsonPath('success', true);
        $this->getJson("/api/v1/groups/{$group->id}/media")->assertStatus(200)->assertJsonPath('success', true);
        $this->getJson("/api/v1/groups/{$group->id}/members")->assertStatus(200)->assertJsonPath('success', true);
        $this->getJson("/api/v1/groups/{$group->id}/events")->assertStatus(200)->assertJsonPath('success', true);
        $this->getJson("/api/v1/groups/{$group->id}/files")->assertStatus(200)->assertJsonPath('success', true);
        $this->getJson("/api/v1/groups/{$group->id}/rules")->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_hidden_group_visibility_control(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $hiddenGroup = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Secret Boardroom',
            'username' => 'secret-boardroom',
            'slug' => 'secret-boardroom-group',
            'privacy' => 'hidden',
            'category' => 'Business',
            'members_count' => 1,
        ]);

        GroupMember::create([
            'group_id' => $hiddenGroup->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        // Outsider cannot access hidden group
        Sanctum::actingAs($outsider);
        $outsiderResponse = $this->getJson("/api/v1/groups/{$hiddenGroup->slug}/profile");
        $outsiderResponse->assertStatus(403);

        // Owner/Member can access hidden group
        Sanctum::actingAs($owner);
        $ownerResponse = $this->getJson("/api/v1/groups/{$hiddenGroup->slug}/profile");
        $ownerResponse->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_dashboard_tab_restricted_to_group_managers(): void
    {
        $owner = User::factory()->create(['name' => 'Owner']);
        $regularMember = User::factory()->create(['name' => 'Regular Member']);

        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Leadership Cohort',
            'username' => 'leadership-cohort',
            'slug' => 'leadership-cohort',
            'privacy' => 'public',
            'category' => 'Career Development',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $regularMember->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        // Regular member has dashboard_access = false
        Sanctum::actingAs($regularMember);
        $memberProfile = $this->getJson("/api/v1/groups/{$group->slug}/profile");
        $memberProfile->assertStatus(200)
            ->assertJsonPath('data.tabs.dashboard_access', false);

        // Owner has dashboard_access = true
        Sanctum::actingAs($owner);
        $ownerProfile = $this->getJson("/api/v1/groups/{$group->slug}/profile");
        $ownerProfile->assertStatus(200)
            ->assertJsonPath('data.tabs.dashboard_access', true);
    }
}
