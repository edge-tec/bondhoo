<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DeviceSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DeviceWebController extends Controller
{
    public function __construct(
        protected DeviceSessionService $deviceService
    ) {}

    /**
     * Show the "My Devices" dashboard with active sessions and trusted devices.
     */
    public function index(Request $request): View|JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('web')->user() ?? $request->user();

        if (! $user) {
            abort(401, 'লগইন আবশ্যক।');
        }

        $meta = $this->deviceService->parseRequest($request);

        // Fetch user sessions
        $sessions = $user->sessions()->latest('last_active_at')->get();

        // If no sessions recorded yet, auto-register the current active session
        if ($sessions->isEmpty()) {
            $this->deviceService->registerSession($user, $request);
            $sessions = $user->sessions()->latest('last_active_at')->get();
        }

        // Identify current session
        $currentSession = $sessions->firstWhere('is_current', true)
            ?? $sessions->firstWhere('device_fingerprint', $meta['fingerprint'])
            ?? $sessions->first();

        // Fetch trusted devices
        $trustedDevices = $user->trustedDevices()->latest('last_used_at')->get();

        // Fetch login history
        $loginHistories = $this->deviceService->getLoginHistory($user, 20);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'current_session' => $currentSession,
                    'sessions' => $sessions,
                    'trusted_devices' => $trustedDevices,
                    'login_histories' => $loginHistories,
                ],
                'message' => 'আমার ডিভাইসসমূহ সফলভাবে লোড হয়েছে।',
            ]);
        }

        return view('settings.devices', [
            'user' => $user,
            'currentSession' => $currentSession,
            'sessions' => $sessions,
            'trustedDevices' => $trustedDevices,
            'loginHistories' => $loginHistories,
            'currentMeta' => $meta,
        ]);
    }

    /**
     * Revoke a single active device session.
     */
    public function revoke(Request $request, int|string $id): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('web')->user() ?? $request->user();

        if (! $user) {
            abort(401, 'লগইন আবশ্যক।');
        }

        $revoked = $this->deviceService->revokeSession($user, (int) $id);

        if (! $revoked) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ডিভাইস সেশন পাওয়া যায়নি।',
                ], 404);
            }

            return back()->withErrors(['error' => 'ডিভাইস সেশন পাওয়া যায়নি।']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ডিভাইস সেশন সফলভাবে লগআউট করা হয়েছে।',
            ]);
        }

        return back()->with('success', 'ডিভাইস সেশন সফলভাবে লগআউট করা হয়েছে।');
    }

    /**
     * Logout from all devices (current and others).
     */
    public function logoutAll(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('web')->user() ?? $request->user();

        if (! $user) {
            abort(401, 'লগইন আবশ্যক।');
        }

        $this->deviceService->logoutAllDevices($user, $request);

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            $res = response()->json([
                'success' => true,
                'message' => 'সবগুলো ডিভাইস থেকে সফলভাবে লগআউট সম্পন্ন হয়েছে।',
            ]);
            foreach (DeviceSessionService::getLogoutCookies() as $cookie) {
                $res->withCookie($cookie);
            }
            $res->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
            $res->headers->set('Pragma', 'no-cache');

            return $res;
        }

        $res = redirect()->route('login')->with('success', 'সবগুলো ডিভাইস থেকে সফলভাবে লগআউট সম্পন্ন হয়েছে।');
        foreach (DeviceSessionService::getLogoutCookies() as $cookie) {
            $res->withCookie($cookie);
        }
        $res->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $res->headers->set('Pragma', 'no-cache');

        return $res;
    }

    /**
     * Revoke a single trusted device.
     */
    public function revokeTrustedDevice(Request $request, int|string $id): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('web')->user() ?? $request->user();

        if (! $user) {
            abort(401, 'লগইন আবশ্যক।');
        }

        $revoked = $this->deviceService->revokeTrustedDevice($user, (int) $id);

        if (! $revoked) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'বিশ্বস্ত ডিভাইসটি পাওয়া যায়নি।',
                ], 404);
            }

            return back()->withErrors(['error' => 'বিশ্বস্ত ডিভাইসটি পাওয়া যায়নি।']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'বিশ্বস্ত ডিভাইস সফলভাবে মুছে ফেলা হয়েছে।',
            ]);
        }

        return back()->with('success', 'বিশ্বস্ত ডিভাইস সফলভাবে মুছে ফেলা হয়েছে।');
    }
}
