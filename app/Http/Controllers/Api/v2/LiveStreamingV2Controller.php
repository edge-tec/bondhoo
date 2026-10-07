<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\LiveStream;
use App\Models\User;
use App\Services\Calling\CallingService;
use App\Services\Streaming\LiveStreamingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class LiveStreamingV2Controller extends Controller
{
    public function __construct(
        protected LiveStreamingService $streamingService,
        protected CallingService $callingService
    ) {}

    /**
     * Resolve authenticated user from Sanctum token, session, or cookie.
     */
    protected function resolveCurrentUser(Request $request): ?User
    {
        if ($request->bearerToken()) {
            $pat = PersonalAccessToken::findToken($request->bearerToken());
            if ($pat && $pat->tokenable instanceof User) {
                return $pat->tokenable;
            }
        }

        $user = $request->user();
        if ($user) {
            return $user;
        }

        if ($request->hasCookie('bondhoo_token') || $request->hasCookie('jugajug_token')) {
            $rawToken = (string) ($request->cookie('bondhoo_token') ?: $request->cookie('jugajug_token'));
            $pat = PersonalAccessToken::findToken($rawToken);
            if ($pat && $pat->tokenable instanceof User) {
                return $pat->tokenable;
            }
        }

        return auth('web')->user();
    }

    /**
     * Get current authenticated user profile for live suite.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->resolveCurrentUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত অনুরোধ।'], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user->load('profile'),
            ],
        ]);
    }

    /**
     * List active and replay live streams.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->resolveCurrentUser($request);
        $status = $request->query('status', 'live');

        if ($status === 'live' || $status === 'all') {
            $this->streamingService->pruneStaleStreams();
        }

        $query = LiveStream::with(['user.profile'])
            ->latest('id');

        if ($status === 'live') {
            $query->whereIn('status', [LiveStream::STATUS_LIVE, LiveStream::STATUS_READY]);
        } elseif ($status === 'replays') {
            $query->where('status', LiveStream::STATUS_ENDED)
                ->where('recording_status', LiveStream::RECORDING_STATUS_READY);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $streams = $query->limit(30)->get()->filter(function (LiveStream $stream) use ($user) {
            return $stream->canView($user);
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => $streams,
        ]);
    }

    /**
     * Retrieve single live stream details with privacy check.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $user = $this->resolveCurrentUser($request);
        $stream = LiveStream::with([
            'user.profile',
            'comments' => fn ($q) => $q->with('user.profile')->latest('id')->limit(50),
            'moderators.user.profile',
        ])->findOrFail($id);

        if (! $stream->canView($user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'এই লাইভ স্ট্রিমটি প্রাইভেট এবং আপনার দেখার অনুমতি নেই।',
            ], 403);
        }

        $gateway = $this->streamingService->getGateway();
        $isBroadcaster = $user && $user->id === $stream->user_id;
        $hostToken = $isBroadcaster ? ($stream->sfu_host_token ?: $gateway->generateHostToken($stream, $user)) : null;

        return response()->json([
            'status' => 'success',
            'data' => [
                'stream' => $stream,
                'can_broadcast' => $isBroadcaster,
                'is_moderator' => $user ? $stream->isModerator($user) : false,
                'sfu_room_id' => $stream->sfu_room_id,
                'sfu_endpoint' => $gateway->getSfuEndpoint(),
                'sfu_provider' => $stream->sfu_provider ?: $gateway->getProvider(),
                'host_token' => $hostToken,
                'ice_servers' => $this->callingService->getIceServers($user),
            ],
        ]);
    }

    /**
     * Create new broadcast channel with privacy & feature flags.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'privacy' => 'nullable|string|in:public,friends,only_me',
            'comments_enabled' => 'nullable|boolean',
            'reactions_enabled' => 'nullable|boolean',
            'sharing_enabled' => 'nullable|boolean',
            'recording_enabled' => 'nullable|boolean',
        ]);

        $user = $this->resolveCurrentUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত অনুরোধ।'], 401);
        }

        $stream = $this->streamingService->createChannel(
            user: $user,
            title: $request->input('title'),
            description: $request->input('description'),
            options: [
                'privacy' => $request->input('privacy', LiveStream::PRIVACY_PUBLIC),
                'comments_enabled' => $request->boolean('comments_enabled', true),
                'reactions_enabled' => $request->boolean('reactions_enabled', true),
                'sharing_enabled' => $request->boolean('sharing_enabled', true),
                'recording_enabled' => $request->boolean('recording_enabled', true),
            ]
        );

        $gateway = $this->streamingService->getGateway();
        $hostToken = $stream->sfu_host_token ?: $gateway->generateHostToken($stream, $user);

        return response()->json([
            'status' => 'success',
            'data' => $stream->load('user.profile'),
            'sfu_room_id' => $stream->sfu_room_id,
            'sfu_endpoint' => $gateway->getSfuEndpoint(),
            'sfu_provider' => $stream->sfu_provider ?: $gateway->getProvider(),
            'host_token' => $hostToken,
            'ice_servers' => $this->callingService->getIceServers($user),
        ], 201);
    }

    /**
     * Issue short-lived SFU viewer token with privacy and block verification.
     */
    public function viewerToken(int $id, Request $request): JsonResponse
    {
        $user = $this->resolveCurrentUser($request);
        $stream = LiveStream::findOrFail($id);

        if (! $stream->canView($user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'এই লাইভ স্ট্রিমটিতে প্রবেশের অনুমতি আপনার নেই।',
            ], 403);
        }

        $token = $this->streamingService->issueViewerToken($stream, $user);
        $gateway = $this->streamingService->getGateway();

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
                'room_id' => $stream->sfu_room_id,
                'sfu_endpoint' => $gateway->getSfuEndpoint(),
                'sfu_provider' => $stream->sfu_provider ?: $gateway->getProvider(),
                'ice_servers' => $this->callingService->getIceServers($user),
            ],
        ]);
    }

    /**
     * Broadcaster confirms WebRTC publication on SFU gateway.
     */
    public function confirmPublish(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'host_token' => 'required|string',
            'track_meta' => 'nullable|array',
        ]);

        $stream = LiveStream::findOrFail($id);

        try {
            $this->streamingService->confirmSfuPublication(
                stream: $stream,
                hostToken: $request->input('host_token'),
                trackMeta: $request->input('track_meta', [])
            );

            return response()->json([
                'status' => 'success',
                'message' => 'SFU ট্র্যাক পাবলিকেশন সফলভাবে নিশ্চিত হয়েছে।',
                'data' => $stream->fresh()->load('user.profile'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Start broadcasting.
     */
    public function start(int $id, Request $request): JsonResponse
    {
        $user = $this->resolveCurrentUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত অনুরোধ।'], 401);
        }

        $stream = LiveStream::where('user_id', $user->id)->findOrFail($id);
        $this->streamingService->startStream($stream);

        return response()->json([
            'status' => 'success',
            'data' => $stream->fresh()->load('user.profile'),
            'ice_servers' => $this->callingService->getIceServers($user),
        ]);
    }

    /**
     * End broadcast.
     */
    public function end(int $id, Request $request): JsonResponse
    {
        $user = $this->resolveCurrentUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত অনুরোধ।'], 401);
        }

        $stream = LiveStream::where('user_id', $user->id)->findOrFail($id);
        $ended = $this->streamingService->endStream($stream, $user);

        return response()->json([
            'status' => 'success',
            'data' => $ended,
        ]);
    }

    /**
     * Viewer presence: Join.
     */
    public function join(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|max:64',
        ]);

        $user = $this->resolveCurrentUser($request);
        $stream = LiveStream::findOrFail($id);

        if (! $stream->canView($user)) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত।'], 403);
        }

        $stats = $this->streamingService->joinViewer($stream, $user, $request->input('session_id'));

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * Viewer presence: Heartbeat.
     */
    public function heartbeat(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|max:64',
        ]);

        $user = $this->resolveCurrentUser($request);
        $stream = LiveStream::findOrFail($id);

        $stats = $this->streamingService->heartbeat($stream, $user, $request->input('session_id'));

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * Viewer presence: Leave.
     */
    public function leave(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|max:64',
        ]);

        $user = $this->resolveCurrentUser($request);
        $stream = LiveStream::findOrFail($id);

        $stats = $this->streamingService->leaveViewer($stream, $user, $request->input('session_id'));

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * WebRTC Signaling relay.
     */
    public function signal(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'signal_type' => 'required|string|in:offer,answer,candidate,screen_share_started,screen_share_stopped',
            'payload' => 'required|array',
            'target_user_id' => 'nullable|integer',
        ]);

        $user = $this->resolveCurrentUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত অনুরোধ।'], 401);
        }

        $stream = LiveStream::findOrFail($id);
        if (! $stream->canView($user)) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত।'], 403);
        }

        $signal = $this->streamingService->sendSignal(
            stream: $stream,
            sender: $user,
            signalType: $request->input('signal_type'),
            payload: $request->input('payload'),
            targetUserId: $request->input('target_user_id')
        );

        return response()->json([
            'status' => 'success',
            'data' => $signal,
        ]);
    }

    /**
     * Post comment in live chat.
     */
    public function comment(int $id, Request $request): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:500']);

        $user = $this->resolveCurrentUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত অনুরোধ।'], 401);
        }

        $stream = LiveStream::findOrFail($id);
        if (! $stream->canView($user)) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত।'], 403);
        }

        try {
            $comment = $this->streamingService->sendComment($stream, $user, $request->input('message'));

            return response()->json([
                'status' => 'success',
                'data' => $comment->load('user.profile'),
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete comment in live chat.
     */
    public function deleteComment(int $id, int $commentId, Request $request): JsonResponse
    {
        $user = $this->resolveCurrentUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত অনুরোধ।'], 401);
        }

        $stream = LiveStream::findOrFail($id);

        try {
            $this->streamingService->deleteComment($stream, $commentId, $user);

            return response()->json(['status' => 'success', 'message' => 'মন্তব্যটি মুছে ফেলা হয়েছে।']);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * Send reaction in live stream.
     */
    public function reaction(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'reaction_type' => 'required|string|in:like,love,care,haha,wow,sad,angry',
        ]);

        $user = $this->resolveCurrentUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত অনুরোধ।'], 401);
        }

        $stream = LiveStream::findOrFail($id);
        if (! $stream->canView($user)) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত।'], 403);
        }

        try {
            $reaction = $this->streamingService->sendReaction($stream, $user, $request->input('reaction_type'));

            return response()->json([
                'status' => 'success',
                'data' => $reaction,
                'total_reactions' => $stream->fresh()->total_reactions,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Send virtual gift in live stream.
     */
    public function gift(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'gift_type' => 'required|string|in:rose,diamond,crown,rocket',
            'coins' => 'required|integer|min:1',
        ]);

        $user = $this->resolveCurrentUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত অনুরোধ।'], 401);
        }

        $stream = LiveStream::findOrFail($id);
        if (! $stream->canView($user)) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত।'], 403);
        }

        $gift = $this->streamingService->sendGift(
            stream: $stream,
            user: $user,
            giftType: $request->input('gift_type'),
            coins: (int) $request->input('coins')
        );

        return response()->json(['status' => 'success', 'data' => $gift], 201);
    }

    /**
     * Record a share of the live stream.
     */
    public function share(int $id, Request $request): JsonResponse
    {
        $user = $this->resolveCurrentUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত অনুরোধ।'], 401);
        }

        $stream = LiveStream::findOrFail($id);
        $shares = $this->streamingService->recordShare($stream, $user, $request->input('destination', 'feed'));

        return response()->json([
            'status' => 'success',
            'total_shares' => $shares,
        ]);
    }

    /**
     * Appoint moderator.
     */
    public function addModerator(int $id, Request $request): JsonResponse
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        $user = $this->resolveCurrentUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত অনুরোধ।'], 401);
        }

        $stream = LiveStream::findOrFail($id);
        $moderatorUser = User::findOrFail($request->input('user_id'));

        try {
            $moderator = $this->streamingService->appointModerator($stream, $user, $moderatorUser);

            return response()->json([
                'status' => 'success',
                'data' => $moderator->load('user.profile'),
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * Remove moderator.
     */
    public function removeModerator(int $id, int $userId, Request $request): JsonResponse
    {
        $user = $this->resolveCurrentUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত অনুরোধ।'], 401);
        }

        $stream = LiveStream::findOrFail($id);
        $moderatorUser = User::findOrFail($userId);

        try {
            $this->streamingService->removeModerator($stream, $user, $moderatorUser);

            return response()->json(['status' => 'success', 'message' => 'মডারেটর পদ সফলভাবে অপসারণ করা হয়েছে।']);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * Report live stream.
     */
    public function report(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|in:harassment,violence,sexual_content,hate,spam,copyright,other',
            'details' => 'nullable|string|max:1000',
        ]);

        $user = $this->resolveCurrentUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত অনুরোধ।'], 401);
        }

        $stream = LiveStream::findOrFail($id);
        $report = $this->streamingService->reportStream(
            stream: $stream,
            reporter: $user,
            reason: $request->input('reason'),
            details: $request->input('details')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'রিপোর্ট সফলভাবে গৃহীত হয়েছে। আমাদের টিম পর্যালোচনা করবে।',
            'data' => $report,
        ], 201);
    }

    /**
     * Upload recorded replay file.
     */
    public function uploadReplay(int $id, Request $request): JsonResponse
    {
        $file = $request->file('replay_file') ?: $request->file('video');
        if (! $file) {
            return response()->json([
                'status' => 'error',
                'message' => 'The replay file or video field is required.',
                'errors' => ['replay_file' => ['The replay file or video field is required.']],
            ], 422);
        }

        $request->validate([
            'replay_file' => 'nullable|file|mimes:mp4,webm,mkv,mov|max:512000',
            'video' => 'nullable|file|mimes:mp4,webm,mkv,mov|max:512000',
            'thumbnail_file' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:10240',
        ]);

        $user = $this->resolveCurrentUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'অননুমোদিত অনুরোধ।'], 401);
        }

        $stream = LiveStream::findOrFail($id);

        try {
            $result = $this->streamingService->uploadReplay(
                stream: $stream,
                user: $user,
                file: $file,
                thumbnailFile: $request->file('thumbnail_file')
            );

            $recordingUrl = is_array($result) ? $result['recording_url'] : $result;
            $postId = is_array($result) ? ($result['post_id'] ?? null) : null;

            return response()->json([
                'status' => 'success',
                'message' => 'রিপ্লে ফাইল সফলভাবে প্রক্রিয়াভুক্ত হয়েছে এবং ভিডিও পোস্ট তৈরি হয়েছে।',
                'recording_url' => $recordingUrl,
                'post_id' => $postId,
                'post_url' => $postId ? "/dashboard#post-card-{$postId}" : null,
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * Get WebRTC ICE Servers (STUN/TURN).
     */
    public function iceServers(Request $request): JsonResponse
    {
        $user = $this->resolveCurrentUser($request);

        return response()->json([
            'status' => 'success',
            'ice_servers' => $this->callingService->getIceServers($user),
        ]);
    }
}
