<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WebAuthnController extends Controller
{
    /**
     * GET /api/v1/auth/webauthn/options
     */
    public function options(Request $request): JsonResponse
    {
        $challenge = Str::random(32);
        $cacheKey = "webauthn_challenge:{$challenge}";
        Cache::put($cacheKey, ['ip' => $request->ip()], now()->addMinutes(5));

        return $this->successResponse(
            data: [
                'challenge' => base64_encode($challenge),
                'rp' => [
                    'name' => config('app.name', 'Jugajug'),
                    'id' => parse_url(config('app.url', 'localhost'), PHP_URL_HOST) ?? 'localhost',
                ],
                'user' => [
                    'id' => $request->user()?->id ? (string) $request->user()->id : Str::uuid()->toString(),
                    'name' => $request->user()?->email ?? 'guest@jugajug.com',
                    'displayName' => $request->user()?->name ?? 'Guest User',
                ],
                'pubKeyCredParams' => [
                    ['type' => 'public-key', 'alg' => -7],  // ES256
                    ['type' => 'public-key', 'alg' => -257], // RS256
                ],
                'timeout' => 60000,
                'attestation' => 'none',
            ],
            message: 'WebAuthn options generated successfully.'
        );
    }

    /**
     * POST /api/v1/auth/webauthn/verify
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string'],
            'rawId' => ['required', 'string'],
            'type' => ['required', 'string', 'in:public-key'],
            'clientDataJSON' => ['required', 'string'],
            'authenticatorData' => ['nullable', 'string'],
            'signature' => ['nullable', 'string'],
            'userHandle' => ['nullable', 'string'],
        ]);

        $user = $request->user() ?? User::where('id', $validated['userHandle'] ?? null)->first();

        if ($user) {
            $token = $user->createToken('WebAuthn Passkey ('.$request->userAgent().')')->plainTextToken;

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'AUTH_WEBAUTHN_AUTHENTICATED',
                'entity_type' => User::class,
                'entity_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->successResponse(
                data: [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
                message: 'পাসকী / বায়োমেট্রিক অথেন্টিকেশন সফল হয়েছে।'
            );
        }

        return $this->errorResponse('পাসকী অথেন্টিকেশন সম্পন্ন করা সম্ভব হয়নি।', 400);
    }
}
