<?php

use App\Http\Controllers\Admin\EmailManagementController;
use App\Http\Controllers\Api\v1\Admin\AdminAuthController;
use App\Http\Controllers\Api\v1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\v1\VoiceMessageController;
use App\Http\Controllers\Api\v2\FederationV2Controller;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\CallWebController;
use App\Http\Controllers\Web\DeviceWebController;
use App\Http\Controllers\Web\FriendsWebController;
use App\Http\Controllers\Web\GroupWebController;
use App\Http\Controllers\Web\MarketplaceWebController;
use App\Http\Controllers\Web\MessengerWebController;
use App\Http\Controllers\Web\NotificationWebController;
use App\Http\Controllers\Web\PageWebController;
use App\Http\Controllers\Web\ProfileWebController;
use App\Http\Controllers\Web\SettingsWebController;
use App\Http\Controllers\Web\SocialWebController;
use App\Http\Controllers\Web\TwoFactorWebController;
use App\Http\Controllers\Web\WatchWebController;
use App\Http\Requests\Auth\RegisterV2Request;
use App\Models\Admin;
use App\Models\Post;
use App\Models\User;
use App\Services\AuthServiceV2;
use App\Services\Email\SmtpConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;

$resolveWebUser = function (?Request $request = null) {
    if (! auth('web')->check()) {
        $req = $request ?? request();
        $token = $req->cookie('jugajug_token') ?? $req->cookie('bondhoo_token');
        if ($token) {
            $rawToken = (string) $token;
            $pat = PersonalAccessToken::findToken($rawToken);
            if (! $pat) {
                try {
                    $decrypted = decrypt($rawToken, false);
                    if (is_string($decrypted)) {
                        if (preg_match('/^[a-f0-9]{40}\|(.+)$/', $decrypted, $m)) {
                            $decrypted = $m[1];
                        }
                        $pat = PersonalAccessToken::findToken($decrypted);
                    }
                } catch (Throwable) {
                    // Ignore decryption failure
                }
            }
            if ($pat && $pat->tokenable instanceof User) {
                auth('web')->login($pat->tokenable);
            }
        }
    }
};

Route::get('/', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser($request);
    if (auth('web')->check() || $request->cookie('bondhoo_token') || $request->cookie('jugajug_token')) {
        if ($request->query('blade')) {
            return view('dashboard');
        }

        return app(SocialWebController::class)->feed($request);
    }

    return view('auth.login');
})->name('dashboard');

Route::get('/dashboard', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser($request);
    if (! auth('web')->check() && ! $request->cookie('bondhoo_token') && ! $request->cookie('jugajug_token')) {
        return redirect()->route('login');
    }

    if ($request->query('blade')) {
        return view('dashboard');
    }

    return app(SocialWebController::class)->feed($request);
})->name('dashboard.view');

Route::get('/feed', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser($request);
    if (! auth('web')->check() && ! $request->cookie('bondhoo_token') && ! $request->cookie('jugajug_token')) {
        return redirect()->route('login');
    }

    if ($request->query('blade')) {
        return view('dashboard');
    }

    return app(SocialWebController::class)->feed($request);
})->name('feed');

// Authentication Pages & Actions (Facebook-style UX & Government Grade Security)
Route::get('/login', function () {
    if (auth('web')->check() || request()->cookie('bondhoo_token') || request()->cookie('jugajug_token')) {
        return redirect()->route('dashboard');
    }

    return view('auth.login');
})->name('login');

// Dedicated Administrator Authentication Entries
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'webLogin']);
Route::post('/admin/logout', [AdminAuthController::class, 'webLogout'])->name('admin.logout');

if (app()->environment('local')) {
    Route::get('/dev/login/{user?}', function ($user = 'admin') {
        $u = is_numeric($user) ? User::find($user) : User::where('username', $user)->first();
        if ($u) {
            auth('web')->login($u);
            $token = $u->createToken('jugajug_web')->plainTextToken;

            return redirect('/@'.$u->username)->withCookie(cookie('jugajug_token', $token, 60 * 24 * 7));
        }

        return redirect('/login');
    })->name('dev.quick-login');
}
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/logout/all', [AuthController::class, 'logoutAll'])->name('logout.all');
Route::get('/me', [AuthController::class, 'me'])->name('me');
Route::post('/session/refresh', [AuthController::class, 'refreshSession'])->name('session.refresh');
Route::post('/session/rotate', [AuthController::class, 'rotateSession'])->name('session.rotate');

// Active Sessions & Devices Management ("My Devices" Dashboard)
Route::middleware(['auth:web'])->group(function () {
    Route::get('/devices', [DeviceWebController::class, 'index'])->name('devices.index');
    Route::post('/devices/logout-all', [DeviceWebController::class, 'logoutAll'])->name('devices.logout-all');
    Route::match(['delete', 'post'], '/devices/trusted/{id}', [DeviceWebController::class, 'revokeTrustedDevice'])->name('devices.trusted.revoke');
    Route::match(['delete', 'post'], '/devices/{id}', [DeviceWebController::class, 'revoke'])->name('devices.revoke');

    // Two-Factor Authentication Settings
    Route::get('/settings/two-factor', [TwoFactorWebController::class, 'show'])->name('settings.two-factor');
    Route::post('/settings/two-factor/setup', [TwoFactorWebController::class, 'setup'])->name('settings.two-factor.setup');
    Route::post('/settings/two-factor/enable', [TwoFactorWebController::class, 'enable'])->name('settings.two-factor.enable');
    Route::post('/settings/two-factor/disable', [TwoFactorWebController::class, 'disable'])->name('settings.two-factor.disable');

    // Notification Preferences Settings
    Route::get('/settings/notifications', function () {
        $user = auth()->user();
        $settings = $user ? $user->notificationSettings()->firstOrCreate(['user_id' => $user->id]) : null;

        return view('settings.notifications', ['settings' => $settings]);
    })->name('settings.notifications');
});

