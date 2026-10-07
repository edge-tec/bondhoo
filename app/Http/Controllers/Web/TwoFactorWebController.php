<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorWebController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactorService
    ) {}

    /**
     * Show the 2FA settings and configuration page.
     */
    public function show(Request $request): View|JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('web')->user() ?? $request->user();

        if (! $user) {
            abort(401, 'লগইন আবশ্যক।');
        }

        $twoFactor = $user->twoFactor;
        $recoveryCodesCount = $user->recoveryCodes()->whereNull('used_at')->count();
        $isEnabled = (bool) ($user->two_factor_enabled && ($twoFactor?->is_enabled ?? true));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'enabled' => $isEnabled,
                    'type' => $twoFactor?->type ?? 'totp',
                    'confirmed_at' => $twoFactor?->confirmed_at,
                    'recovery_codes_remaining' => $recoveryCodesCount,
                ],
            ]);
        }

        return view('settings.two-factor', [
            'user' => $user,
            'twoFactor' => $twoFactor,
            'isEnabled' => $isEnabled,
            'recoveryCodesCount' => $recoveryCodesCount,
        ]);
    }

    /**
     * Initiate 2FA setup: generate secret, QR Code URI, and backup recovery codes.
     */
    public function setup(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::guard('web')->user() ?? $request->user();

        if (! $user) {
            abort(401, 'লগইন আবশ্যক।');
        }

        $setupData = $this->twoFactorService->setupTotp($user);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $setupData,
                'message' => 'টু-ফ্যাক্টর সেটআপ তথ্য তৈরি হয়েছে।',
            ]);
        }

        return back()->with('setupData', $setupData);
    }

    /**
     * Confirm code and activate 2FA.
     */
    public function enable(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = Auth::guard('web')->user() ?? $request->user();

        if (! $user) {
            abort(401, 'লগইন আবশ্যক।');
        }

        $this->twoFactorService->enable($user, $request->input('code'), 'app');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'টু-ফ্যাক্টর অথেনটিকেশন সফলভাবে চালু করা হয়েছে।',
            ]);
        }

        return redirect()->route('settings.two-factor')->with('success', 'টু-ফ্যাক্টর অথেনটিকেশন সফলভাবে চালু করা হয়েছে।');
    }

    /**
     * Require password and disable 2FA.
     */
    public function disable(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = Auth::guard('web')->user() ?? $request->user();

        if (! $user) {
            abort(401, 'লগইন আবশ্যক।');
        }

        $this->twoFactorService->disable($user, $request->input('password'));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'টু-ফ্যাক্টর অথেনটিকেশন নিষ্ক্রিয় করা হয়েছে।',
            ]);
        }

        return redirect()->route('settings.two-factor')->with('success', 'টু-ফ্যাক্টর অথেনটিকেশন নিষ্ক্রিয় করা হয়েছে।');
    }
}
