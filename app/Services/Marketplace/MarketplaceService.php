<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceDispute;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceReview;
use App\Models\Report;
use App\Models\SavedItem;
use App\Models\User;
use App\Services\Marketplace\Payments\PaymentGatewayManager;
use App\Services\MessengerService;
use App\Services\NotificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MarketplaceService
{
    /**
     * List marketplace categories.
     *
     * @return Collection<int, MarketplaceCategory>
     */
    public function getCategories(): Collection
    {
        return MarketplaceCategory::orderBy('display_order')->get();
    }

    /**
     * Get categories with actual count of active products.
     *
     * @return Collection<int, MarketplaceCategory>
     */
    public function getTopCategoriesWithCounts(): Collection
    {
        return MarketplaceCategory::withCount([
            'products' => function ($q) {
                $q->where('status', MarketplaceProduct::STATUS_ACTIVE);
            },
        ])->orderBy('display_order')->get();
    }

    /**
     * Retrieve featured listings for the hero carousel/grid.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFeaturedListings(?User $viewer = null, int $limit = 8): array
    {
        $products = MarketplaceProduct::with(['seller.profile', 'category'])
            ->where('status', MarketplaceProduct::STATUS_ACTIVE)
            ->orderByDesc('is_boosted')
            ->orderByDesc('views_count')
            ->latest('id')
            ->take($limit)
            ->get();

        return $products->map(fn (MarketplaceProduct $p) => $p->toResponseArray($viewer))->all();
    }

    /**
     * Query latest listings with comprehensive filtering and sorting.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getLatestListings(?User $viewer = null, array $filters = [], int $perPage = 16): LengthAwarePaginator
    {
        $query = MarketplaceProduct::with(['seller.profile', 'category'])
            ->where('status', MarketplaceProduct::STATUS_ACTIVE);

        // Filter by category slug or ID
        if (! empty($filters['category']) && $filters['category'] !== 'all') {
            $cat = $filters['category'];
            $query->whereHas('category', function ($q) use ($cat) {
                if (is_numeric($cat)) {
                    $q->where('id', (int) $cat);
                } else {
                    $q->where('slug', $cat);
                }
            });
        }

        // Full-text or title/description search
        if (! empty($filters['q'])) {
            $search = trim((string) $filters['q']);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Price range
        if (isset($filters['min_price']) && is_numeric($filters['min_price']) && (float) $filters['min_price'] > 0) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price']) && (float) $filters['max_price'] > 0) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        // Location filter
        if (! empty($filters['location'])) {
            $loc = trim((string) $filters['location']);
            $query->where('location', 'like', "%{$loc}%");
        }

        // Condition filter (new, used)
        if (! empty($filters['condition']) && in_array($filters['condition'], ['new', 'used', 'refurbished'], true)) {
            $query->where('condition', $filters['condition']);
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'price_asc', 'price_low' => $query->orderBy('price', 'asc'),
            'price_desc', 'price_high' => $query->orderBy('price', 'desc'),
            'popular' => $query->orderByDesc('views_count')->latest('id'),
            default => $query->latest('id'),
        };

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function (MarketplaceProduct $p) use ($viewer) {
            return $p->toResponseArray($viewer);
        });

        return $paginator;
    }

    /**
     * Create product listing with AI fraud screening.
     *
     * @param  array<string, mixed>  $data
     */
    public function createProduct(User $seller, array $data): MarketplaceProduct
    {
        $fraudScore = $this->calculateFraudScore($seller, $data);

        $images = is_array($data['images'] ?? null) ? array_values(array_filter($data['images'])) : [];

        return MarketplaceProduct::create([
            'seller_id' => $seller->id,
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'currency' => $data['currency'] ?? 'BDT',
            'condition' => $data['condition'] ?? 'used',
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            'is_negotiable' => (bool) ($data['is_negotiable'] ?? false),
            'delivery_type' => $data['delivery_type'] ?? 'pickup',
            'delivery_charge' => $data['delivery_charge'] ?? 0.00,
            'location' => $data['location'] ?? 'Dhaka, Bangladesh',
            'status' => MarketplaceProduct::STATUS_ACTIVE,
            'images' => $images,
            'variants' => $data['variants'] ?? [],
            'ai_fraud_score' => $fraudScore,
            'is_boosted' => (bool) ($data['is_boosted'] ?? false),
        ]);
    }

    /**
     * Toggle saved/favorite status of a product for the user.
     */
    public function toggleFavorite(User $user, MarketplaceProduct $product): bool
    {
        $existing = SavedItem::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('item_type', 'marketplace_product')
                    ->orWhere('item_type', MarketplaceProduct::class);
            })
            ->where('item_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        SavedItem::create([
            'user_id' => $user->id,
            'item_type' => 'marketplace_product',
            'item_id' => $product->id,
            'collection_name' => 'Marketplace Favorites',
        ]);

        return true;
    }

    /**
     * Get user's saved/favorited marketplace listings.
     */
    public function getSavedListings(User $user, int $perPage = 16): LengthAwarePaginator
    {
        $savedIds = SavedItem::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('item_type', 'marketplace_product')
                    ->orWhere('item_type', MarketplaceProduct::class);
            })
            ->pluck('item_id');

        $paginator = MarketplaceProduct::with(['seller.profile', 'category'])
            ->whereIn('id', $savedIds)
            ->latest('id')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function (MarketplaceProduct $p) use ($user) {
            return $p->toResponseArray($user);
        });

        return $paginator;
    }

    /**
     * Start conversation with seller regarding a product.
     *
     * @return array{conversation_id: int, seller_id: int, message_sent: bool}
     */
    public function contactSeller(User $buyer, MarketplaceProduct $product, ?string $initialMessage = null): array
    {
        if ($buyer->id === $product->seller_id) {
            throw new InvalidArgumentException('You cannot message yourself about your own product.');
        }

        $messenger = app(MessengerService::class);
        $conversation = $messenger->getOrCreateDirectConversation($buyer, $product->seller_id);

        $messageSent = false;
        $body = trim((string) $initialMessage);
        if ($body === '') {
            $body = "Hi, I am interested in your listing: '{$product->title}'. Is this still available?";
        }

        if ($conversation) {
            try {
                $messenger->sendMessage($buyer, $conversation, [
                    'body' => $body,
                ]);
                $messageSent = true;
            } catch (\Throwable) {
                // Ignore if message sending encounters throttling, conversation still opened
            }
        }

        // Notify seller about buyer interest
        try {
            app(NotificationService::class)->send(
                $product->seller_id,
                'marketplace_inquiry',
                [
                    'title' => 'New Marketplace Inquiry',
                    'message' => "{$buyer->name} sent an inquiry about {$product->title}.",
                    'product_id' => $product->id,
                    'buyer_id' => $buyer->id,
                    'conversation_id' => $conversation->id,
                ]
            );
        } catch (\Throwable) {
            // Notification failsafe
        }

        return [
            'conversation_id' => $conversation->id,
            'seller_id' => $product->seller_id,
            'message_sent' => $messageSent,
        ];
    }

    /**
     * Report an offensive or fraudulent marketplace listing.
     */
    public function reportListing(User $reporter, MarketplaceProduct $product, string $reason, ?string $details = null): Report
    {
        return Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => MarketplaceProduct::class,
            'reportable_id' => $product->id,
            'reason' => $reason,
            'details' => $details,
            'status' => Report::STATUS_PENDING,
        ]);
    }

    /**
     * Place order with secure payment hold.
     *
     * @param  array<string, mixed>|string  $shippingOrData
     */
    public function placeOrder(User $buyer, MarketplaceProduct $product, array|string $shippingOrData, string $paymentMethod = 'bkash'): MarketplaceOrder
    {
        return DB::transaction(function () use ($buyer, $product, $shippingOrData, $paymentMethod) {
            if ($product->status !== MarketplaceProduct::STATUS_ACTIVE) {
                throw new InvalidArgumentException('Product is not available for purchase.');
            }

            if ($product->seller_id === $buyer->id) {
                throw new InvalidArgumentException('You cannot purchase your own product.');
            }

            // Normalize shipping/order input data
            $data = is_array($shippingOrData) ? $shippingOrData : [
                'shipping_address' => $shippingOrData,
                'payment_method' => $paymentMethod,
            ];

            $deliveryType = (string) ($data['delivery_type'] ?? MarketplaceOrder::DELIVERY_STANDARD);
            $shippingAddress = trim((string) ($data['shipping_address'] ?? ''));

            if ($deliveryType === MarketplaceOrder::DELIVERY_PICKUP && $shippingAddress === '') {
                $shippingAddress = 'Store Pickup — '.($product->location ?: 'Dhaka, Bangladesh');
            } elseif ($shippingAddress === '') {
                throw new InvalidArgumentException('Shipping address is required.');
            }

            $method = (string) ($data['payment_method'] ?? $paymentMethod ?: 'bkash');
            $quantity = max(1, (int) ($data['quantity'] ?? 1));
            $unitPrice = (float) $product->price;
            $subtotal = round($unitPrice * $quantity, 2);

            // Server-side delivery charge calculation
            $baseDelivery = max(0, (float) ($product->delivery_charge ?? 0.00));

            $deliveryCharge = match ($deliveryType) {
                MarketplaceOrder::DELIVERY_PICKUP => 0.00,
                MarketplaceOrder::DELIVERY_EXPRESS => round($baseDelivery > 0 ? ($baseDelivery * 2) : 120.00, 2),
                default => round($baseDelivery, 2),
            };

            $discountAmount = 0.00;
            $totalAmount = round($subtotal + $deliveryCharge - $discountAmount, 2);

            // Product snapshot at time of checkout
            $snapshot = [
                'id' => $product->id,
                'title' => $product->title,
                'price' => (float) $product->price,
                'currency' => $product->currency ?: 'BDT',
                'condition' => $product->condition,
                'brand' => $product->brand,
                'model' => $product->model,
                'category_name' => $product->category?->name,
                'category_id' => $product->category_id,
                'seller_name' => $product->seller?->name,
                'images' => $product->images ?: [],
                'delivery_type' => $product->delivery_type,
                'delivery_charge' => (float) ($product->delivery_charge ?: 0),
            ];

            $idempotencyKey = $data['idempotency_key'] ?? null;
            if ($idempotencyKey) {
                $existingOrder = MarketplaceOrder::where('buyer_id', $buyer->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();
                if ($existingOrder) {
                    return $existingOrder;
                }
            }

            $order = MarketplaceOrder::create([
                'order_number' => MarketplaceOrder::generateOrderNumber(),
                'buyer_id' => $buyer->id,
                'seller_id' => $product->seller_id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'delivery_type' => $deliveryType,
                'delivery_charge' => $deliveryCharge,
                'discount_amount' => $discountAmount,
                'amount' => $totalAmount,
                'currency' => $product->currency ?: 'BDT',
                'shipping_name' => $data['shipping_name'] ?? $buyer->name,
                'shipping_phone' => $data['shipping_phone'] ?? null,
                'shipping_address' => $shippingAddress,
                'product_snapshot' => $snapshot,
                'status' => MarketplaceOrder::STATUS_PENDING,
                'payment_status' => MarketplaceOrder::PAYMENT_UNPAID,
                'escrow_status' => MarketplaceOrder::ESCROW_HELD,
                'payment_method' => $method,
                'idempotency_key' => $idempotencyKey,
            ]);

            // Notify seller about new order
            try {
                app(NotificationService::class)->send(
                    $product->seller_id,
                    'marketplace_order_placed',
                    [
                        'title' => 'নতুন অর্ডার পাওয়া গেছে',
                        'message' => "{$buyer->name} আপনার '{$product->title}' পণ্যটির জন্য একটি নতুন অর্ডার করেছেন (#{$order->order_number})।",
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'product_id' => $product->id,
                    ]
                );
            } catch (\Throwable) {
                // Failsafe
            }

            return $order;
        });
    }

    /**
     * Release payment hold / escrow to seller after delivery.
     */
    public function releaseEscrow(MarketplaceOrder $order, ?User $actor = null): MarketplaceOrder
    {
        return DB::transaction(function () use ($order, $actor) {
            $locked = MarketplaceOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($actor && ! $locked->canReleaseEscrow($actor)) {
                throw new AuthorizationException('শুধুমাত্র ক্রেতা বা অ্যাডমিন এসক্রো রিলিজ করতে পারেন।');
            }

            if ($locked->escrow_status === MarketplaceOrder::ESCROW_RELEASED) {
                return $locked;
            }

            $locked->update([
                'status' => MarketplaceOrder::STATUS_DELIVERED,
                'escrow_status' => MarketplaceOrder::ESCROW_RELEASED,
                'escrow_released_at' => now(),
            ]);

            try {
                app(NotificationService::class)->send(
                    $locked->seller_id,
                    'marketplace_escrow_released',
                    [
                        'title' => 'পেমেন্ট রিলিজ সম্পন্ন হয়েছে',
                        'message' => "অর্ডার #{$locked->order_number} এর জন্য {$locked->amount} {$locked->currency} সফলভাবে রিলিজ করা হয়েছে।",
                        'order_id' => $locked->id,
                        'order_number' => $locked->order_number,
                    ]
                );
            } catch (\Throwable) {
                // Failsafe
            }

            return $locked;
        });
    }

    /**
     * Get user orders by role (buyer or seller) with optional status, search, and date filters.
     */
    public function getUserOrders(
        User $user,
        string $role = 'buyer',
        ?string $status = null,
        int $perPage = 15,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ) {
        $query = MarketplaceOrder::with(['product.category', 'buyer.profile', 'seller.profile', 'review', 'disputes']);

        if ($role === 'seller') {
            $query->where('seller_id', $user->id);
        } else {
            $query->where('buyer_id', $user->id);
        }

        if ($status && $status !== 'all') {
            if ($status === 'active') {
                $query->whereIn('status', [
                    MarketplaceOrder::STATUS_PROCESSING,
                    MarketplaceOrder::STATUS_CONFIRMED,
                    MarketplaceOrder::STATUS_SHIPPED,
                ]);
            } elseif ($status === 'unpaid') {
                $query->where('payment_status', MarketplaceOrder::PAYMENT_UNPAID)
                    ->whereNotIn('status', [MarketplaceOrder::STATUS_CANCELLED]);
            } elseif ($status === 'completed') {
                $query->whereIn('status', [
                    MarketplaceOrder::STATUS_DELIVERED,
                    MarketplaceOrder::STATUS_COMPLETED,
                ]);
            } elseif ($status === 'refunds_disputes') {
                $query->where(function ($q) {
                    $q->whereIn('status', [
                        MarketplaceOrder::STATUS_REFUND_REQUESTED,
                        MarketplaceOrder::STATUS_REFUNDED,
                        MarketplaceOrder::STATUS_DISPUTED,
                    ])->orWhereNotNull('dispute_status');
                });
            } else {
                $query->where('status', $status);
            }
        }

        if ($search = trim((string) $search)) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('shipping_name', 'like', "%{$search}%")
                    ->orWhere('tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('title', 'like', "%{$search}%");
                    });
            });
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Get seller sales summary from real verified records.
     *
     * @return array<string, mixed>
     */
    public function getSellerSalesSummary(User $seller): array
    {
        $orders = MarketplaceOrder::where('seller_id', $seller->id)->get();

        $grossSales = $orders->whereNotIn('status', [MarketplaceOrder::STATUS_CANCELLED, MarketplaceOrder::STATUS_REFUNDED])
            ->whereIn('payment_status', [MarketplaceOrder::PAYMENT_PAID, MarketplaceOrder::PAYMENT_AUTHORIZED])
            ->sum('amount');

        $netEarnings = $orders->whereIn('status', [MarketplaceOrder::STATUS_DELIVERED, MarketplaceOrder::STATUS_COMPLETED])
            ->where('escrow_status', MarketplaceOrder::ESCROW_RELEASED)
            ->sum('amount');

        $pendingSettlements = $orders->where('escrow_status', MarketplaceOrder::ESCROW_HELD)
            ->whereIn('payment_status', [MarketplaceOrder::PAYMENT_PAID, MarketplaceOrder::PAYMENT_AUTHORIZED])
            ->sum('amount');

        $refundedAmount = $orders->where('status', MarketplaceOrder::STATUS_REFUNDED)
            ->sum('refund_amount');

        return [
            'total_orders' => $orders->count(),
            'active_orders' => $orders->whereIn('status', [MarketplaceOrder::STATUS_PROCESSING, MarketplaceOrder::STATUS_CONFIRMED, MarketplaceOrder::STATUS_SHIPPED])->count(),
            'completed_orders' => $orders->whereIn('status', [MarketplaceOrder::STATUS_DELIVERED, MarketplaceOrder::STATUS_COMPLETED])->count(),
            'cancelled_orders' => $orders->where('status', MarketplaceOrder::STATUS_CANCELLED)->count(),
            'disputed_orders' => $orders->where('status', MarketplaceOrder::STATUS_DISPUTED)->count(),
            'gross_sales' => round((float) $grossSales, 2),
            'net_earnings' => round((float) $netEarnings, 2),
            'pending_settlements' => round((float) $pendingSettlements, 2),
            'refunded_amount' => round((float) $refundedAmount, 2),
            'currency' => 'BDT',
        ];
    }

    /**
     * Get user listings (as a seller).
     */
    public function getUserListings(User $seller, ?string $status = null, int $perPage = 15)
    {
        $query = MarketplaceProduct::where('seller_id', $seller->id)
            ->with(['category'])
            ->withCount('orders');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Update order fulfillment status by seller or admin (e.g. processing, shipped, delivered).
     */
    public function updateOrderStatus(MarketplaceOrder $order, string $status, User $actor): MarketplaceOrder
    {
        if ($order->seller_id !== $actor->id && ! ($actor->is_admin ?? false)) {
            throw new AuthorizationException('শুধুমাত্র বিক্রেতা বা অ্যাডমিন অর্ডারের স্ট্যাটাস পরিবর্তন করতে পারেন।');
        }

        $validStatuses = [
            MarketplaceOrder::STATUS_PENDING,
            MarketplaceOrder::STATUS_PROCESSING,
            MarketplaceOrder::STATUS_CONFIRMED,
            MarketplaceOrder::STATUS_SHIPPED,
            MarketplaceOrder::STATUS_DELIVERED,
            MarketplaceOrder::STATUS_CANCELLED,
        ];
        if (! in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException('অবৈধ অর্ডার স্ট্যাটাস।');
        }

        $order->update([
            'status' => $status,
            'shipped_at' => $status === MarketplaceOrder::STATUS_SHIPPED ? ($order->shipped_at ?: now()) : $order->shipped_at,
            'delivered_at' => $status === MarketplaceOrder::STATUS_DELIVERED ? ($order->delivered_at ?: now()) : $order->delivered_at,
        ]);

        return $order->fresh(['product', 'buyer', 'seller']);
    }

    /**
     * Seller marks order as shipped with carrier tracking details.
     *
     * @param  array{carrier?: string, tracking_number?: string}  $tracking
     */
    public function shipOrder(MarketplaceOrder $order, User $seller, array $tracking = []): MarketplaceOrder
    {
        if (! $order->canBeShippedBy($seller)) {
            throw new AuthorizationException('আপনি এই অর্ডারটি শিপ করার জন্য অনুমোদিত নন বা অর্ডারটির স্ট্যাটাস উপযুক্ত নয়।');
        }

        $order->update([
            'status' => MarketplaceOrder::STATUS_SHIPPED,
            'shipped_at' => now(),
            'tracking_carrier' => $tracking['carrier'] ?? null,
            'tracking_number' => $tracking['tracking_number'] ?? null,
        ]);

        try {
            app(NotificationService::class)->send(
                $order->buyer_id,
                'marketplace_order_shipped',
                [
                    'title' => 'অর্ডার শিপ করা হয়েছে',
                    'message' => "আপনার অর্ডার #{$order->order_number} বিক্রেতা শিপ করেছেন। ট্র্যাকিং: ".($tracking['tracking_number'] ?? 'N/A'),
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]
            );
        } catch (\Throwable) {
            // Failsafe
        }

        return $order->fresh(['product', 'buyer', 'seller']);
    }

    /**
     * Buyer or admin confirms order delivery.
     */
    public function confirmDelivery(MarketplaceOrder $order, User $actor): MarketplaceOrder
    {
        if ($order->buyer_id !== $actor->id && ! ($actor->is_admin ?? false)) {
            throw new AuthorizationException('শুধুমাত্র ক্রেতা বা অ্যাডমিন ডেলিভারি নিশ্চিত করতে পারেন।');
        }

        if (! in_array($order->status, [MarketplaceOrder::STATUS_SHIPPED, MarketplaceOrder::STATUS_PROCESSING, MarketplaceOrder::STATUS_CONFIRMED], true)) {
            throw new InvalidArgumentException("Order in status '{$order->status}' cannot be marked as delivered.");
        }

        $order->update([
            'status' => MarketplaceOrder::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        try {
            app(NotificationService::class)->send(
                $order->seller_id,
                'marketplace_delivery_confirmed',
                [
                    'title' => 'ডেলিভারি নিশ্চিত হয়েছে',
                    'message' => "অর্ডার #{$order->order_number} এর ক্রেতা পণ্য ডেলিভারি গ্রহণ নিশ্চিত করেছেন।",
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]
            );
        } catch (\Throwable) {
            // Failsafe
        }

        return $order->fresh(['product', 'buyer', 'seller']);
    }

    /**
     * Cancel order with validated permissions and reason.
     */
    public function cancelOrder(MarketplaceOrder $order, User $actor, string $reason): MarketplaceOrder
    {
        if (! $order->canBeCancelledBy($actor)) {
            throw new AuthorizationException('আপনি এই অর্ডারটি বাতিল করতে পারবেন না। শিপড বা ডেলিভারড অর্ডার সরাসরি বাতিল করা যায় না।');
        }

        $order->update([
            'status' => MarketplaceOrder::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $actor->id,
            'cancellation_reason' => $reason,
        ]);

        $recipientId = ($actor->id === $order->buyer_id) ? $order->seller_id : $order->buyer_id;
        try {
            app(NotificationService::class)->send(
                $recipientId,
                'marketplace_order_cancelled',
                [
                    'title' => 'অর্ডার বাতিল হয়েছে',
                    'message' => "অর্ডার #{$order->order_number} বাতিল করা হয়েছে। কারণ: {$reason}",
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]
            );
        } catch (\Throwable) {
            // Failsafe
        }

        return $order->fresh(['product', 'buyer', 'seller']);
    }

    /**
     * Raise a dispute for an order.
     *
     * @param  array{reason: string, description?: string, evidence_photos?: array}  $data
     */
    public function raiseDispute(MarketplaceOrder $order, User $buyer, array $data): MarketplaceDispute
    {
        if (! $order->canBeDisputedBy($buyer)) {
            throw new AuthorizationException('আপনি এই অর্ডারের জন্য বিরোধ দায়ের করার উপযুক্ত নন।');
        }

        $reason = trim((string) ($data['reason'] ?? 'Item not as described'));
        $description = trim((string) ($data['description'] ?? ''));

        return DB::transaction(function () use ($order, $buyer, $reason, $description, $data) {
            $dispute = MarketplaceDispute::create([
                'order_id' => $order->id,
                'raised_by' => $buyer->id,
                'reason' => $reason,
                'details' => $description ?: $reason,
                'evidence_files' => $data['evidence_photos'] ?? ($data['evidence_files'] ?? []),
                'status' => MarketplaceDispute::STATUS_OPEN,
            ]);

            $order->update([
                'status' => MarketplaceOrder::STATUS_DISPUTED,
                'disputed_at' => now(),
                'dispute_reason' => $reason,
                'dispute_status' => MarketplaceDispute::STATUS_OPEN,
                'dispute_notes' => $description,
            ]);

            try {
                app(NotificationService::class)->send(
                    $order->seller_id,
                    'marketplace_dispute_opened',
                    [
                        'title' => 'অর্ডার বিরোধ দায়ের করা হয়েছে',
                        'message' => "অর্ডার #{$order->order_number} এর জন্য ক্রেতা বিরোধ ফাইল করেছেন। আমাদের সহায়তা দল বিষয়টি পর্যালোচনা করছে।",
                        'order_id' => $order->id,
                        'dispute_id' => $dispute->id,
                    ]
                );
            } catch (\Throwable) {
                // Failsafe
            }

            return $dispute;
        });
    }

    /**
     * Admin resolves a marketplace dispute.
     *
     * @param  array{notes?: string, refund_amount?: float}  $data
     */
    public function resolveDispute(MarketplaceDispute $dispute, User $admin, string $resolution, array $data = []): MarketplaceDispute
    {
        if (! $admin->isAdmin()) {
            throw new AuthorizationException('শুধুমাত্র অ্যাডমিন বিরোধ নিষ্পত্তি করতে পারেন।');
        }

        $validResolutions = [
            MarketplaceDispute::STATUS_REFUNDED,
            MarketplaceDispute::STATUS_RELEASED,
            MarketplaceDispute::STATUS_REJECTED,
        ];
        if (! in_array($resolution, $validResolutions, true)) {
            throw new InvalidArgumentException('Invalid dispute resolution status.');
        }

        return DB::transaction(function () use ($dispute, $admin, $resolution, $data) {
            $order = $dispute->order;

            if ($resolution === MarketplaceDispute::STATUS_REFUNDED) {
                $refundAmount = (float) ($data['refund_amount'] ?? $order->amount);
                $paymentManager = app(PaymentGatewayManager::class);
                $paymentManager->processRefund($order, $refundAmount, 'Dispute resolved in favor of buyer: '.($data['notes'] ?? ''), $admin);
                $order->update([
                    'dispute_status' => MarketplaceDispute::STATUS_REFUNDED,
                ]);
            } elseif ($resolution === MarketplaceDispute::STATUS_RELEASED) {
                $this->releaseEscrow($order, $admin);
                $order->update([
                    'dispute_status' => MarketplaceDispute::STATUS_RELEASED,
                ]);
            } else {
                $order->update([
                    'status' => MarketplaceOrder::STATUS_DELIVERED,
                    'dispute_status' => MarketplaceDispute::STATUS_REJECTED,
                ]);
            }

            $dispute->update([
                'status' => $resolution,
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
                'admin_notes' => $data['notes'] ?? null,
            ]);

            return $dispute->fresh(['order', 'buyer', 'seller', 'resolver']);
        });
    }

    /**
     * Update product listing status (active, sold, inactive).
     */
    public function updateListingStatus(MarketplaceProduct $product, string $status, User $seller): MarketplaceProduct
    {
        if ($product->seller_id !== $seller->id && ! ($seller->is_admin ?? false)) {
            throw new AuthorizationException('শুধুমাত্র বিক্রেতা লিস্টিং স্ট্যাটাস পরিবর্তন করতে পারেন।');
        }

        $validStatuses = ['active', 'sold', 'inactive', 'archived'];
        if (! in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException('অবৈধ লিস্টিং স্ট্যাটাস।');
        }

        $product->update(['status' => $status]);

        return $product;
    }

    /**
     * Delete product listing.
     */
    public function deleteListing(MarketplaceProduct $product, User $seller): bool
    {
        if ($product->seller_id !== $seller->id && ! ($seller->is_admin ?? false)) {
            throw new AuthorizationException('শুধুমাত্র বিক্রেতা লিস্টিং মুছে ফেলতে পারেন।');
        }

        return (bool) $product->delete();
    }

    /**
     * Leave rating and review for an eligible purchased product.
     */
    public function leaveReview(User $buyer, MarketplaceProduct $product, int $rating, ?string $comment = null, ?MarketplaceOrder $order = null, bool $enforceOrder = false): MarketplaceReview
    {
        $rating = max(1, min(5, $rating));

        // If strict order verification is enforced or an order is passed
        $eligibleOrder = $order;
        if (! $eligibleOrder) {
            $eligibleOrder = MarketplaceOrder::where('buyer_id', $buyer->id)
                ->where('product_id', $product->id)
                ->whereIn('status', [MarketplaceOrder::STATUS_DELIVERED, MarketplaceOrder::STATUS_COMPLETED])
                ->latest('id')
                ->first();
        }

        if ($enforceOrder && ! $eligibleOrder) {
            throw new InvalidArgumentException('Only verified buyers of delivered orders can review this product.');
        }

        $review = MarketplaceReview::updateOrCreate(
            [
                'product_id' => $product->id,
                'buyer_id' => $buyer->id,
            ],
            [
                'order_id' => $eligibleOrder?->id,
                'seller_id' => $product->seller_id,
                'rating' => $rating,
                'comment' => $comment,
            ]
        );

        // Recalculate seller rating and reviews count
        $sellerReviews = MarketplaceReview::where('seller_id', $product->seller_id)->get();
        if ($sellerReviews->isNotEmpty() && $product->seller) {
            $product->seller->update([
                'seller_rating' => round($sellerReviews->avg('rating'), 2),
                'seller_reviews_count' => $sellerReviews->count(),
            ]);
        }

        return $review;
    }

    /**
     * Initiate payment gateway checkout via PaymentGatewayManager.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function initiateCheckout(MarketplaceOrder $order, string $gateway = 'bkash', array $options = [], ?string $ip = null): array
    {
        $manager = app(PaymentGatewayManager::class);

        return $manager->initiateCheckout($order, $gateway, $options, $ip);
    }

    /**
     * Verify payment transaction callback from payment gateway via PaymentGatewayManager.
     *
     * @param  string|array<string, mixed>  $trxIdOrPayload
     */
    public function verifyPaymentCallback(MarketplaceOrder $order, string|array $trxIdOrPayload, string $status = 'COMPLETED', ?string $ip = null): MarketplaceOrder
    {
        $manager = app(PaymentGatewayManager::class);

        $payload = is_array($trxIdOrPayload) ? $trxIdOrPayload : [
            'transaction_id' => $trxIdOrPayload,
            'payment_status' => $status,
        ];

        if (! isset($payload['payment_status'])) {
            $payload['payment_status'] = $status;
        }

        $result = $manager->processCallback($order, $payload, $ip);

        if (! $result['verified']) {
            throw new InvalidArgumentException('Payment verification failed.');
        }

        return $result['order'];
    }

    /**
     * Calculate AI fraud score based on user age, price anomaly, and keywords.
     *
     * @param  array<string, mixed>  $data
     */
    protected function calculateFraudScore(User $seller, array $data): float
    {
        $score = 0.05;

        if ($seller->created_at && $seller->created_at->diffInDays(now()) < 2) {
            $score += 0.35;
        }

        if (($data['price'] ?? 0) < 10) {
            $score += 0.20;
        }

        $text = strtolower(($data['title'] ?? '').' '.($data['description'] ?? ''));
        if (str_contains($text, 'urgent money') || str_contains($text, 'advance bkash') || str_contains($text, 'lottery')) {
            $score += 0.30;
        }

        return min(1.0, round($score, 2));
    }
}
