<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() || $request->is('api/*') ? null : route('login');
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param  Request  $request
     * @return void
     *
     * @throws AuthenticationException
     */
    protected function unauthenticated($request, array $guards)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            abort(response()->json([
                'success' => false,
                'message' => 'অননুমোদিত অ্যাক্সেস। অনুগ্রহ করে লগইন করুন।',
            ], 401));
        }

        throw new AuthenticationException(
            'Unauthenticated.',
            $guards,
            $this->redirectTo($request)
        );
    }
}
