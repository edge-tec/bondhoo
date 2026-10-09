<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Conversation;
use App\Models\MessengerSyncEvent;
use App\Models\User;
use App\Services\Calling\CallingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class CallWebController extends Controller
{
    public function __construct(
        protected CallingService $callingService
    ) {}

    /**
     * Resolve the current authenticated user from web guard or cookie token.
     */
    protected function resolveUser(Request $request): ?User
    {
        $user = $request->user();
        if (! $user && $request->hasCookie('jugajug_token')) {
            $rawToken = (string) $request->cookie('jugajug_token');
            $pat = PersonalAccessToken::findToken($rawToken);
            if ($pat && $pat->tokenable instanceof User) {
                $user = $pat->tokenable;
                auth('web')->login($user);
            }
        }

        return $user;
    }

    /**
     * Display the active WebRTC Audio/Video Call Screen.
     */
    public function show(Request $request, int $id): View|RedirectResponse|Response
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->route('login')->with('error', 'কল করতে বা যোগ দিতে অনুগ্রহ করে প্রথমে লগইন করুন।');
        }

        $conversation = Conversation::with(['participants.user.profile'])->find($id);

        if (! $conversation) {
            abort(404, 'চ্যাট কনভার্সনটি খুঁজে পাওয়া যায়নি।');
        }

        if (! $conversation->hasParticipant($user->id)) {
            abort(403, 'এই চ্যাট কলে যোগ দেওয়ার অনুমতি আপনার নেই।');
        }

        $callType = strtolower($request->query('type', 'audio'));
        if (! in_array($callType, ['audio', 'video'], true)) {
            $callType = 'audio';
        }

        // Identify other participant
        $otherParticipant = $conversation->participants->where('user_id', '!=', $user->id)->first();
        $peerUser = $otherParticipant?->user;

        $iceServers = $this->callingService->getIceServers($user);

        // Callee / Specific Call mode: validate authoritative status
        $answerCallId = null;
        if ($request->filled('call_id')) {
            $call = Call::where('id', (int) $request->query('call_id'))
                ->where('conversation_id', $conversation->id)
                ->first();

            // If the specified call is already ended, rejected, or missed, never revive or dial anew
            if ($call && in_array($call->status, [Call::STATUS_ENDED, Call::STATUS_REJECTED, Call::STATUS_MISSED, Call::STATUS_BUSY, Call::STATUS_FAILED], true)) {
                return redirect()->route('messages.show', ['id' => $conversation->id])
                    ->with('info', 'কলটি ইতোমধ্যে সমাপ্ত হয়েছে।');
            }

            if ($request->boolean('answer')) {
                if ($call && in_array($call->status, [Call::STATUS_RINGING, Call::STATUS_INITIATING, Call::STATUS_ACTIVE]) && $call->participants()->where('user_id', $user->id)->exists()) {
                    $answerCallId = $call->id;
                    $callType = in_array($call->call_type, [Call::TYPE_VIDEO, Call::TYPE_GROUP_VIDEO], true) ? 'video' : 'audio';
                } else {
                    return redirect()->route('messages.show', ['id' => $conversation->id])
                        ->with('info', 'কলটি আর সক্রিয় নেই।');
                }
            } elseif ($call && in_array($call->status, [Call::STATUS_RINGING, Call::STATUS_INITIATING, Call::STATUS_ACTIVE])) {
                $answerCallId = $call->id;
                $callType = in_array($call->call_type, [Call::TYPE_VIDEO, Call::TYPE_GROUP_VIDEO], true) ? 'video' : 'audio';
            }
        }

        $syncCursor = 0;
        if ($answerCallId) {
            $minEventId = MessengerSyncEvent::where('conversation_id', $conversation->id)
                ->where(function ($q) use ($answerCallId) {
                    $q->where('payload->call_id', $answerCallId)
                        ->orWhere('payload->call_id', (string) $answerCallId);
                })
                ->min('id');
            $syncCursor = $minEventId ? max(0, $minEventId - 1) : max(0, ((int) MessengerSyncEvent::max('id')) - 30);
        } else {
            $syncCursor = max(0, ((int) MessengerSyncEvent::max('id')) - 30);
        }

        $apiToken = $user->createToken('call_session')->plainTextToken;

        return response()->view('calls.show', [
            'currentUser' => $user,
            'conversation' => $conversation,
            'peerUser' => $peerUser,
            'callType' => $callType,
            'iceServers' => $iceServers,
            'answerCallId' => $answerCallId,
            'syncCursor' => $syncCursor,
            'apiToken' => $apiToken,
        ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT')
            ->withCookie(cookie('jugajug_token', $apiToken, 60 * 24));
    }
}
