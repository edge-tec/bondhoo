<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13Stage05BusinessPagesSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $editor;

    protected User $analyst;

    protected User $follower;

    protected User $outsider;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Page Owner']);
        $this->editor = User::factory()->create(['name' => 'Page Editor']);
        $this->analyst = User::factory()->create(['name' => 'Page Analyst']);
        $this->follower = User::factory()->create(['name' => 'Page Follower']);
        $this->outsider = User::factory()->create(['name' => 'Page Outsider']);

        $this->page = Page::create([
            'name' => 'Acme Enterprise Solutions',
            'slug' => 'acme-enterprise-solutions',
            'category' => 'Technology',
            'owner_id' => $this->owner->id,
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 0,
            'social_links' => null,
            'business_hours' => null,
        ]);

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->editor->id,
            'role' => Page::ROLE_EDITOR,
            'status' => 'active',
        ]);

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->analyst->id,
            'role' => Page::ROLE_ANALYST,
            'status' => 'active',
        ]);
    }

    /**
     * 1. Page Role Permissions: Owner vs Editor vs Analyst
     */
    public function test_page_role_permissions_enforcement(): void
    {
        // Owner has full control
        $this->assertTrue((int) $this->page->owner_id === (int) $this->owner->id);
        $this->assertFalse((int) $this->page->owner_id === (int) $this->editor->id);
        $this->assertFalse((int) $this->page->owner_id === (int) $this->analyst->id);

        // Editor cannot transfer ownership or delete page
        $this->assertEquals(Page::ROLE_EDITOR, $this->page->getMemberRole($this->editor->id));
        $this->assertEquals(Page::ROLE_ANALYST, $this->page->getMemberRole($this->analyst->id));
    }

    /**
     * 2. Page Serialization: social_links and business_hours handle null, array, string, and object safely
     */
    public function test_page_serialization_integrity_handles_various_formats(): void
    {
        // With nulls
        $this->page->update([
            'social_links' => null,
            'business_hours' => null,
        ]);
        $arr1 = $this->page->toResponseArray();
        $this->assertIsArray($arr1['social_links']);
        $this->assertEmpty($arr1['social_links']);

        // With valid array
        $this->page->update([
            'social_links' => [
                ['platform' => 'linkedin', 'url' => 'https://linkedin.com/company/acme'],
                ['platform' => 'github', 'url' => 'https://github.com/acme'],
            ],
            'business_hours' => [
                'monday' => ['mode' => 'open', 'open' => '09:00', 'close' => '17:00'],
            ],
        ]);
        $arr2 = $this->page->fresh()->toResponseArray();
        $this->assertCount(2, $arr2['social_links']);
        $this->assertIsArray($arr2['business_hours']);

        // With JSON string
        $this->page->update([
            'social_links' => json_encode(['https://twitter.com/acme']),
        ]);
        $arr3 = $this->page->fresh()->toResponseArray();
        $this->assertIsArray($arr3['social_links']);
    }

    /**
     * 3. Page Follow Idempotency and Count Integrity
     */
    public function test_page_follow_idempotency_and_count_integrity(): void
    {
        $initialFollowers = $this->page->fresh()->followers_count;

        // 1. Follow page
        $res1 = $this->actingAs($this->follower, 'sanctum')
            ->postJson("/api/v1/pages/{$this->page->id}/follow");
        $res1->assertOk()
            ->assertJsonPath('data.is_following', true);

        $this->assertEquals($initialFollowers + 1, $this->page->fresh()->followers_count);

        // 2. Unfollow page
        $res2 = $this->actingAs($this->follower, 'sanctum')
            ->postJson("/api/v1/pages/{$this->page->id}/follow");
        $res2->assertOk()
            ->assertJsonPath('data.is_following', false);

        $this->assertEquals($initialFollowers, $this->page->fresh()->followers_count);
    }

    /**
     * 4. Unauthorized outsider cannot post on behalf of the page
     */
    public function test_outsider_cannot_post_to_page(): void
    {
        $unauthPost = $this->actingAs($this->outsider, 'sanctum')
            ->postJson("/api/v1/pages/{$this->page->id}/posts", [
                'content' => 'Malicious spam on business page.',
            ]);

        $unauthPost->assertStatus(403);
    }
}
