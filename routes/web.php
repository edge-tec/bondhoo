<?php

use App\Http\Controllers\Admin\EmailManagementController;
use App\Http\Controllers\Api\v1\Admin\AdminAuthController;
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
use App\Http\Controllers\Web\TwoFactorWebController;
use App\Http\Controllers\Web\WatchWebController;
use App\Http\Requests\Auth\RegisterV2Request;
use App\Models\User;
use App\Services\AuthServiceV2;
use App\Services\Email\SmtpConfigService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth('web')->check() || request()->cookie('bondhoo_token') || request()->cookie('jugajug_token')) {
        return view('dashboard');
    }

    return view('auth.login');
})->name('dashboard');

Route::get('/dashboard', function () {
    if (! auth('web')->check() && ! request()->cookie('bondhoo_token') && ! request()->cookie('jugajug_token')) {
        return redirect()->route('login');
    }

    return view('dashboard');
})->name('dashboard.view');

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

    if ($request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'নিবন্ধন সফল হয়েছে। আপনার ইমেইল বা মোবাইল যাচাই করতে ওটিপি কোড চেক করুন।',
            'data' => $result,
        ], 201);
    }

    return redirect()->route('verify.email')->with('success', 'নিবন্ধন সফল হয়েছে।');
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

Route::get('/reset-password', function () {
    return view('auth.reset-password');
})->name('password.reset');

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

        return view('admin.auth.dashboard', compact('smtpSettings'));
    })->name('admin.auth.dashboard');

    Route::get('/admin/system/dashboard', function () {
        return view('dashboard');
    });

    // Enterprise SMTP Email Management
    Route::prefix('admin/smtp')->group(function () {
        Route::get('/settings', [EmailManagementController::class, 'getSettings'])->name('admin.smtp.settings');
        Route::post('/settings', [EmailManagementController::class, 'updateSettings'])->name('admin.smtp.update');
        Route::post('/test', [EmailManagementController::class, 'testConnection'])->name('admin.smtp.test');
        Route::get('/logs', [EmailManagementController::class, 'getLogs'])->name('admin.smtp.logs');
        Route::post('/logs/{id}/retry', [EmailManagementController::class, 'retryLog'])->name('admin.smtp.retry')->whereNumber('id');
        Route::get('/stats', [EmailManagementController::class, 'getStats'])->name('admin.smtp.stats');
        Route::get('/templates', [EmailManagementController::class, 'getTemplates'])->name('admin.smtp.templates');
        Route::get('/templates/{key}/preview', [EmailManagementController::class, 'previewTemplate'])->name('admin.smtp.preview');
    });
});

// RFC 7033 WebFinger discovery for ActivityPub Federation
Route::get('/.well-known/webfinger', [FederationV2Controller::class, 'webfinger']);

// User Profile & Personal Timeline (Facebook-style UX & Enterprise Canonical Identity)
Route::get('/u/{username}', [ProfileWebController::class, 'show'])->name('profile.u')->where('username', '[A-Za-z0-9_.-]+');
Route::get('/profile/@{username}', [ProfileWebController::class, 'show'])->name('profile.at')->where('username', '[A-Za-z0-9_.-]+');
Route::get('/user/{username}', [ProfileWebController::class, 'show'])->name('profile.show');
Route::get('/profile/{username}', [ProfileWebController::class, 'show'])->name('profile.alias');
Route::get('/@{username}', [ProfileWebController::class, 'show'])->name('profile.handle')->where('username', '[A-Za-z0-9_.-]+');
Route::get('/profile', [ProfileWebController::class, 'me'])->name('profile.me');

// Dedicated Frontend Pages (Step 1 Modularization)
Route::get('/messages', [MessengerWebController::class, 'index'])->name('messages.index');
Route::get('/messages/{id}', [MessengerWebController::class, 'show'])->name('messages.show')->whereNumber('id');
Route::get('/messages/{id}/voice', [VoiceMessageController::class, 'stream'])->name('messages.voice.stream')->whereNumber('id');
Route::get('/call/{id}', [CallWebController::class, 'show'])->name('call.show')->whereNumber('id');

Route::get('/groups', [GroupWebController::class, 'index'])->name('groups.index');
Route::get('/groups/{slug}', [GroupWebController::class, 'show'])->name('groups.show');

Route::get('/pages', [PageWebController::class, 'index'])->name('pages.index');
Route::get('/pages/create', [PageWebController::class, 'create'])->name('pages.create');
Route::get('/pages/{slug}/manage', [PageWebController::class, 'manage'])->name('pages.manage');
Route::get('/pages/{slug}', [PageWebController::class, 'show'])->name('pages.show');

Route::get('/marketplace', [MarketplaceWebController::class, 'index'])->name('marketplace.index');
Route::get('/marketplace/{id}', [MarketplaceWebController::class, 'show'])->name('marketplace.show')->whereNumber('id');

Route::get('/watch', [WatchWebController::class, 'index'])->name('watch.index');
Route::get('/live/create', [WatchWebController::class, 'create'])->name('live.create');
Route::get('/live/studio', [WatchWebController::class, 'studio'])->name('live.studio');
Route::get('/live/{id}', [WatchWebController::class, 'show'])->name('live.show')->whereNumber('id');

// Admin Live Management (Strict RBAC Protected)
Route::middleware(['admin.web'])->group(function () {
    Route::get('/admin/live', [WatchWebController::class, 'adminLive'])->name('admin.live.index');
    Route::post('/admin/live/{id}/terminate', [WatchWebController::class, 'adminTerminate'])->name('admin.live.terminate')->whereNumber('id');
});

Route::get('/friends', [FriendsWebController::class, 'index'])->name('friends.index');

Route::get('/notifications', [NotificationWebController::class, 'index'])->name('notifications.index');
