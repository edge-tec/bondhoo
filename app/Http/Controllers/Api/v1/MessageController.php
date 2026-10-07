<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Media;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\Report;
use App\Services\Contracts\MediaStorageServiceInterface;
use App\Services\Contracts\MessengerServiceInterface;
use App\Services\MediaProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * মেসেজ কন্ট্রোলার:
 * মেসেজ সেন্ড, হিস্ট্রি, এডিট, ডিলিট, রিঅ্যাকশন, ফরোয়ার্ড, ডেলিভারি, রিড ও সিকিউর অ্যাটাচমেন্টস।
 */
class MessageController extends Controller
{
    public function __construct(
        protected MessengerServiceInterface $messengerService,
        protected MediaStorageServiceInterface $mediaStorageService
    ) {}

    /**
     * নির্দিষ্ট কনভার্সনের মেসেজ তালিকা পেজিনেশন ও সার্চ সহ প্রদর্শন।
     */
    public function index(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        $conversation = Conversation::findOrFail($conversationId);

        if (! $conversation->hasParticipant($user->id)) {
            return $this->errorResponse('You are not a participant in this conversation.', 403);
        }

        $perPage = (int) $request->query('per_page', 30);
        $filters = [
            'search' => (string) $request->query('search', ''),
            'type' => (string) $request->query('type', ''),
        ];

        $messages = $this->messengerService->getMessages($conversation, $user, $perPage, $filters);

        return $this->successResponse(
            data: $messages->items(),
            message: 'Messages retrieved successfully.',
            meta: [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ]
        );
    }

    /**
     * কনভার্সনে নতুন মেসেজ পাঠানো (টেক্সট, মিডিয়া, ভয়েস বা রিপ্লাই)।
     */
    public function store(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        $conversation = Conversation::findOrFail($conversationId);

        if (! $conversation->hasParticipant($user->id)) {
            return $this->errorResponse('You are not a participant in this conversation.', 403);
        }

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
            'type' => ['nullable', 'string', 'in:text,media,voice,file,link,system'],
            'reply_to_id' => ['nullable', 'integer', 'exists:messages,id'],
            'reply_to_message_id' => ['nullable', 'integer', 'exists:messages,id'],
            'client_message_id' => ['nullable', 'string', 'max:128'],
            'client_uuid' => ['nullable', 'string', 'max:128'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
            'metadata' => ['nullable', 'array'],
        ]);

        if (empty(trim($validated['body'] ?? '')) && empty($validated['media_ids'])) {
            return $this->errorResponse('Message must have text or media attachment.', 422);
        }

        $message = $this->messengerService->sendMessage($user, $conversation, $validated);

