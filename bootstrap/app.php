<?php

require_once __DIR__.'/../app/helpers.php';

use App\Http\Middleware\ActiveUserMiddleware;
use App\Http\Middleware\AdminPermissionMiddleware;
use App\Http\Middleware\AdminSessionTimeoutMiddleware;
use App\Http\Middleware\AdminWebMiddleware;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CorrelationIdMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\UserSessionTimeoutMiddleware;
use App\Http\Middleware\VerifiedUserMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->encryptCookies(except: ['jugajug_token', 'bondhoo_token']);
        $middleware->append(SecurityHeadersMiddleware::class);
        $middleware->append(CorrelationIdMiddleware::class);
        $middleware->appendToGroup('web', UserSessionTimeoutMiddleware::class);

        $middleware->alias([
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
            'active.user' => ActiveUserMiddleware::class,
            'verified.user' => VerifiedUserMiddleware::class,
            'session.timeout' => UserSessionTimeoutMiddleware::class,
            'admin.timeout' => AdminSessionTimeoutMiddleware::class,
            'admin.permission' => AdminPermissionMiddleware::class,
            'admin.web' => AdminWebMiddleware::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
