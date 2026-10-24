<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceDispute;
use App\Models\MarketplaceOrder;
use App\Models\MarketplacePaymentTransaction;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceReview;
use App\Models\Report;
use App\Models\User;
use App\Services\Marketplace\MarketplaceService;
use App\Services\Marketplace\Payments\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminMarketplaceController extends Controller
{
    public function __construct(
        protected MarketplaceService $marketplaceService,
        protected PaymentGatewayManager $paymentManager
    ) {}

    /**
     * Check admin authorization.
     */
    protected function authorizeAdmin(Request $request): User
    {
        $user = $request->user();
        if (! $user || (method_exists($user, 'isAdmin') ? ! $user->isAdmin() : ! ($user->is_admin ?? false))) {
            abort(403, 'Unauthorized. Administrator access required.');
        }

        return $user;
    }

    /**
     * List marketplace listings for moderation.
     */
    public function listings(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $query = MarketplaceProduct::with(['seller.profile', 'category'])
            ->withCount(['orders', 'reviews']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        $listings = $query->latest('id')->paginate(20);

        return response()->json(['status' => 'success', 'data' => $listings]);
    }

    /**
     * Suspend or restore a product listing.
     */
    public function moderateListing(Request $request, int $id): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);

        $request->validate([
            'status' => 'required|string|in:active,inactive,archived',
            'reason' => 'nullable|string|max:500',
        ]);

        $product = MarketplaceProduct::findOrFail($id);
        $oldStatus = $product->status;
        $newStatus = $request->input('status');

        $product->update(['status' => $newStatus]);

        Log::info("Admin #{$admin->id} changed MarketplaceProduct #{$product->id} status from {$oldStatus} to {$newStatus}. Reason: ".$request->input('reason'));

        return response()->json([
            'status' => 'success',
            'message' => "Listing status successfully updated to {$newStatus}.",
            'data' => $product,
        ]);
    }

    /**
     * List marketplace reports.
     */
    public function reports(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $reports = Report::with(['reporter.profile'])
            ->where('reportable_type', MarketplaceProduct::class)
            ->latest('id')
            ->paginate(20);

        return response()->json(['status' => 'success', 'data' => $reports]);
    }

    /**
     * Resolve or dismiss a marketplace report.
     */
    public function resolveReport(Request $request, int $id): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);

        $request->validate([
            'status' => 'required|string|in:resolved,dismissed',
            'notes' => 'nullable|string|max:1000',
        ]);

        $report = Report::where('reportable_type', MarketplaceProduct::class)->findOrFail($id);
        $report->update([
            'status' => $request->input('status'),
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
            'notes' => $request->input('notes'),
        ]);

        return response()->json(['status' => 'success', 'data' => $report]);
    }

    /**
     * Inspect all orders with search and filtering.
     */
    public function orders(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $query = MarketplaceOrder::with(['buyer.profile', 'seller.profile', 'product.category']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhere('transaction_id', 'like', "%{$search}%");
            });
        }

        $orders = $query->latest('id')->paginate(20);

        return response()->json(['status' => 'success', 'data' => $orders]);
    }

    /**
     * Order detail lookup with full audit history.
     */
    public function orderDetail(Request $request, int $id): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);

        $order = MarketplaceOrder::with([
            'buyer.profile',
            'seller.profile',
            'product.category',
            'paymentTransactions',
            'dispute.buyer.profile',
            'dispute.seller.profile',
            'reviews',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $order->toResponseArray($admin),
        ]);
    }

    /**
     * View immutable payment transactions audit ledger.
     */
    public function paymentTransactions(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $query = MarketplacePaymentTransaction::with(['order.buyer.profile', 'order.seller.profile']);

        if ($gateway = $request->query('gateway')) {
            $query->where('payment_gateway', $gateway);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $query->where('transaction_type', $type);
        }

        $transactions = $query->latest('id')->paginate(30);

        return response()->json(['status' => 'success', 'data' => $transactions]);
    }

    /**
     * List all marketplace disputes.
     */
    public function disputes(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $query = MarketplaceDispute::with(['order.product', 'buyer.profile', 'seller.profile', 'resolver.profile']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $disputes = $query->latest('id')->paginate(20);

        return response()->json(['status' => 'success', 'data' => $disputes]);
    }

    /**
     * Resolve a customer dispute.
     */
    public function resolveDispute(Request $request, int $id): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);

        $request->validate([
            'resolution' => 'required|string|in:refunded,released_to_seller,rejected',
            'notes' => 'nullable|string|max:1000',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        $dispute = MarketplaceDispute::with('order')->findOrFail($id);

        $dispute = $this->marketplaceService->resolveDispute(
            $dispute,
            $admin,
            $request->input('resolution'),
            $request->all()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Dispute resolved successfully.',
            'data' => $dispute,
        ]);
    }

    /**
     * Process administrative refund for an order.
     */
    public function refundOrder(Request $request, int $id): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);

        $request->validate([
            'amount' => 'nullable|numeric|min:1',
            'reason' => 'required|string|max:500',
        ]);

        $order = MarketplaceOrder::findOrFail($id);
        $amount = (float) ($request->input('amount') ?: $order->amount);

        try {
            $result = $this->paymentManager->processRefund($order, $amount, $request->input('reason'), $admin);

            return response()->json([
                'status' => 'success',
                'message' => 'Refund processed and recorded in financial audit ledger.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Moderate or delete a product review.
     */
    public function moderateReview(Request $request, int $id): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);

        $review = MarketplaceReview::with('product.seller')->findOrFail($id);
        $product = $review->product;

        $review->delete();

        // Recalculate seller rating after deletion
        if ($product && $product->seller) {
            $sellerReviews = MarketplaceReview::where('seller_id', $product->seller_id)->get();
            $product->seller->update([
                'seller_rating' => $sellerReviews->isNotEmpty() ? round($sellerReviews->avg('rating'), 2) : 0.0,
                'seller_reviews_count' => $sellerReviews->count(),
            ]);
        }

        Log::info("Admin #{$admin->id} deleted MarketplaceReview #{$id}");

        return response()->json(['status' => 'success', 'message' => 'Review removed successfully.']);
    }
}
