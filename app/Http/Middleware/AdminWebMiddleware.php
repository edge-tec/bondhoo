<?php

namespace App\Http\Middleware;

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

        if ($isAdminGuard || $isWebAdmin) {
            return $next($request);
        }

        // If authenticated as a normal user but NOT an administrator -> 403 Forbidden
        if ($webUser) {
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
