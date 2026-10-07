<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserSessionTimeoutMiddleware
{
    /**
     * Handle an incoming request and enforce idle session expiration.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user() ?? $request->user();

        if ($user && $request->hasSession()) {
            // Idle timeout in minutes, default 120 minutes (can be overridden in config or tests)
            $idleTimeoutMinutes = (int) config('session.idle_timeout', 120);
            $idleTimeoutSeconds = $idleTimeoutMinutes * 60;

            $lastActivity = $request->session()->get('last_activity_time');

            if ($lastActivity && (time() - $lastActivity > $idleTimeoutSeconds)) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'নিষ্ক্রিয়তার কারণে সেশনের মেয়াদ শেষ হয়েছে। অনুগ্রহ করে আবার লগইন করুন।',
                        'code' => 'SESSION_EXPIRED',
                    ], 401);
                }

                return redirect()->route('login')->withErrors([
                    'session' => 'নিষ্ক্রিয়তার কারণে সেশনের মেয়াদ শেষ হয়েছে। অনুগ্রহ করে আবার লগইন করুন।',
                ]);
            }

            // Update session activity timestamp
            $request->session()->put('last_activity_time', time());
        }

        return $next($request);
    }
}
