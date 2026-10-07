<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Messenger\VoiceMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VoiceMessageController extends Controller
{
    public function __construct(
        protected VoiceMessageService $voiceMessageService
    ) {}

    /**
     * Resolve the current user from auth guards or token cookie.
     */
    protected function resolveAuthenticatedUser(Request $request): ?User
    {
        $user = $request->user();
        if ($user) {
            return $user;
        }

        if (auth('web')->check()) {
            return auth('web')->user();
        }

        $rawToken = $request->bearerToken()
            ?: (string) $request->query('token')
            ?: (string) $request->query('auth_token')
            ?: (string) $request->cookie('jugajug_token');

        if (! empty($rawToken)) {
            $pat = PersonalAccessToken::findToken($rawToken);
            if ($pat && $pat->tokenable instanceof User) {
                return $pat->tokenable;
            }
        }

        return null;
    }

    /**
     * Upload and send a voice audio note.
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'audio' => ['required', 'file', 'mimes:webm,weba,ogg,mp3,wav,m4a,aac,bin,mp4,mov', 'max:51200'], // max 50MB
            'duration' => ['required', 'integer', 'min:1', 'max:3600'], // up to 60 mins
            'waveform' => ['nullable', 'array'],
        ]);

        $conversation = Conversation::findOrFail($id);
        $user = $request->user();

        if (! $conversation->hasParticipant($user->id)) {
            return $this->errorResponse('You are not a participant in this conversation.', 403);
        }

        $message = $this->voiceMessageService->sendVoiceMessage(
            conversation: $conversation,
            sender: $user,
            audioFile: $request->file('audio'),
            durationSeconds: (int) $validated['duration'],
            waveform: $validated['waveform'] ?? []
        );

        return $this->successResponse(
            data: $message->toResponseArray($user),
            message: 'Voice message sent successfully.',
            statusCode: 201
        );
    }

    /**
     * Stream an authenticated voice audio note with HTTP byte-range and CORS support.
     */
    public function stream(Request $request, int $id): BinaryFileResponse|JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);
        if (! $user) {
            return $this->errorResponse('Unauthenticated.', 401);
        }

        $message = Message::with('conversation.participants')->findOrFail($id);

        if (! $message->conversation || ! $message->conversation->hasParticipant($user->id)) {
            return $this->errorResponse('Unauthorized audio access.', 403);
        }

        $metadata = $message->metadata ?? [];
        $storagePath = $metadata['storage_path'] ?? null;
        $fullPath = null;

        if ($storagePath) {
            if (Storage::disk('public')->exists($storagePath)) {
                $fullPath = Storage::disk('public')->path($storagePath);
            } elseif (file_exists(storage_path('app/public/'.$storagePath))) {
                $fullPath = storage_path('app/public/'.$storagePath);
            }
        }

        // Fallback: check original path in media relationship if attached
        if (! $fullPath || ! file_exists($fullPath)) {
            $media = $message->media()->where('collection', 'voice')->first() ?? $message->media()->first();
            if ($media && Storage::disk($media->disk)->exists($media->original_path)) {
                $fullPath = Storage::disk($media->disk)->path($media->original_path);
            }
        }

        // Secondary fallback: extract path from audio_url if storage_path was omitted
        if ((! $fullPath || ! file_exists($fullPath)) && ! empty($metadata['audio_url'])) {
            $parsedPath = parse_url($metadata['audio_url'], PHP_URL_PATH);
            if ($parsedPath && str_contains($parsedPath, '/storage/')) {
                $relPath = ltrim(substr($parsedPath, strpos($parsedPath, '/storage/') + 9), '/');
                if (file_exists(storage_path('app/public/'.$relPath))) {
                    $fullPath = storage_path('app/public/'.$relPath);
                }
            }
        }

        if (! $fullPath || ! file_exists($fullPath)) {
            return $this->errorResponse('Voice recording file not found.', 404);
        }

        // Determine correct browser-compatible audio MIME type
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeType = match ($ext) {
            'webm', 'weba' => 'audio/webm',
            'ogg', 'oga' => 'audio/ogg',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'm4a', 'aac', 'mp4' => 'audio/mp4',
            default => 'audio/webm',
        };

        // If client sends Range request (Safari, iOS, Chrome audio element seeking)
        $response = new BinaryFileResponse($fullPath);
        $response->setAutoEtag();
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Range, Authorization, X-Requested-With, X-CSRF-TOKEN');
        $response->headers->set('Cache-Control', 'private, max-age=86400, must-revalidate');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setContentDisposition('inline', basename($fullPath));

        $response->prepare($request);

        return $response;
    }
}