Route::get('/register', function () {
    if (auth('web')->check() || request()->cookie('bondhoo_token') || request()->cookie('jugajug_token')) {
        return redirect()->route('dashboard');
    }

    return view('auth.register');
})->name('register');

Route::post('/register', function (RegisterV2Request $request, AuthServiceV2 $authService) {
    $result = $authService->register($request->validated(), $request);

    $emailDelivery = $result['email_delivery'] ?? null;
    $emailSent = $emailDelivery ? (bool) ($emailDelivery['sent'] ?? false) : true;

    $message = $emailSent
        ? 'নিবন্ধন সফল হয়েছে। আপনার ইমেইল বা মোবাইল যাচাই করতে ওটিপি কোড চেক করুন।'
        : 'আপনার অ্যাকাউন্ট তৈরি হয়েছে, কিন্তু ভেরিফিকেশন ইমেইল পাঠানো সম্ভব হয়নি। অনুগ্রহ করে ভেরিফিকেশন পাতায় গিয়ে পুনরায় চেষ্টা করুন।';

    if ($request->wantsJson()) {
        return response()->json([
            'success' => true,
            'email_sent' => $emailSent,
            'message' => $message,
            'data' => $result,
        ], 201);
    }

    if (! $emailSent) {
        return redirect()->route('verify.email')->with('warning', $message);
    }

    return redirect()->route('verify.email')->with('success', 'নিবন্ধন সফল হয়েছে। ইনবক্স চেক করুন।');
});

Route::get('/verify-email', function () {
    return view('auth.verify-email');
})->name('verify.email');

Route::get('/email/verify/{id}/{hash}', [App\Http\Controllers\Api\v2\Auth\AuthController::class, 'verifySignedEmail'])
    ->name('verification.verify');

Route::get('/verify-mobile', function () {
    return view('auth.verify-mobile');
})->name('verify.mobile');

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->name('password.email');

Route::get('/reset-password', function () {
    return view('auth.reset-password');
})->name('password.reset');
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])
    ->name('password.update');

Route::get('/two-factor-challenge', function () {
    return view('auth.two-factor-challenge');
})->name('2fa.challenge');

Route::get('/account-locked', function () {
    return view('auth.account-locked');
})->name('account.locked');

Route::get('/suspicious-login', function () {
    return view('auth.suspicious-login');
})->name('login.suspicious');

// Admin Authentication & System Management Console (Strict RBAC Protected)
Route::middleware(['admin.web'])->group(function () {
    Route::get('/admin/auth-management', function (SmtpConfigService $smtpConfigService) {
        $smtpSettings = $smtpConfigService->getSafeSettings();

        /** @var Admin|User|null $admin */
        $admin = Auth::guard('admin')->user()
            ?? Auth::guard('web')->user();

        $adminToken = null;
        if ($admin && method_exists($admin, 'createToken')) {
            $adminToken = $admin->createToken('admin_web_console', ['admin', 'manage.users', 'auth.admin'])->plainTextToken;
            session(['admin_api_token' => $adminToken]);
        }

        return view('admin.auth.dashboard', compact('smtpSettings', 'adminToken'));
    })->name('admin.auth.dashboard');

    Route::get('/admin/system/dashboard', function () {
        return view('dashboard');
    });

    // Enterprise SMTP Email Management
    Route::prefix('admin/smtp')->group(function () {
        Route::get('/settings', [EmailManagementController::class, 'getSettings'])->name('admin.smtp.settings');
        Route::post('/settings', [EmailManagementController::class, 'updateSettings'])->name('admin.smtp.update');
        Route::post('/test', [EmailManagementController::class, 'testConnection'])->name('admin.smtp.test');
        Route::post('/verify-connection', [EmailManagementController::class, 'verifyConnection'])->name('admin.smtp.verify_connection');
        Route::get('/dns-check', [EmailManagementController::class, 'checkDns'])->name('admin.smtp.dns_check');
        Route::get('/logs', [EmailManagementController::class, 'getLogs'])->name('admin.smtp.logs');
        Route::post('/logs/{id}/retry', [EmailManagementController::class, 'retryLog'])->name('admin.smtp.retry')->whereNumber('id');
        Route::get('/stats', [EmailManagementController::class, 'getStats'])->name('admin.smtp.stats');
        Route::get('/templates', [EmailManagementController::class, 'getTemplates'])->name('admin.smtp.templates');
        Route::get('/templates/{key}/preview', [EmailManagementController::class, 'previewTemplate'])->name('admin.smtp.preview');
    });
});

// RFC 7033 WebFinger discovery for ActivityPub Federation
Route::get('/.well-known/webfinger', [FederationV2Controller::class, 'webfinger']);

