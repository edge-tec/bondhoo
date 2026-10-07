<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\ConfirmUploadRequest;
use App\Http\Requests\Media\DirectUploadRequest;
use App\Http\Requests\Media\SignedUploadUrlRequest;
use App\Jobs\ProcessMediaJob;
use App\Models\Media;
use App\Services\Contracts\MediaStorageServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function __construct(
        protected MediaStorageServiceInterface $storageService,
        protected QueueServiceInterface $queueService
    ) {}

    /**
     * Generate a temporary signed direct upload URL.
     */
    public function signedUploadUrl(SignedUploadUrlRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $ext = pathinfo($validated['filename'], PATHINFO_EXTENSION) ?: 'bin';

        /** @var MediaStorageService $storage */
        $storage = $this->storageService;
        $path = $storage->generatePath(
            userId: $user->id,
            collection: $validated['collection'],
            extension: strtolower($ext),
            entityId: $validated['entity_id'] ?? null
        );

        $signedData = $this->storageService->generateSignedUploadUrl($path, $validated['mime_type']);

        // Create pending Media record
        $media = Media::create([
            'user_id' => $user->id,
            'collection' => $validated['collection'],
            'disk' => $this->storageService->getDisk(),
            'original_path' => $path,
            'mime_type' => $validated['mime_type'],
            'size' => $validated['size'],
            'processing_status' => 'pending',
            'metadata' => [
                'original_filename' => $validated['filename'],
                'upload_method' => 'signed_url',
            ],
        ]);

        return $this->successResponse(
            data: [
                'media_id' => $media->id,
                'upload_url' => $signedData['upload_url'],
                'method' => $signedData['method'],
                'headers' => $signedData['headers'],
                'path' => $path,
                'expires_at' => $signedData['expires_at'],
            ],
            message: 'Signed upload URL generated successfully.',
            statusCode: 201
        );
    }

    /**
     * Confirm client direct upload and trigger background processing queue.
     */
    public function confirmUpload(ConfirmUploadRequest $request): JsonResponse
    {
        $user = $request->user();
        $media = Media::where('id', $request->input('media_id'))
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Validate that the file actually exists in storage
        if (! $this->storageService->exists($media->original_path)) {
            return $this->errorResponse('Uploaded file was not found in storage. Please upload before confirming.', 422);
        }

        // Dispatch background processing job to MEDIA queue
        $this->queueService->dispatchMedia(new ProcessMediaJob($media));

        return $this->successResponse(
            data: $media->fresh()->toResponseArray(),
            message: 'Upload confirmed and queued for processing.'
        );
    }

    /**
     * Direct multipart upload for API/Mobile clients.
     */
    public function directUpload(DirectUploadRequest $request): JsonResponse
    {
        $user = $request->user();
        $file = $request->file('file');
        $collection = $request->input('collection');
        $entityId = $request->input('entity_id');

        /** @var MediaStorageService $storage */
        $storage = $this->storageService;
        $path = $storage->generatePath(
            userId: $user->id,
            collection: $collection,
            extension: $file->getClientOriginalExtension(),
            entityId: $entityId
        );

        $disk = $this->storageService->getDisk();
        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()), 'public');

        $media = Media::create([
            'user_id' => $user->id,
            'collection' => $collection,
            'disk' => $disk,
            'original_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'processing_status' => 'pending',
            'metadata' => [
                'original_filename' => $file->getClientOriginalName(),
                'upload_method' => 'multipart',
            ],
        ]);

        // Dispatch background processing job to MEDIA queue
        $this->queueService->dispatchMedia(new ProcessMediaJob($media));

        return $this->successResponse(
            data: $media->toResponseArray(),
            message: 'Media uploaded successfully and queued for processing.',
            statusCode: 201
        );
    }

    /**
     * Retrieve media metadata, processing status, and URLs or stream binary bytes.
     */
    public function show(Request $request, int $id): mixed
    {
        $media = Media::findOrFail($id);

        if ($request->wantsJson() && ! $request->has('stream')) {
            return $this->successResponse(
                data: $media->toResponseArray(),
                message: 'Media retrieved successfully.'
            );
        }

        return app(MessageController::class)->viewAttachment($request, $id);
    }

    /**
     * Internal endpoint handling signed local uploads during testing/fallback.
     */
    public function handleLocalSignedUpload(Request $request): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return $this->errorResponse('Invalid or expired upload signature.', 403);
        }

        $path = base64_decode($request->query('path'));
        $content = $request->getContent();

        $disk = config('filesystems.default', 'public');
        Storage::disk($disk)->put($path, $content, 'public');

        return $this->successResponse(
            data: ['path' => $path],
            message: 'File stored successfully via signed URL.'
        );
    }
}
