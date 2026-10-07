<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Story;
use App\Services\Contracts\MediaStorageServiceInterface;
use App\Services\StoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * স্টোরি কন্ট্রোলার:
 * ২৪ ঘণ্টার স্টোরি তৈরি, ভিউ রেকর্ড এবং সক্রিয় স্টোরি ট্রে প্রদর্শন করে।
 */
class StoryController extends Controller
{
    public function __construct(
        protected StoryService $storyService,
        protected MediaStorageServiceInterface $storageService
    ) {}

    /**
     * সক্রিয় স্টোরিগুলো সংগ্রহ করে ইউজার অনুযায়ী গ্রুপ করে রিটার্ন করে।
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $feed = $this->storyService->getActiveFeedStories($user);

        return $this->successResponse(
            data: $feed,
            message: 'Active stories retrieved successfully.'
        );
    }

    /**
     * নতুন স্টোরি তৈরি করা।
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // Support aliases from web form ('photo' -> 'media', 'caption' -> 'content')
        $input = $request->all();
        if (($input['type'] ?? '') === 'photo') {
            $input['type'] = Story::TYPE_MEDIA;
        }
        if (! isset($input['content']) && isset($input['caption'])) {
            $input['content'] = $input['caption'];
        }

        $validated = validator($input, [
            'type' => ['nullable', 'string', 'in:text,media'],
            'content' => ['nullable', 'string', 'max:1000'],
            'background_color' => ['nullable', 'string', 'max:30'],
            'privacy' => ['nullable', 'string', 'in:public,friends,only_me'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
            'media' => ['nullable', 'file', 'image', 'max:20480'],
            'file' => ['nullable', 'file', 'image', 'max:20480'],
        ])->validate();

        $mediaIds = $validated['media_ids'] ?? [];

        // Direct file upload support
        $uploadedFile = $request->file('media') ?? $request->file('file');
        if ($uploadedFile && $uploadedFile->isValid()) {
            $disk = $this->storageService->getDisk();
            $path = $this->storageService->generatePath($user->id, 'story', $uploadedFile->getClientOriginalExtension());
            Storage::disk($disk)->put($path, file_get_contents($uploadedFile->getRealPath()), 'public');

            $media = Media::create([
                'user_id' => $user->id,
                'collection' => 'story',
                'disk' => $disk,
                'original_path' => $path,
                'mime_type' => $uploadedFile->getMimeType(),
                'size' => $uploadedFile->getSize(),
                'checksum' => hash_file('sha256', $uploadedFile->getRealPath()),
                'processing_status' => 'completed',
                'metadata' => [
                    'original_filename' => $uploadedFile->getClientOriginalName(),
                ],
            ]);

            $mediaIds[] = $media->id;
            $validated['type'] = Story::TYPE_MEDIA;
        }

        $validated['media_ids'] = $mediaIds;

        $story = $this->storyService->createStory($user, $validated);

        return $this->successResponse(
            data: $story->toResponseArray($user),
            message: 'স্টোরি সফলভাবে প্রকাশিত হয়েছে।',
            statusCode: 201
        );
    }

    /**
     * স্টোরি ভিউ রেকর্ড করা।
     */
    public function view(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $story = Story::findOrFail($id);

        $recorded = $this->storyService->recordView($story, $user);

        return $this->successResponse(
            data: [
                'story_id' => $story->id,
                'view_recorded' => $recorded,
                'views_count' => $story->fresh()->views_count,
            ],
            message: 'Story view updated.'
        );
    }

    /**
     * স্টোরির ভিউয়ারদের তালিকা (শুধুমাত্র স্টোরির লেখক দেখতে পারবেন)।
     */
    public function views(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $story = Story::findOrFail($id);

        $perPage = (int) $request->query('per_page', 20);
        $views = $this->storyService->getStoryViews($story, $user, $perPage);

        return $this->successResponse(
            data: $views->items(),
            message: 'Story viewers retrieved successfully.',
            meta: [
                'current_page' => $views->currentPage(),
                'last_page' => $views->lastPage(),
                'total' => $views->total(),
            ]
        );
    }

    /**
     * স্টোরি মুছে ফেলা।
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $story = Story::findOrFail($id);

        $this->storyService->deleteStory($story, $user);

        return $this->successResponse(
            message: 'Story deleted successfully.'
        );
    }
}
