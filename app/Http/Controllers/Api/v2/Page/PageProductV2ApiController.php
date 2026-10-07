<?php

namespace App\Http\Controllers\Api\v2\Page;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageProduct;
use App\Services\Page\EnterprisePageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * এন্টারপ্রাইজ পেজ কমার্স ও প্রোডাক্টস কন্ট্রোলার
 */
class PageProductV2ApiController extends Controller
{
    public function __construct(
        protected EnterprisePageService $enterprisePageService
    ) {}

    /**
     * পেজের প্রোডাক্টস ক্যাটালগ তালিকা
     */
    public function index(Request $request, Page $page): JsonResponse
    {
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $products = PageProduct::where('page_id', $page->id)
            ->where('status', 'active')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * নতুন প্রোডাক্ট যোগ করা
     */
    public function store(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageSettings', $page)) {
            abort(403, 'You do not have permission to manage products for this page.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'media_urls' => ['nullable', 'array'],
            'media_urls.*' => ['string', 'url'],
            'status' => ['nullable', 'string', 'in:active,inactive,out_of_stock'],
        ]);

        $product = $this->enterprisePageService->createProduct($page, $user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Product added successfully.',
            'data' => $product,
        ], 201);
    }

    /**
     * নির্দিষ্ট প্রোডাক্টের বিবরণ
     */
    public function show(Request $request, Page $page, int $id): JsonResponse
    {
        $product = PageProduct::where('page_id', $page->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * প্রোডাক্ট আপডেট করা
     */
    public function update(Request $request, Page $page, int $id): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageSettings', $page)) {
            abort(403, 'You do not have permission to update products for this page.');
        }

        $product = PageProduct::where('page_id', $page->id)->findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', 'in:active,inactive,out_of_stock'],
        ]);

        $prev = $product->only(array_keys($validated));
        $product->update($validated);

        $this->enterprisePageService->logAudit($page, $user, 'product.update', 'PageProduct', $product->id, $prev, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => $product,
        ]);
    }

    /**
     * প্রোডাক্ট ডিলিট করা
     */
    public function destroy(Request $request, Page $page, int $id): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageSettings', $page)) {
            abort(403, 'You do not have permission to delete products for this page.');
        }

        $product = PageProduct::where('page_id', $page->id)->findOrFail($id);
        $product->delete();

        $this->enterprisePageService->logAudit($page, $user, 'product.delete', 'PageProduct', $id);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }

    /**
     * প্রোডাক্ট ক্রয় করা (কনকারেন্সি ও স্টক রেস কন্ডিশন সেফটি সহ)
     */
    public function purchase(Request $request, Page $page, int $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $qty = (int) ($validated['quantity'] ?? 1);

        try {
            $product = $this->enterprisePageService->purchaseProduct($page, $user, $id, $qty);

            return response()->json([
                'success' => true,
                'message' => 'Product purchased successfully.',
                'data' => $product,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
