<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use App\Services\DeviceSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class LoginController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $deviceName = $credentials['device_name'] ?? $request->header('User-Agent', 'api-client');

        $result = $this->authService->login(
            identifier: $credentials['identifier'],
            password: $credentials['password'],
            deviceName: $deviceName,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        if (! empty($result['requires_2fa'])) {
            return $this->successResponse(
                data: [
                    'requires_2fa' => true,
                    'challenge_token' => $result['challenge_token'],
                ],
                message: 'Two-factor authentication code required.'
            );
        }

        if ($request->hasSession()) {
            auth('web')->login($result['user']);
        }

        return $this->successResponse(
            data: [
                'user' => $result['user'],
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
            message: 'Login successful.'
        )->cookie('jugajug_token', $result['token'], 60 * 24 * 7, '/', null, false, false);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['profile', 'settings', 'roles.permissions']);

        return $this->successResponse(
            data: [
                'user' => $user,
                'permissions' => $user->roles->flatMap->permissions->pluck('name')->unique()->values(),
            ],
            message: 'Authenticated user profile.'
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $bearer = $request->bearerToken();
        if ($bearer) {
            PersonalAccessToken::findToken($bearer)?->delete();
        }

        foreach (['bondhoo_token', 'jugajug_token'] as $cookieName) {
            if ($request->hasCookie($cookieName)) {
                PersonalAccessToken::findToken((string) $request->cookie($cookieName))?->delete();
            }
        }

        $user = $request->user() ?? Auth::guard('web')->user();
        if ($user) {
            if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

            /** @var DeviceSessionService $deviceService */
            $deviceService = app(DeviceSessionService::class);
            $deviceService->logoutCurrentDevice($user, $request);
        }

        Auth::guard('web')->logout();
        Auth::guard('admin')->logout();
        try {
            Auth::guard('sanctum')->forgetUser();
        } catch (\Throwable) {
        }
        Auth::forgetGuards();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $res = $this->successResponse(
            data: null,
            message: 'Successfully logged out from current device.'
        );

        foreach (DeviceSessionService::getLogoutCookies() as $cookie) {
            $res->withCookie($cookie);
        }
        $res->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $res->headers->set('Pragma', 'no-cache');

        return $res;
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user() ?? Auth::guard('web')->user();
        if ($user) {
            $this->authService->logoutAllDevices($user);
            /** @var DeviceSessionService $deviceService */
            $deviceService = app(DeviceSessionService::class);
            $deviceService->logoutAllDevices($user, $request);
        }

        Auth::guard('web')->logout();
        Auth::guard('admin')->logout();
        try {
            Auth::guard('sanctum')->forgetUser();
        } catch (\Throwable) {
        }
        Auth::forgetGuards();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $res = $this->successResponse(
            data: null,
            message: 'Successfully logged out from all devices.'
        );

        foreach (DeviceSessionService::getLogoutCookies() as $cookie) {
            $res->withCookie($cookie);
        }
        $res->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $res->headers->set('Pragma', 'no-cache');

        return $res;
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        $bearer = $request->bearerToken();
        $currentToken = null;
        if ($bearer) {
            $currentToken = PersonalAccessToken::findToken($bearer);
        }

        $deviceName = $currentToken ? $currentToken->name : ($request->header('User-Agent') ?: 'api-client');
        $abilities = $currentToken ? $currentToken->abilities : ['*'];

        if ($currentToken) {
            $currentToken->delete();
        }

        if ($user && method_exists($user, 'currentAccessToken') && $user->currentAccessToken()?->exists) {
            $user->currentAccessToken()->delete();
        }

        $newToken = $user->createToken($deviceName, $abilities)->plainTextToken;

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return $this->successResponse(
            data: [
                'token' => $newToken,
                'token_type' => 'Bearer',
                'csrf_token' => $request->hasSession() ? csrf_token() : null,
            ],
            message: 'সেশন সফলভাবে রিফ্রেশ করা হয়েছে।'
        );
    }
}
