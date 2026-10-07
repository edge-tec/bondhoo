<?php

namespace App\Services\Observability;

class OpenTelemetryService
{
    /**
     * Start a new OpenTelemetry distributed trace span.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{trace_id: string, span_id: string, parent_id: ?string, name: string, start_time: float, attributes: array<string, mixed>}
     */
    public function startSpan(string $name, array $attributes = [], ?string $parentSpanId = null): array
    {
        $traceId = request()->header('X-Trace-ID', bin2hex(random_bytes(16)));
        $spanId = bin2hex(random_bytes(8));

        return [
            'trace_id' => $traceId,
            'span_id' => $spanId,
            'parent_id' => $parentSpanId,
            'name' => $name,
            'start_time' => microtime(true),
            'attributes' => array_merge([
                'service.name' => 'jugajug-api',
                'service.version' => '2.0.0',
                'deployment.environment' => app()->environment(),
            ], $attributes),
        ];
    }

    /**
     * Finish span and calculate duration.
     *
     * @param  array{trace_id: string, span_id: string, parent_id: ?string, name: string, start_time: float, attributes: array<string, mixed>}  $span
     * @param  array<string, mixed>  $finalAttributes
     * @return array<string, mixed>
     */
    public function endSpan(array $span, array $finalAttributes = []): array
    {
        $durationMs = round((microtime(true) - $span['start_time']) * 1000, 2);

        $span['duration_ms'] = $durationMs;
        $span['attributes'] = array_merge($span['attributes'], $finalAttributes);
        $span['end_time'] = microtime(true);

        return $span;
    }

    /**
     * Generate W3C traceparent header string (00-traceid-spanid-01).
     */
    public function getTraceparentHeader(array $span): string
    {
        return sprintf('00-%s-%s-01', $span['trace_id'], $span['span_id']);
    }
}
