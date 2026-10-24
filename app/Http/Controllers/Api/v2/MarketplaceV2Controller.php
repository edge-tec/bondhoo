<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Admin\AdminMarketplaceController;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceDispute;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceReview;
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
    public function order(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address' => 'nullable|string|max:500',
            'shipping_name' => 'nullable|string|max:150',
            'shipping_phone' => 'nullable|string|max:30',
            'delivery_type' => 'nullable|string|in:standard,express,pickup',
            'quantity' => 'nullable|integer|min:1|max:100',
            'payment_method' => 'nullable|string|in:bkash,nagad,sslcommerz,card,sandbox',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        $product = MarketplaceProduct::findOrFail($id);

        try {
            $isDuplicate = ! empty($validated['idempotency_key'])
                && MarketplaceOrder::where('idempotency_key', $validated['idempotency_key'])->exists();

            $order = $this->marketplaceService->placeOrder(
                $request->user(),
                $product,
                $validated,
                $request->input('payment_method', 'bkash')
            );

            $status = $isDuplicate ? 200 : 201;

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => 'Order created successfully.',
                'data' => $order,
                'order' => $order,
            ], $status);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Release escrow / payment hold.
     */
    public function releaseEscrow(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $order = MarketplaceOrder::where(function ($q) use ($user) {
            $q->where('buyer_id', $user->id);
            if ($user->is_admin ?? false) {
                $q->orWhere('id', '>', 0);
            }
        })->findOrFail($id);

        try {
            $order = $this->marketplaceService->releaseEscrow($order, $user);

            return response()->json(['status' => 'success', 'data' => $order]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * Initiate payment checkout for an order.
     */
    public function checkout(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $order = MarketplaceOrder::where('buyer_id', $user->id)->findOrFail($id);
        $gateway = $request->input('gateway', 'bkash');

        try {
            $session = $this->marketplaceService->initiateCheckout($order, $gateway, [], $request->ip());

            return response()->json(['status' => 'success', 'data' => $session]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Verify payment callback for an order.
     */
    public function verifyPayment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'payment_status' => 'required|string|in:COMPLETED,FAILED,CANCELLED,SUCCESS,PAID',
            'amount' => 'nullable|numeric',
            'currency' => 'nullable|string',
            'signature' => 'nullable|string',
        ]);

        $user = $request->user();
        $order = MarketplaceOrder::where(function ($q) use ($user) {
            $q->where('buyer_id', $user->id);
            if ($user->is_admin ?? false) {
                $q->orWhere('id', '>', 0);
            }
        })->findOrFail($id);

        try {
            $order = $this->marketplaceService->verifyPaymentCallback(
                $order,
                $request->all(),
                $request->input('payment_status'),
                $request->ip()
            );

            return response()->json(['status' => 'success', 'data' => $order]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get user orders (as buyer or seller).
     */
    public function orders(Request $request): JsonResponse
    {
        $role = $request->query('role', 'buyer');
        $status = $request->query('status');
        $search = $request->query('q') ?: $request->query('search');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));

        $orders = $this->marketplaceService->getUserOrders(
            $request->user(),
            $role,
            $status,
            $perPage,
            $search,
            $dateFrom,
            $dateTo
        );

        $summary = null;
        if ($role === 'seller') {
            $summary = $this->marketplaceService->getSellerSalesSummary($request->user());
        }

        return response()->json([
            'status' => 'success',
            'data' => $orders,
            'summary' => $summary,
        ]);
    }

    /**
     * View a specific order.
     */
    public function showOrder(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $order = MarketplaceOrder::with(['buyer.profile', 'seller.profile', 'product.category', 'paymentTransactions', 'dispute'])
            ->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                    ->orWhere('seller_id', $user->id);
                if ($user->is_admin ?? false) {
                    $q->orWhere('id', '>', 0);
                }
            })
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $order->toResponseArray($user),
        ]);
    }

    /**
     * Update order fulfillment status by seller.
     */
    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,confirmed,shipped,delivered,cancelled',
        ]);

        $order = MarketplaceOrder::findOrFail($id);

        try {
            $order = $this->marketplaceService->updateOrderStatus($order, $request->input('status'), $request->user());

            return response()->json(['status' => 'success', 'data' => $order]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * Seller marks order as shipped.
     */
    public function shipOrder(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'carrier' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        $order = MarketplaceOrder::findOrFail($id);

        try {
            $order = $this->marketplaceService->shipOrder($order, $request->user(), $request->only(['carrier', 'tracking_number']));

            return response()->json(['status' => 'success', 'data' => $order]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * Buyer confirms order delivery.
     */
    public function confirmDelivery(Request $request, int $id): JsonResponse
    {
        $order = MarketplaceOrder::findOrFail($id);

        try {
            $order = $this->marketplaceService->confirmDelivery($order, $request->user());

            return response()->json(['status' => 'success', 'data' => $order]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * Buyer or seller cancels order.
     */
    public function cancelOrder(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $order = MarketplaceOrder::findOrFail($id);

        try {
            $order = $this->marketplaceService->cancelOrder($order, $request->user(), $request->input('reason'));

            return response()->json(['status' => 'success', 'data' => $order]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * Buyer raises dispute for order.
     */
    public function disputeOrder(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'evidence_photos' => 'nullable|array',
            'evidence_photos.*' => 'string',
        ]);

        $order = MarketplaceOrder::findOrFail($id);

        try {
            $dispute = $this->marketplaceService->raiseDispute($order, $request->user(), $request->all());

            return response()->json(['status' => 'success', 'data' => $dispute], 201);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * Buyer submits review for delivered purchase.
     */
    public function reviewOrder(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $order = MarketplaceOrder::with('product')->findOrFail($id);
        $user = $request->user();

        if ($order->buyer_id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized. Only the buyer of this order can submit a review.'], 403);
        }

        if (! $order->canBeReviewedBy($user)) {
            return response()->json(['status' => 'error', 'message' => 'This order is not eligible for review.'], 422);
        }

        // Prevent duplicate review for same order
        if (MarketplaceReview::where('order_id', $order->id)->where('buyer_id', $user->id)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'You have already reviewed this purchase.'], 422);
        }

        try {
            $review = $this->marketplaceService->leaveReview(
                $user,
                $order->product,
                (int) $request->input('rating'),
                $request->input('comment'),
                $order,
                true // enforce verified purchase of delivered order
            );

            return response()->json(['status' => 'success', 'data' => $review], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * Get seller sales financial summary.
     */
    public function sellerSummary(Request $request): JsonResponse
    {
        $summary = $this->marketplaceService->getSellerSalesSummary($request->user());

        return response()->json(['status' => 'success', 'data' => $summary]);
    }

    /**
     * View audit ledger transactions for an order.
     */
    public function orderTransactions(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $order = MarketplaceOrder::where(function ($q) use ($user) {
            $q->where('buyer_id', $user->id)
                ->orWhere('seller_id', $user->id);
            if ($user->is_admin ?? false) {
                $q->orWhere('id', '>', 0);
            }
        })->findOrFail($id);

        $transactions = $order->paymentTransactions()->latest('id')->get();

        return response()->json(['status' => 'success', 'data' => $transactions]);
    }

    /**
     * Get current user's listings.
     */
    public function myListings(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $listings = $this->marketplaceService->getUserListings($request->user(), $status);

        return response()->json(['status' => 'success', 'data' => $listings]);
    }

    /**
     * Update listing status.
     */
    public function updateListingStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:active,sold,inactive,archived',
        ]);

        $product = MarketplaceProduct::findOrFail($id);
        $product = $this->marketplaceService->updateListingStatus($product, $request->input('status'), $request->user());

        return response()->json(['status' => 'success', 'data' => $product]);
    }

    /**
     * Delete listing.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $product = MarketplaceProduct::findOrFail($id);
        $this->marketplaceService->deleteListing($product, $request->user());

        return response()->json(['status' => 'success', 'message' => 'লিস্টিং সফলভাবে মুছে ফেলা হয়েছে।']);
    }

    /**
     * Toggle favorite status for a product.
     */
    public function toggleFavorite(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $product = MarketplaceProduct::findOrFail($id);
        $isFavorited = $this->marketplaceService->toggleFavorite($user, $product);

        return response()->json([
            'status' => 'success',
            'data' => [
                'product_id' => $product->id,
                'is_favorited' => $isFavorited,
            ],
            'message' => $isFavorited ? 'পণ্যটি ফেভারিটে যোগ করা হয়েছে।' : 'পণ্যটি ফেভারিট থেকে বাদ দেওয়া হয়েছে।',
        ]);
    }

    /**
     * Process order refund (strictly requires admin authorization).
     */
    public function refundOrder(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized. Only platform administrators can process refunds.'], 403);
        }

        return app(AdminMarketplaceController::class)->refundOrder($request, $id);
    }

    /**
     * Admin resolves order dispute.
     */
    public function resolveDispute(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized. Only platform administrators can resolve disputes.'], 403);
        }

        $order = MarketplaceOrder::with('disputes')->findOrFail($id);
        $dispute = $order->disputes()->latest()->first();
        if (! $dispute) {
            return response()->json(['status' => 'error', 'message' => 'No active dispute found for this order.'], 404);
        }

        $resolution = match ($request->input('resolution')) {
            'release', 'released', 'released_to_seller', 'resolved_released' => MarketplaceDispute::STATUS_RELEASED,
            'refund', 'refunded', 'resolved_refund' => MarketplaceDispute::STATUS_REFUNDED,
            default => MarketplaceDispute::STATUS_REJECTED,
        };

        $dispute = $this->marketplaceService->resolveDispute(
            $dispute,
            $user,
            $resolution,
            $request->all()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Dispute resolved successfully.',
            'data' => $dispute,
        ]);
    }

    /**
     * Submit a review for a product (requires delivered verified purchase).
     */
    public function createProductReview(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $product = MarketplaceProduct::findOrFail($id);
        $user = $request->user();

        // Check if user has an eligible delivered order for this product
        $order = MarketplaceOrder::where('buyer_id', $user->id)
            ->where('product_id', $product->id)
            ->where('status', MarketplaceOrder::STATUS_DELIVERED)
            ->whereDoesntHave('review')
            ->first();

        if (! $order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only verified buyers of delivered orders can submit a product review.',
            ], 422);
        }

        try {
            $review = $this->marketplaceService->leaveReview(
                $user,
                $product,
                (int) $request->input('rating'),
                $request->input('comment'),
                $order,
                true
            );

            return response()->json(['status' => 'success', 'data' => $review], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        }
    }
}
