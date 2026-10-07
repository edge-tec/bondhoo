<?php

namespace App\Services\Streaming;

use App\Events\LiveStreamCommentBroadcastEvent;
use App\Events\LiveStreamGiftBroadcastEvent;
use App\Models\LiveStream;
use App\Models\LiveStreamComment;
use App\Models\LiveStreamGift;
use App\Models\LiveStreamModerator;
use App\Models\LiveStreamReaction;
use App\Models\LiveStreamReport;
use App\Models\LiveStreamViewer;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\Calling\CallingService;
use App\Services\NotificationService;
use App\Services\RealtimeService;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class LiveStreamingService
{
    public function __construct(
        protected RealtimeService $realtime,
        protected CallingService $callingService,
        protected NotificationService $notificationService,
        protected LiveGatewayService $gatewayService
    ) {}

    /**
     * Get the Live SFU Gateway service.
     */
    public function getGateway(): LiveGatewayService
    {
        return $this->gatewayService;
    }

    /**
     * Issue an authorized short-lived SFU viewer token.
     */
    public function issueViewerToken(LiveStream $stream, ?User $user): string
    {
        return $this->gatewayService->generateViewerToken($stream, $user);
    }

    /**
     * Confirm SFU track publication from broadcaster.
     *
     * @param  array<string, mixed>  $trackMeta
     */
    public function confirmSfuPublication(LiveStream $stream, string $hostToken, array $trackMeta = []): bool
    {
        return $this->gatewayService->confirmPublication($stream, $hostToken, $trackMeta);
    }

    /**
     * Initialize a new live broadcast channel with optional enterprise settings.
     *
     * @param  array<string, mixed>  $options
     */
    public function createChannel(User $user, string $title, ?string $description = null, array $options = []): LiveStream
    {
        // Enforce maximum 1 active live stream per user (Requirement 16)
        $this->terminateExistingActiveStreams($user);

        $channelId = (string) Str::uuid();
        $streamKey = 'live_'.Str::random(32);

        $ingestBase = config('streaming.rtmp_ingest', env('RTMP_INGEST_URL', 'rtmp://live.jugajug.com/live'));
        $playbackBase = config('streaming.hls_playback', env('HLS_PLAYBACK_URL', 'https://live-cdn.jugajug.com/hls'));

        $privacy = $options['privacy'] ?? LiveStream::PRIVACY_PUBLIC;
        if (! in_array($privacy, [LiveStream::PRIVACY_PUBLIC, LiveStream::PRIVACY_FRIENDS, LiveStream::PRIVACY_ONLY_ME], true)) {
            $privacy = LiveStream::PRIVACY_PUBLIC;
        }

        $stream = LiveStream::create([
            'channel_id' => $channelId,
            'user_id' => $user->id,
            'title' => $title,
            'description' => $description,
            'privacy' => $privacy,
            'stream_key' => $streamKey,
            'ingest_url' => "{$ingestBase}/{$streamKey}",
            'playback_url' => "{$playbackBase}/{$channelId}/master.m3u8",
            'status' => LiveStream::STATUS_READY,
            'comments_enabled' => (bool) ($options['comments_enabled'] ?? true),
            'reactions_enabled' => (bool) ($options['reactions_enabled'] ?? true),
            'sharing_enabled' => (bool) ($options['sharing_enabled'] ?? true),
            'recording_enabled' => (bool) ($options['recording_enabled'] ?? true),
            'recording_status' => LiveStream::RECORDING_STATUS_NONE,
            'thumbnail' => $options['thumbnail'] ?? null,
            'health_stats' => [
                'fps' => 60,
                'bitrate_kbps' => 4500,
                'dropped_frames' => 0,
                'codec' => 'H.264/AAC',
                'resolution' => '1080p',
            ],
        ]);

        // Initialize SFU Room & generate short-lived Host Token
        $this->gatewayService->createRoom($stream);
        $this->gatewayService->generateHostToken($stream, $user);

        return $stream->fresh();
    }

    /**
     * Terminate any active or draft sessions for a user to enforce maximum 1 active stream.
     */
    public function terminateExistingActiveStreams(User $user, ?int $exceptStreamId = null): void
    {
        $query = LiveStream::where('user_id', $user->id)
            ->whereIn('status', [LiveStream::STATUS_LIVE, LiveStream::STATUS_READY, LiveStream::STATUS_PREPARING]);

        if ($exceptStreamId) {
            $query->where('id', '!=', $exceptStreamId);
        }

        $existing = $query->get();
        foreach ($existing as $oldStream) {
            $this->endStream($oldStream, $user);
        }
    }

    /**
     * Start live streaming session, broadcast event, and notify audience.
     */
    public function startStream(LiveStream $stream): LiveStream
    {
        // Enforce maximum 1 active session per user
        $this->terminateExistingActiveStreams($stream->user, $stream->id);

        $now = now();
        $stream->update([
            'status' => LiveStream::STATUS_LIVE,
            'started_at' => $now,
            'last_heartbeat_at' => $now,
            'recording_status' => $stream->recording_enabled ? LiveStream::RECORDING_STATUS_RECORDING : LiveStream::RECORDING_STATUS_NONE,
        ]);

        $payload = [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'title' => $stream->title,
            'broadcaster' => [
                'id' => $stream->user->id,
                'name' => $stream->user->name,
                'avatar' => $stream->user->profile?->avatar_url,
            ],
            'playback_url' => $stream->playback_url,
            'started_at' => $stream->started_at?->toIso8601String(),
        ];

        $this->realtime->broadcast("stream.{$stream->channel_id}", 'stream.started', $payload);
        $this->realtime->broadcast("live.{$stream->channel_id}", 'live.started', $payload);

        // Dispatch live notification to friends or followers if stream is public or friends-only
        if ($stream->privacy !== LiveStream::PRIVACY_ONLY_ME) {
            $this->notifyFollowersAndFriends($stream);
        }

        return $stream;
    }

    /**
     * End live streaming session, close SFU room, mark viewers inactive, and notify viewers.
     */
    public function endStream(LiveStream $stream, ?User $actor = null): LiveStream
    {
        $now = now();
        $durationSeconds = $stream->started_at ? (int) abs($now->diffInSeconds($stream->started_at, false)) : 0;

        // 1. Mark session ENDING and close SFU room
        $stream->update(['status' => LiveStream::STATUS_ENDING]);
        $this->gatewayService->closeRoom($stream);

        DB::transaction(function () use ($stream, $now, $durationSeconds) {
            // Mark all active viewers as left
            LiveStreamViewer::where('live_stream_id', $stream->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'left_at' => $now,
                ]);

            $newRecordingStatus = $stream->recording_enabled ? LiveStream::RECORDING_STATUS_PROCESSING : LiveStream::RECORDING_STATUS_NONE;

            $stream->update([
                'status' => LiveStream::STATUS_ENDED,
                'ended_at' => $now,
                'duration' => $durationSeconds,
                'viewers_count' => 0,
                'recording_status' => $newRecordingStatus,
            ]);
        });

        $summaryData = [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'duration_seconds' => $durationSeconds,
            'peak_viewers' => $stream->peak_viewers,
            'total_unique_viewers' => $stream->total_unique_viewers,
            'total_reactions' => $stream->total_reactions,
            'total_shares' => $stream->total_shares,
            'comments_count' => $stream->comments()->count(),
            'recording_status' => $stream->recording_status,
        ];

        $this->realtime->broadcast("stream.{$stream->channel_id}", 'stream.ended', $summaryData);
        $this->realtime->broadcast("live.{$stream->channel_id}", 'live.ended', $summaryData);

        // 2. Authoritative server-side recording finalization if server recording file path exists
        if ($stream->recording_enabled && $stream->recording_file_path && $stream->recording_status !== LiveStream::RECORDING_STATUS_READY) {
            try {
                $this->gatewayService->finalizeRecording($stream);
            } catch (\Throwable $e) {
                Log::warning('Live recording finalization notice: '.$e->getMessage());
            }
        }

        return $stream->fresh();
    }

    /**
     * Viewer presence: Join stream.
     *
     * @return array{viewers_count: int, peak_viewers: int, total_unique_viewers: int}
     */
    public function joinViewer(LiveStream $stream, ?User $user, string $sessionId): array
    {
        $now = now();

        LiveStreamViewer::updateOrCreate(
            [
                'live_stream_id' => $stream->id,
                'session_id' => $sessionId,
            ],
            [
                'user_id' => $user?->id,
                'is_active' => true,
                'joined_at' => $now,
                'last_ping_at' => $now,
                'left_at' => null,
            ]
        );

        return $this->recalculateViewerMetrics($stream);
    }

    /**
     * Viewer presence: Heartbeat ping.
     *
     * @return array{viewers_count: int, peak_viewers: int, total_unique_viewers: int}
     */
    public function heartbeat(LiveStream $stream, ?User $user, string $sessionId): array
    {
        $now = now();

        if ($user && $user->id === $stream->user_id) {
            $stream->update(['last_heartbeat_at' => $now]);
        }

        LiveStreamViewer::where('live_stream_id', $stream->id)
            ->where('session_id', $sessionId)
            ->update([
                'last_ping_at' => $now,
                'is_active' => true,
            ]);

        // Prune stale viewers inactive after 45 seconds of silence
        LiveStreamViewer::where('live_stream_id', $stream->id)
            ->where('is_active', true)
            ->where('last_ping_at', '<', $now->copy()->subSeconds(45))
            ->update([
                'is_active' => false,
                'left_at' => $now,
            ]);

        return $this->recalculateViewerMetrics($stream);
    }

    /**
     * Terminate abandoned or crashed live sessions where broadcaster heartbeat stopped.
     */
    public function pruneStaleStreams(int $silenceSeconds = 75): int
    {
        $cutoff = now()->subSeconds($silenceSeconds);

        $staleStreams = LiveStream::where('status', LiveStream::STATUS_LIVE)
            ->where(function ($q) use ($cutoff) {
                $q->where('last_heartbeat_at', '<', $cutoff)
                    ->orWhere(function ($q2) use ($cutoff) {
                        $q2->whereNull('last_heartbeat_at')
                            ->where('started_at', '<', $cutoff);
                    });
            })
            ->get();

        $count = 0;
        foreach ($staleStreams as $stale) {
            $this->endStream($stale);
            $count++;
        }

        return $count;
    }

    /**
     * Viewer presence: Leave stream.
     *
     * @return array{viewers_count: int, peak_viewers: int, total_unique_viewers: int}
     */
    public function leaveViewer(LiveStream $stream, ?User $user, string $sessionId): array
    {
        LiveStreamViewer::where('live_stream_id', $stream->id)
            ->where('session_id', $sessionId)
            ->update([
                'is_active' => false,
                'left_at' => now(),
            ]);

        return $this->recalculateViewerMetrics($stream);
    }

    /**
     * Recalculate authoritative viewer stats and broadcast updates.
     *
     * @return array{viewers_count: int, peak_viewers: int, total_unique_viewers: int}
     */
    protected function recalculateViewerMetrics(LiveStream $stream): array
    {
        $cutoff = now()->subSeconds(45);

        $activeCount = LiveStreamViewer::where('live_stream_id', $stream->id)
            ->where('is_active', true)
            ->where('last_ping_at', '>=', $cutoff)
            ->count();

        $totalUnique = LiveStreamViewer::where('live_stream_id', $stream->id)->count();

        $peak = max($stream->peak_viewers, $activeCount);

        $stream->update([
            'viewers_count' => $activeCount,
            'peak_viewers' => $peak,
            'total_unique_viewers' => $totalUnique,
        ]);

        $payload = [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'viewers_count' => $activeCount,
            'peak_viewers' => $peak,
            'total_unique_viewers' => $totalUnique,
        ];

        $this->realtime->broadcast("live.{$stream->channel_id}", 'live.viewer.count', $payload);
        $this->realtime->broadcast("stream.{$stream->channel_id}", 'live.viewer.count', $payload);

        return [
            'viewers_count' => $activeCount,
            'peak_viewers' => $peak,
            'total_unique_viewers' => $totalUnique,
        ];
    }

    /**
     * WebRTC Signaling relay for live stream offer, answer, ice-candidate and screen-share.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendSignal(LiveStream $stream, User $sender, string $signalType, array $payload, ?int $targetUserId = null): array
    {
        if (! in_array($signalType, ['offer', 'answer', 'candidate', 'screen_share_started', 'screen_share_stopped'], true)) {
            throw new InvalidArgumentException("Invalid signal type: {$signalType}");
        }

        $signalData = [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'signal_type' => $signalType,
            'sender_id' => $sender->id,
            'sender_name' => $sender->name,
            'target_user_id' => $targetUserId,
            'payload' => $payload,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->realtime->broadcast("live.{$stream->channel_id}", 'live.signal', $signalData);
        $this->realtime->broadcast("stream.{$stream->channel_id}", 'live.signal', $signalData);

        return $signalData;
    }

    /**
     * Post comment in live chat.
     */
    public function sendComment(LiveStream $stream, User $user, string $message): LiveStreamComment
    {
        if (! $stream->comments_enabled) {
            throw new InvalidArgumentException('এই লাইভ স্ট্রিমে মন্তব্য করা সাময়িকভাবে বন্ধ রয়েছে।');
        }

        $comment = LiveStreamComment::create([
            'live_stream_id' => $stream->id,
            'user_id' => $user->id,
            'message' => $message,
            'is_pinned' => false,
        ]);

        $payload = [
            'id' => $comment->id,
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'avatar_url' => $user->profile?->avatar_url,
            ],
            'message' => $message,
            'is_pinned' => false,
            'created_at' => $comment->created_at?->toIso8601String(),
        ];

        $this->realtime->broadcast("stream.{$stream->channel_id}", 'chat.message', $payload);
        $this->realtime->broadcast("live.{$stream->channel_id}", 'live.comment.created', $payload);

        try {
            broadcast(new LiveStreamCommentBroadcastEvent(
                channelId: $stream->channel_id,
                userId: $user->id,
                userName: $user->name,
                userAvatar: $user->profile?->avatar_url,
                comment: $message,
                createdAt: $comment->created_at?->toIso8601String()
            ));
        } catch (Exception) {
            // Non-blocking fallback
        }

        return $comment;
    }

    /**
     * Delete live stream comment by comment author, broadcaster, or moderator.
     */
    public function deleteComment(LiveStream $stream, int $commentId, User $actor): bool
    {
        $comment = LiveStreamComment::where('live_stream_id', $stream->id)->findOrFail($commentId);

        $isAuthor = $comment->user_id === $actor->id;
        $isBroadcaster = $stream->user_id === $actor->id;
        $isModerator = $stream->isModerator($actor);

        if (! $isAuthor && ! $isBroadcaster && ! $isModerator) {
            throw new InvalidArgumentException('মন্তব্য মুছে ফেলার অনুমতি নেই।');
        }

        $comment->delete();

        $payload = [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'comment_id' => $commentId,
            'deleted_by' => $actor->id,
        ];

        $this->realtime->broadcast("live.{$stream->channel_id}", 'live.comment.deleted', $payload);
        $this->realtime->broadcast("stream.{$stream->channel_id}", 'live.comment.deleted', $payload);

        return true;
    }

    /**
     * Send real-time reaction (like, love, care, haha, wow, sad, angry).
     */
    public function sendReaction(LiveStream $stream, User $user, string $reactionType): LiveStreamReaction
    {
        if (! $stream->reactions_enabled) {
            throw new InvalidArgumentException('এই লাইভ স্ট্রিমে রিঅ্যাকশন প্রদান সাময়িকভাবে বন্ধ রয়েছে।');
        }

        $validReactions = ['like', 'love', 'care', 'haha', 'wow', 'sad', 'angry'];
        if (! in_array($reactionType, $validReactions, true)) {
            $reactionType = 'like';
        }

        $reaction = LiveStreamReaction::create([
            'live_stream_id' => $stream->id,
            'user_id' => $user->id,
            'reaction_type' => $reactionType,
        ]);

        $stream->increment('total_reactions');

        $payload = [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'reaction_type' => $reactionType,
            'total_reactions' => $stream->fresh()->total_reactions,
        ];

        $this->realtime->broadcast("live.{$stream->channel_id}", 'live.reaction.created', $payload);
        $this->realtime->broadcast("stream.{$stream->channel_id}", 'live.reaction.created', $payload);

        return $reaction;
    }

    /**
     * Send virtual live gift (Rose, Diamond, Crown, Rocket).
     */
    public function sendGift(LiveStream $stream, User $user, string $giftType, int $coins): LiveStreamGift
    {
        $gift = LiveStreamGift::create([
            'live_stream_id' => $stream->id,
            'user_id' => $user->id,
            'gift_type' => $giftType,
            'coin_amount' => $coins,
        ]);

        $stream->increment('total_reactions', $coins);

        $payload = [
            'id' => $gift->id,
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'user' => ['id' => $user->id, 'name' => $user->name],
            'gift_type' => $giftType,
            'coins' => $coins,
        ];

        $this->realtime->broadcast("stream.{$stream->channel_id}", 'gift.received', $payload);
        $this->realtime->broadcast("live.{$stream->channel_id}", 'gift.received', $payload);

        try {
            broadcast(new LiveStreamGiftBroadcastEvent(
                channelId: $stream->channel_id,
                senderId: $user->id,
                senderName: $user->name,
                senderAvatar: $user->profile?->avatar_url,
                giftType: $giftType,
                giftAmount: $coins,
                createdAt: $gift->created_at?->toIso8601String()
            ));
        } catch (Exception) {
            // Non-blocking fallback
        }

        return $gift;
    }

    /**
     * Record a share of the live stream.
     */
    public function recordShare(LiveStream $stream, User $user, string $destination = 'feed'): int
    {
        $stream->increment('total_shares');

        $this->realtime->broadcast("live.{$stream->channel_id}", 'live.shared', [
            'stream_id' => $stream->id,
            'user_id' => $user->id,
            'destination' => $destination,
            'total_shares' => $stream->fresh()->total_shares,
        ]);

        return $stream->fresh()->total_shares;
    }

    /**
     * Appoint a moderator for the live stream.
     */
    public function appointModerator(LiveStream $stream, User $broadcaster, User $moderatorUser): LiveStreamModerator
    {
        if ($stream->user_id !== $broadcaster->id) {
            throw new InvalidArgumentException('শুধুমাত্র ব্রডকাস্টার মডারেটর নিয়োগ করতে পারেন।');
        }

        $moderator = LiveStreamModerator::firstOrCreate(
            [
                'live_stream_id' => $stream->id,
                'user_id' => $moderatorUser->id,
            ],
            [
                'appointed_by' => $broadcaster->id,
            ]
        );

        $this->realtime->broadcast("live.{$stream->channel_id}", 'live.moderator.added', [
            'stream_id' => $stream->id,
            'user_id' => $moderatorUser->id,
            'name' => $moderatorUser->name,
        ]);

        return $moderator;
    }

    /**
     * Remove a moderator from the live stream.
     */
    public function removeModerator(LiveStream $stream, User $broadcaster, User $moderatorUser): bool
    {
        if ($stream->user_id !== $broadcaster->id) {
            throw new InvalidArgumentException('শুধুমাত্র ব্রডকাস্টার মডারেটর অপসারণ করতে পারেন।');
        }

        $deleted = (bool) LiveStreamModerator::where('live_stream_id', $stream->id)
            ->where('user_id', $moderatorUser->id)
            ->delete();

        if ($deleted) {
            $this->realtime->broadcast("live.{$stream->channel_id}", 'live.moderator.removed', [
                'stream_id' => $stream->id,
                'user_id' => $moderatorUser->id,
            ]);
        }

        return $deleted;
    }

    /**
     * File a report against a live stream.
     */
    public function reportStream(LiveStream $stream, User $reporter, string $reason, ?string $details = null): LiveStreamReport
    {
        $validReasons = ['harassment', 'violence', 'sexual_content', 'hate', 'spam', 'copyright', 'other'];
        if (! in_array($reason, $validReasons, true)) {
            $reason = 'other';
        }

        return LiveStreamReport::create([
            'live_stream_id' => $stream->id,
            'reporter_id' => $reporter->id,
            'reason' => $reason,
            'details' => $details,
            'status' => 'pending',
        ]);
    }

    /**
     * Handle replay file upload from client MediaRecorder, finalize recording,
     * create permanent Post and Media video entries on user's profile and feed.
     *
     * @return array{recording_url: string, post_id: int, media_id: int, thumbnail_url: ?string}
     */
    public function uploadReplay(LiveStream $stream, User $user, UploadedFile $file, ?UploadedFile $thumbnailFile = null): array
    {
        if ($stream->user_id !== $user->id) {
            throw new InvalidArgumentException('শুধুমাত্র ব্রডকাস্টার রিপ্লে ফাইল আপলোড করতে পারেন।');
        }

        $extension = $file->getClientOriginalExtension() ?: 'webm';
        $filename = "replays/{$stream->channel_id}_".time().".{$extension}";

        $file->storeAs('public/'.$filename);
        $publicUrl = Storage::url($filename);

        $thumbFilename = null;
        $thumbPublicUrl = null;
        if ($thumbnailFile && $thumbnailFile->isValid()) {
            $thumbExt = $thumbnailFile->getClientOriginalExtension() ?: 'jpg';
            $thumbFilename = "thumbnails/{$stream->channel_id}_".time().".{$thumbExt}";
            $thumbnailFile->storeAs('public/'.$thumbFilename);
            $thumbPublicUrl = Storage::url($thumbFilename);
        }

        // Create Media entry for user's video library and profile
        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'videos',
            'disk' => 'public',
            'original_path' => $filename,
            'thumbnail_path' => $thumbFilename,
            'mime_type' => $file->getMimeType() ?: 'video/webm',
            'size' => $file->getSize() ?: 0,
            'processing_status' => 'completed',
            'metadata' => [
                'original_filename' => $file->getClientOriginalName(),
                'live_stream_id' => $stream->id,
                'channel_id' => $stream->channel_id,
                'duration' => $stream->duration,
                'title' => $stream->title,
            ],
        ]);

        // Create permanent Video Post on broadcaster's feed and profile
        $postAudience = match ($stream->privacy) {
            LiveStream::PRIVACY_FRIENDS => 'friends',
            LiveStream::PRIVACY_ONLY_ME => 'only_me',
            default => 'public',
        };

        $post = Post::create([
            'user_id' => $user->id,
            'content' => $stream->title.($stream->description ? "\n\n".$stream->description : ''),
            'audience' => $postAudience,
            'type' => 'video',
            'status' => 'published',
            'media_meta' => [
                [
                    'url' => $publicUrl,
                    'type' => 'video',
                    'thumbnail_url' => $thumbPublicUrl,
                    'duration' => $stream->duration,
                    'mime_type' => $file->getMimeType() ?: 'video/webm',
                    'live_stream_id' => $stream->id,
                ],
            ],
        ]);

        $media->update([
            'mediable_type' => Post::class,
            'mediable_id' => $post->id,
        ]);

        $stream->update([
            'recording_url' => $publicUrl,
            'recording_status' => LiveStream::RECORDING_STATUS_READY,
            'generated_post_id' => $post->id,
            'thumbnail' => $thumbPublicUrl ?: $stream->thumbnail,
        ]);

        $payload = [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'recording_url' => $publicUrl,
            'recording_status' => LiveStream::RECORDING_STATUS_READY,
            'post_id' => $post->id,
            'post_url' => "/dashboard#post-card-{$post->id}",
        ];

        $this->realtime->broadcast("live.{$stream->channel_id}", 'live.replay.ready', $payload);
        $this->realtime->broadcast("stream.{$stream->channel_id}", 'live.replay.ready', $payload);

        return [
            'recording_url' => $publicUrl,
            'post_id' => $post->id,
            'media_id' => $media->id,
            'thumbnail_url' => $thumbPublicUrl,
        ];
    }

    /**
     * Admin force termination of a live stream with audit logging.
     */
    public function adminEndStream(LiveStream $stream, User $admin, string $reason): LiveStream
    {
        $stream->update([
            'status' => LiveStream::STATUS_ENDED,
            'ended_at' => now(),
            'viewers_count' => 0,
        ]);

        LiveStreamViewer::where('live_stream_id', $stream->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'left_at' => now(),
            ]);

        Log::warning('Admin terminated live stream', [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'admin_id' => $admin->id,
            'reason' => $reason,
        ]);

        $payload = [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'terminated_by_admin' => true,
            'reason' => $reason,
        ];

        $this->realtime->broadcast("live.{$stream->channel_id}", 'live.ended', $payload);
        $this->realtime->broadcast("stream.{$stream->channel_id}", 'live.ended', $payload);

        return $stream;
    }

    /**
     * Dispatch live notification to broadcaster's friends and followers.
     */
    protected function notifyFollowersAndFriends(LiveStream $stream): void
    {
        try {
            $broadcaster = $stream->user;
            $recipientIds = [];

            // Get friends
            $friendIds = $broadcaster->friends()->pluck('users.id')->all();
            $recipientIds = array_merge($recipientIds, $friendIds);

            // Get followers
            $followerIds = $broadcaster->followers()->pluck('users.id')->all();
            $recipientIds = array_unique(array_merge($recipientIds, $followerIds));

            $notificationData = [
                'stream_id' => $stream->id,
                'channel_id' => $stream->channel_id,
                'broadcaster_id' => $broadcaster->id,
                'broadcaster_name' => $broadcaster->name,
                'title' => $broadcaster->name.' এখন লাইভ সম্প্রচারে আছেন',
                'message' => $stream->title ?: $broadcaster->name.'-এর লাইভ ভিডিও শুরু হয়েছে। এখনই যুক্ত হোন!',
                'link' => "/live/{$stream->id}",
                'icon' => 'video',
            ];

            foreach (array_slice($recipientIds, 0, 50) as $recipientId) {
                if ($recipientId !== $broadcaster->id) {
                    $this->notificationService->send(
                        recipientId: (int) $recipientId,
                        type: 'live.started',
                        data: $notificationData,
                        channels: ['database']
                    );
                }
            }
        } catch (Exception $e) {
            Log::info('Live notification dispatch non-blocking notice: '.$e->getMessage());
        }
    }

    /**
     * Generate adaptive bitrate master HLS playlist spec.
     *
     * @return array<string, string>
     */
    public function getAdaptiveBitrates(LiveStream $stream): array
    {
        $base = dirname($stream->playback_url);

        return [
            '1080p' => "{$base}/1080p/index.m3u8",
            '720p' => "{$base}/720p/index.m3u8",
            '480p' => "{$base}/480p/index.m3u8",
            '360p' => "{$base}/360p/index.m3u8",
            '240p' => "{$base}/240p/index.m3u8",
        ];
    }
}