// User Profile & Personal Timeline (SocialHub Modern UI & Enterprise Canonical Identity)
Route::get('/u/{username}', function (Request $request, string $username) use ($resolveWebUser) {
    $resolveWebUser();
    if ($request->query('blade')) {
        return app(ProfileWebController::class)->show($request, $username);
    }

    if ($request->header('X-Inertia') || $request->query('inertia')) {
        return app(SocialWebController::class)->profile($request, ltrim($username, '@'));
    }

    return app(ProfileWebController::class)->show($request, $username);
})->name('profile.u')->where('username', '[A-Za-z0-9_.-]+');

Route::get('/profile/@{username}', function (Request $request, string $username) use ($resolveWebUser) {
    $resolveWebUser();
    if ($request->query('blade')) {
        return app(ProfileWebController::class)->show($request, '@'.$username);
    }

    return app(SocialWebController::class)->profile($request, ltrim($username, '@'));
})->name('profile.at')->where('username', '[A-Za-z0-9_.-]+');

Route::get('/user/{username}', [ProfileWebController::class, 'show'])->name('profile.show');

Route::get('/profile/{username}', function (Request $request, string $username) use ($resolveWebUser) {
    $resolveWebUser();
    if ($request->query('blade')) {
        return app(ProfileWebController::class)->show($request, $username);
    }

    return app(SocialWebController::class)->profile($request, ltrim($username, '@'));
})->name('profile.alias');

Route::get('/@{username}', function (Request $request, string $username) use ($resolveWebUser) {
    $resolveWebUser();
    if ($request->query('blade')) {
        return app(ProfileWebController::class)->show($request, '@'.$username);
    }

    return app(SocialWebController::class)->profile($request, ltrim($username, '@'));
})->name('profile.handle')->where('username', '[A-Za-z0-9_.-]+');

Route::get('/profile', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser();
    $user = auth('web')->user() ?? $request->user();
    if (! $user) {
        return redirect()->route('login');
    }

    return redirect('/profile/'.$user->username);
})->name('profile.me');

// Dedicated Frontend Pages (Step 1 Modularization)
Route::get('/messages', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser();
    if (! auth('web')->check() && ! $request->cookie('bondhoo_token') && ! $request->cookie('jugajug_token')) {
        return redirect()->route('login');
    }

    if ($request->query('blade') || ($request->has('user') && ! $request->header('X-Inertia')) || ($request->has('conversation_id') && ! $request->header('X-Inertia'))) {
        return app(MessengerWebController::class)->index($request);
    }

    return app(SocialWebController::class)->messages($request);
})->name('messages.index');
Route::get('/messages/{id}', [MessengerWebController::class, 'show'])->name('messages.show')->whereNumber('id');
Route::post('/messages/{id}/send', [MessengerWebController::class, 'sendMessage'])->name('messages.web.send')->whereNumber('id');
Route::get('/messages/{id}/voice', [VoiceMessageController::class, 'stream'])->name('messages.voice.stream')->whereNumber('id');
Route::get('/call/{id}', [CallWebController::class, 'show'])->name('call.show')->whereNumber('id');
Route::get('/calls/{id}', [CallWebController::class, 'show'])->name('calls.show')->whereNumber('id');
Route::get('/calls', [CallWebController::class, 'history'])->name('calls.index');
Route::get('/calls/history', [CallWebController::class, 'history'])->name('calls.history');

Route::get('/groups', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser();
    if (! auth('web')->check() && app()->environment('local')) {
        $devUser = User::first();
        if ($devUser) {
            auth('web')->login($devUser);
        }
    }

    if ($request->query('blade')) {
        return app(GroupWebController::class)->index($request);
    }

    return app(SocialWebController::class)->groups($request);
})->name('groups.index');

Route::get('/groups/create', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser();
    if (! auth('web')->check() && app()->environment('local')) {
        $devUser = User::first();
        if ($devUser) {
            auth('web')->login($devUser);
        }
    }

    return app(SocialWebController::class)->createGroup($request);
})->name('groups.create');

Route::get('/groups/{slug}', function (Request $request, string $slug) use ($resolveWebUser) {
    $resolveWebUser();
    if (! auth('web')->check() && app()->environment('local')) {
        $devUser = User::first();
        if ($devUser) {
            auth('web')->login($devUser);
        }
    }

    if ($request->query('blade')) {
        return app(GroupWebController::class)->show($request, $slug);
    }

    return app(SocialWebController::class)->groupProfile($request, $slug);
})->name('groups.show');

Route::get('/pages', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser();
    if (! auth('web')->check() && app()->environment('local')) {
        $devUser = User::first();
        if ($devUser) {
            auth('web')->login($devUser);
        }
    }

    if ($request->query('blade')) {
        return app(PageWebController::class)->index($request);
    }

    return app(SocialWebController::class)->pages($request);
})->name('pages.index');

