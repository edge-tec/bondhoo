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
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'লগইন সেশন পাওয়া যায়নি। অনুগ্রহ করে পুনরায় লগইন করুন।',
            ], 401);
        }

        $validated = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'file_size' => ['required', 'integer', 'min:1'],
            'mime_type' => ['required', 'string', 'max:120'],
            'collection' => ['nullable', 'string', 'in:story,story_photo,story_video,reel,post,general'],
            'chunk_size' => ['nullable', 'integer', 'min:1', 'max:10485760'], // 1 byte to 10MB
            'checksum' => ['nullable', 'string', 'size:64'],
            'metadata' => ['nullable', 'array'],
        ]);

        // ক্লায়েন্ট নির্দিষ্ট সাইজ না পাঠালে সার্ভার লিমিটের সাথে সামঞ্জস্য রেখে নিরাপদ ১ মেগাবাইট ডিফল্ট
        $serverMaxBytes = $this->parseBytes(ini_get('upload_max_filesize') ?: '2M');
        $safeDefaultChunkSize = min(1048576, max(524288, (int) floor($serverMaxBytes * 0.75)));

        if (empty($validated['chunk_size'])) {
            $validated['chunk_size'] = $safeDefaultChunkSize;
        }

        try {
            $session = $this->uploadService->initSession($user, $validated);

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
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'is_unauthorized' => true,
                'message' => 'অননুমোদিত অ্যাক্সেস। অনুগ্রহ করে পুনরায় লগইন করুন।',
            ], 401);
        }

        // পিএইচপি লেভেল আপলোড এরর হ্যান্ডলিং (UPLOAD_ERR_INI_SIZE ইত্যাদি)
        if (isset($_FILES['chunk']['error']) && $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
            $errorCode = (int) $_FILES['chunk']['error'];
            $msg = match ($errorCode) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'চাঙ্ক ফাইলটি সার্ভার লিমিট (upload_max_filesize) অতিক্রম করেছে। অনুগ্রহ করে ১MB বা ছোট চাঙ্ক সাইজ ব্যবহার করুন।',
                UPLOAD_ERR_PARTIAL => 'চাঙ্ক আংশিক আপলোড হয়েছে। পুনরায় চেষ্টা করুন।',
                UPLOAD_ERR_NO_FILE => 'কোনো চাঙ্ক ফাইল পাওয়া যায়নি।',
                UPLOAD_ERR_NO_TMP_DIR => 'সার্ভার টেম্পোরারি ডিরেক্টরি অনুপস্থিত।',
                UPLOAD_ERR_CANT_WRITE => 'সার্ভার ডিস্কে চাঙ্ক লিখতে ব্যর্থ হয়েছে।',
                default => "চাঙ্ক আপলোডে সমস্যা হয়েছে (ত্রুটি কোড: {$errorCode})।",
            };

            return response()->json([
                'success' => false,
                'is_permanent' => ($errorCode === UPLOAD_ERR_INI_SIZE),
                'message' => $msg,
            ], 422);
        }

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
                $user,
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
     * পিএইচপি সাইজ স্ট্রিং বাইটে কনভার্ট করা (যেমন: 2M -> 2097152)
     */
    protected function parseBytes(string $val): int
    {
        $val = trim($val);
        if (empty($val)) {
            return 2097152;
        }
        $last = strtolower($val[strlen($val) - 1]);
        $num = (int) $val;

        return match ($last) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => (int) $val,
        };
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
