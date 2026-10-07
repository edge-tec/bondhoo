<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class NotificationWebController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
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
     * Display the notifications center page.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->route('login')->with('error', 'নোটিফিকেশন দেখতে অনুগ্রহ করে প্রথমে লগইন করুন।');
        }

        $filter = (string) $request->query('filter', 'all');
        $category = (string) $request->query('category', 'all');
        $perPage = min(max((int) $request->query('per_page', 20), 5), 50);

        $notifications = $this->notificationService->getNotifications(
            user: $user,
            perPage: $perPage,
            filter: $filter,
            category: $category
        );
        $unreadCount = $this->notificationService->getUnreadCount($user);

        return view('notifications.index', [
            'currentUser' => $user,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'filter' => $filter,
            'category' => $category,
        ]);
    }
}
