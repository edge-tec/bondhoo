<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Page;
use App\Models\PageMember;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase166SmartSharingAndFeedInteractionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->post = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'audience' => 'public',
            'shares_count' => 0,
        ]);
    }

    public function test_eligible_groups_returns_only_user_active_groups(): void
    {
        $group1 = Group::factory()->create(['name' => 'Laravel Developers', 'slug' => 'laravel-devs']);
        $group2 = Group::factory()->create(['name' => 'Kotlin Masters', 'slug' => 'kotlin-masters']);
        $inactiveGroup = Group::factory()->create(['name' => 'Inactive Group', 'slug' => 'inactive-group']);
        $otherGroup = Group::factory()->create(['name' => 'Stranger Group', 'slug' => 'stranger-group']);

        GroupMember::create([
            'group_id' => $group1->id,
            'user_id' => $this->user->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        GroupMember::create([
            'group_id' => $group2->id,
            'user_id' => $this->user->id,
            'role' => GroupMember::ROLE_ADMIN,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        GroupMember::create([
            'group_id' => $inactiveGroup->id,
            'user_id' => $this->user->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/sharing/eligible-groups');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $groups = $response->json('data');
        $this->assertCount(2, $groups);
        $groupIds = collect($groups)->pluck('id')->toArray();
        $this->assertContains($group1->id, $groupIds);
        $this->assertContains($group2->id, $groupIds);
        $this->assertNotContains($inactiveGroup->id, $groupIds);
        $this->assertNotContains($otherGroup->id, $groupIds);
    }

    public function test_eligible_pages_returns_owned_and_managed_pages(): void
    {
        $ownedPage = Page::create([
            'owner_id' => $this->user->id,
            'name' => 'My Personal Tech Page',
            'slug' => 'my-personal-tech-page',
            'category' => 'Technology',
        ]);

        $managedPage = Page::create([
            'owner_id' => $this->otherUser->id,
            'name' => 'Community Page Managed',
            'slug' => 'community-page-managed',
            'category' => 'Community',
        ]);

        PageMember::create([
            'page_id' => $managedPage->id,
            'user_id' => $this->user->id,
            'role' => Page::ROLE_ADMIN,
            'status' => 'active',
        ]);

        $unrelatedPage = Page::create([
            'owner_id' => $this->otherUser->id,
            'name' => 'Unrelated Page',
            'slug' => 'unrelated-page',
            'category' => 'Business',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/sharing/eligible-pages');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $pages = $response->json('data');
        $this->assertCount(2, $pages);
        $pageIds = collect($pages)->pluck('id')->toArray();
        $this->assertContains($ownedPage->id, $pageIds);
        $this->assertContains($managedPage->id, $pageIds);
        $this->assertNotContains($unrelatedPage->id, $pageIds);
    }

    public function test_share_to_timeline_creates_share_record_and_increments_count(): void
    {
        $this->assertEquals(0, $this->post->shares_count);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/posts/{$this->post->id}/share/timeline", [
                'caption' => 'Sharing this awesome post to my timeline!',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.shares_count', 1);

        $this->assertDatabaseHas('post_shares', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'caption' => 'Sharing this awesome post to my timeline!',
            'target_type' => 'timeline',
        ]);

        $this->assertEquals(1, $this->post->fresh()->shares_count);
    }

    public function test_share_to_multiple_groups_creates_records_and_increments_count(): void
    {
        $groupA = Group::factory()->create();
        $groupB = Group::factory()->create();

        GroupMember::create([
            'group_id' => $groupA->id,
            'user_id' => $this->user->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        GroupMember::create([
            'group_id' => $groupB->id,
            'user_id' => $this->user->id,
            'role' => GroupMember::ROLE_ADMIN,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/posts/{$this->post->id}/share/groups", [
                'caption' => 'Check this out group members!',
                'group_ids' => [$groupA->id, $groupB->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.shares_count', 2)
            ->assertJsonPath('data.shared_count', 2);

        $this->assertDatabaseHas('post_shares', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'target_type' => 'group',
            'target_id' => $groupA->id,
        ]);

        $this->assertDatabaseHas('post_shares', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'target_type' => 'group',
            'target_id' => $groupB->id,
        ]);

        $this->assertEquals(2, $this->post->fresh()->shares_count);
    }

    public function test_share_to_page_verifies_authorization(): void
    {
        $page = Page::create([
            'owner_id' => $this->user->id,
            'name' => 'Official Brand Page',
            'slug' => 'official-brand-page',
            'category' => 'Brand',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/posts/{$this->post->id}/share/pages", [
                'caption' => 'Sharing to official page!',
                'page_id' => $page->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.shares_count', 1);

        $this->assertDatabaseHas('post_shares', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'target_type' => 'page',
            'target_id' => $page->id,
        ]);
    }
}
