<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifiedUserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! ($user->email_verified_at || $user->phone_verified_at)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'আপনার অ্যাকাউন্টটি এখনো যাচাই (ভেরিফাই) করা হয়নি। অনুগ্রহ করে ইমেইল বা ফোন ওটিপি যাচাই করুন।',
                    'needs_verification' => true,
                ], 403);
            }

            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