Route::get('/pages/create', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser();
    if (! auth('web')->check() && app()->environment('local')) {
        $devUser = User::first();
        if ($devUser) {
            auth('web')->login($devUser);
        }
    }

    if ($request->query('blade')) {
        return app(PageWebController::class)->create($request);
    }

    return app(SocialWebController::class)->createPage($request);
})->name('pages.create');
Route::post('/pages', [PageWebController::class, 'store'])->name('pages.store');
Route::get('/pages/{slug}/manage', [PageWebController::class, 'manage'])->name('pages.manage');
Route::get('/pages/{slug}/realtime/sync', [PageWebController::class, 'realtimeSync'])->name('pages.realtime.sync');
Route::get('/pages/{slug}/posts', [PageWebController::class, 'posts'])->name('pages.posts');
Route::get('/pages/{slug}/feed', [PageWebController::class, 'feed'])->name('pages.feed');
Route::get('/pages/{slug}/posts/{postId}', [PageWebController::class, 'showPost'])->name('pages.posts.show')->whereNumber('postId');
Route::post('/pages/{slug}/posts', [PageWebController::class, 'storePost'])->name('pages.posts.store');
Route::put('/pages/{slug}/posts/{postId}', [PageWebController::class, 'updatePost'])->name('pages.posts.update')->whereNumber('postId');
Route::delete('/pages/{slug}/posts/{postId}', [PageWebController::class, 'deletePost'])->name('pages.posts.delete')->whereNumber('postId');
Route::post('/pages/{slug}/posts/{postId}/pin', [PageWebController::class, 'togglePinPost'])->name('pages.posts.pin')->whereNumber('postId');
Route::post('/pages/{slug}/posts/{postId}/visibility', [PageWebController::class, 'updatePostVisibility'])->name('pages.posts.visibility')->whereNumber('postId');
// Page Stories
Route::get('/pages/{slug}/stories', [PageWebController::class, 'stories'])->name('pages.stories');
Route::post('/pages/{slug}/stories', [PageWebController::class, 'storeStory'])->name('pages.stories.store');
Route::post('/pages/{slug}/stories/{storyId}/view', [PageWebController::class, 'recordStoryView'])->name('pages.stories.view')->whereNumber('storyId');
Route::get('/pages/{slug}/stories/{storyId}/viewers', [PageWebController::class, 'storyViewers'])->name('pages.stories.viewers')->whereNumber('storyId');
Route::delete('/pages/{slug}/stories/{storyId}', [PageWebController::class, 'deleteStory'])->name('pages.stories.delete')->whereNumber('storyId');

// Page Reels
Route::get('/pages/{slug}/reels', [PageWebController::class, 'reels'])->name('pages.reels');
Route::post('/pages/{slug}/reels', [PageWebController::class, 'storeReel'])->name('pages.reels.store');
Route::post('/pages/{slug}/reels/{reelId}/view', [PageWebController::class, 'recordReelView'])->name('pages.reels.view')->whereNumber('reelId');
Route::put('/pages/{slug}/reels/{reelId}', [PageWebController::class, 'updateReel'])->name('pages.reels.update')->whereNumber('reelId');
Route::post('/pages/{slug}/reels/{reelId}/publish', [PageWebController::class, 'publishReel'])->name('pages.reels.publish')->whereNumber('reelId');
Route::delete('/pages/{slug}/reels/{reelId}', [PageWebController::class, 'deleteReel'])->name('pages.reels.delete')->whereNumber('reelId');
Route::post('/pages/{slug}/reels/{reelId}/react', [PageWebController::class, 'reactReel'])->name('pages.reels.react')->whereNumber('reelId');
Route::get('/pages/{slug}/reels/{reelId}/comments', [PageWebController::class, 'reelComments'])->name('pages.reels.comments')->whereNumber('reelId');
Route::post('/pages/{slug}/reels/{reelId}/comments', [PageWebController::class, 'storeReelComment'])->name('pages.reels.comments.store')->whereNumber('reelId');
Route::delete('/pages/{slug}/reels/comments/{commentId}', [PageWebController::class, 'deleteReelComment'])->name('pages.reels.comments.delete')->whereNumber('commentId');
Route::post('/pages/{slug}/reels/{reelId}/share', [PageWebController::class, 'shareReel'])->name('pages.reels.share')->whereNumber('reelId');

// Page Videos Tab & Management
Route::get('/pages/{slug}/videos', [PageWebController::class, 'videos'])->name('pages.videos');
Route::post('/pages/{slug}/videos', [PageWebController::class, 'storeVideo'])->name('pages.videos.store');
Route::post('/pages/{slug}/videos/{videoId}/share', [PageWebController::class, 'shareVideo'])->name('pages.videos.share')->whereNumber('videoId');

