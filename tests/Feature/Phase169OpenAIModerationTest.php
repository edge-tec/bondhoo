<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\LinkScanResult;
use App\Models\ModerationPolicySetting;
use App\Models\ModerationRequest;
use App\Models\Post;
use App\Models\Story;
use App\Models\User;
use App\Services\Moderation\LinkScanningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase169OpenAIModerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'status' => 'active',
        ]);

        config([
            'moderation.enabled' => true,
            'moderation.api_key' => 'sk-test-mock-key',
            'moderation.adult_content_policy' => 'manual_review',
            'moderation.fail_closed' => true,
        ]);
    }

    /**
     * 1. Clean benign post is automatically approved and published.
     */
    public function test_clean_text_is_auto_approved_and_post_is_published(): void
    {
        Http::fake([
            'https://api.openai.com/v1/moderations' => Http::response([
                'id' => 'modr-test-clean',
                'model' => 'omni-moderation-latest',
                'results' => [
                    [
                        'flagged' => false,
                        'categories' => [
                            'sexual' => false,
                            'hate' => false,
                            'harassment' => false,
                            'self-harm' => false,
                            'violence' => false,
                            'sexual/minors' => false,
                        ],
                        'category_scores' => [
                            'sexual' => 0.0001,
                            'hate' => 0.0001,
                            'harassment' => 0.0001,
                            'self-harm' => 0.0001,
                            'violence' => 0.0001,
                            'sexual/minors' => 0.00001,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'Welcoming everyone to the BONDHOO developer community!',
                'audience' => 'public',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('moderation_requests', [
            'user_id' => $this->user->id,
            'status' => ModerationRequest::STATUS_APPROVED,
            'action_taken' => 'auto_approved',
        ]);
    }

    /**
     * 2. Severe hate speech and violent threats are automatically rejected.
     */
    public function test_severe_harm_and_hate_content_is_auto_rejected(): void
    {
        Http::fake([
            'https://api.openai.com/v1/moderations' => Http::response([
                'id' => 'modr-test-hate',
                'model' => 'omni-moderation-latest',
                'results' => [
                    [
                        'flagged' => true,
                        'categories' => [
                            'sexual' => false,
                            'hate' => true,
                            'hate/threatening' => true,
                            'harassment' => true,
                            'violence' => true,
                        ],
                        'category_scores' => [
                            'hate' => 0.96,
                            'hate/threatening' => 0.94,
                            'harassment' => 0.88,
                            'violence' => 0.91,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'I am going to kill all of you in a massive explosion.',
                'audience' => 'public',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('content');

        $this->assertDatabaseHas('moderation_requests', [
            'user_id' => $this->user->id,
            'status' => ModerationRequest::STATUS_REJECTED,
            'action_taken' => 'auto_rejected',
        ]);
    }

    /**
     * 3. Zero tolerance Child Safety (CSAM / sexual/minors) triggers quarantine & urgent audit log.
     */
    public function test_child_safety_csam_triggers_immediate_quarantine_and_account_restriction(): void
    {
        Http::fake([
            'https://api.openai.com/v1/moderations' => Http::response([
                'id' => 'modr-test-csam',
                'model' => 'omni-moderation-latest',
                'results' => [
                    [
                        'flagged' => true,
                        'categories' => [
                            'sexual' => true,
                            'sexual/minors' => true,
                        ],
                        'category_scores' => [
                            'sexual' => 0.95,
                            'sexual/minors' => 0.99,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'Illegal prohibited csam material',
                'audience' => 'public',
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('moderation_requests', [
            'user_id' => $this->user->id,
            'status' => ModerationRequest::STATUS_QUARANTINED,
            'action_taken' => 'quarantined',
        ]);

        $this->assertDatabaseHas('moderation_audit_logs', [
            'action' => 'quarantine',
            'reason' => 'Mandatory Child Safety Escalation (CSAM / sexual/minors flag)',
        ]);
    }

    /**
     * 4. Adult Content Policy: manual_review holds post with status 'pending_review'.
     */
    public function test_adult_content_policy_holds_post_for_manual_review(): void
    {
        ModerationPolicySetting::set('adult_content_policy', 'manual_review');

        Http::fake([
            'https://api.openai.com/v1/moderations' => Http::response([
                'id' => 'modr-test-adult',
                'model' => 'omni-moderation-latest',
                'results' => [
                    [
                        'flagged' => true,
                        'categories' => [
                            'sexual' => true,
                            'sexual/minors' => false,
                        ],
                        'category_scores' => [
                            'sexual' => 0.88,
                            'sexual/minors' => 0.0001,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'Adult explicit romance novel excerpts with nsfw scenes.',
                'audience' => 'public',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'user_id' => $this->user->id,
            'status' => 'pending_review',
        ]);

        $this->assertDatabaseHas('moderation_requests', [
            'user_id' => $this->user->id,
            'status' => ModerationRequest::STATUS_MANUAL_REVIEW,
            'action_taken' => 'queued_for_review',
        ]);
    }

    /**
     * 5. Adult Content Policy: reject mode immediately rejects post.
     */
    public function test_adult_content_policy_reject_mode(): void
    {
        ModerationPolicySetting::set('adult_content_policy', 'reject');

        Http::fake([
            'https://api.openai.com/v1/moderations' => Http::response([
                'id' => 'modr-test-adult-rej',
                'model' => 'omni-moderation-latest',
                'results' => [
                    [
                        'flagged' => true,
                        'categories' => [
                            'sexual' => true,
                        ],
                        'category_scores' => [
                            'sexual' => 0.95,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'Explicit adult pornography content',
                'audience' => 'public',
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('moderation_requests', [
            'user_id' => $this->user->id,
            'status' => ModerationRequest::STATUS_REJECTED,
            'action_taken' => 'auto_rejected',
        ]);
    }

    /**
     * 6. SSRF Link Scanning blocks AWS/GCP cloud metadata and loopback IPs.
     */
    public function test_ssrf_link_scanning_blocks_private_and_cloud_metadata_ips(): void
    {
        $linkScanner = app(LinkScanningService::class);

        // Check loopback IP
        $resultLoopback = $linkScanner->scanUrl('http://127.0.0.1:8000/internal-admin');
        $this->assertTrue($resultLoopback->is_ssrf_blocked);
        $this->assertEquals(LinkScanResult::STATUS_MALICIOUS, $resultLoopback->status);

        // Check Cloud Metadata IP
        $resultMetadata = $linkScanner->scanUrl('http://169.254.169.254/latest/meta-data/');
        $this->assertTrue($resultMetadata->is_ssrf_blocked);
        $this->assertEquals(LinkScanResult::STATUS_MALICIOUS, $resultMetadata->status);

        // Check RFC 1918 Private IP
        $resultPrivate = $linkScanner->scanUrl('http://10.0.0.1/secrets.json');
        $this->assertTrue($resultPrivate->is_ssrf_blocked);
        $this->assertEquals(LinkScanResult::STATUS_MALICIOUS, $resultPrivate->status);

        $this->assertDatabaseHas('link_scan_results', [
            'is_ssrf_blocked' => true,
            'status' => LinkScanResult::STATUS_MALICIOUS,
        ]);
    }

    /**
     * 7. Comment moderation rejects harmful comments with 422 English error message.
     */
    public function test_comment_moderation_rejects_harmful_comments(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
            'audience' => 'public',
        ]);

        Http::fake([
            'https://api.openai.com/v1/moderations' => Http::response([
                'id' => 'modr-comment-hate',
                'model' => 'omni-moderation-latest',
                'results' => [
                    [
                        'flagged' => true,
                        'categories' => ['hate' => true, 'harassment' => true],
                        'category_scores' => ['hate' => 0.92, 'harassment' => 0.90],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'body' => 'You are despicable and I will violently attack you.',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);

        $this->assertDatabaseHas('moderation_requests', [
            'moderatable_type' => Comment::class,
            'status' => ModerationRequest::STATUS_REJECTED,
        ]);
    }

    /**
     * 8. Story moderation intercepts harmful content.
     */
    public function test_story_moderation_intercepts_harmful_story(): void
    {
        Http::fake([
            'https://api.openai.com/v1/moderations' => Http::response([
                'id' => 'modr-story-vio',
                'model' => 'omni-moderation-latest',
                'results' => [
                    [
                        'flagged' => true,
                        'categories' => ['violence' => true],
                        'category_scores' => ['violence' => 0.95],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/stories', [
                'content' => 'Watch this violent bloody massacre footage.',
                'privacy' => 'public',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * 9. Admin Moderation Queue, approval, and publication flow.
     */
    public function test_admin_moderation_queue_and_manual_approval(): void
    {
        // Create a post held for manual review
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending_review',
            'content' => 'Sensory provocative poetry.',
        ]);

        $modRequest = ModerationRequest::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $this->user->id,
            'moderatable_type' => Post::class,
            'moderatable_id' => $post->id,
            'status' => ModerationRequest::STATUS_MANUAL_REVIEW,
            'model' => 'omni-moderation-latest',
            'flagged' => true,
            'categories' => ['sexual' => true],
            'category_scores' => ['sexual' => 0.72],
            'action_taken' => 'queued_for_review',
        ]);

        // 1. Admin lists queue
        $queueResponse = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/moderation/queue');

        $queueResponse->assertStatus(200);
        $queueResponse->assertJsonFragment(['id' => $modRequest->id]);

        // 2. Admin manually approves
        $approveResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/moderation/{$modRequest->id}/approve", [
                'notes' => 'Permissible artistic content.',
            ]);

        $approveResponse->assertStatus(200);
        $approveResponse->assertJson(['success' => true]);

        // Post status should now be updated to published!
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('moderation_audit_logs', [
            'action' => 'manual_approve',
            'target_id' => $post->id,
        ]);
    }

    /**
     * 10. Admin updates policy settings and thresholds.
     */
    public function test_admin_moderation_settings_update(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v1/admin/moderation/settings', [
                'adult_content_policy' => 'reject',
                'threshold_hate' => 0.55,
                'fail_closed' => true,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'adult_content_policy' => 'reject',
                'threshold_hate' => 0.55,
                'fail_closed' => true,
            ],
        ]);

        $this->assertEquals('reject', ModerationPolicySetting::get('adult_content_policy'));
        $this->assertEquals(0.55, ModerationPolicySetting::get('threshold_hate'));
    }

    /**
     * 11. Fail-closed holds content for manual review when OpenAI API is unavailable.
     */
    public function test_fail_closed_holds_content_when_external_api_fails(): void
    {
        Http::fake([
            'https://api.openai.com/v1/moderations' => Http::response(null, 503),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'Network outage test post.',
                'audience' => 'public',
            ]);

        // Under fail-closed, post is held for manual review rather than silently published
        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'user_id' => $this->user->id,
            'status' => 'pending_review',
        ]);

        $this->assertDatabaseHas('moderation_requests', [
            'user_id' => $this->user->id,
            'status' => ModerationRequest::STATUS_MANUAL_REVIEW,
            'action_taken' => 'queued_for_review',
        ]);
    }
}
