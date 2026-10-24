<?php

namespace App\Services\Marketplace\Payments;

abstract class AbstractPaymentGateway implements PaymentGatewayInterface
{
    /**
     * Sanitize array to strip any potential sensitive credentials before logging or database saving.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function sanitizePayload(array $data): array
    {
        $sensitiveKeys = [
            'password', 'pin', 'cvv', 'cvc', 'card_number', 'card_cvv', 'card_expiry',
            'app_secret', 'secret', 'private_key', 'token', 'authorization',
        ];

        $sanitized = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            $isSensitive = false;
            foreach ($sensitiveKeys as $pattern) {
                if (str_contains($lowerKey, $pattern)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Compute verification HMAC signature for payload.
     *
     * @param  array<string, mixed>  $data
     */
    public function generateSignature(array $data, string $secret): string
    {
        ksort($data);
        $encoded = json_encode($data, JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha256', (string) $encoded, $secret);
    }

    /**
     * Validate monetary amounts safely without floating-point inaccuracies.
     */
    protected function amountsMatch(float|int|string $expected, float|int|string $actual): bool
    {
        $expectedCents = (int) round(((float) $expected) * 100);
        $actualCents = (int) round(((float) $actual) * 100);

        return $expectedCents === $actualCents;
    }
}
