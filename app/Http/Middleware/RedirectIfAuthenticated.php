<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => true,
                        'message' => 'আপনি ইতোমধ্যে লগইন অবস্থায় আছেন।',
                    ], 200);
                }

                return redirect('/feed');
            }
        }

        return $next($request);
    }
}
