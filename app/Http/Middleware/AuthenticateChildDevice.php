<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Parental\ChildDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthenticateChildDevice
 *
 * Validates cryptographic child device bearer tokens.
 */
class AuthenticateChildDevice
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'Missing or malformed Authorization header.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $rawToken = substr($header, 7);
        $tokenHash = hash('sha256', trim($rawToken));

        /** @var ChildDevice|null $device */
        $device = ChildDevice::where('device_token_hash', $tokenHash)
            ->where('device_status', 'active')
            ->first();

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid, revoked, or inactive child device token.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set('childDevice', $device);

        return $next($request);
    }
}