// Page Live Video Streaming Lifecycle
Route::post('/pages/{slug}/live', [PageWebController::class, 'createLiveStream'])->name('pages.live.create');
Route::post('/pages/{slug}/live/{streamId}/publish', [PageWebController::class, 'publishLiveStream'])->name('pages.live.publish')->whereNumber('streamId');
Route::post('/pages/{slug}/live/{streamId}/heartbeat', [PageWebController::class, 'heartbeatLiveStream'])->name('pages.live.heartbeat')->whereNumber('streamId');
Route::get('/pages/{slug}/live/active', [PageWebController::class, 'activeLiveStream'])->name('pages.live.active');
Route::get('/pages/{slug}/live/{streamId}/viewer-token', [PageWebController::class, 'viewerTokenLiveStream'])->name('pages.live.viewerToken')->whereNumber('streamId');
Route::post('/pages/{slug}/live/{streamId}/join', [PageWebController::class, 'joinLiveStream'])->name('pages.live.join')->whereNumber('streamId');
Route::post('/pages/{slug}/live/{streamId}/leave', [PageWebController::class, 'leaveLiveStream'])->name('pages.live.leave')->whereNumber('streamId');
Route::post('/pages/{slug}/live/{streamId}/end', [PageWebController::class, 'endLiveStream'])->name('pages.live.end')->whereNumber('streamId');
Route::post('/pages/{slug}/live/{streamId}/recording', [PageWebController::class, 'uploadLiveRecording'])->name('pages.live.recording')->whereNumber('streamId');
Route::post('/posts/{id}/react', [PageWebController::class, 'reactPost'])->name('posts.react')->whereNumber('id');
Route::post('/pages/posts/{id}/react', [PageWebController::class, 'reactPost'])->whereNumber('id');
Route::post('/pages/posts/{id}/comments', [PageWebController::class, 'storeComment'])->name('pages.comments.store')->whereNumber('id');
Route::post('/posts/{id}/comments', [PageWebController::class, 'storeComment'])->whereNumber('id');
Route::put('/pages/comments/{id}', [PageWebController::class, 'updateComment'])->name('pages.comments.update')->whereNumber('id');
Route::delete('/pages/comments/{id}', [PageWebController::class, 'deleteComment'])->name('pages.comments.delete')->whereNumber('id');
Route::post('/pages/posts/{id}/share', [PageWebController::class, 'sharePost'])->name('pages.posts.share')->whereNumber('id');
Route::post('/posts/{id}/share', [PageWebController::class, 'sharePost'])->whereNumber('id');
Route::post('/pages/posts/{id}/report', [PageWebController::class, 'reportPost'])->name('pages.posts.report')->whereNumber('id');
Route::post('/posts/{id}/report', [PageWebController::class, 'reportPost'])->whereNumber('id');
Route::match(['put', 'post'], '/pages/{slug}/details', [PageWebController::class, 'updateDetails'])->name('pages.updateDetails');
Route::post('/pages/{slug}/branding', [PageWebController::class, 'uploadBranding'])->name('pages.branding');
Route::match(['put', 'post'], '/pages/{slug}/settings', [PageWebController::class, 'updateSettings'])->name('pages.updateSettings');
Route::get('/pages/{slug}/audit-logs', [PageWebController::class, 'auditLogs'])->name('pages.auditLogs');
Route::get('/pages/{slug}/analytics', [PageWebController::class, 'analytics'])->name('pages.analytics');
Route::post('/pages/{slug}/archive', [PageWebController::class, 'archivePage'])->name('pages.archive');
Route::delete('/pages/{slug}', [PageWebController::class, 'deletePage'])->name('pages.delete');
Route::post('/pages/{id}/restore', [PageWebController::class, 'restorePage'])->name('pages.restore')->whereNumber('id');
Route::post('/pages/{slug}/transfer-ownership', [PageWebController::class, 'transferOwnership'])->name('pages.transferOwnership');
Route::post('/pages/{slug}/team/invite', [PageWebController::class, 'inviteTeamMember'])->name('pages.team.invite');
Route::put('/pages/{slug}/team/{memberId}', [PageWebController::class, 'updateTeamMemberRole'])->name('pages.team.updateRole')->whereNumber('memberId');
Route::delete('/pages/{slug}/team/{memberId}', [PageWebController::class, 'removeTeamMember'])->name('pages.team.remove')->whereNumber('memberId');
Route::delete('/pages/{slug}/team/{memberId}/revoke', [PageWebController::class, 'revokeTeamInvitation'])->name('pages.team.revoke')->whereNumber('memberId');
Route::get('/pages/{slug}/followers', [PageWebController::class, 'followers'])->name('pages.followers');
Route::delete('/pages/{slug}/followers/{userId}', [PageWebController::class, 'removeFollower'])->name('pages.followers.remove')->whereNumber('userId');
Route::delete('/pages/{slug}/branding/{type}', [PageWebController::class, 'removeBranding'])->name('pages.branding.remove');
Route::get('/pages/{slug}/about', [PageWebController::class, 'about'])->name('pages.about');
Route::get('/pages/{slug}/events', [PageWebController::class, 'events'])->name('pages.events');
Route::post('/pages/{slug}/events', [PageWebController::class, 'storeEvent'])->name('pages.events.store');
Route::match(['put', 'patch', 'post'], '/pages/{slug}/events/{eventId}', [PageWebController::class, 'updateEvent'])->name('pages.events.update')->whereNumber('eventId');
Route::delete('/pages/{slug}/events/{eventId}', [PageWebController::class, 'deleteEvent'])->name('pages.events.delete')->whereNumber('eventId');
Route::post('/pages/{slug}/events/{eventId}/rsvp', [PageWebController::class, 'rsvpEvent'])->name('pages.events.rsvp')->whereNumber('eventId');
Route::get('/pages/{slug}/photos', [PageWebController::class, 'photos'])->name('pages.photos');
Route::post('/pages/{slug}/photos', [PageWebController::class, 'uploadPhoto'])->name('pages.photos.store');
Route::delete('/pages/{slug}/photos/{mediaId}', [PageWebController::class, 'deletePhoto'])->name('pages.photos.delete')->whereNumber('mediaId');

// Page Follow Invitations
Route::get('/pages/{slug}/invitations/eligible-friends', [PageWebController::class, 'getEligibleFriendsForInvite'])->name('pages.invitations.eligible');
Route::post('/pages/{slug}/invitations', [PageWebController::class, 'sendInvitation'])->name('pages.invitations.send');
Route::get('/pages/{slug}/invitations', [PageWebController::class, 'getPageInvitations'])->name('pages.invitations.index');
Route::get('/invitations/pages', [PageWebController::class, 'getUserPageInvitations'])->name('user.pages.invitations');
Route::post('/invitations/pages/{id}/accept', [PageWebController::class, 'acceptInvitation'])->name('user.pages.invitations.accept')->whereNumber('id');
Route::post('/invitations/pages/{id}/decline', [PageWebController::class, 'declineInvitation'])->name('user.pages.invitations.decline')->whereNumber('id');

