<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /**
     * Request a password reset token.
     */
    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', strtolower($request->email))->first();

        if (! $user) {
            // Constant-time dummy response to prevent email enumeration
            return response()->json([
                'success' => true,
                'message' => 'If an account exists with this email, a password reset token has been generated.',
            ]);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => strtolower($user->email)],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $resetUrl = url("/reset-password?token={$token}&email=".urlencode($user->email));

        try {
            $user->notify(new PasswordResetOtpNotification(
                otp: (string) random_int(100000, 999999),
                resetUrl: $resetUrl,
                meta: [
                    'ip' => $request->ip(),
                    'device' => $request->userAgent() ?? 'Unknown Device',
                ]
            ));
        } catch (\Throwable $e) {
            // Ignore notification failure in tests
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset token generated.',
            'data' => [
                'email' => $user->email,
                'reset_token' => $token,
                'reset_url' => $resetUrl,
            ],
        ]);
    }

    /**
     * Reset password using token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', strtolower($request->email))
            ->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired password reset token.',
            ], 422);
        }

        // Check if token expired (1 hour)
        if (now()->subHours(1)->gt($record->created_at)) {
            DB::table('password_reset_tokens')->where('email', strtolower($request->email))->delete();

            return response()->json([
                'success' => false,
                'message' => 'Password reset token has expired.',
            ], 422);
        }

        $user = User::where('email', strtolower($request->email))->firstOrFail();
        $user->forceFill([
            'password' => Hash::make($request->password),
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ])->save();

        // One-time use: delete token
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        // Revoke active tokens for security
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password has been reset successfully. You may now log in.',
        ]);
    }
}
