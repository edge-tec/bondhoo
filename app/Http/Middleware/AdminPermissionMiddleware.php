<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = $request->user('admin') ?? $request->user('admin-api');

        if (! $admin) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'অননুমোদিত অ্যাক্সেস। অনুগ্রহ করে অ্যাডমিন হিসেবে লগইন করুন।',
                ], 401);
            }

            return redirect()->route('login');
        }

        if (! $admin->hasPermission($permission)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'এই অ্যাকশনটি সম্পাদন করার পর্যাপ্ত অনুমতি আপনার নেই।',
                ], 403);
            }

            abort(403, 'এই অ্যাকশনটি সম্পাদন করার পর্যাপ্ত অনুমতি আপনার নেই।');
        }

        return $next($request);
    }
}