        return $this->successResponse(
            data: $message->toResponseArray($user),
            message: 'Message sent successfully.',
            statusCode: 201
        );
    }

    /**
     * প্রেরক কর্তৃক নিজের পূর্ববর্তী মেসেজ এডিট করা।
     */
    public function edit(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'version' => ['nullable', 'integer'],
            'expected_version' => ['nullable', 'integer'],
        ]);

        $expectedVersion = $validated['expected_version'] ?? $validated['version'] ?? null;
        try {
            $message = $this->messengerService->editMessage($user, $id, $validated['body'], $expectedVersion);
        } catch (ConflictHttpException $e) {
            $currentMsg = Message::find($id);

            return $this->errorResponse($e->getMessage(), 409, [
                'current_message' => $currentMsg?->toResponseArray($user),
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }

        return $this->successResponse(
            data: $message->toResponseArray($user),
            message: 'Message edited successfully.'
        );
    }

    /**
     * মেসেজ ডিলিট করা (নিজের জন্য অথবা সবার জন্য)।
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $forEveryone = $request->boolean('for_everyone', false) || $request->input('mode') === 'everyone';

        $this->messengerService->deleteMessage($user, $id, $forEveryone);

        return $this->successResponse(
            data: ['message_id' => $id, 'for_everyone' => $forEveryone],
            message: $forEveryone ? 'Message deleted for everyone.' : 'Message deleted for you.'
        );
    }

    /**
     * শুধুমাত্র নিজের জন্য মেসেজ ডিলিট করা।
     */
    public function deleteForMe(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $this->messengerService->deleteMessage($user, $id, false);

        return $this->successResponse(
            data: [
                'message_id' => $id,
                'for_everyone' => false,
                'deleted_for_me' => true,
            ],
            message: 'Message deleted for you.'
        );
    }

    /**
     * সবার জন্য মেসেজ ডিলিট করা (Tombstone soft delete)।
     */
    public function deleteForEveryone(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $this->messengerService->deleteMessage($user, $id, true);

        return $this->successResponse(
            data: [
                'message_id' => $id,
                'for_everyone' => true,
                'is_deleted' => true,
                'body' => null,
            ],
            message: 'Message deleted for everyone.'
        );
    }

    /**
     * মেসেজে রিঅ্যাকশন যোগ, পরিবর্তন বা প্রত্যাহার।
     */
    public function react(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'reaction' => ['required', 'string', 'max:32'],
        ]);

        $reactions = $this->messengerService->reactToMessage($user, $id, $validated['reaction']);

        return $this->successResponse(
            data: $reactions,
            message: 'Reaction updated.'
        );
    }

    /**
     * নির্দিষ্ট মেসেজে কারা কি রিঅ্যাকশন দিয়েছেন তার তালিকা।
     */
    public function getReactions(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $message = Message::with('conversation.participants')->findOrFail($id);

        if (! $message->conversation->hasParticipant($user->id)) {
            return $this->errorResponse('You cannot view reactions for this conversation.', 403);
        }

        $reactions = MessageReaction::where('message_id', $message->id)
            ->with(['user.profile'])
            ->latest('id')
            ->get()
            ->map(fn ($rx) => [
                'id' => $rx->id,
                'reaction' => $rx->reaction,
                'user' => [
                    'id' => $rx->user?->id,
                    'name' => $rx->user?->name,
                    'username' => $rx->user?->username,
                    'avatar_url' => $rx->user?->profile?->avatar_url,
                ],
                'created_at' => $rx->created_at?->toIso8601String(),
            ]);

        return $this->successResponse(
            data: $reactions,
            message: 'Reactions retrieved successfully.'
        );
    }

    /**
     * মেসেজ এক বা একাধিক চ্যাটে ফরোয়ার্ড করা।
     */
    public function forward(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'target_conversation_ids' => ['nullable', 'array', 'min:1'],
            'target_conversation_ids.*' => ['integer', 'exists:conversations,id'],
            'conversation_ids' => ['nullable', 'array', 'min:1'],
            'conversation_ids.*' => ['integer', 'exists:conversations,id'],
        ]);

        $targets = $validated['target_conversation_ids'] ?? $validated['conversation_ids'] ?? [];
        if (empty($targets)) {
            return $this->errorResponse('At least one target conversation must be selected.', 422);
        }

        $forwarded = $this->messengerService->forwardMessage($user, $id, $targets);

        return $this->successResponse(
            data: $forwarded,
            message: 'Message forwarded successfully.'
        );
    }

    /**
     * মেসেজ প্রাপকের ডিভাইসে পৌঁছেছে বলে মার্ক করা (ডেলিভারড স্ট্যাটাস)।
     */
    public function delivered(Request $request, int $messageId): JsonResponse
    {
        $user = $request->user();
        $message = Message::with('conversation.participants')->findOrFail($messageId);

        if (! $message->conversation || ! $message->conversation->hasParticipant($user->id)) {
            return $this->errorResponse('You are not a participant in this conversation.', 403);
        }

        $message = $this->messengerService->markAsDelivered($message, $user);

        return $this->successResponse(
            data: [
                'message_id' => $message->id,
                'delivery_status' => $message->delivery_status,
                'delivered_at' => $message->delivered_at?->toIso8601String(),
            ],
            message: 'Message delivery status updated.'
        );
    }

    /**
     * কনভার্সনের মেসেজগুলো পঠিত (seen) হিসেবে মার্ক করা।
     */
    public function read(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        $conversation = Conversation::findOrFail($conversationId);

        if (! $conversation->hasParticipant($user->id)) {
            return $this->errorResponse('You are not a participant in this conversation.', 403);
        }

        $updatedCount = $this->messengerService->markConversationAsRead($conversation, $user);

        return $this->successResponse(
            data: [
                'conversation_id' => $conversationId,
                'messages_marked_read' => $updatedCount,
            ],
            message: 'Conversation marked as read.'
        );
    }

    /**
     * ইউজারের মোট আনরিড মেসেজ সংখ্যা পাওয়া।
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->messengerService->getUnreadMessageCount($user);

        return $this->successResponse(
            data: [
                'unread_count' => $count,
            ],
            message: 'Unread message count retrieved.'
        );
    }

    /**
     * অনুপযুক্ত বা ক্ষতিকর মেসেজ রিপোর্ট করা।
     */
    public function report(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $message = Message::findOrFail($id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:spam,harassment,abuse,fraud,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $report = Report::create([
            'reporter_id' => $user->id,
            'reportable_type' => Message::class,
            'reportable_id' => $message->id,
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'status' => Report::STATUS_PENDING,
        ]);

        return $this->successResponse(
            data: $report,
            message: 'Message report submitted successfully.',
            statusCode: 201
        );
    }

    /**
     * চ্যাট অ্যাটাচমেন্ট (ছবি, ভিডিও, অডিও, ভয়েস বা ফাইল) নিরাপদ আপলোড।
     */
    public function uploadAttachment(Request $request): JsonResponse
    {
        $user = $request->user();

        $allowedMimes = 'jpg,jpeg,png,webp,gif,avif,mp4,webm,mov,mp3,wav,ogg,m4a,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,txt,csv';

        if ($request->hasFile('files')) {
            $request->validate([
                'files' => ['required', 'array', 'min:1', 'max:10'],
                'files.*' => ['required', 'file', 'max:51200', 'mimes:'.$allowedMimes],
                'collection' => ['nullable', 'string', 'in:message,voice'],
            ]);

            $collection = $request->input('collection', 'message');
            $uploadedList = [];
            foreach ($request->file('files') as $fileItem) {
                $media = $this->storeSingleAttachmentFile($fileItem, $user, $collection);
                $uploadedList[] = $media->toResponseArray();
            }

            return $this->successResponse(
                data: $uploadedList,
                message: 'Attachments uploaded successfully.',
                statusCode: 201
            );
        }

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:51200', // 50MB max
                'mimes:'.$allowedMimes,
            ],
            'collection' => ['nullable', 'string', 'in:message,voice'],
        ]);

        $media = $this->storeSingleAttachmentFile($request->file('file'), $user, $request->input('collection', 'message'));

        return $this->successResponse(
            data: $media->toResponseArray(),
            message: 'Attachment uploaded successfully.',
            statusCode: 201
        );
    }

    /**
     * একক ফাইল স্টোরেজে সংরক্ষণ ও মিডিয়া মডেল তৈরি।
     */
    protected function storeSingleAttachmentFile(UploadedFile $file, $user, string $collection = 'message'): Media
    {
        $size = $file->getSize();
        $clientOriginalName = pathinfo($file->getClientOriginalName(), PATHINFO_BASENAME);
        $rawExt = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $ext = preg_replace('/[^a-z0-9]/', '', $rawExt);

        $blockedExts = ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'vbs', 'cgi', 'pl', 'py', 'js', 'html', 'htm'];
        if (in_array($ext, $blockedExts, true)) {
            abort(422, 'File type not permitted for upload.');
        }

        // Server-side magic-byte inspection
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file->getRealPath()) ?: ($file->getMimeType() ?: 'application/octet-stream');

        $isImage = str_starts_with($detectedMime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true);
        $width = null;
        $height = null;
        $thumbnailPath = null;
        $mediumPath = null;
        $largePath = null;

        if ($isImage) {
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo !== false) {
                $width = $imageInfo[0] ?? null;
                $height = $imageInfo[1] ?? null;
                $detectedMime = $imageInfo['mime'] ?? $detectedMime;
            }
        }

        $filename = sprintf('msg_%d_%s_%s.%s', $user->id, time(), bin2hex(random_bytes(6)), $ext);
        $disk = 'public';
        $path = $file->storeAs('messages', $filename, $disk);

        // Process image variants (thumbnail and preview) synchronously for instant messenger display
        if ($isImage) {
            try {
                $processingService = app(MediaProcessingService::class);
                $variants = $processingService->processImage($path);
                $thumbnailPath = $variants['thumbnail_path'] ?? null;
                $mediumPath = $variants['medium_path'] ?? null;
                $largePath = $variants['large_path'] ?? null;
                $width = $variants['original_width'] ?? $width;
                $height = $variants['original_height'] ?? $height;
            } catch (\Throwable $e) {
                Log::warning('Chat image processing warning for '.$path.': '.$e->getMessage());
            }
        }

        return Media::create([
            'user_id' => $user->id,
            'collection' => $collection,
            'disk' => $disk,
            'original_path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'medium_path' => $mediumPath,
            'large_path' => $largePath,
            'mime_type' => $detectedMime,
            'width' => $width,
            'height' => $height,
            'size' => $size,
            'processing_status' => 'ready',
            'metadata' => [
                'original_filename' => $clientOriginalName,
                'extension' => $ext,
            ],
        ]);
    }

    /**
     * সিকিউর অ্যাটাচমেন্ট ডাউনলোড (IDOR প্রতিরোধ ও মেম্বারশিপ যাচাই)।
     */
    public function downloadAttachment(Request $request, int $mediaId): BinaryFileResponse|StreamedResponse|JsonResponse
    {
        $user = $request->user();
        $media = Media::findOrFail($mediaId);

        $originalPath = $media->original_path;
        if (str_contains($originalPath, "\0") ||
            str_contains($originalPath, '..') ||
            str_starts_with($originalPath, '/') ||
            str_starts_with($originalPath, '\\')) {
            return $this->errorResponse('Invalid or unsafe attachment path.', 403);
        }

        // যদি মিডিয়াটি কোনো মেসেজের সাথে সংযুক্ত থাকে, ব্যবহারকারী সেই কনভার্সনের সদস্য কিনা যাচাই করা
        if ($media->mediable_type === Message::class && $media->mediable_id) {
            $message = Message::with('conversation.participants')->find($media->mediable_id);
            if (! $message || ! $message->conversation || ! $message->conversation->hasParticipant($user?->id ?? 0)) {
                return $this->errorResponse('Unauthorized attachment access.', 403);
            }
        } elseif ((int) $media->user_id !== (int) ($user?->id ?? 0)) {
            return $this->errorResponse('Unauthorized attachment access.', 403);
        }

        $disk = Storage::disk($media->disk);
        if (! $disk->exists($originalPath)) {
            return $this->errorResponse('Attachment file not found.', 404);
        }

        $downloadFilename = $media->metadata['original_filename'] ?? basename($originalPath);

        return $disk->download($originalPath, $downloadFilename);
    }

    /**
     * সিকিউর অ্যাটাচমেন্ট ভিউ / প্রিভিউ (ইনলাইন স্ট্রিমিং ও মেম্বারশিপ যাচাই)।
     */
    public function viewAttachment(Request $request, int $mediaId): BinaryFileResponse|StreamedResponse|JsonResponse
    {
        $user = $request->user();
        $media = Media::findOrFail($mediaId);

        $originalPath = $media->original_path;
        if (str_contains($originalPath, "\0") ||
            str_contains($originalPath, '..') ||
            str_starts_with($originalPath, '/') ||
            str_starts_with($originalPath, '\\')) {
            return $this->errorResponse('Invalid or unsafe attachment path.', 403);
        }

        // মেম্বারশিপ এবং অনুমতি যাচাই
        if ($media->mediable_type === Message::class && $media->mediable_id) {
            $message = Message::with('conversation.participants')->find($media->mediable_id);
            if (! $message || ! $message->conversation || ! $message->conversation->hasParticipant($user?->id ?? 0)) {
                return $this->errorResponse('Unauthorized attachment access.', 403);
            }
        } elseif ((int) $media->user_id !== (int) ($user?->id ?? 0)) {
            return $this->errorResponse('Unauthorized attachment access.', 403);
        }

        $variant = $request->query('variant', 'original');
        $targetPath = match ($variant) {
            'thumbnail' => $media->thumbnail_path ?: ($media->medium_path ?: $media->original_path),
            'medium', 'preview' => $media->medium_path ?: $media->original_path,
            'large' => $media->large_path ?: $media->original_path,
            default => $media->original_path,
        };

        $disk = Storage::disk($media->disk);
        if (! $disk->exists($targetPath)) {
            $targetPath = $media->original_path;
        }

        if (! $disk->exists($targetPath)) {
            return $this->errorResponse('Attachment file not found.', 404);
        }

        $mimeType = str_ends_with($targetPath, '.webp') ? 'image/webp' : $media->mime_type;
        $size = $disk->size($targetPath);
        $etag = '"'.md5($targetPath.$media->updated_at?->timestamp).'"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'private, max-age=86400, must-revalidate',
            ]);
        }

        $filename = $media->metadata['original_filename'] ?? basename($targetPath);

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => $size,
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            'Cache-Control' => 'private, max-age=86400, must-revalidate',
            'ETag' => $etag,
            'X-Content-Type-Options' => 'nosniff',
        ];

        return $disk->response($targetPath, $filename, $headers);
    }
}
