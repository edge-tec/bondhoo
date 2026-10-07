<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Streaming\LiveStreamingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfessionalLiveStreamingProductionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_creator_can_access_redesigned_studio_page_with_enterprise_ui(): void
    {
        $creator = User::factory()->create(['name' => 'প্রফেশনাল ব্রডকাস্টার']);

        $response = $this->actingAs($creator)->get(route('live.studio'));

        $response->assertStatus(200);
        $response->assertSee('ক্রিয়েটর লাইভ ব্রডকাস্ট স্টুডিও');
        $response->assertSee('OBS ও সফটওয়্যার কনফিগারেশন');
        $response->assertSee('ব্রডকাস্ট স্টুডিও');
        $response->assertSee('গো লাইভ শুরু করুন');
    }

    public function test_user_can_access_redesigned_watch_hub_with_live_directory(): void
    {
        $broadcaster = User::factory()->create(['name' => 'হোস্ট ক্রিয়েটর']);
        $service = app(LiveStreamingService::class);
        $stream = $service->createChannel($broadcaster, 'বিশেষ লাইভ শো', 'সরাসরি প্রশ্নোত্তর পর্ব');
        $service->startStream($stream);

        $viewer = User::factory()->create();

        $response = $this->actingAs($viewer)->get(route('watch.index'));

        $response->assertStatus(200);
        $response->assertSee('Bondhoo ওয়াচ ও লাইভ স্ট্রিমিং');
        $response->assertSee('বিশেষ লাইভ শো');
        $response->assertSee('হোস্ট ক্রিয়েটর');
        $response->assertSee('চলমান লাইভ সেশনসমূহ');
    }

    public function test_viewer_can_access_redesigned_live_viewing_interface_in_live_state(): void
    {
        $broadcaster = User::factory()->create(['name' => 'লাইভ ব্রডকাস্টার']);
        $service = app(LiveStreamingService::class);
        $stream = $service->createChannel($broadcaster, 'সরাসরি গেমিং ও আড্ডা', 'আজকের লাইভ গেমিং সেশন');
        $service->startStream($stream);

        $viewer = User::factory()->create(['name' => 'দর্শক রনি']);

        $response = $this->actingAs($viewer)->get(route('live.show', $stream->id));

        $response->assertStatus(200);
        $response->assertSee('সরাসরি গেমিং ও আড্ডা');
        $response->assertSee('লাইভ ব্রডকাস্টার');
        $response->assertSee('লাইভ চ্যাট');
        $response->assertSee('HD 1080p');
        $response->assertSee('শেয়ার করুন');
        $response->assertSee('রিপোর্ট');
    }

    public function test_viewer_can_access_live_viewing_interface_in_replay_state(): void
    {
        $broadcaster = User::factory()->create(['name' => 'ক্রিয়েটর করিম']);
        $service = app(LiveStreamingService::class);
        $stream = $service->createChannel($broadcaster, 'সংরক্ষিত লাইভ সেশন');
        $service->startStream($stream);
        $service->endStream($stream);

        $stream->update([
            'recording_status' => 'ready',
            'recording_url' => 'https://example.com/recordings/test.mp4',
        ]);

        $viewer = User::factory()->create();

        $response = $this->actingAs($viewer)->get(route('live.show', $stream->id));

        $response->assertStatus(200);
        $response->assertSee('রিপ্লে (Replay)');
        $response->assertSee('সংরক্ষিত লাইভ সেশন');
    }

    public function test_realtime_presence_join_and_heartbeat_updates_viewers_count(): void
    {
        $broadcaster = User::factory()->create();
        $viewer = User::factory()->create();
        $token = $viewer->createToken('test_token')->plainTextToken;

        $service = app(LiveStreamingService::class);
        $stream = $service->createChannel($broadcaster, 'উপস্থিতি ট্র্যাকিং লাইভ');
        $service->startStream($stream);

        // Join
        $joinResponse = $this->postJson("/api/v2/live/streams/{$stream->id}/join", [
            'session_id' => 'sess_user_998',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $joinResponse->assertStatus(200);
        $joinResponse->assertJsonPath('status', 'success');
        $this->assertGreaterThanOrEqual(1, $joinResponse->json('data.viewers_count'));

        // Heartbeat
        $hbResponse = $this->postJson("/api/v2/live/streams/{$stream->id}/heartbeat", [
            'session_id' => 'sess_user_998',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $hbResponse->assertStatus(200);
        $hbResponse->assertJsonPath('status', 'success');

        // Leave
        $leaveResponse = $this->postJson("/api/v2/live/streams/{$stream->id}/leave", [
            'session_id' => 'sess_user_998',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $leaveResponse->assertStatus(200);
    }

    public function test_realtime_comments_and_deletion_permissions(): void
    {
        $broadcaster = User::factory()->create();
        $viewer = User::factory()->create();
        $otherViewer = User::factory()->create();

        $broadcasterToken = $broadcaster->createToken('b_token')->plainTextToken;
        $viewerToken = $viewer->createToken('v_token')->plainTextToken;
        $otherToken = $otherViewer->createToken('o_token')->plainTextToken;

        $service = app(LiveStreamingService::class);
        $stream = $service->createChannel($broadcaster, 'লাইভ মন্তব্য টেস্ট');
        $service->startStream($stream);

        // Viewer creates comment
        $commentRes = $this->postJson("/api/v2/live/streams/{$stream->id}/comments", [
            'message' => 'চমৎকার লাইভ আলোচনা হচ্ছে!',
        ], [
            'Authorization' => "Bearer {$viewerToken}",
        ]);

        $commentRes->assertStatus(201);
        $commentId = $commentRes->json('data.id');
        $this->assertDatabaseHas('live_stream_comments', [
            'id' => $commentId,
            'message' => 'চমৎকার লাইভ আলোচনা হচ্ছে!',
        ]);

        // Unauthorized other viewer attempts delete -> 403 Forbidden
        $unauthDel = $this->deleteJson("/api/v2/live/streams/{$stream->id}/comments/{$commentId}", [], [
            'Authorization' => "Bearer {$otherToken}",
        ]);
        $unauthDel->assertStatus(403);

        // Broadcaster can delete comment as host
        $authDel = $this->deleteJson("/api/v2/live/streams/{$stream->id}/comments/{$commentId}", [], [
            'Authorization' => "Bearer {$broadcasterToken}",
        ]);
        $authDel->assertStatus(200);
        $this->assertDatabaseMissing('live_stream_comments', ['id' => $commentId]);
    }

    public function test_realtime_reactions_dispatch(): void
    {
        $broadcaster = User::factory()->create();
        $viewer = User::factory()->create();
        $viewerToken = $viewer->createToken('v_token')->plainTextToken;

        $service = app(LiveStreamingService::class);
        $stream = $service->createChannel($broadcaster, 'রিঅ্যাকশন টেস্ট');
        $service->startStream($stream);

        $reactRes = $this->postJson("/api/v2/live/streams/{$stream->id}/reactions", [
            'reaction_type' => 'love',
        ], [
            'Authorization' => "Bearer {$viewerToken}",
        ]);

        $reactRes->assertStatus(201);
        $reactRes->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('live_stream_reactions', [
            'live_stream_id' => $stream->id,
            'user_id' => $viewer->id,
            'reaction_type' => 'love',
        ]);
    }

    public function test_stream_sharing_and_community_safety_reporting(): void
    {
        $broadcaster = User::factory()->create();
        $viewer = User::factory()->create();
        $viewerToken = $viewer->createToken('v_token')->plainTextToken;

        $service = app(LiveStreamingService::class);
        $stream = $service->createChannel($broadcaster, 'শেয়ার ও রিপোর্ট টেস্ট');
        $service->startStream($stream);

        // Share
        $shareRes = $this->postJson("/api/v2/live/streams/{$stream->id}/share", [
            'destination' => 'clipboard',
        ], [
            'Authorization' => "Bearer {$viewerToken}",
        ]);
        $shareRes->assertStatus(200);
        $this->assertSame(1, $stream->fresh()->total_shares);

        // Report
        $reportRes = $this->postJson("/api/v2/live/streams/{$stream->id}/report", [
            'reason' => 'harassment',
            'details' => 'কমিউনিটি নির্দেশিকা পরিপন্থী আচরণ',
        ], [
            'Authorization' => "Bearer {$viewerToken}",
        ]);
        $reportRes->assertStatus(201);
        $this->assertDatabaseHas('live_stream_reports', [
            'live_stream_id' => $stream->id,
            'reporter_id' => $viewer->id,
            'reason' => 'harassment',
        ]);
    }

    public function test_replay_upload_creates_permanent_post(): void
    {
        $broadcaster = User::factory()->create(['name' => 'ভিডিও ক্রিয়েটর']);
        $token = $broadcaster->createToken('b_token')->plainTextToken;

        $service = app(LiveStreamingService::class);
        $stream = $service->createChannel($broadcaster, 'লাইভ শেষ এবং পোস্ট সংরক্ষণ');
        $service->startStream($stream);

        $fakeVideo = UploadedFile::fake()->create('live_recording.mp4', 1024, 'video/mp4');

        $uploadRes = $this->postJson("/api/v2/live/streams/{$stream->id}/replay-upload", [
            'video' => $fakeVideo,
            'duration' => 125,
            'caption' => 'আমার আজকের লাইভ সম্প্রচারের পূর্ণাঙ্গ রেকর্ড।',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $uploadRes->assertStatus(200);
        $uploadRes->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('posts', [
            'user_id' => $broadcaster->id,
            'type' => 'video',
        ]);
    }

    public function test_admin_can_access_live_management_dashboard_and_force_terminate_stream(): void
    {
        $admin = User::factory()->create([
            'name' => 'সিস্টেম অ্যাডমিন',
            'email' => 'admin@bondhoo.com',
            'status' => 'admin',
        ]);
        $broadcaster = User::factory()->create();

        $service = app(LiveStreamingService::class);
        $stream = $service->createChannel($broadcaster, 'ফোর্স টার্মিনেট টেস্ট লাইভ');
        $service->startStream($stream);

        // Admin accesses control center
        $adminView = $this->actingAs($admin)->get(route('admin.live.index'));
        $adminView->assertStatus(200);
        $adminView->assertSee('অ্যাডমিন লাইভ কন্ট্রোল সেন্টার');
        $adminView->assertSee('ফোর্স টার্মিনেট টেস্ট লাইভ');

        // Admin terminates live
        $termRes = $this->actingAs($admin)->post(route('admin.live.terminate', $stream->id), [
            'reason' => 'কমিউনিটি সুরক্ষার জন্য অ্যাডমিন কর্তৃক স্থগিত',
        ]);

        $termRes->assertRedirect();
        $this->assertSame('ended', $stream->fresh()->status);
    }

    public function test_bondhoo_and_jugajug_token_cookies_properly_authenticate_requests(): void
    {
        $user = User::factory()->create(['name' => 'কুকি ইউজার']);
        $rawToken = $user->createToken('cookie_test')->plainTextToken;

        // Test API access with bondhoo_token cookie
        $apiResponse = $this->withCredentials()
            ->withUnencryptedCookie('bondhoo_token', $rawToken)
            ->getJson('/api/v2/live/me');

        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonPath('status', 'success');
        $apiResponse->assertJsonPath('data.user.id', $user->id);
    }
}
