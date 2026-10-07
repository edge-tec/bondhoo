<?php

namespace App\Http\Middleware;

use App\Models\AdminSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminSessionTimeoutMiddleware
{
    /**
     * Maximum idle time in seconds (30 minutes).
     */
    protected int $timeout = 1800;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin') ?? $request->user('admin-api');

        if ($admin) {
            // Find active admin session
            $session = AdminSession::where('admin_id', $admin->id)
                ->latest('last_activity')
                ->first();

            if ($session) {
                if (time() - $session->last_activity > $this->timeout) {
                    $session->delete();

                    if (Auth::guard('admin')->check()) {
                        Auth::guard('admin')->logout();
                    }

                    if (method_exists($admin, 'currentAccessToken') && $admin->currentAccessToken()) {
                        $admin->currentAccessToken()->delete();
                    }

                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json([
                            'success' => false,
                            'message' => 'নিষ্ক্রিয়তার কারণে অ্যাডমিন সেশনের মেয়াদ শেষ হয়ে গেছে। অনুগ্রহ করে পুনরায় লগইন করুন।',
                        ], 401);
                    }

                    return redirect()->route('login')->withErrors([
                        'session' => 'অ্যাডমিন সেশনের মেয়াদ শেষ হয়েছে। অনুগ্রহ করে আবার লগইন করুন।',
                    ]);
                }

                // Update activity timestamp
                $session->update(['last_activity' => time()]);
            }
        }

        return $next($request);
    }
}
