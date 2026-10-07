<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ActiveUserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== 'active') {
            // Revoke current token if using Sanctum
            if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

            // Invalidate web session if active
            if (Auth::guard('web')->check()) {
                Auth::guard('web')->logout();
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'আপনার অ্যাকাউন্টটি স্থগিত বা নিষ্ক্রিয় করা হয়েছে। অনুগ্রহ করে সাপোর্ট টিমের সাথে যোগাযোগ করুন।',
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'account' => 'আপনার অ্যাকাউন্টটি স্থগিত বা নিষ্ক্রিয় করা হয়েছে।',
            ]);
        }

        return $next($request);
    }
}
