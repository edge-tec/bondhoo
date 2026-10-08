<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request and ensure user has the required permission.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
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

        // Super Admin or Admin bypasses all individual permission checks
        if (
            $user->hasRole('SUPER_ADMIN')
            || $user->hasRole('super_admin')
            || $user->hasRole('ADMIN')
            || $user->hasRole('admin')
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || (method_exists($user, 'isAdmin') && $user->isAdmin())
        ) {
            return $next($request);
        }

        // Support comma-separated or variadic permissions
        $expandedPermissions = [];
        foreach ($permissions as $perm) {
            foreach (explode(',', $perm) as $p) {
                $trimmed = trim($p);
                if ($trimmed !== '') {
                    $expandedPermissions[] = $trimmed;
                }
            }
        }

        $hasAnyPermission = false;
        foreach ($expandedPermissions as $perm) {
            if ($user->hasPermission($perm)) {
                $hasAnyPermission = true;
                break;
            }
        }

        if (! $hasAnyPermission) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'এই অ্যাকশনটি সম্পাদন করার পর্যাপ্ত পারমিশন আপনার নেই।',
                ], 403);
            }

            abort(403, 'এই অ্যাকশনটি সম্পাদন করার পর্যাপ্ত পারমিশন আপনার নেই।');
        }

        return $next($request);
    }
}
