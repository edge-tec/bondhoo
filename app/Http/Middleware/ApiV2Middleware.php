<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiV2Middleware
{
    /**
     * Handle an incoming request for API v2.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Idempotency Key Handling for mutating requests (POST, PUT, PATCH)
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey && in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $cachedKey = "idempotency:v2:{$idempotencyKey}";
            if (Cache::has($cachedKey)) {
                $cachedData = Cache::get($cachedKey);

                return response()->json($cachedData['body'], $cachedData['status'], [
                    'X-Cache-Lookup' => 'HIT (Idempotent)',
                    'X-Idempotency-Key' => $idempotencyKey,
                ]);
            }
        }

        // 2. Correlation ID
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $response = $next($request);

        $response->headers->set('X-Correlation-ID', $correlationId);
        $response->headers->set('X-API-Version', '2.0.0-enterprise');

        // Cache idempotent responses for 24 hours if successful
        if ($idempotencyKey && $response->isSuccessful()) {
            Cache::put("idempotency:v2:{$idempotencyKey}", [
                'status' => $response->getStatusCode(),
                'body' => json_decode($response->getContent(), true),
            ], 86400);
        }

        return $response;
    }
}
