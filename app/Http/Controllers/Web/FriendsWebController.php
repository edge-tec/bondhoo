<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FriendshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class FriendsWebController extends Controller
{
    public function __construct(
        protected FriendshipService $friendshipService
    ) {}

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
     * Display the Enterprise Friends Center dashboard.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->route('login')->with('error', 'বন্ধু তালিকা দেখতে অনুগ্রহ করে প্রথমে লগইন করুন।');
        }

        $tab = $request->query('tab', 'all');
        $filter = $request->query('filter', 'all');
        $sort = $request->query('sort', 'recently_added');
        $search = $request->query('search');
        $listId = $request->query('list_id');

        $counters = $this->friendshipService->getCounters($user);
        $customLists = $this->friendshipService->getLists($user);

        $friends = null;
        $requests = null;
        $sentRequests = null;
        $suggestions = null;
        $birthdays = null;
        $listMembers = null;
        $selectedList = null;

        switch ($tab) {
            case 'requests':
                $requests = $this->friendshipService->getPendingRequests($user, 24);
                break;
            case 'sent':
                $sentRequests = $this->friendshipService->getSentRequests($user, 24);
                break;
            case 'suggestions':
                $suggestions = $this->friendshipService->getSuggestions($user, 30);
                break;
            case 'birthdays':
                $birthdays = $this->friendshipService->getBirthdays($user);
                break;
            case 'custom_lists':
                if ($listId) {
                    $selectedList = $customLists->firstWhere('id', (int) $listId);
                    if ($selectedList) {
                        $listMembers = $this->friendshipService->getListMembers($user, (int) $listId, 24);
                    }
                }
                break;
            case 'all':
            default:
                $friends = $this->friendshipService->getFriends(
                    $user,
                    ['filter' => $filter, 'sort' => $sort, 'search' => $search],
                    $sort,
                    $search,
                    24
                );
                break;
        }

        return view('friends.index', [
            'currentUser' => $user,
            'tab' => $tab,
            'filter' => $filter,
            'sort' => $sort,
            'search' => $search,
            'counters' => $counters,
            'customLists' => $customLists,
            'friends' => $friends,
            'requests' => $requests,
            'sentRequests' => $sentRequests,
            'suggestions' => $suggestions,
            'birthdays' => $birthdays,
            'selectedList' => $selectedList,
            'listMembers' => $listMembers,
        ]);
    }
}
