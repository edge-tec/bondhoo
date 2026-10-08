<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request and ensure user has at least one of the required roles.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user()
            ?? Auth::guard('admin')->user()
            ?? Auth::guard('sanctum')->user()
            ?? Auth::guard('web')->user();

        if (! $user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'অননুমোদিত অ্যাক্সেস। অনুগ্রহ করে লগইন করুন।',
                ], 401);
            }

            return redirect()->route('login');
        }

        // Support comma-separated strings inside roles array (e.g. "ADMIN,SUPER_ADMIN")
        $expandedRoles = [];
        foreach ($roles as $role) {
            foreach (explode(',', $role) as $r) {
                $trimmed = trim($r);
                if ($trimmed !== '') {
                    $expandedRoles[] = $trimmed;
                }
            }
        }

        if (! $user->hasRole($expandedRoles)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'এই অ্যাকশনটিতে প্রবেশ করার অনুমতি আপনার নেই।',
                ], 403);
            }

            abort(403, 'এই অ্যাকশনটিতে প্রবেশ করার অনুমতি আপনার নেই।');
        }

        return $next($request);
    }
}
