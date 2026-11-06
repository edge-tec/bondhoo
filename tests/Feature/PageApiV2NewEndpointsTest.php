<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PageApiV2NewEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function createPageForUser(User $owner, string $name = 'Tech Test Page'): Page
    {
        return Page::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'username' => 'testpage_'.uniqid(),
            'category' => 'Technology',
            'status' => Page::STATUS_ACTIVE,
        ]);
    }

    public function test_page_settings_endpoint_requires_auth_and_permission(): void
    {
        $owner = User::factory()->create();
        $page = $this->createPageForUser($owner);

        // Unauthenticated
        $response = $this->getJson("/api/v2/pages/{$page->id}/settings");
        $response->assertStatus(401);

        // Authenticated as owner
        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v2/pages/{$page->id}/settings");
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_page_reviews_can_be_retrieved_and_submitted(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $page = $this->createPageForUser($owner);

        // Submit review
        $response = $this->actingAs($reviewer, 'sanctum')->postJson("/api/v2/pages/{$page->id}/reviews", [
            'rating' => 5,
            'review_text' => 'Outstanding quality and fast customer support!',
        ]);
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data' => [
                'rating' => 5,
                'review_text' => 'Outstanding quality and fast customer support!',
            ],
        ]);

        // Get reviews
        $response = $this->getJson("/api/v2/pages/{$page->id}/reviews");
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_page_monetization_endpoint_returns_real_metrics(): void
    {
        $owner = User::factory()->create();
        $page = $this->createPageForUser($owner);

        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v2/pages/{$page->id}/monetization");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'is_eligible',
                'stars_enabled',
                'current_followers',
                'required_followers',
                'current_watch_minutes',
                'required_watch_minutes',
                'payout_status',
                'estimated_earnings',
            ],
        ]);
    }

    public function test_page_roles_and_inbox_aliases_work_cleanly(): void
    {
        $owner = User::factory()->create();
        $page = $this->createPageForUser($owner);

        // Roles alias
        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v2/pages/{$page->id}/roles");
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);

        // Inbox alias
        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v2/pages/{$page->id}/inbox");
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_page_settings_can_be_updated_with_visibility_and_toggles(): void
    {
        $owner = User::factory()->create();
        $page = $this->createPageForUser($owner);

        $response = $this->actingAs($owner, 'sanctum')->putJson("/api/v2/pages/{$page->id}/settings", [
            'is_public' => false,
            'allow_reviews' => false,
            'allow_messages' => false,
            'comment_moderation' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'is_public' => false,
                'page_visibility' => 'private',
                'allow_reviews' => false,
                'allow_messages' => false,
                'comment_moderation' => true,
            ],
        ]);

        $this->assertEquals('private', $page->fresh()->visibility);
    }

    public function test_page_verification_request_can_be_submitted_by_owner(): void
    {
        $owner = User::factory()->create();
        $page = $this->createPageForUser($owner);

        $response = $this->actingAs($owner, 'sanctum')->postJson("/api/v2/pages/{$page->id}/verification-request", [
            'verification_type' => 'business',
            'legal_name' => 'Acme Corporation Ltd.',
            'additional_info' => 'Official corporate registration.',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data' => [
                'page_id' => $page->id,
                'status' => 'pending',
                'legal_name' => 'Acme Corporation Ltd.',
            ],
        ]);

        $this->assertEquals('pending', $page->fresh()->verification_status);
    }
}
