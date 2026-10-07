<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * CorrelationIdMiddleware — রিকোয়েস্ট ট্র্যাকিং ও কোরিলেশন আইডি মিডলওয়্যার
 *
 * এই মিডলওয়্যারটি মাইক্রোসার্ভিস ও ডিস্ট্রিবিউটেড ট্রেসিংয়ের জন্য প্রতিটি
 * রিকোয়েস্টে уникальный Correlation ID ও Request ID যুক্ত করে।
 */
class CorrelationIdMiddleware
{
    /**
     * ইনকামিং রিকোয়েস্ট হ্যান্ডেল করা।
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Correlation-ID') ?: Str::uuid()->toString();
        $requestId = $request->header('X-Request-ID') ?: Str::uuid()->toString();

        $request->headers->set('X-Correlation-ID', $correlationId);
        $request->headers->set('X-Request-ID', $requestId);

        // লগে কোরিলেশন আইডি বাইন্ড করা
        Log::withContext([
            'correlation_id' => $correlationId,
            'request_id' => $requestId,
            'ip' => $request->ip(),
        ]);

        $response = $next($request);

        $response->headers->set('X-Correlation-ID', $correlationId);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
