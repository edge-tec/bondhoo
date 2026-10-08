<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminWebMiddleware
{
    /**
     * Handle an incoming request.
     * Ensures only authenticated administrators can access admin console routes.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isAdminGuard = Auth::guard('admin')->check();
        $webUser = Auth::guard('web')->user();
        $isWebAdmin = $webUser && method_exists($webUser, 'isAdmin') && $webUser->isAdmin();

        // Also recognize Sanctum authenticated administrator if Bearer token is provided
        $sanctumUser = null;
        if (! $isAdminGuard && ! $isWebAdmin && $request->bearerToken()) {
            $sanctumUser = Auth::guard('sanctum')->user();
        }
        $isAdminSanctum = $sanctumUser && (
            $sanctumUser instanceof Admin
            || (method_exists($sanctumUser, 'isAdmin') && $sanctumUser->isAdmin())
            || (method_exists($sanctumUser, 'hasRole') && ($sanctumUser->hasRole('ADMIN') || $sanctumUser->hasRole('SUPER_ADMIN')))
        );

        if ($isAdminGuard || $isWebAdmin || $isAdminSanctum) {
            $effectiveUser = Auth::guard('admin')->user() ?? $webUser ?? $sanctumUser;
            if ($effectiveUser) {
                $request->setUserResolver(fn () => $effectiveUser);
            }

            return $next($request);
        }

        // If authenticated as a normal user but NOT an administrator -> 403 Forbidden
        if ($webUser || ($sanctumUser && ! $isAdminSanctum)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'অননুমোদিত অ্যাক্সেস। শুধুমাত্র অ্যাডমিনদের জন্য অনুমোদিত।',
                ], 403);
            }

            abort(403, 'অননুমোদিত অ্যাক্সেস। শুধুমাত্র অ্যাডমিনদের জন্য অনুমোদিত।');
        }

        // Unauthenticated guest -> redirect to /admin/login (or 401 for JSON)
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'অননুমোদিত অনুরোধ। অনুগ্রহ করে অ্যাডমিন হিসেবে লগইন করুন।',
            ], 401);
        }

        return redirect()->guest(route('admin.login'));
    }
}