// Page Messaging
Route::get('/pages/{slug}/messages', [PageWebController::class, 'getPageMessages'])->name('pages.messages.index');
Route::post('/pages/{slug}/messages', [PageWebController::class, 'sendPageMessage'])->name('pages.messages.send');
Route::post('/pages/{slug}/messages/read', [PageWebController::class, 'markMessagesRead'])->name('pages.messages.read');

Route::get('/pages/{slug}', function (Request $request, string $slug) use ($resolveWebUser) {
    $resolveWebUser();
    if (! auth('web')->check() && app()->environment('local')) {
        $devUser = User::first();
        if ($devUser) {
            auth('web')->login($devUser);
        }
    }

    if ($slug === '{slug}' || $slug === '%7Bslug%7D' || str_contains($slug, '{') || str_contains($slug, '}')) {
        $targetPage = Page::where('slug', 'socialhub-official')->first()
            ?? Page::where('slug', 'bondhoo-official')->first()
            ?? Page::where('status', '!=', Page::STATUS_TRASH)->first();

        if ($targetPage) {
            return redirect()->route('pages.show', ['slug' => $targetPage->slug]);
        }
    }

    if ($request->query('blade')) {
        return app(PageWebController::class)->show($request, $slug);
    }

    return app(SocialWebController::class)->pageProfile($request, $slug);
})->name('pages.show');
Route::post('/pages/{id}/follow', [PageWebController::class, 'toggleFollow'])->name('pages.follow')->whereNumber('id');
Route::post('/pages/{slug}/follow', [PageWebController::class, 'followPage'])->name('pages.follow.slug');
Route::post('/pages/{slug}/unfollow', [PageWebController::class, 'unfollowPage'])->name('pages.unfollow.slug');

Route::get('/marketplace', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser();
    if (! auth('web')->check() && app()->environment('local')) {
        $devUser = User::first();
        if ($devUser) {
            auth('web')->login($devUser);
        }
    }

    if ($request->query('blade')) {
        return app(MarketplaceWebController::class)->index($request);
    }

    return app(SocialWebController::class)->marketplace($request);
})->name('marketplace.index');

Route::get('/marketplace/create', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser();
    if (! auth('web')->check() && app()->environment('local')) {
        $devUser = User::first();
        if ($devUser) {
            auth('web')->login($devUser);
        }
    }

    return app(SocialWebController::class)->marketplaceCreate($request);
})->name('marketplace.create');

Route::get('/marketplace/orders', [MarketplaceWebController::class, 'orders'])->name('marketplace.orders');
Route::get('/marketplace/orders/{id}', [MarketplaceWebController::class, 'orderDetails'])->name('marketplace.orders.show')->whereNumber('id');
Route::post('/marketplace/orders/{id}/release-escrow', [MarketplaceWebController::class, 'releaseEscrowWeb'])->name('marketplace.orders.release-escrow')->whereNumber('id');
Route::post('/marketplace/orders/{id}/status', [MarketplaceWebController::class, 'updateOrderStatusWeb'])->name('marketplace.orders.status')->whereNumber('id');
Route::post('/marketplace/orders/{id}/ship', [MarketplaceWebController::class, 'shipOrderWeb'])->name('marketplace.orders.ship')->whereNumber('id');
Route::post('/marketplace/orders/{id}/confirm-delivery', [MarketplaceWebController::class, 'confirmDeliveryWeb'])->name('marketplace.orders.confirm-delivery')->whereNumber('id');
Route::post('/marketplace/orders/{id}/cancel', [MarketplaceWebController::class, 'cancelOrderWeb'])->name('marketplace.orders.cancel')->whereNumber('id');
Route::post('/marketplace/orders/{id}/dispute', [MarketplaceWebController::class, 'disputeOrderWeb'])->name('marketplace.orders.dispute')->whereNumber('id');
Route::post('/marketplace/orders/{id}/review', [MarketplaceWebController::class, 'reviewOrderWeb'])->name('marketplace.orders.review')->whereNumber('id');

Route::get('/marketplace/my-listings', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser();
    if (! auth('web')->check() && app()->environment('local')) {
        $devUser = User::first();
        if ($devUser) {
            auth('web')->login($devUser);
        }
    }

    if ($request->query('blade')) {
        return app(MarketplaceWebController::class)->myListings($request);
    }

    return app(SocialWebController::class)->marketplaceMyListings($request);
})->name('marketplace.my-listings');

Route::post('/marketplace/products', [MarketplaceWebController::class, 'storeListingWeb'])->name('marketplace.products.store');
Route::post('/marketplace/products/{id}/status', [MarketplaceWebController::class, 'updateListingStatusWeb'])->name('marketplace.products.status')->whereNumber('id');
Route::delete('/marketplace/products/{id}', [MarketplaceWebController::class, 'destroyListingWeb'])->name('marketplace.products.destroy')->whereNumber('id');

