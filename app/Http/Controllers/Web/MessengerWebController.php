<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class MessengerWebController extends Controller
{
    public function __construct(
        protected MessengerServiceInterface $messengerService
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
     * Display the full Messenger interface.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->route('login')->with('error', 'মেসেঞ্জার ব্যবহার করতে অনুগ্রহ করে প্রথমে লগইন করুন।');
        }

        $conversations = $this->messengerService->getUserConversations($user, 30);
        $activeConversation = null;

        // Open specific conversation only if explicitly requested
        if ($request->filled('conversation_id')) {
            $convId = (int) $request->input('conversation_id');
            $activeConversation = Conversation::with(['participants.user.profile', 'lastMessage'])
                ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
                ->find($convId);
            if ($activeConversation) {
                $this->messengerService->markConversationAsRead($activeConversation, $user);
            }
        } elseif ($request->filled('user')) {
            $targetUserId = (int) $request->input('user');
            $targetUser = User::find($targetUserId);
            if ($targetUser && $targetUser->id !== $user->id) {
                $activeConversation = $this->messengerService->getOrCreateDirectConversation($user, $targetUser->id);
                if ($activeConversation) {
                    $activeConversation->load(['participants.user.profile', 'lastMessage']);
                    $this->messengerService->markConversationAsRead($activeConversation, $user);
                }
            }
        }

        $messages = $activeConversation ? $this->messengerService->getMessages($activeConversation, $user, 50) : collect();

        return view('messages.index', [
            'currentUser' => $user,
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messages' => $messages,
        ]);
    }

    /**
     * Display a specific active conversation in the Messenger interface.
     */
    public function show(Request $request, int $id): View|RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->route('login')->with('error', 'মেসেঞ্জার ব্যবহার করতে অনুগ্রহ করে প্রথমে লগইন করুন।');
        }

        $activeConversation = Conversation::with(['participants.user.profile', 'lastMessage'])
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->find($id);

        if (! $activeConversation) {
            return redirect()->route('messages.index')->with('error', 'কনভার্সনটি পাওয়া যায়নি বা আপনার দেখার অনুমতি নেই।');
        }

        // Mark as read
        $this->messengerService->markConversationAsRead($activeConversation, $user);

        $conversations = $this->messengerService->getUserConversations($user, 30);
        $messages = $this->messengerService->getMessages($activeConversation, $user, 50);

        return view('messages.index', [
            'currentUser' => $user,
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messages' => $messages,
        ]);
    }
}
