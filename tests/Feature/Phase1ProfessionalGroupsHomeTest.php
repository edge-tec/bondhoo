<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMember;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1ProfessionalGroupsHomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_categories_endpoint_returns_all_19_professional_categories(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/groups/categories');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $categories = collect($response->json('data'));
        $this->assertGreaterThanOrEqual(19, $categories->count());

        $expectedCategories = [
            'Technology',
            'Software Development',
            'Business',
            'Entrepreneurship',
            'Digital Marketing',
            'Affiliate Marketing',
            'Freelancing',
            'Finance',
            'Education',
            'Design & Creative',
            'Engineering',
            'Healthcare',
            'Science',
            'Career Development',
            'Job & Recruitment',
            'Startups',
            'E-commerce',
            'Networking',
            'General Community',
        ];

        foreach ($expectedCategories as $categoryName) {
            $this->assertTrue(
                $categories->contains('name', $categoryName),
                "Category '{$categoryName}' is missing from categories list."
            );
        }
    }

    public function test_admin_can_create_new_group_category_and_regular_user_cannot(): void
    {
        $regularUser = User::factory()->create(['email' => 'regular@example.com']);
        $adminUser = User::factory()->create(['email' => 'admin@connectly.test']);

        // Regular user attempt
        $resForbidden = $this->actingAs($regularUser, 'sanctum')
            ->postJson('/api/v1/groups/categories', [
                'name' => 'Biotechnology & Genetics',
                'icon' => 'dna-outline',
                'description' => 'Discussions on bioinformatics, crispr, and biotech ventures.',
            ]);
        $resForbidden->assertForbidden();

        // Admin attempt
        $resAdmin = $this->actingAs($adminUser, 'sanctum')
            ->postJson('/api/v1/groups/categories', [
                'name' => 'Biotechnology & Genetics',
                'icon' => 'dna-outline',
                'description' => 'Discussions on bioinformatics, crispr, and biotech ventures.',
            ]);

        $resAdmin->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Biotechnology & Genetics')
            ->assertJsonPath('data.slug', 'biotechnology-genetics');

        $this->assertDatabaseHas('group_categories', [
            'name' => 'Biotechnology & Genetics',
            'slug' => 'biotechnology-genetics',
        ]);
    }

    public function test_groups_home_endpoint_returns_all_required_sections(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // 1. Group managed by user
        $managedGroup = Group::factory()->create([
            'name' => 'Laravel Bangladesh Pros',
            'privacy' => Group::PRIVACY_PUBLIC,
            'creator_id' => $user->id,
            'category' => 'Software Development',
            'members_count' => 150,
            'status' => Group::STATUS_ACTIVE,
        ]);
        GroupMember::create([
            'group_id' => $managedGroup->id,
            'user_id' => $user->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        // 2. Group joined by user (regular member)
        $joinedGroup = Group::factory()->create([
            'name' => 'React & Next.js Developers',
            'privacy' => Group::PRIVACY_PUBLIC,
            'creator_id' => $otherUser->id,
            'category' => 'Technology',
            'members_count' => 320,
            'status' => Group::STATUS_ACTIVE,
        ]);
        GroupMember::create([
            'group_id' => $joinedGroup->id,
            'user_id' => $user->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        // 3. Suggested / Discover Group (not joined)
        $suggestedGroup = Group::factory()->create([
            'name' => 'Silicon Valley Startups Hub',
            'privacy' => Group::PRIVACY_PUBLIC,
            'creator_id' => $otherUser->id,
            'category' => 'Startups',
            'members_count' => 5000,
            'health_score' => 98.5,
            'status' => Group::STATUS_ACTIVE,
        ]);

        // 4. Pending invitation for user
        $invitationGroup = Group::factory()->create([
            'name' => 'Fintech Innovators Network',
            'privacy' => Group::PRIVACY_PRIVATE,
            'creator_id' => $otherUser->id,
            'category' => 'Finance',
            'members_count' => 45,
            'status' => Group::STATUS_ACTIVE,
        ]);
        GroupInvitation::create([
            'group_id' => $invitationGroup->id,
            'inviter_id' => $otherUser->id,
            'invitee_id' => $user->id,
            'status' => GroupInvitation::STATUS_PENDING,
            'role' => GroupMember::ROLE_MEMBER,
            'token' => 'test-invitation-token-1234',
        ]);

        // 5. Activity feed post
        Post::factory()->create([
            'user_id' => $user->id,
            'group_id' => $managedGroup->id,
            'content' => 'Welcome to Laravel Bangladesh Pros official group!',
            'status' => 'published',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/groups/home');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'featured_group',
                    'my_groups',
                    'groups_you_manage',
                    'suggested_groups',
                    'discover_communities',
                    'trending_groups',
                    'recently_active_groups',
                    'groups_you_may_like',
                    'categories',
                    'pending_invitations',
                    'activity_feed',
                ],
            ]);

        $data = $response->json('data');

        // Verify My Groups contains both managed and joined
        $myGroupIds = collect($data['my_groups'])->pluck('id')->all();
        $this->assertContains($managedGroup->id, $myGroupIds);
        $this->assertContains($joinedGroup->id, $myGroupIds);

        // Verify Groups You Manage contains managed group
        $manageGroupIds = collect($data['groups_you_manage'])->pluck('id')->all();
        $this->assertContains($managedGroup->id, $manageGroupIds);

        // Verify Pending Invitations
        $pendingInvGroupIds = collect($data['pending_invitations'])->pluck('group_id')->all();
        $this->assertContains($invitationGroup->id, $pendingInvGroupIds);

        // Verify Categories
        $this->assertNotEmpty($data['categories']);

        // Verify Activity Feed
        $this->assertNotEmpty($data['activity_feed']);
        $this->assertEquals($managedGroup->id, $data['activity_feed'][0]['group']['id']);
    }

    public function test_group_search_with_comprehensive_filters(): void
    {
        $creator = User::factory()->create();

        // Seed diverse test groups
        $techGroup = Group::factory()->create([
            'name' => 'Dhaka AI & Machine Learning Community',
            'username' => 'dhaka_ai',
            'category' => 'Technology',
            'subcategory' => 'Artificial Intelligence',
            'location' => 'Dhaka, Bangladesh',
            'privacy' => Group::PRIVACY_PUBLIC,
            'members_count' => 1200,
            'posts_count' => 45,
            'description' => 'A premier community for ML researchers and PyTorch engineers in Dhaka.',
            'status' => Group::STATUS_ACTIVE,
            'creator_id' => $creator->id,
        ]);

        $healthGroup = Group::factory()->create([
            'name' => 'Chittagong Doctors & Healthcare Network',
            'username' => 'ctg_doctors',
            'category' => 'Healthcare',
            'subcategory' => 'Medical Practice',
            'location' => 'Chittagong, Bangladesh',
            'privacy' => Group::PRIVACY_PRIVATE,
            'members_count' => 150,
            'posts_count' => 10,
            'description' => 'Private medical discussion forum for licensed physicians.',
            'status' => Group::STATUS_ACTIVE,
            'creator_id' => $creator->id,
        ]);

        $designGroup = Group::factory()->create([
            'name' => 'Creative UI/UX Designers Guild',
            'username' => 'designers_guild',
            'category' => 'Design & Creative',
            'subcategory' => 'Product Design',
            'location' => 'Remote / Global',
            'privacy' => Group::PRIVACY_PUBLIC,
            'members_count' => 850,
            'posts_count' => 0,
            'description' => 'Figma, design systems, and UX portfolio critiques.',
            'status' => Group::STATUS_ACTIVE,
            'creator_id' => $creator->id,
        ]);

        $user = User::factory()->create();

        // 1. Search by name query
        $resName = $this->actingAs($user, 'sanctum')->getJson('/api/v1/groups?q=Dhaka AI');
        $resName->assertOk();
        $ids = collect($resName->json('data'))->pluck('id')->all();
        $this->assertContains($techGroup->id, $ids);
        $this->assertNotContains($healthGroup->id, $ids);

        // 2. Filter by category
        $resCat = $this->actingAs($user, 'sanctum')->getJson('/api/v1/groups?category=Healthcare');
        $resCat->assertOk();
        $catIds = collect($resCat->json('data'))->pluck('id')->all();
        $this->assertContains($healthGroup->id, $catIds);
        $this->assertNotContains($techGroup->id, $catIds);

        // 3. Filter by location
        $resLoc = $this->actingAs($user, 'sanctum')->getJson('/api/v1/groups?location=Chittagong');
        $resLoc->assertOk();
        $locIds = collect($resLoc->json('data'))->pluck('id')->all();
        $this->assertContains($healthGroup->id, $locIds);
        $this->assertNotContains($techGroup->id, $locIds);

        // 4. Filter by privacy
        $resPriv = $this->actingAs($user, 'sanctum')->getJson('/api/v1/groups?privacy=public');
        $resPriv->assertOk();
        $privIds = collect($resPriv->json('data'))->pluck('id')->all();
        $this->assertContains($techGroup->id, $privIds);
        $this->assertContains($designGroup->id, $privIds);
        $this->assertNotContains($healthGroup->id, $privIds);

        // 5. Filter by member count
        $resCount = $this->actingAs($user, 'sanctum')->getJson('/api/v1/groups?min_members=800&max_members=1500');
        $resCount->assertOk();
        $countIds = collect($resCount->json('data'))->pluck('id')->all();
        $this->assertContains($techGroup->id, $countIds);
        $this->assertContains($designGroup->id, $countIds);
        $this->assertNotContains($healthGroup->id, $countIds);

        // 6. Filter by activity (groups with posts)
        $resAct = $this->actingAs($user, 'sanctum')->getJson('/api/v1/groups?activity=active&privacy=public');
        $resAct->assertOk();
        $actIds = collect($resAct->json('data'))->pluck('id')->all();
        $this->assertContains($techGroup->id, $actIds);
        $this->assertNotContains($designGroup->id, $actIds); // design group has posts_count = 0

        // 7. Sort by popularity
        $resPop = $this->actingAs($user, 'sanctum')->getJson('/api/v1/groups?sort=popular');
        $resPop->assertOk();
        $popIds = collect($resPop->json('data'))->pluck('id')->all();
        $this->assertEquals($techGroup->id, $popIds[0]); // 1200 members vs 850 / 150
    }
}
