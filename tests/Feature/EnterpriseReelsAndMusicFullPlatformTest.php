<?php

namespace Tests\Feature;

use App\Jobs\DispatchNotificationJob;
use App\Models\Media;
use App\Models\MusicTrack;
use App\Models\Reel;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Enterprise Reels, Music Platform & Stories Full Verification Test
 */
class EnterpriseReelsAndMusicFullPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_music_api_lists_active_tracks_genres_and_search(): void
    {
        $user = User::factory()->create();

        $trackFolk = MusicTrack::create([
            'title' => 'Bhatiali Waves',
            'artist' => 'Jugajug Folk Ensemble',
            'genre' => 'Folk',
            'audio_url' => 'https://cdn.example.com/audio/bhatiali.mp3',
            'duration_seconds' => 180,
            'is_active' => true,
            'is_licensed' => true,
        ]);

        $trackElectro = MusicTrack::create([
            'title' => 'Dhaka Cyber Beat',
            'artist' => 'SynthBD',
            'genre' => 'Electronic',
            'audio_url' => 'https://cdn.example.com/audio/dhaka_beat.mp3',
            'duration_seconds' => 145,
            'is_active' => true,
            'is_licensed' => true,
        ]);

        // 1. List all active tracks
        $resList = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/music');

        $resList->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        // 2. Filter by genre
        $resGenre = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/music?genre=Folk');

        $resGenre->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Bhatiali Waves');

        // 3. Search by title
        $resSearch = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/music?query=Cyber');

        $resSearch->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Dhaka Cyber Beat');

        // 4. Genres endpoint
        $resGenres = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/music/genres');

        $resGenres->assertStatus(200)
            ->assertJsonPath('success', true);

        // 5. Single track details
        $resSingle = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v2/music/{$trackFolk->id}");

        $resSingle->assertStatus(200)
            ->assertJsonPath('data.title', 'Bhatiali Waves');
    }

    public function test_reel_creation_with_music_trim_and_editor_controls(): void
    {
        $user = User::factory()->create();

        $track = MusicTrack::create([
            'title' => 'Serene Rain',
            'artist' => 'Nature Beats',
            'genre' => 'Lo-Fi',
            'audio_url' => 'https://cdn.example.com/audio/rain.mp3',
            'duration_seconds' => 120,
            'is_active' => true,
            'is_licensed' => true,
        ]);

        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => 'uploads/reel/video_test.mp4',
            'mime_type' => 'video/mp4',
            'size' => 4194304,
            'processing_status' => 'ready',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/reels', [
                'media_id' => $media->id,
                'caption' => 'বর্ষার বৃষ্টি ও কফি #rain #coffeelover',
                'privacy' => 'public',
                'comments_enabled' => true,
                'music_track_id' => $track->id,
                'audio_volume' => 0.6,
                'music_volume' => 0.85,
                'trim_start' => 2.5,
                'trim_end' => 17.5,
                'rotation_deg' => 90,
                'crop_aspect' => '9:16',
                'is_draft' => false,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.caption', 'বর্ষার বৃষ্টি ও কফি #rain #coffeelover')
            ->assertJsonPath('data.trim_start', 2.5)
            ->assertJsonPath('data.trim_end', 17.5)
            ->assertJsonPath('data.rotation_deg', 90)
            ->assertJsonPath('data.crop_aspect', '9:16')
            ->assertJsonPath('data.music_track.title', 'Serene Rain');

        $reelId = $response->json('data.id');

        $this->assertDatabaseHas('reels', [
            'id' => $reelId,
            'music_track_id' => $track->id,
            'rotation_deg' => 90,
            'crop_aspect' => '9:16',
            'is_draft' => false,
        ]);

        $this->assertDatabaseHas('music_usages', [
            'music_track_id' => $track->id,
            'usable_type' => Reel::class,
            'usable_id' => $reelId,
        ]);

        $this->assertEquals(1, $track->fresh()->usages_count);
    }

    public function test_reel_comments_lifecycle(): void
    {
        $creator = User::factory()->create();
        $commenter = User::factory()->create();

        $reel = Reel::create([
            'user_id' => $creator->id,
            'caption' => 'Comment test reel',
            'status' => 'ready',
            'privacy' => 'public',
            'comments_enabled' => true,
        ]);

        // 1. Post comment
        $resPost = $this->actingAs($commenter, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/comments", [
                'comment' => 'অসাধারণ ভিডিও হয়েছে ভাই! ❤️',
            ]);

        $resPost->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.comment', 'অসাধারণ ভিডিও হয়েছে ভাই! ❤️');

        $commentId = $resPost->json('data.id');

        $this->assertDatabaseHas('reel_comments', [
            'id' => $commentId,
            'reel_id' => $reel->id,
            'user_id' => $commenter->id,
        ]);

        $this->assertEquals(1, $reel->fresh()->comments_count);

        // 2. Fetch comments list
        $resList = $this->actingAs($creator, 'sanctum')
            ->getJson("/api/v2/reels/{$reel->id}/comments");

        $resList->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.comment', 'অসাধারণ ভিডিও হয়েছে ভাই! ❤️');

        // 3. Like comment
        $resLike = $this->actingAs($creator, 'sanctum')
            ->postJson("/api/v2/reels/comments/{$commentId}/like");

        $resLike->assertStatus(200)
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.likes_count', 1);

        // 4. Delete comment by author (SoftDeletes)
        $resDelete = $this->actingAs($commenter, 'sanctum')
            ->deleteJson("/api/v2/reels/comments/{$commentId}");

        $resDelete->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('reel_comments', ['id' => $commentId]);
        $this->assertEquals(0, $reel->fresh()->comments_count);
    }

    public function test_reel_save_bookmark_lifecycle(): void
    {
        $creator = User::factory()->create();
        $user = User::factory()->create();

        $reel = Reel::create([
            'user_id' => $creator->id,
            'caption' => 'Save bookmark test reel',
            'status' => 'ready',
            'privacy' => 'public',
        ]);

        // 1. Toggle save ON
        $resSave = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/save");

        $resSave->assertStatus(200)
            ->assertJsonPath('data.saved', true)
            ->assertJsonPath('data.saves_count', 1);

        $this->assertDatabaseHas('reel_saves', [
            'reel_id' => $reel->id,
            'user_id' => $user->id,
        ]);

        // 2. Saved reels list
        $resSavedList = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/reels/saved');

        $resSavedList->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $reel->id);

        // 3. Toggle save OFF
        $resUnsave = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/save");

        $resUnsave->assertStatus(200)
            ->assertJsonPath('data.saved', false)
            ->assertJsonPath('data.saves_count', 0);

        $this->assertDatabaseMissing('reel_saves', [
            'reel_id' => $reel->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_reel_share_and_report(): void
    {
        $creator = User::factory()->create();
        $user = User::factory()->create();

        $reel = Reel::create([
            'user_id' => $creator->id,
            'caption' => 'Share & Report test',
            'status' => 'ready',
            'privacy' => 'public',
            'shares_count' => 0,
        ]);

        // 1. Record share
        $resShare = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/share");

        $resShare->assertStatus(200)
            ->assertJsonPath('data.shares_count', 1);

        $this->assertDatabaseHas('reel_shares', [
            'reel_id' => $reel->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals(1, $reel->fresh()->shares_count);

        // 2. Submit report
        $resReport = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/report", [
                'reason' => 'inappropriate',
                'details' => 'This video contains inappropriate material.',
            ]);

        $resReport->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_reel_draft_isolation_from_feed(): void
    {
        $creator = User::factory()->create();
        $viewer = User::factory()->create();

        $draftReel = Reel::create([
            'user_id' => $creator->id,
            'caption' => 'Secret unfinished draft reel',
            'status' => 'ready',
            'privacy' => 'public',
            'is_draft' => true,
        ]);

        $publishedReel = Reel::create([
            'user_id' => $creator->id,
            'caption' => 'Published live reel',
            'status' => 'ready',
            'privacy' => 'public',
            'is_draft' => false,
        ]);

        // Public feed should NOT include draft reel
        $resFeed = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v2/reels/feed');

        $resFeed->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $publishedReel->id);

        // Creator drafts endpoint should show the draft
        $resDrafts = $this->actingAs($creator, 'sanctum')
            ->getJson('/api/v2/reels/drafts');

        $resDrafts->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $draftReel->id);
    }

    public function test_story_with_music_and_interactive_sticker(): void
    {
        $user = User::factory()->create();
        $viewer = User::factory()->create();

        $track = MusicTrack::create([
            'title' => 'Sunset Melody',
            'artist' => 'Chill Studio',
            'genre' => 'Lo-Fi',
            'audio_url' => 'https://cdn.example.com/audio/sunset.mp3',
            'duration_seconds' => 150,
            'is_active' => true,
            'is_licensed' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/stories', [
                'type' => 'text',
                'content' => 'আজকের সূর্যাস্ত অসাধারণ সুন্দর! 🌅',
                'background_color' => 'linear-gradient(135deg, #ff416c, #ff4b2b)',
                'music_track_id' => $track->id,
                'interactive_sticker' => [
                    'type' => 'poll',
                    'question' => 'সূর্যাস্ত কি দেখেছেন?',
                    'option1' => 'হ্যাঁ',
                    'option2' => 'না',
                ],
                'privacy' => 'public',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.music_track.title', 'Sunset Melody')
            ->assertJsonPath('data.interactive_sticker.type', 'poll');

        $storyId = $response->json('data.id');

        // Viewer records view
        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/stories/{$storyId}/view")
            ->assertStatus(200);

        // Author fetches viewer list
        $resViewers = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v2/stories/{$storyId}/viewers");

        $resViewers->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.id', $viewer->id);
    }

    public function test_story_reporting_and_duplicate_protection(): void
    {
        $author = User::factory()->create();
        $reporter = User::factory()->create();

        $storyResponse = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v2/stories', [
                'type' => 'text',
                'content' => 'Test story for reporting',
                'privacy' => 'public',
            ]);

        $storyId = $storyResponse->json('data.id');

        // First report: Success
        $resReport = $this->actingAs($reporter, 'sanctum')
            ->postJson("/api/v2/stories/{$storyId}/report", [
                'reason' => 'inappropriate',
                'details' => 'Contains offensive text',
            ]);

        $resReport->assertStatus(200)
            ->assertJsonPath('success', true);

        // Second report by same user: Duplicate protection triggered
        $resDuplicate = $this->actingAs($reporter, 'sanctum')
            ->postJson("/api/v2/stories/{$storyId}/report", [
                'reason' => 'spam',
            ]);

        $resDuplicate->assertStatus(200)
            ->assertJsonPath('already_reported', true);
    }

    public function test_story_share_and_shares_count(): void
    {
        $author = User::factory()->create();
        $sharer = User::factory()->create();

        $storyResponse = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v2/stories', [
                'type' => 'text',
                'content' => 'Shareable story',
                'privacy' => 'public',
            ]);

        $storyId = $storyResponse->json('data.id');

        $resShare = $this->actingAs($sharer, 'sanctum')
            ->postJson("/api/v2/stories/{$storyId}/share", [
                'platform' => 'messenger',
            ]);

        $resShare->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.shares_count', 1);
    }

    public function test_reel_reporting_with_duplicate_protection(): void
    {
        $creator = User::factory()->create();
        $reporter = User::factory()->create();

        $media = Media::create([
            'user_id' => $creator->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => 'uploads/reel/report_test.mp4',
            'path' => 'uploads/reel/report_test.mp4',
            'mime_type' => 'video/mp4',
            'size' => 2048576,
        ]);

        $reel = Reel::create([
            'user_id' => $creator->id,
            'media_id' => $media->id,
            'caption' => 'Reel to be reported',
            'privacy' => 'public',
            'status' => 'ready',
        ]);

        // First report: Success
        $resReport = $this->actingAs($reporter, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/report", [
                'reason' => 'harassment',
                'details' => 'Targeting individuals',
            ]);

        $resReport->assertStatus(200)
            ->assertJsonPath('success', true);

        // Second report: Duplicate protection
        $resDuplicate = $this->actingAs($reporter, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/report", [
                'reason' => 'harassment',
            ]);

        $resDuplicate->assertStatus(200)
            ->assertJsonPath('already_reported', true);
    }

    public function test_reel_reaction_comment_and_share_dispatches_realtime_notifications(): void
    {
        Queue::fake([DispatchNotificationJob::class]);

        $creator = User::factory()->create();
        $actor = User::factory()->create();

        $media = Media::create([
            'user_id' => $creator->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => 'uploads/reel/notif_test.mp4',
            'path' => 'uploads/reel/notif_test.mp4',
            'mime_type' => 'video/mp4',
            'size' => 2048576,
        ]);

        $reel = Reel::create([
            'user_id' => $creator->id,
            'media_id' => $media->id,
            'caption' => 'Reel with notification sync',
            'privacy' => 'public',
            'status' => 'ready',
            'allow_comments' => true,
        ]);

        // 1. React to reel
        $resReact = $this->actingAs($actor, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/react", ['type' => 'love']);
        $resReact->assertStatus(200);

        Queue::assertPushed(DispatchNotificationJob::class, function ($job) use ($creator) {
            return $job->recipientId === $creator->id && $job->type === 'reel.reaction';
        });

        // 2. Comment on reel
        $resComment = $this->actingAs($actor, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/comments", ['comment' => 'Brilliant reel!']);
        $resComment->assertStatus(201);

        Queue::assertPushed(DispatchNotificationJob::class, function ($job) use ($creator) {
            return $job->recipientId === $creator->id && $job->type === 'reel.comment';
        });

        // 3. Share reel
        $resShare = $this->actingAs($actor, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/share", ['platform' => 'feed']);
        $resShare->assertStatus(200);

        Queue::assertPushed(DispatchNotificationJob::class, function ($job) use ($creator) {
            return $job->recipientId === $creator->id && $job->type === 'reel.share';
        });
    }

    public function test_story_reaction_reply_and_share_dispatches_realtime_notifications(): void
    {
        Queue::fake([DispatchNotificationJob::class]);

        $creator = User::factory()->create();
        $actor = User::factory()->create();

        $story = Story::create([
            'user_id' => $creator->id,
            'type' => Story::TYPE_TEXT,
            'content' => 'Story notification test',
            'background_color' => '#1877f2',
            'privacy' => 'public',
            'expires_at' => now()->addHours(24),
            'allow_replies' => true,
        ]);

        // 1. React to story
        $resReact = $this->actingAs($actor, 'sanctum')
            ->postJson("/api/v2/stories/{$story->id}/react", ['type' => 'love']);
        $resReact->assertStatus(200);

        Queue::assertPushed(DispatchNotificationJob::class, function ($job) use ($creator) {
            return $job->recipientId === $creator->id && $job->type === 'story.reaction';
        });

        // 2. Reply to story
        $resReply = $this->actingAs($actor, 'sanctum')
            ->postJson("/api/v2/stories/{$story->id}/reply", ['message' => 'Loved your story!']);
        $resReply->assertStatus(201);

        Queue::assertPushed(DispatchNotificationJob::class, function ($job) use ($creator) {
            return $job->recipientId === $creator->id && $job->type === 'story.reply';
        });

        // 3. Share story
        $resShare = $this->actingAs($actor, 'sanctum')
            ->postJson("/api/v2/stories/{$story->id}/share", ['platform' => 'feed']);
        $resShare->assertStatus(200);

        Queue::assertPushed(DispatchNotificationJob::class, function ($job) use ($creator) {
            return $job->recipientId === $creator->id && $job->type === 'story.share';
        });
    }
}
