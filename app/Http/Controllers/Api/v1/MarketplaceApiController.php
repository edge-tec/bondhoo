<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceProduct;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

class MarketplaceApiController extends Controller
{
    public function __construct(
        protected MarketplaceService $marketplaceService
    ) {}

    /**
     * Get list of active products with search, category, location, price, and condition filters.
     */
    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user('sanctum') ?? $request->user('web');

        $filters = [
            'category' => $request->query('category'),
            'q' => $request->query('q'),
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'location' => $request->query('location'),
            'condition' => $request->query('condition'),
            'sort' => $request->query('sort', 'newest'),
        ];

        $perPage = min(50, max(1, (int) $request->query('per_page', 16)));
        $paginator = $this->marketplaceService->getLatestListings($viewer, $filters, $perPage);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Retrieve featured listings.
     */
    public function featured(Request $request): JsonResponse
    {
        $viewer = $request->user('sanctum') ?? $request->user('web');
        $limit = min(20, max(1, (int) $request->query('limit', 8)));
        $featured = $this->marketplaceService->getFeaturedListings($viewer, $limit);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $featured,
        ]);
    }

    /**
     * Get marketplace categories with actual counts.
     */
    public function categories(): JsonResponse
    {
        $categories = $this->marketplaceService->getTopCategoriesWithCounts();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Display single product details with related items.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $viewer = $request->user('sanctum') ?? $request->user('web');

        $product = MarketplaceProduct::with(['seller.profile', 'category'])->findOrFail($id);

        if (! Gate::forUser($viewer)->allows('view', $product)) {
            return response()->json([
                'success' => false,
                'message' => 'This listing is not accessible.',
            ], 403);
        }

        $product->increment('views_count');

        $related = MarketplaceProduct::with(['seller.profile', 'category'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', MarketplaceProduct::STATUS_ACTIVE)
            ->latest('id')
            ->take(4)
            ->get()
            ->map(fn (MarketplaceProduct $p) => $p->toResponseArray($viewer))
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => array_merge($product->toResponseArray($viewer), [
                'related_products' => $related,
            ]),
        ]);
    }

    /**
     * Create product listing.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:marketplace_categories,id',
            'title' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:10000',
            'price' => 'required|numeric|min:0|max:100000000',
            'currency' => 'nullable|string|max:5',
            'condition' => 'nullable|string|in:new,used,refurbished',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'is_negotiable' => 'nullable|boolean',
            'delivery_type' => 'nullable|string|in:pickup,delivery,both',
            'delivery_charge' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'images.*' => 'string',
        ]);

        $product = $this->marketplaceService->createProduct($user, $validated);

        return response()->json([
            'success' => true,
            'data' => $product->toResponseArray($user),
            'message' => 'Listing created successfully.',
        ], 201);
    }

    /**
     * Update product listing.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $product = MarketplaceProduct::findOrFail($id);

        if (! Gate::forUser($user)->allows('update', $product)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:marketplace_categories,id',
            'title' => 'sometimes|string|min:3|max:255',
            'description' => 'nullable|string|max:10000',
            'price' => 'sometimes|numeric|min:0',
            'currency' => 'nullable|string|max:5',
            'condition' => 'nullable|string|in:new,used,refurbished',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'is_negotiable' => 'nullable|boolean',
            'delivery_type' => 'nullable|string|in:pickup,delivery,both',
            'delivery_charge' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'status' => 'nullable|string|in:active,sold,inactive,archived',
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'data' => $product->fresh(['seller.profile', 'category'])->toResponseArray($user),
            'message' => 'Listing updated successfully.',
        ]);
    }

    /**
     * Delete product listing.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $product = MarketplaceProduct::findOrFail($id);

        if (! Gate::forUser($user)->allows('delete', $product)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $this->marketplaceService->deleteListing($product, $user);

        return response()->json([
            'success' => true,
            'message' => 'Listing deleted successfully.',
        ]);
    }

    /**
     * Update listing status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $product = MarketplaceProduct::findOrFail($id);

        if (! Gate::forUser($user)->allows('changeStatus', $product)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'status' => 'required|string|in:active,sold,inactive,archived',
        ]);

        $product = $this->marketplaceService->updateListingStatus($product, $request->input('status'), $user);

        return response()->json([
            'success' => true,
            'data' => $product->fresh(['seller.profile', 'category'])->toResponseArray($user),
            'message' => 'Listing status updated.',
        ]);
    }

    /**
     * Toggle favorite/saved listing for the viewer.
     */
    public function toggleFavorite(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $product = MarketplaceProduct::findOrFail($id);
        $isFavorited = $this->marketplaceService->toggleFavorite($user, $product);

        return response()->json([
            'success' => true,
            'is_favorited' => $isFavorited,
            'message' => $isFavorited ? 'Listing saved to favorites.' : 'Listing removed from favorites.',
        ]);
    }

    /**
     * Get user's favorited listings.
     */
    public function favorites(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $perPage = min(50, max(1, (int) $request->query('per_page', 16)));
        $paginator = $this->marketplaceService->getSavedListings($user, $perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Contact seller and initiate/resume conversation.
     */
    public function contact(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $product = MarketplaceProduct::findOrFail($id);

        try {
            $result = $this->marketplaceService->contactSeller(
                $user,
                $product,
                $request->input('message')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Conversation opened with seller.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Report listing for moderation.
     */
    public function report(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'reason' => 'required|string|max:100',
            'details' => 'nullable|string|max:1000',
        ]);

        $product = MarketplaceProduct::findOrFail($id);

        $report = $this->marketplaceService->reportListing(
            $user,
            $product,
            $request->input('reason'),
            $request->input('details')
        );

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully. Our safety team will review it.',
            'report_id' => $report->id,
        ]);
    }

    /**
     * Direct upload for listing photos.
     */
    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:15360'],
            'file' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:15360'],
        ]);

        $file = $request->file('image') ?? $request->file('file');
        if (! $file) {
            return response()->json(['success' => false, 'message' => 'No image file uploaded.'], 422);
        }

        $path = $file->store('marketplace/products', 'public');
        $url = '/storage/'.$path;

        return response()->json([
            'success' => true,
            'url' => $url,
            'path' => $path,
            'message' => 'Image uploaded successfully.',
        ]);
    }

    /**
     * Get seller's own listings.
     */
    public function myListings(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $status = $request->query('status');
        $perPage = min(50, max(1, (int) $request->query('per_page', 16)));
        $paginator = $this->marketplaceService->getUserListings($user, $status, $perPage);

        $paginator->getCollection()->transform(function (MarketplaceProduct $p) use ($user) {
            return $p->toResponseArray($user);
        });

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
