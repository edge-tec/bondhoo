<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceV2Controller extends Controller
{
    protected MarketplaceService $marketplaceService;

    public function __construct(MarketplaceService $marketplaceService)
    {
        $this->marketplaceService = $marketplaceService;
    }

    /**
     * List categories.
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->marketplaceService->getCategories(),
        ]);
    }

    /**
     * List products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MarketplaceProduct::with(['seller', 'category'])->where('status', 'active');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('q')) {
            $q = $request->input('q');
            $query->where('title', 'like', "%{$q}%");
        }

        $products = $query->latest('id')->paginate(20);

        return response()->json(['status' => 'success', 'data' => $products]);
    }

    /**
     * Create product listing.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:marketplace_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'condition' => 'nullable|string',
            'location' => 'nullable|string',
            'images' => 'nullable|array',
            'variants' => 'nullable|array',
        ]);

        $product = $this->marketplaceService->createProduct($request->user(), $validated);

        return response()->json(['status' => 'success', 'data' => $product], 201);
    }

    /**
     * Place order with Escrow.
     */
    public function order(int $productId, Request $request): JsonResponse
    {
        $request->validate([
            'shipping_address' => 'required|string',
            'payment_method' => 'nullable|string',
        ]);

        $product = MarketplaceProduct::findOrFail($productId);
        $order = $this->marketplaceService->placeOrder(
            $request->user(),
            $product,
            $request->input('shipping_address'),
            $request->input('payment_method', 'bkash')
        );

        return response()->json(['status' => 'success', 'data' => $order], 201);
    }

    /**
     * Release escrow.
     */
    public function releaseEscrow(int $orderId, Request $request): JsonResponse
    {
        $order = MarketplaceOrder::where('buyer_id', $request->user()->id)->findOrFail($orderId);
        $order = $this->marketplaceService->releaseEscrow($order);

        return response()->json(['status' => 'success', 'data' => $order]);
    }

    /**
     * Initiate payment checkout for an order.
     */
    public function checkout(int $orderId, Request $request): JsonResponse
    {
        $order = MarketplaceOrder::where('buyer_id', $request->user()->id)->findOrFail($orderId);
        $gateway = $request->input('gateway', 'bkash');

        $session = $this->marketplaceService->initiateCheckout($order, $gateway);

        return response()->json(['status' => 'success', 'data' => $session]);
    }

    /**
     * Verify payment callback for an order.
     */
    public function verifyPayment(int $orderId, Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'payment_status' => 'required|string|in:COMPLETED,FAILED,CANCELLED',
        ]);

        $order = MarketplaceOrder::where('buyer_id', $request->user()->id)->findOrFail($orderId);

        try {
            $order = $this->marketplaceService->verifyPaymentCallback(
                $order,
                $request->input('transaction_id'),
                $request->input('payment_status')
            );

            return response()->json(['status' => 'success', 'data' => $order]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }
}
