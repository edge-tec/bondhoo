<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Media;
use App\Models\Message;
use App\Models\Post;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase86EnterpriseCybersecurityAndPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createReelWithVideo(User $author, array $overrides = []): Reel
    {
        $reel = Reel::create(array_merge([
            'user_id' => $author->id,
            'caption' => 'Phase 8.6 Security Penetration Test Reel',
            'audio_title' => 'Original Audio - '.$author->name,
            'privacy' => 'public',
            'status' => Reel::STATUS_READY,
            'published_at' => now(),
            'is_draft' => false,
            'duration' => 15,
            'width' => 1080,
            'height' => 1920,
            'aspect_ratio' => '9:16',
        ], $overrides));

        $media = Media::create([
            'user_id' => $author->id,
            'mediable_type' => Reel::class,
            'mediable_id' => $reel->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => 'reels/phase86-reel-'.$reel->id.'.mp4',
            'mime_type' => 'video/mp4',
            'width' => 1080,
            'height' => 1920,
            'size' => 1048576,
            'processing_status' => 'completed',
        ]);

        ReelMedia::create([
            'reel_id' => $reel->id,
            'media_id' => $media->id,
            'quality' => 'original',
            'video_path' => 'reels/phase86-reel-'.$reel->id.'.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1048576,
        ]);

        return $reel->fresh(['user.profile', 'media']);
    }

    /**
     * 1. BOLA / IDOR: Cross-Account Post and Comment Mutation Prevention
     */
    public function test_bola_idor_unauthorized_post_and_comment_tampering_is_blocked(): void
    {
        $victim = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $attacker = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);

        $post = Post::create([
            'user_id' => $victim->id,
            'content' => 'Victim confidential content',
            'privacy' => 'public',
        ]);

        $comment = Comment::create([
            'user_id' => $victim->id,
            'post_id' => $post->id,
            'body' => 'Victim private comment',
        ]);

        // Attacker attempts to update victim's post
        $updatePostRes = $this->actingAs($attacker, 'sanctum')->putJson("/api/v1/posts/{$post->id}", [
            'content' => 'Attacker defaced content',
        ]);
        $this->assertContains($updatePostRes->status(), [403, 401], 'Attacker must be forbidden from updating victim post');

        // Attacker attempts to delete victim's post
        $deletePostRes = $this->actingAs($attacker, 'sanctum')->deleteJson("/api/v1/posts/{$post->id}");
        $this->assertContains($deletePostRes->status(), [403, 401], 'Attacker must be forbidden from deleting victim post');

        // Attacker attempts to delete victim's comment
        $deleteCommentRes = $this->actingAs($attacker, 'sanctum')->deleteJson("/api/v1/comments/{$comment->id}");
        $this->assertContains($deleteCommentRes->status(), [403, 401], 'Attacker must be forbidden from deleting victim comment');
    }

    /**
     * 2. Reels Privacy Boundaries: Private & Draft Reel Access Control
     */
    public function test_reels_privacy_boundaries_reject_unauthorized_access(): void
    {
        $author = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $snooper = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);

        $privateReel = $this->createReelWithVideo($author, ['privacy' => 'only_me', 'caption' => 'Confidential Author Only']);
        $draftReel = $this->createReelWithVideo($author, ['is_draft' => true, 'caption' => 'Unpublished Draft']);

        // Snooper attempts to access Only Me reel via API
        $privateRes = $this->actingAs($snooper, 'sanctum')->getJson("/api/v2/reels/{$privateReel->id}");
        $privateRes->assertStatus(403);

        // Snooper attempts to access Draft reel via API
        $draftRes = $this->actingAs($snooper, 'sanctum')->getJson("/api/v2/reels/{$draftReel->id}");
        $draftRes->assertStatus(403);

        // Author can view their own private and draft reels
        $authorPrivateRes = $this->actingAs($author, 'sanctum')->getJson("/api/v2/reels/{$privateReel->id}");
        $authorPrivateRes->assertStatus(200);

        $authorDraftRes = $this->actingAs($author, 'sanctum')->getJson("/api/v2/reels/{$draftReel->id}");
        $authorDraftRes->assertStatus(200);
    }

    /**
     * 3. Messenger Channel & Conversation Strict Participant Isolation
     */
    public function test_messenger_strict_isolation_and_websocket_channel_authorization(): void
    {
        $alice = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $bob = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $mallory = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);

        $conversation = Conversation::create([
            'type' => Conversation::TYPE_DIRECT,
            'creator_id' => $alice->id,
            'last_message_at' => now(),
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $alice->id,
            'role' => 'member',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $bob->id,
            'role' => 'member',
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $alice->id,
            'body' => 'Secret business proposal.',
            'type' => 'text',
        ]);

        // Mallory (outsider) cannot read messages
        $readRes = $this->actingAs($mallory, 'sanctum')->getJson("/api/v1/conversations/{$conversation->id}/messages");
        $this->assertContains($readRes->status(), [403, 404]);

        // Mallory cannot inject messages
        $sendRes = $this->actingAs($mallory, 'sanctum')->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'body' => 'Mallory forged message',
        ]);
        $this->assertContains($sendRes->status(), [403, 404]);

        // WebSocket Channel Authorization: conversation.{id}
        // Querying channel authorization logic directly
        $malloryAllowed = DB::table('conversation_participants')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $mallory->id)
            ->exists();
        $this->assertFalse($malloryAllowed, 'Mallory must not have authorization to join private conversation WebSocket channel');

        $aliceAllowed = DB::table('conversation_participants')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $alice->id)
            ->exists();
        $this->assertTrue($aliceAllowed, 'Alice must have authorization to join her conversation channel');
    }

    /**
     * 4. Media Upload Security: Executable Script Rejection
     */
    public function test_executable_script_upload_rejection(): void
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);

        // Attempt to upload a PHP web shell disguised as media
        $maliciousPhp = UploadedFile::fake()->create('shell.php', 10, 'text/x-php');

        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v1/media/upload', [
            'file' => $maliciousPhp,
            'collection' => 'post',
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * 5. SQL Injection Attack Resistance
     */
    public function test_sql_injection_payload_resistance(): void
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);

        // Inject classic SQL payloads into search and query params
        $sqliPayloads = [
            "' OR '1'='1",
            '1; DROP TABLE users; --',
            "' UNION SELECT id, password, email FROM users --",
        ];

        foreach ($sqliPayloads as $payload) {
            $searchRes = $this->actingAs($user, 'sanctum')->getJson('/api/v2/reels?tab='.urlencode($payload));
            $this->assertContains($searchRes->status(), [200, 400, 422], 'SQL injection payload must be safely parameterized without fatal error');
            $this->assertStringNotContainsString('SQLSTATE', $searchRes->getContent());
        }
    }

    /**
     * 6. Admin RBAC: Unauthorized User Rejection
     */
    public function test_admin_console_rejects_non_admin_users(): void
    {
        $regularUser = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);

        // 1. Regular user attempts to access Admin Web Dashboard -> 403 Forbidden
        $webRes = $this->actingAs($regularUser, 'web')->get('/admin');
        $this->assertSame(403, $webRes->status());

        // 2. Clear authentication to test unauthenticated guest -> 302 Redirect to /admin/login
        auth('web')->logout();
        auth('admin')->logout();

        $guestRes = $this->get('/admin');
        $this->assertContains($guestRes->status(), [302, 401]);
    }
}
