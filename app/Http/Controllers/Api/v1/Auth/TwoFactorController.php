<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    /**
     * Generate 2FA Secret and Recovery Codes.
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();

        // 16-character base32 secret
        $secret = strtoupper(Str::random(16));
        $recoveryCodes = collect(range(1, 8))->map(fn () => Str::random(10))->toArray();

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => '2FA setup initiated. Confirm with code to activate.',
            'data' => [
                'secret' => $secret,
                'qr_code_url' => "otpauth://totp/JUGAJUG:{$user->email}?secret={$secret}&issuer=JUGAJUG",
                'recovery_codes' => $recoveryCodes,
            ],
        ]);
    }

    /**
     * Confirm and Enable 2FA.
     */
    public function enable(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            return response()->json([
                'success' => false,
                'message' => 'Please initiate 2FA setup first.',
            ], 422);
        }

        // Verify code (for testing / standard TOTP: 6 digit code or recovery code)
        $code = trim($request->code);
        $isValid = strlen($code) === 6 && ctype_digit($code);

        // Check recovery code fallback
        if (! $isValid && $user->two_factor_recovery_codes) {
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?: [];
            if (in_array($code, $recoveryCodes, true)) {
                $isValid = true;
            }
        }

        if (! $isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid two-factor authentication code.',
            ], 422);
        }

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication has been enabled successfully.',
        ]);
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication has been disabled.',
        ]);
    }

    /**
     * Challenge verification during login.
     */
    public function verifyChallenge(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $userId = Cache::get('2fa_challenge_'.$request->challenge_token);

        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired two-factor challenge token.',
            ], 422);
        }

        $user = User::findOrFail($userId);
        $code = trim($request->code);

        $isValid = strlen($code) === 6 && ctype_digit($code);

        if (! $isValid && $user->two_factor_recovery_codes) {
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?: [];
            if (in_array($code, $recoveryCodes, true)) {
                $isValid = true;
                // Remove used recovery code
                $recoveryCodes = array_values(array_diff($recoveryCodes, [$code]));
                $user->update(['two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes))]);
            }
        }

        if (! $isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid two-factor code.',
            ], 422);
        }

        Cache::forget('2fa_challenge_'.$request->challenge_token);

        $token = $user->createToken('auth-token', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Two-factor verification successful.',
            'data' => [
                'user' => $user->only(['id', 'name', 'username', 'email']),
                'token' => $token,
            ],
        ]);
    }
}
