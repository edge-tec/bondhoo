<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Laravel\Sanctum\PersonalAccessToken;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user('web') ?? $request->user('sanctum');

        if (! $user && ($request->hasCookie('jugajug_token') || $request->hasCookie('bondhoo_token'))) {
            $rawToken = (string) ($request->cookie('jugajug_token') ?? $request->cookie('bondhoo_token'));
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
                } catch (\Throwable) {
                    // Ignore decryption failure
                }
            }
            if ($pat && $pat->tokenable instanceof User) {
                $user = $pat->tokenable;
            }
        }

        if (! $user && app()->environment('local')) {
            $user = User::first();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar_url' => $user->profile?->avatar_url ?: ($user->avatar_url ?? null),
                    'bio' => $user->profile?->bio,
                    'is_verified' => (bool) $user->is_verified,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()?->get('success'),
                'error' => fn () => $request->session()?->get('error'),
            ],
        ];
    }
}
