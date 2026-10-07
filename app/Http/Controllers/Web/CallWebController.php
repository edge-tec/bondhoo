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

        // Callee mode: validate the call being answered belongs to this conversation and is still ringing
        $answerCallId = null;
        if ($request->boolean('answer') && $request->filled('call_id')) {
            $call = Call::where('id', (int) $request->query('call_id'))
                ->where('conversation_id', $conversation->id)
                ->whereIn('status', [Call::STATUS_RINGING, Call::STATUS_INITIATING, Call::STATUS_ACTIVE])
                ->first();

            if ($call && $call->participants()->where('user_id', $user->id)->exists()) {
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
        ])->withCookie(cookie('jugajug_token', $apiToken, 60 * 24));
    }
}
