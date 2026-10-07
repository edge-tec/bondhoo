<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LiveStream;
use App\Models\LiveStreamReport;
use App\Models\Post;
use App\Models\Reel;
use App\Models\User;
use App\Services\Calling\CallingService;
use App\Services\Streaming\LiveStreamingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class WatchWebController extends Controller
{
    public function __construct(
        protected CallingService $callingService,
        protected LiveStreamingService $streamingService
    ) {}

    protected function resolveUser(Request $request): ?User
    {
        $user = $request->user();
        if (! $user && ($request->hasCookie('bondhoo_token') || $request->hasCookie('jugajug_token'))) {
            $rawToken = (string) ($request->cookie('bondhoo_token') ?: $request->cookie('jugajug_token'));
            $pat = PersonalAccessToken::findToken($rawToken);
            if ($pat && $pat->tokenable instanceof User) {
                $user = $pat->tokenable;
                auth('web')->login($user);
            }
        }

        return $user ?: auth('web')->user();
    }

    /**
     * Display Watch video feed and live streams list.
     */
    public function index(Request $request): View
    {
        $user = $this->resolveUser($request);

        $this->streamingService->pruneStaleStreams();

        $liveStreams = LiveStream::with('user.profile')
            ->whereIn('status', [LiveStream::STATUS_LIVE, LiveStream::STATUS_READY])
            ->latest('viewers_count')
            ->get()
            ->filter(fn (LiveStream $s) => $s->canView($user))
            ->values();

        $featuredStream = $liveStreams->first();

        $videoPosts = Post::with(['user.profile', 'media'])
            ->where(function ($q) {
                $q->where('type', 'video')
                    ->orWhereHas('media', fn ($mq) => $mq->where('mime_type', 'like', 'video/%')->orWhere('collection', 'videos'));
            })
            ->latest('id')
            ->paginate(10);

        $reels = Reel::with(['user.profile', 'media', 'musicTrack'])
            ->where('privacy', 'public')
            ->where('is_draft', false)
            ->latest('id')
            ->take(12)
            ->get();

        return view('watch.index', [
            'currentUser' => $user,
            'liveStreams' => $liveStreams,
            'featuredStream' => $featuredStream,
            'videoPosts' => $videoPosts,
            'reels' => $reels,
        ]);
    }

    /**
     * Alias for Live Studio creation entry.
     */
    public function create(Request $request): View|RedirectResponse
    {
        return $this->studio($request);
    }

    /**
     * Display Creator Live Studio for broadcasting.
     */
    public function studio(Request $request): View|RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->route('login')->with('error', 'লাইভ স্টুডিওতে প্রবেশ করতে অনুগ্রহ করে প্রথমে লগইন করুন।');
        }

        // Find existing live stream or create a session
        $myStream = null;
        if ($request->filled('stream_id')) {
            $myStream = LiveStream::where('user_id', $user->id)
                ->where('id', (int) $request->query('stream_id'))
                ->whereIn('status', [LiveStream::STATUS_LIVE, LiveStream::STATUS_READY, LiveStream::STATUS_PREPARING])
                ->first();
        }

        if (! $myStream) {
            $myStream = LiveStream::where('user_id', $user->id)
                ->whereIn('status', [LiveStream::STATUS_LIVE, LiveStream::STATUS_READY, LiveStream::STATUS_PREPARING])
                ->latest('id')
                ->first();
        }

        if (! $myStream) {
            $myStream = $this->streamingService->createChannel(
                user: $user,
                title: $user->name.'-এর লাইভ সেশন',
                description: 'Bondhoo পেশাদার লাইভ ব্রডকাস্টিং',
                options: [
                    'privacy' => LiveStream::PRIVACY_PUBLIC,
                    'recording_enabled' => true,
                    'comments_enabled' => true,
                    'reactions_enabled' => true,
                    'sharing_enabled' => true,
                ]
            );
        }

        $iceServers = $this->callingService->getIceServers($user);
        $recentGifts = $myStream->gifts()->with('sender.profile')->latest('id')->limit(10)->get();
        $gateway = $this->streamingService->getGateway();
        $hostToken = $myStream->sfu_host_token ?: $gateway->generateHostToken($myStream, $user);

        return view('watch.studio', [
            'currentUser' => $user,
            'stream' => $myStream,
            'recentGifts' => $recentGifts,
            'iceServers' => $iceServers,
            'sfuRoomId' => $myStream->sfu_room_id,
            'sfuEndpoint' => $gateway->getSfuEndpoint(),
            'hostToken' => $hostToken,
        ]);
    }

    /**
     * Display Single Live Video Stream or Replay for Viewers.
     */
    public function show(int $id, Request $request): View|RedirectResponse
    {
        $user = $this->resolveUser($request);
        $stream = LiveStream::with([
            'user.profile',
            'comments' => fn ($q) => $q->with('user.profile')->latest('id')->limit(50),
            'moderators.user.profile',
        ])->findOrFail($id);

        if (! $stream->canView($user)) {
            return response()->view('watch.access_denied', [
                'currentUser' => $user,
                'stream' => $stream,
            ], 403);
        }

        $iceServers = $this->callingService->getIceServers($user);
        $isModerator = $user ? $stream->isModerator($user) : false;
        $isBroadcaster = $user && $user->id === $stream->user_id;
        $gateway = $this->streamingService->getGateway();
        $viewerToken = $user ? $this->streamingService->issueViewerToken($stream, $user) : null;

        return view('watch.show', [
            'currentUser' => $user,
            'stream' => $stream,
            'iceServers' => $iceServers,
            'isModerator' => $isModerator,
            'isBroadcaster' => $isBroadcaster,
            'sfuRoomId' => $stream->sfu_room_id,
            'sfuEndpoint' => $gateway->getSfuEndpoint(),
            'viewerToken' => $viewerToken,
        ]);
    }

    /**
     * Admin Live Streams Management Dashboard.
     */
    public function adminLive(Request $request): View|RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user || ! ($user->is_admin ?? false || $user->status === 'admin' || str_contains($user->email, 'admin') || (method_exists($user, 'hasRole') && $user->hasRole(['admin', 'ADMIN'])))) {
            abort(403, 'অননুমোদিত অ্যাক্সেস। শুধুমাত্র অ্যাডমিনরা লাইভ ম্যানেজমেন্ট ব্যবহার করতে পারেন।');
        }

        $activeStreams = LiveStream::with(['user.profile'])
            ->where('status', LiveStream::STATUS_LIVE)
            ->latest('viewers_count')
            ->get();

        $recentStreams = LiveStream::with(['user.profile'])
            ->where('status', LiveStream::STATUS_ENDED)
            ->latest('ended_at')
            ->limit(25)
            ->get();

        $reports = LiveStreamReport::with(['liveStream.user.profile', 'reporter.profile'])
            ->latest('id')
            ->limit(50)
            ->get();

        return view('admin.live.index', [
            'currentUser' => $user,
            'activeStreams' => $activeStreams,
            'recentStreams' => $recentStreams,
            'reports' => $reports,
        ]);
    }

    /**
     * Admin force terminate live stream.
     */
    public function adminTerminate(int $id, Request $request): RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user || ! ($user->is_admin ?? false || $user->status === 'admin' || str_contains($user->email, 'admin') || (method_exists($user, 'hasRole') && $user->hasRole(['admin', 'ADMIN'])))) {
            abort(403, 'অননুমোদিত।');
        }

        $stream = LiveStream::findOrFail($id);
        $reason = $request->input('reason', 'Community guidelines violation');

        $this->streamingService->adminEndStream($stream, $user, $reason);

        return redirect()->back()->with('success', "লাইভ স্ট্রিম #{$id} সফলভাবে বন্ধ করা হয়েছে।");
    }
}
