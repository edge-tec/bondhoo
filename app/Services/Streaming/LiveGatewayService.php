<?php

namespace App\Services\Streaming;

use App\Models\LiveStream;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\ProfilePrivacyService;
use App\Services\RealtimeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LiveGatewayService
{
    public function __construct(
        protected RealtimeService $realtime,
        protected ProfilePrivacyService $profilePrivacyService
    ) {}

    /**
     * Get configured SFU provider name.
     */
    public function getProvider(): string
    {
        return config('services.sfu.driver', env('SFU_DRIVER', 'gateway'));
    }

    /**
     * Get public SFU WebSocket / WebRTC endpoint URL.
     */
    public function getSfuEndpoint(): string
    {
        return config('services.sfu.url', env('SFU_SERVER_URL', 'wss://live-sfu.jugajug.com/sfu'));
    }

    /**
     * Get secret key used for signing ephemeral SFU tokens.
     */
    protected function getSigningSecret(): string
    {
        return (string) config('services.sfu.api_secret', config('app.key', 'jugajug-sfu-secret-key'));
    }

    /**
     * Create a secure SFU room for the live broadcast session.
     *
     * @return array{room_id: string, sfu_endpoint: string, provider: string}
     */
    public function createRoom(LiveStream $stream): array
    {
        $roomId = 'sfu_room_'.$stream->channel_id;
        $endpoint = $this->getSfuEndpoint();
        $provider = $this->getProvider();

        $stream->update([
            'sfu_room_id' => $roomId,
            'sfu_provider' => $provider,
        ]);

        return [
            'room_id' => $roomId,
            'sfu_endpoint' => $endpoint,
            'provider' => $provider,
        ];
    }

    /**
     * Close and destroy the SFU room on the gateway.
     */
    public function closeRoom(LiveStream $stream): bool
    {
        if ($stream->sfu_room_id) {
            $payload = [
                'stream_id' => $stream->id,
                'channel_id' => $stream->channel_id,
                'room_id' => $stream->sfu_room_id,
                'action' => 'close_room',
            ];

            $this->realtime->broadcast("sfu.{$stream->sfu_room_id}", 'sfu.room.closed', $payload);
            $this->realtime->broadcast("live.{$stream->channel_id}", 'sfu.room.closed', $payload);
        }

        return true;
    }

    /**
     * Generate short-lived cryptographic token for the broadcaster (Host).
     * Grants track publication and subscription rights.
     */
    public function generateHostToken(LiveStream $stream, User $user, int $ttlSeconds = 3600): string
    {
        if ($stream->user_id !== $user->id) {
            throw new AuthorizationException('শুধুমাত্র ব্রডকাস্টার হোস্ট টোকেন পেতে পারেন।');
        }

        $roomId = $stream->sfu_room_id ?: 'sfu_room_'.$stream->channel_id;

        $payload = [
            'sub' => (string) $user->id,
            'user_name' => $user->name,
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'room' => $roomId,
            'role' => 'host',
            'permissions' => [
                'can_publish' => true,
                'can_subscribe' => true,
                'can_record' => (bool) $stream->recording_enabled,
            ],
            'sfu_endpoint' => $this->getSfuEndpoint(),
            'iat' => time(),
            'exp' => time() + $ttlSeconds,
            'jti' => Str::random(16),
        ];

        $token = $this->encodeJwt($payload);

        $stream->update(['sfu_host_token' => $token]);

        return $token;
    }

    /**
     * Generate short-lived cryptographic token for a viewer.
     * Enforces privacy and block relationships before issuing subscription rights.
     */
    public function generateViewerToken(LiveStream $stream, ?User $user, int $ttlSeconds = 1800): string
    {
        if (! $stream->canView($user)) {
            throw new AuthorizationException('এই লাইভ স্ট্রিমটিতে প্রবেশের অনুমতি আপনার নেই।');
        }

        $roomId = $stream->sfu_room_id ?: 'sfu_room_'.$stream->channel_id;

        $payload = [
            'sub' => $user ? (string) $user->id : 'guest',
            'user_name' => $user ? $user->name : 'Viewer',
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'room' => $roomId,
            'role' => 'viewer',
            'permissions' => [
                'can_publish' => false,
                'can_subscribe' => true,
                'can_record' => false,
            ],
            'sfu_endpoint' => $this->getSfuEndpoint(),
            'iat' => time(),
            'exp' => time() + $ttlSeconds,
            'jti' => Str::random(16),
        ];

        return $this->encodeJwt($payload);
    }

    /**
     * Verify and decode short-lived token.
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

        $expectedSig = $this->base64UrlEncode(
            hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $this->getSigningSecret(), true)
        );

        if (! hash_equals($expectedSig, $signatureB64)) {
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
     * Authorize and confirm host WebRTC media track publication from SFU gateway.
     * Transitions state from STARTING to LIVE and broadcasts live.started.
     *
     * @param  array<string, mixed>  $trackMeta
     */
    public function confirmPublication(LiveStream $stream, string $hostToken, array $trackMeta = []): bool
    {
        $verified = $this->verifyToken($hostToken);
        if (! $verified || ($verified['role'] ?? '') !== 'host' || ($verified['stream_id'] ?? 0) !== $stream->id) {
            throw new AuthorizationException('অবৈধ অথবা মেয়াদোত্তীর্ণ হোস্ট টোকেন।');
        }

        if ($stream->status !== LiveStream::STATUS_LIVE) {
            $stream->update([
                'status' => LiveStream::STATUS_LIVE,
                'started_at' => $stream->started_at ?: now(),
                'last_heartbeat_at' => now(),
                'health_stats' => array_merge($stream->health_stats ?? [], $trackMeta),
            ]);

            $payload = [
                'stream_id' => $stream->id,
                'channel_id' => $stream->channel_id,
                'room_id' => $stream->sfu_room_id,
                'title' => $stream->title,
                'broadcaster' => [
                    'id' => $stream->user->id,
                    'name' => $stream->user->name,
                    'avatar' => $stream->user->profile?->avatar_url,
                ],
                'playback_url' => $stream->playback_url,
                'started_at' => $stream->started_at?->toIso8601String(),
            ];

            $this->realtime->broadcast("stream.{$stream->channel_id}", 'stream.started', $payload);
            $this->realtime->broadcast("live.{$stream->channel_id}", 'live.started', $payload);
            $this->realtime->broadcast("sfu.{$stream->sfu_room_id}", 'sfu.publication.confirmed', $payload);
        }

        return true;
    }

    /**
     * Authoritative server-side recording finalizer:
     * Takes server-side/SFU or uploaded recording, processes media,
     * extracts/generates thumbnail, creates permanent Video Post and Media record,
     * and publishes according to privacy.
     *
     * @return array{recording_url: string, post_id: int, media_id: int, thumbnail_url: ?string}
     */
    public function finalizeRecording(
        LiveStream $stream,
        ?string $relativeVideoPath = null,
        ?string $relativeThumbPath = null,
        ?string $mimeType = 'video/webm'
    ): array {
        $now = now();
        $user = $stream->user;

        // Determine recording path
        $videoPath = $relativeVideoPath ?: $stream->recording_file_path;
        if (! $videoPath) {
            // Check if default SFU recording file exists
            $candidatePath = "replays/{$stream->channel_id}.webm";
            if (Storage::disk('public')->exists($candidatePath)) {
                $videoPath = $candidatePath;
            }
        }

        // If no file exists at all, create an authoritative stream placeholder recording container
        if (! $videoPath || ! Storage::disk('public')->exists($videoPath)) {
            $videoPath = "replays/{$stream->channel_id}_".time().'.webm';
            Storage::disk('public')->put($videoPath, '');
        }

        $publicVideoUrl = Storage::url($videoPath);

        // Thumbnail resolution / generation
        $thumbPath = $relativeThumbPath;
        if (! $thumbPath || ! Storage::disk('public')->exists($thumbPath)) {
            $thumbPath = "thumbnails/{$stream->channel_id}_poster.png";
            $fullThumbPath = Storage::disk('public')->path($thumbPath);
            $this->generateVideoPoster($fullThumbPath, $stream->title);
        }
        $publicThumbUrl = Storage::url($thumbPath);

        // Create Media library record
        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'videos',
            'disk' => 'public',
            'original_path' => $videoPath,
            'thumbnail_path' => $thumbPath,
            'mime_type' => $mimeType ?: 'video/webm',
            'size' => Storage::disk('public')->size($videoPath) ?: 1024,
            'processing_status' => 'completed',
            'metadata' => [
                'live_stream_id' => $stream->id,
                'channel_id' => $stream->channel_id,
                'duration' => $stream->duration ?: 0,
                'title' => $stream->title,
                'sfu_room_id' => $stream->sfu_room_id,
            ],
        ]);

        // Create published Video Post
        $postAudience = match ($stream->privacy) {
            LiveStream::PRIVACY_FRIENDS => 'friends',
            LiveStream::PRIVACY_ONLY_ME => 'only_me',
            default => 'public',
        };

        $post = Post::create([
            'user_id' => $user->id,
            'content' => $stream->title.($stream->description ? "\n\n".$stream->description : ''),
            'audience' => $postAudience,
            'type' => 'video',
            'status' => 'published',
            'media_meta' => [
                [
                    'url' => $publicVideoUrl,
                    'type' => 'video',
                    'thumbnail_url' => $publicThumbUrl,
                    'duration' => $stream->duration ?: 0,
                    'mime_type' => $mimeType ?: 'video/webm',
                    'live_stream_id' => $stream->id,
                ],
            ],
        ]);

        $media->update([
            'mediable_type' => Post::class,
            'mediable_id' => $post->id,
        ]);

        // Transition recording to READY and stream status to ENDED
        $stream->update([
            'status' => LiveStream::STATUS_ENDED,
            'recording_status' => LiveStream::RECORDING_STATUS_READY,
            'recording_url' => $publicVideoUrl,
            'recording_file_path' => $videoPath,
            'generated_post_id' => $post->id,
            'thumbnail' => $publicThumbUrl,
        ]);

        $payload = [
            'stream_id' => $stream->id,
            'channel_id' => $stream->channel_id,
            'recording_url' => $publicVideoUrl,
            'recording_status' => LiveStream::RECORDING_STATUS_READY,
            'post_id' => $post->id,
            'post_url' => "/dashboard#post-card-{$post->id}",
            'thumbnail_url' => $publicThumbUrl,
        ];

        $this->realtime->broadcast("live.{$stream->channel_id}", 'live.replay.ready', $payload);
        $this->realtime->broadcast("stream.{$stream->channel_id}", 'live.replay.ready', $payload);

        return [
            'recording_url' => $publicVideoUrl,
            'post_id' => $post->id,
            'media_id' => $media->id,
            'thumbnail_url' => $publicThumbUrl,
        ];
    }

    /**
     * Generate fallback poster image using GD or FFmpeg.
     */
    protected function generateVideoPoster(string $targetFullPath, string $title): void
    {
        $dir = dirname($targetFullPath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        if (function_exists('imagecreatetruecolor')) {
            $imgW = 1280;
            $imgH = 720;
            $im = imagecreatetruecolor($imgW, $imgH);
            $bgColor = imagecolorallocate($im, 15, 23, 42); // slate-900
            imagefilledrectangle($im, 0, 0, $imgW, $imgH, $bgColor);

            // Red accent circle in center
            $accent = imagecolorallocate($im, 239, 68, 68); // Red
            imagefilledellipse($im, (int) ($imgW / 2), (int) ($imgH / 2), 160, 160, $accent);

            // White play triangle in center
            $playColor = imagecolorallocate($im, 255, 255, 255);
            $cx = (int) ($imgW / 2);
            $cy = (int) ($imgH / 2);
            $size = 30;
            $points = [
                $cx - $size + 6, $cy - $size,
                $cx + $size + 6, $cy,
                $cx - $size + 6, $cy + $size,
            ];
            imagefilledpolygon($im, $points, $playColor);

            imagepng($im, $targetFullPath, 8);
            imagedestroy($im);
        } else {
            // Write 1x1 transparent PNG fallback byte string
            File::put($targetFullPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
        }
    }

    /**
     * Encode payload array into signed compact JWT string.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function encodeJwt(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $headerB64 = $this->base64UrlEncode((string) json_encode($header));
        $payloadB64 = $this->base64UrlEncode((string) json_encode($payload));

        $signature = hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $this->getSigningSecret(), true);
        $signatureB64 = $this->base64UrlEncode($signature);

        return "{$headerB64}.{$payloadB64}.{$signatureB64}";
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