Route::get('/marketplace/{id}', function (Request $request, int $id) use ($resolveWebUser) {
    $resolveWebUser();
    if (! auth('web')->check() && app()->environment('local')) {
        $devUser = User::first();
        if ($devUser) {
            auth('web')->login($devUser);
        }
    }

    if ($request->query('blade')) {
        return app(MarketplaceWebController::class)->show($request, $id);
    }

    return app(SocialWebController::class)->marketplaceShow($request, $id);
})->name('marketplace.show')->whereNumber('id');

Route::get('/marketplace/{id}/checkout', function (Request $request, int $id) use ($resolveWebUser) {
    $resolveWebUser();
    if (! auth('web')->check() && app()->environment('local')) {
        $devUser = User::first();
        if ($devUser) {
            auth('web')->login($devUser);
        }
    }

    return app(SocialWebController::class)->marketplaceCheckout($request, $id);
})->name('marketplace.checkout')->whereNumber('id');

Route::get('/watch', [WatchWebController::class, 'index'])->name('watch.index');
Route::get('/live/create', [WatchWebController::class, 'create'])->name('live.create');
Route::get('/live/studio', [WatchWebController::class, 'studio'])->name('live.studio');
Route::get('/live/{id}', [WatchWebController::class, 'show'])->name('live.show')->whereNumber('id');

// Admin Live Management (Strict RBAC Protected)
Route::middleware(['admin.web'])->group(function () {
    Route::get('/admin/live', [WatchWebController::class, 'adminLive'])->name('admin.live.index');
    Route::post('/admin/live/{id}/terminate', [WatchWebController::class, 'adminTerminate'])->name('admin.live.terminate')->whereNumber('id');
});

Route::get('/friends', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser();
    if ($request->query('blade')) {
        return app(FriendsWebController::class)->index($request);
    }

    return app(SocialWebController::class)->friends($request);
})->name('friends.index');

Route::get('/notifications', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser();
    if ($request->query('blade')) {
        return app(NotificationWebController::class)->index($request);
    }

    return app(SocialWebController::class)->notifications($request);
})->name('notifications.index');

Route::get('/reels', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser($request);
    if (! auth('web')->check() && ! $request->cookie('bondhoo_token') && ! $request->cookie('jugajug_token')) {
        return redirect()->route('login');
    }

    return app(SocialWebController::class)->reels($request);
})->name('reels.index');

Route::get('/reels/{id}', function (Request $request, int|string $id) use ($resolveWebUser) {
    $resolveWebUser($request);
    if (! auth('web')->check() && ! $request->cookie('bondhoo_token') && ! $request->cookie('jugajug_token')) {
        return redirect()->route('login');
    }

    return app(SocialWebController::class)->reels($request, (int) $id);
})->name('reels.show')->whereNumber('id');

Route::get('/posts/{id}', function (Request $request, int|string $id) use ($resolveWebUser) {
    $resolveWebUser();
    $post = Post::with(['user.profile', 'media'])->find($id);
    if (! $post) {
        return redirect()->route('dashboard')->with('error', 'পোস্টটি পাওয়া যায়নি বা মুছে ফেলা হয়েছে।');
    }

    if ($request->query('blade')) {
        return view('dashboard', [
            'highlightPostId' => (int) $id,
        ]);
    }

    return app(SocialWebController::class)->feed($request, (int) $id);
})->name('posts.show')->whereNumber('id');

Route::get('/search', function (Request $request) use ($resolveWebUser) {
    $resolveWebUser();
    if ($request->query('blade')) {
        $query = (string) ($request->query('q') ?? $request->query('query') ?? $request->query('search') ?? '');

        return view('dashboard', [
            'initialSearchQuery' => $query,
        ]);
    }

    return app(SocialWebController::class)->feed($request);
})->name('search.web');

