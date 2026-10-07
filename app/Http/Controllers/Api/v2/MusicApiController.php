<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\MusicTrack;
use App\Services\MusicService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MusicApiController
 *
 * REST API for search, preview, and selection of background audio tracks for Reels and Stories.
 */
class MusicApiController extends Controller
{
    public function __construct(
        protected MusicService $musicService
    ) {}

    /**
     * Search and list approved tracks.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'query' => $request->input('query'),
            'genre' => $request->input('genre'),
            'language' => $request->input('language'),
            'sort' => $request->input('sort', 'trending'),
        ];

        $paginator = $this->musicService->searchTracks($filters, (int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map(fn (MusicTrack $t) => $t->toResponseArray()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Get available genres.
     */
    public function genres(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->musicService->getGenres(),
        ]);
    }

    /**
     * Show single track details.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $track = $this->musicService->getTrack($id);

            return response()->json([
                'success' => true,
                'data' => $track->toResponseArray(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Admin: Ingest new music track.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'artist' => ['required', 'string', 'max:120'],
            'album' => ['nullable', 'string', 'max:120'],
            'audio_url' => ['required', 'string', 'url'],
            'cover_url' => ['nullable', 'string', 'url'],
            'duration' => ['nullable', 'numeric', 'min:1'],
            'genre' => ['nullable', 'string', 'max:50'],
            'language' => ['nullable', 'string', 'max:30'],
            'tags' => ['nullable', 'array'],
            'bpm' => ['nullable', 'integer', 'min:30', 'max:300'],
            'license_type' => ['nullable', 'string', 'max:50'],
            'license_holder' => ['nullable', 'string', 'max:150'],
        ]);

        $track = $this->musicService->createTrack($validated);

        return response()->json([
            'success' => true,
            'message' => 'Music track created successfully.',
            'data' => $track->toResponseArray(),
        ], 201);
    }

    /**
     * Admin: Delete music track.
     */
    public function destroy(MusicTrack $track): JsonResponse
    {
        $this->musicService->deleteTrack($track);

        return response()->json([
            'success' => true,
            'message' => 'Music track deleted successfully.',
        ]);
    }
}
