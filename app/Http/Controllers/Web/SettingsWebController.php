<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\User;
use App\Services\Messenger\MessengerSoundService;
use App\Services\ProfilePrivacyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsWebController extends Controller
{
    public function __construct(
        protected ProfilePrivacyService $privacyService,
        protected MessengerSoundService $soundService
    ) {}

    /**
     * Show the unified Facebook-style Settings & Privacy Center.
     */
    public function index(Request $request, string $tab = 'account'): View
    {
        /** @var ?User $user */
        $user = Auth::guard('web')->user() ?? $request->user();

        if (! $user && app()->environment('local')) {
            $user = User::first();
            if ($user) {
                Auth::guard('web')->login($user);
            }
        }

        if (! $user) {
            abort(401, 'লগইন আবশ্যক।');
        }

        $activeTab = $request->query('tab', $tab);
        $allowedTabs = ['account', 'security', 'privacy', 'blocking', 'notifications', 'sounds'];
        if (! in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'account';
        }

        $userSetting = $user->settings()->firstOrCreate(['user_id' => $user->id]);
        $privacySetting = $user->privacySettings()->firstOrCreate(['user_id' => $user->id]);
        $notificationSetting = $user->notificationSettings()->firstOrCreate(['user_id' => $user->id]);
        $soundSettings = $this->soundService->getUserSoundSettings($user);

        $twoFactor = $user->twoFactor;
        $recoveryCodesCount = $user->recoveryCodes()->whereNull('used_at')->count();
        $is2FaEnabled = (bool) ($user->two_factor_enabled && ($twoFactor?->is_enabled ?? true));

        // Get blocked users
        $blockedFriendIds = Friendship::where('user_id', $user->id)
            ->where('status', Friendship::STATUS_BLOCKED)
            ->pluck('friend_id')
            ->toArray();

        $blockedUserIds = BlockedUser::where('user_id', $user->id)
            ->active()
            ->whereNotNull('identifier')
            ->pluck('identifier')
            ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
            ->filter()
            ->toArray();

        $allBlockedIds = array_values(array_unique(array_filter(array_merge($blockedFriendIds, $blockedUserIds))));

        $blockedUsers = User::whereIn('id', $allBlockedIds)
            ->with('profile')
            ->get()
            ->map(function (User $u): array {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                    'avatar' => $u->profile?->avatar_url ?: '/images/default-avatar.svg',
                    'is_verified' => (bool) $u->is_verified,
                ];
            });

        return view('settings.index', [
            'user' => $user,
            'activeTab' => $activeTab,
            'userSetting' => $userSetting,
            'privacySetting' => $privacySetting,
            'notificationSetting' => $notificationSetting,
            'soundSettings' => $soundSettings,
            'twoFactor' => $twoFactor,
            'is2FaEnabled' => $is2FaEnabled,
            'recoveryCodesCount' => $recoveryCodesCount,
            'blockedUsers' => $blockedUsers,
        ]);
    }

    /**
     * Account tab shortcut.
     */
    public function account(Request $request): View
    {
        return $this->index($request, 'account');
    }

    /**
     * Security & Login tab shortcut.
     */
    public function security(Request $request): View
    {
        return $this->index($request, 'security');
    }

    /**
     * Privacy tab shortcut.
     */
    public function privacy(Request $request): View
    {
        return $this->index($request, 'privacy');
    }

    /**
     * Blocking tab shortcut.
     */
    public function blocking(Request $request): View
    {
        return $this->index($request, 'blocking');
    }

    /**
     * Messenger Sounds & Ringtones tab shortcut.
     */
    public function sounds(Request $request): View
    {
        return $this->index($request, 'sounds');
    }

    /**
     * Terms of Service page.
     */
    public function terms(): View
    {
        return view('legal.terms');
    }

    /**
     * Cookie and Data Policy page.
     */
    public function cookies(): View
    {
        return view('legal.cookies');
    }

    /**
     * Privacy Policy page.
     */
    public function privacyPolicy(): View
    {
        return view('legal.privacy');
    }

    /**
     * Community Guidelines page.
     */
    public function guidelines(): View
    {
        return view('legal.guidelines');
    }
}
