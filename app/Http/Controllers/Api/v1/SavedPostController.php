<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\SavedCollection;
use App\Models\SavedItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavedPostController extends Controller
{
    /**
     * Get saved posts with cursor pagination and optional collection/search filter.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $collection = $request->query('collection');
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 15);

        $savedQuery = SavedItem::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('item_type', 'post')
                    ->orWhere('item_type', Post::class);
            });

        if ($collection && $collection !== 'All' && $collection !== 'All Saved Posts') {
            $savedQuery->where('collection_name', $collection);
        }

        $savedPostIds = $savedQuery->orderByDesc('id')->pluck('item_id')->toArray();

        $postsQuery = Post::with(['user.profile', 'media', 'reactions'])
            ->whereIn('id', $savedPostIds);

        if ($search) {
            $postsQuery->where('content', 'like', "%{$search}%");
        }

        $posts = $postsQuery->orderByDesc('id')->cursorPaginate($perPage);

        return $this->successResponse(
            data: collect($posts->items())->map(fn (Post $p) => $p->toResponseArray($user))->values(),
            message: 'Saved posts retrieved successfully.',
            meta: [
                'per_page' => $posts->perPage(),
                'has_more' => $posts->hasMorePages(),
                'next_cursor' => $posts->nextCursor()?->encode(),
                'prev_cursor' => $posts->previousCursor()?->encode(),
            ]
        );
    }

    /**
     * Save a post to 'All Saved Posts' or a specific collection.
     */
    public function save(Request $request, int $postId): JsonResponse
    {
        $user = $request->user();
        $post = Post::findOrFail($postId);

        $collectionName = (string) $request->input('collection', 'All Saved Posts');

        $saved = SavedItem::firstOrCreate([
            'user_id' => $user->id,
            'item_type' => 'post',
            'item_id' => $post->id,
        ], [
            'collection_name' => $collectionName,
        ]);

        if ($saved->wasRecentlyCreated === false && $saved->collection_name !== $collectionName) {
            $saved->update(['collection_name' => $collectionName]);
        }

        return $this->successResponse(
            data: [
                'is_saved' => true,
                'collection' => $saved->collection_name,
                'post_id' => $post->id,
            ],
            message: 'Post saved successfully.'
        );
    }

    /**
     * Unsave a post.
     */
    public function unsave(Request $request, int $postId): JsonResponse
    {
        $user = $request->user();

        SavedItem::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('item_type', 'post')
                    ->orWhere('item_type', Post::class);
            })
            ->where('item_id', $postId)
            ->delete();

        return $this->successResponse(
            data: [
                'is_saved' => false,
                'post_id' => $postId,
            ],
            message: 'Post unsaved successfully.'
        );
    }

    /**
     * List user collections with post count.
     */
    public function collections(Request $request): JsonResponse
    {
        $user = $request->user();

        $customCollections = SavedCollection::where('user_id', $user->id)->get();

        $savedCounts = SavedItem::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('item_type', 'post')
                    ->orWhere('item_type', Post::class);
            })
            ->select('collection_name', DB::raw('count(*) as count'))
            ->groupBy('collection_name')
            ->pluck('count', 'collection_name')
            ->toArray();

        $totalSavedCount = array_sum($savedCounts);

        $defaultCollection = [
            'id' => 0,
            'name' => 'All Saved Posts',
            'description' => 'Default collection for all saved posts',
            'posts_count' => $totalSavedCount,
            'is_default' => true,
        ];

        $list = collect([$defaultCollection]);

        foreach ($customCollections as $col) {
            $list->push([
                'id' => $col->id,
                'name' => $col->name,
                'description' => $col->description,
                'posts_count' => $savedCounts[$col->name] ?? 0,
                'is_default' => false,
            ]);
        }

        return $this->successResponse(
            data: $list->values(),
            message: 'Collections retrieved successfully.'
        );
    }

    /**
     * Create a new collection.
     */
    public function storeCollection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        if (strtolower($validated['name']) === 'all saved posts' || strtolower($validated['name']) === 'all items') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create collection with reserved default name.',
            ], 422);
        }

        $collection = SavedCollection::firstOrCreate([
            'user_id' => $user->id,
            'name' => $validated['name'],
        ], [
            'description' => $validated['description'] ?? null,
        ]);

        return $this->successResponse(
            data: $collection,
            message: 'Collection created successfully.',
            statusCode: 201
        );
    }

    /**
     * Rename a collection.
     */
    public function updateCollection(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $collection = SavedCollection::where('user_id', $user->id)->findOrFail($id);

        $oldName = $collection->name;
        $newName = $validated['name'];

        DB::transaction(function () use ($user, $collection, $oldName, $newName, $validated) {
            $collection->update([
                'name' => $newName,
                'description' => $validated['description'] ?? $collection->description,
            ]);

            SavedItem::where('user_id', $user->id)
                ->where('collection_name', $oldName)
                ->update(['collection_name' => $newName]);
        });

        return $this->successResponse(
            data: $collection->fresh(),
            message: 'Collection updated successfully.'
        );
    }

    /**
     * Delete a collection.
     */
    public function destroyCollection(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $collection = SavedCollection::where('user_id', $user->id)->findOrFail($id);

        DB::transaction(function () use ($user, $collection) {
            // Move items to 'All Saved Posts'
            SavedItem::where('user_id', $user->id)
                ->where('collection_name', $collection->name)
                ->update(['collection_name' => 'All Saved Posts']);

            $collection->delete();
        });

        return $this->successResponse(
            data: null,
            message: 'Collection deleted successfully.'
        );
    }

    /**
     * Add post to collection.
     */
    public function addToCollection(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'post_id' => ['required', 'integer', 'exists:posts,id'],
        ]);

        $user = $request->user();
        $collection = SavedCollection::where('user_id', $user->id)->findOrFail($id);

        $item = SavedItem::updateOrCreate([
            'user_id' => $user->id,
            'item_type' => 'post',
            'item_id' => $validated['post_id'],
        ], [
            'collection_name' => $collection->name,
        ]);

        return $this->successResponse(
            data: $item,
            message: "Post added to collection {$collection->name}."
        );
    }

    /**
     * Remove post from collection.
     */
    public function removeFromCollection(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'post_id' => ['required', 'integer', 'exists:posts,id'],
        ]);

        $user = $request->user();
        $collection = SavedCollection::where('user_id', $user->id)->findOrFail($id);

        SavedItem::where('user_id', $user->id)
            ->where('item_id', $validated['post_id'])
            ->where('collection_name', $collection->name)
            ->update(['collection_name' => 'All Saved Posts']);

        return $this->successResponse(
            data: null,
            message: "Post removed from collection {$collection->name}."
        );
    }
}
