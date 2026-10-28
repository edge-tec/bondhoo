<?php

namespace App\Services\Calling;

use App\Models\User;
use Illuminate\Support\Str;

class LiveKitService
{
    /**
     * Get LiveKit WebSocket / HTTP server URL.
     */
    public function getServerUrl(): string
    {
        return (string) config('services.livekit.url', env('LIVEKIT_SERVER_URL', 'wss://livekit.jugajug.com'));
    }

    /**
     * Get LiveKit API Key.
     */
    public function getApiKey(): string
    {
        return (string) config('services.livekit.api_key', env('LIVEKIT_API_KEY', 'jugajug_livekit_key'));
    }

    /**
     * Get LiveKit API Secret.
     */
    public function getApiSecret(): string
    {
        return (string) config('services.livekit.api_secret', env('LIVEKIT_API_SECRET', 'jugajug_livekit_secret_key_2026'));
    }

    /**
     * Generate a cryptographic LiveKit access token with video grants.
     *
     * @param  array<string, mixed>  $customGrants
     */
    public function generateToken(User $user, string $roomName, array $customGrants = [], int $ttlSeconds = 14400): string
    {
        $now = time();
        $apiKey = $this->getApiKey();
        $apiSecret = $this->getApiSecret();

        $videoGrants = array_merge([
            'room' => $roomName,
            'roomJoin' => true,
            'canPublish' => true,
            'canSubscribe' => true,
            'canPublishData' => true,
        ], $customGrants);

        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $apiKey,
            'sub' => (string) $user->id,
            'name' => $user->name,
            'nbf' => $now - 5,
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
            'jti' => (string) Str::uuid(),
            'video' => $videoGrants,
            'metadata' => json_encode([
                'user_id' => $user->id,
                'username' => $user->username,
                'avatar_url' => $user->profile?->avatar_url,
            ], JSON_UNESCAPED_SLASHES),
        ];

        return $this->encodeJwt($header, $payload, $apiSecret);
    }

    /**
     * Cryptographically verify and decode a LiveKit access token.
     *
     * @return array<string, mixed>|null
     */
    public function verifyToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $apiSecret = $this->getApiSecret();
        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $apiSecret, true)
        );

        if (! hash_equals($expectedSignature, $signatureB64)) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($payloadB64);
        $payload = json_decode($payloadJson, true);

        if (! is_array($payload) || ! isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * Encode header and payload into a Signed JWT.
     *
     * @param  array<string, mixed>  $header
     * @param  array<string, mixed>  $payload
     */
    protected function encodeJwt(array $header, array $payload, string $secret): string
    {
        $headerB64 = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadB64 = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signature = hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $secret, true);
        $signatureB64 = $this->base64UrlEncode($signature);

        return "{$headerB64}.{$payloadB64}.{$signatureB64}";
    }

    /**
     * Base64URL encoding helper.
     */
    public function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64URL decoding helper.
     */
    public function base64UrlDecode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/').str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