// Responsive Vue 3 / Inertia Social Platform Routes (Task 4)
Route::middleware(['web'])->group(function () use ($resolveWebUser) {
    Route::get('/app', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();
        if (! auth('web')->check() && app()->environment('local')) {
            $devUser = User::first();
            if ($devUser) {
                auth('web')->login($devUser);
            }
        }

        return app(SocialWebController::class)->feed($request);
    })->name('app.feed');

    Route::get('/app/profile/{username}', function (Request $request, string $username) use ($resolveWebUser) {
        $resolveWebUser();
        if (! auth('web')->check() && app()->environment('local')) {
            $devUser = User::first();
            if ($devUser) {
                auth('web')->login($devUser);
            }
        }

        return app(SocialWebController::class)->profile($request, $username);
    })->name('app.profile');

    Route::get('/app/friends', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();
        if (! auth('web')->check() && app()->environment('local')) {
            $devUser = User::first();
            if ($devUser) {
                auth('web')->login($devUser);
            }
        }

        return app(SocialWebController::class)->friends($request);
    })->name('app.friends');

    Route::get('/app/notifications', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();
        if (! auth('web')->check() && app()->environment('local')) {
            $devUser = User::first();
            if ($devUser) {
                auth('web')->login($devUser);
            }
        }

        return app(SocialWebController::class)->notifications($request);
    })->name('app.notifications');

    Route::get('/app/pages/create', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();
        if (! auth('web')->check() && app()->environment('local')) {
            $devUser = User::first();
            if ($devUser) {
                auth('web')->login($devUser);
            }
        }

        return app(SocialWebController::class)->createPage($request);
    })->name('app.pages.create');

    Route::get('/app/pages', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();
        if (! auth('web')->check() && app()->environment('local')) {
            $devUser = User::first();
            if ($devUser) {
                auth('web')->login($devUser);
            }
        }

        return app(SocialWebController::class)->pages($request);
    })->name('app.pages');

    Route::get('/app/pages/{slug}', function (Request $request, string $slug) use ($resolveWebUser) {
        $resolveWebUser();
        if (! auth('web')->check() && app()->environment('local')) {
            $devUser = User::first();
            if ($devUser) {
                auth('web')->login($devUser);
            }
        }

        if ($slug === '{slug}' || $slug === '%7Bslug%7D' || str_contains($slug, '{') || str_contains($slug, '}')) {
            $targetPage = Page::where('slug', 'socialhub-official')->first()
                ?? Page::where('slug', 'bondhoo-official')->first()
                ?? Page::where('status', '!=', Page::STATUS_TRASH)->first();

            if ($targetPage) {
                return redirect()->route('app.pages.show', ['slug' => $targetPage->slug]);
            }
        }

        return app(SocialWebController::class)->pageProfile($request, $slug);
    })->name('app.pages.show');

    Route::get('/app/groups/create', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();
        if (! auth('web')->check() && app()->environment('local')) {
            $devUser = User::first();
            if ($devUser) {
                auth('web')->login($devUser);
            }
        }

        return app(SocialWebController::class)->createGroup($request);
    })->name('app.groups.create');

    Route::get('/app/groups', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();
        if (! auth('web')->check() && app()->environment('local')) {
            $devUser = User::first();
            if ($devUser) {
                auth('web')->login($devUser);
            }
        }

        return app(SocialWebController::class)->groups($request);
    })->name('app.groups');

    Route::get('/app/groups/{slug}', function (Request $request, string $slug) use ($resolveWebUser) {
        $resolveWebUser();
        if (! auth('web')->check() && app()->environment('local')) {
            $devUser = User::first();
            if ($devUser) {
                auth('web')->login($devUser);
            }
        }

        return app(SocialWebController::class)->groupProfile($request, $slug);
    })->name('app.groups.show');

    Route::get('/app/messages', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();
        if (! auth('web')->check() && app()->environment('local')) {
            $devUser = User::first();
            if ($devUser) {
                auth('web')->login($devUser);
            }
        }

        return app(SocialWebController::class)->messages($request);
    })->name('app.messages');

    Route::get('/messenger', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();
        if (! auth('web')->check() && app()->environment('local')) {
            $devUser = User::first();
            if ($devUser) {
                auth('web')->login($devUser);
            }
        }

        return app(SocialWebController::class)->messages($request);
    })->name('messenger');

    Route::get('/app/marketplace', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();

        return app(SocialWebController::class)->marketplace($request);
    })->name('app.marketplace');

    Route::get('/app/marketplace/create', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();

        return app(SocialWebController::class)->marketplaceCreate($request);
    })->name('app.marketplace.create');

    Route::get('/app/marketplace/my-listings', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();

        return app(SocialWebController::class)->marketplaceMyListings($request);
    })->name('app.marketplace.my-listings');

    Route::get('/saved', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();
        $user = auth('web')->user() ?? ($request->user() ?? (app()->environment('local') ? User::first() : null));
        if (! $user) {
            return redirect()->route('login');
        }

        return redirect('/dashboard?tab=saved');
    })->name('saved.index');

    Route::get('/memories', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();
        $user = auth('web')->user() ?? ($request->user() ?? (app()->environment('local') ? User::first() : null));
        if (! $user) {
            return redirect()->route('login');
        }

        return redirect('/dashboard?tab=memories');
    })->name('memories.index');

    Route::get('/events', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();
        $user = auth('web')->user() ?? ($request->user() ?? (app()->environment('local') ? User::first() : null));
        if (! $user) {
            return redirect()->route('login');
        }

        return redirect('/dashboard?tab=events');
    })->name('events.index');

    Route::get('/jobs', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();
        $user = auth('web')->user() ?? ($request->user() ?? (app()->environment('local') ? User::first() : null));
        if (! $user) {
            return redirect()->route('login');
        }

        return redirect('/dashboard?tab=jobs');
    })->name('jobs.index');

    Route::get('/settings/profile', function (Request $request) use ($resolveWebUser) {
        $resolveWebUser();
        $user = auth('web')->user() ?? ($request->user() ?? (app()->environment('local') ? User::first() : null));
        if (! $user) {
            return redirect()->route('login');
        }

        return redirect('/app/profile/'.$user->username);
    })->name('settings.profile');

    // Unified Settings & Privacy Center
    Route::get('/settings', [SettingsWebController::class, 'index'])->name('settings.index');
    Route::get('/settings/account', [SettingsWebController::class, 'account'])->name('settings.account');
    Route::get('/settings/security', [SettingsWebController::class, 'security'])->name('settings.security');
    Route::get('/settings/privacy', [SettingsWebController::class, 'privacy'])->name('settings.privacy');
    Route::get('/settings/blocking', [SettingsWebController::class, 'blocking'])->name('settings.blocking');
    Route::get('/settings/sounds', [SettingsWebController::class, 'sounds'])->name('settings.sounds');

    Route::get('/terms', [SettingsWebController::class, 'terms'])->name('terms');
    Route::get('/cookies', [SettingsWebController::class, 'cookies'])->name('cookies');
});
