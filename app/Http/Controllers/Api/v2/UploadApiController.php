<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\MediaUploadSession;
use App\Services\Media\ChunkedUploadService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * চাঙ্কড ও রেজুমেবল আপলোড এপিআই কন্ট্রোলার:
 * বড় মিডিয়া ফাইল টুকরো টুকরো করে আপলোড, রিট্রাই, রেজুম এবং মেমরি-সেফ অ্যাসেম্বলি করে।
 */
class UploadApiController extends Controller
{
    public function __construct(
        protected ChunkedUploadService $uploadService
    ) {}

    /**
     * নতুন আপলোড সেশন শুরু করা
     */
    public function init(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'file_size' => ['required', 'integer', 'min:1'],
            'mime_type' => ['required', 'string', 'max:120'],
            'collection' => ['nullable', 'string', 'in:story,story_photo,story_video,reel,post,general'],
            'chunk_size' => ['nullable', 'integer', 'min:1', 'max:10485760'], // 1 byte to 10MB
            'checksum' => ['nullable', 'string', 'size:64'],
            'metadata' => ['nullable', 'array'],
        ]);

        try {
            $session = $this->uploadService->initSession($request->user(), $validated);

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $session->session_id,
                    'chunk_size' => $session->chunk_size,
                    'total_chunks' => $session->total_chunks,
                    'filename' => $session->original_name,
                    'expires_at' => $session->expires_at->toIso8601String(),
                ],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * নির্দিষ্ট চাঙ্ক আপলোড করা
     */
    public function uploadChunk(Request $request, ?string $sessionId = null): JsonResponse
    {
        $request->validate([
            'session_id' => ['nullable', 'string', 'max:64'],
            'chunk_number' => ['required', 'integer', 'min:1'],
            'chunk' => ['required', 'file'],
            'checksum' => ['nullable', 'string', 'size:64'],
        ]);

        $effectiveSessionId = $sessionId ?: (string) $request->input('session_id');
        if (empty($effectiveSessionId)) {
            return response()->json(['success' => false, 'message' => 'Session ID is required.'], 422);
        }

        $chunkNumber = (int) $request->input('chunk_number');
        $chunkFile = $request->file('chunk');
        $clientChecksum = $request->input('checksum');

        try {
            $result = $this->uploadService->uploadChunk(
                $request->user(),
                $effectiveSessionId,
                $chunkNumber,
                $chunkFile,
                $clientChecksum
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ক্লায়েন্ট কর্তৃক আপলোড সম্পন্ন নিশ্চিতকরণ (অ্যাসেম্বলি)
     */
    public function complete(Request $request, string $sessionId): JsonResponse
    {
        try {
            $media = $this->uploadService->completeSession($request->user(), $sessionId);

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $sessionId,
                    'media' => $media->toResponseArray(),
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ইন্টারাপ্টেড আপলোড সেশন রেজুম করার তথ্য পাওয়া
     */
    public function resume(Request $request, string $sessionId): JsonResponse
    {
        try {
            $status = $this->uploadService->resumeSession($request->user(), $sessionId);

            return response()->json([
                'success' => true,
                'data' => $status,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * আপলোড সেশন বাতিল করা
     */
    public function cancel(Request $request, string $sessionId): JsonResponse
    {
        $success = $this->uploadService->cancelSession($request->user(), $sessionId);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Upload session cancelled successfully.' : 'Failed to cancel upload session.',
        ]);
    }

    /**
     * সেশনের বর্তমান স্ট্যাটাস চেক
     */
    public function status(Request $request, string $sessionId): JsonResponse
    {
        $session = MediaUploadSession::where('session_id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->session_id,
                'status' => $session->status,
                'uploaded_chunks_count' => $session->uploaded_chunks_count,
                'total_chunks' => $session->total_chunks,
                'progress' => $session->total_chunks > 0 ? round(($session->uploaded_chunks_count / $session->total_chunks) * 100, 1) : 0,
            ],
        ]);
    }
}
