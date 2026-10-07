<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * গ্রুপ ফিচার টেস্ট:
 * পাবলিক ও প্রাইভেট গ্রুপ তৈরি, মেম্বারশিপ রিকোয়েস্ট ও অনুমোদন,
 * গ্রুপ পোস্ট এবং প্রাইভেসি কন্ট্রোল টেস্ট।
 */
class GroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_group(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/groups', [
                'name' => 'Laravel Developers Bangladesh',
                'description' => 'A community for Laravel engineers.',
                'privacy' => 'public',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Laravel Developers Bangladesh')
            ->assertJsonPath('data.slug', 'laravel-developers-bangladesh')
            ->assertJsonPath('data.members_count', 1);

        $this->assertDatabaseHas('groups', [
            'name' => 'Laravel Developers Bangladesh',
            'creator_id' => $user->id,
        ]);

        $this->assertDatabaseHas('group_members', [
            'user_id' => $user->id,
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_user_can_join_public_group_instantly(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($creator, [
            'name' => 'Tech Talks',
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/join");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'joined')
            ->assertJsonPath('data.members_count', 2);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $member->id,
            'status' => 'active',
        ]);
    }

    public function test_user_joining_private_group_is_pending_and_admin_approves(): void
    {
        $admin = User::factory()->create();
        $applicant = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($admin, [
            'name' => 'Secret Society',
            'privacy' => 'private',
        ]);

        // Applicant requests to join -> status is pending
        $joinResponse = $this->actingAs($applicant, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/join");

        $joinResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $applicant->id,
            'status' => 'pending',
        ]);

        // Admin approves applicant
        $approveResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/approve/{$applicant->id}");

        $approveResponse->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $applicant->id,
            'status' => 'active',
        ]);
    }

    public function test_user_can_create_post_in_group_and_view_feed(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($creator, ['name' => 'Open Source']);
        $groupService->joinGroup($group, $member);

        // Member creates a post
        $postResponse = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/posts", [
                'content' => 'Check out our new release!',
            ]);

        $postResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.group_id', $group->id);

        $this->assertEquals(1, $group->fresh()->posts_count);

        // View group feed
        $feedResponse = $this->actingAs($creator, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/posts");

        $feedResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Check out our new release!');
    }

    public function test_non_member_cannot_view_private_group_feed(): void
    {
        $admin = User::factory()->create();
        $stranger = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($admin, [
            'name' => 'Private Group',
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/posts");

        $response->assertStatus(403);
    }
}
