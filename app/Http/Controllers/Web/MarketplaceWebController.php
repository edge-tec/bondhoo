<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class MarketplaceWebController extends Controller
{
    public function __construct(
        protected MarketplaceService $marketplaceService
    ) {}

    protected function resolveUser(Request $request): ?User
    {
        $user = $request->user();
        if (! $user && $request->hasCookie('jugajug_token')) {
            $rawToken = (string) $request->cookie('jugajug_token');
            $pat = PersonalAccessToken::findToken($rawToken);
            if ($pat && $pat->tokenable instanceof User) {
                $user = $pat->tokenable;
                auth('web')->login($user);
            }
        }

        return $user;
    }

    /**
     * Display the Marketplace hub with search, category filtering and products grid.
     */
    public function index(Request $request): View
    {
        $user = $this->resolveUser($request);
        $categories = MarketplaceCategory::orderBy('display_order')->get();

        $query = MarketplaceProduct::with(['seller.profile', 'category'])
            ->where('status', 'active');

        if ($catSlug = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $catSlug));
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($minPrice = $request->query('min_price')) {
            $query->where('price', '>=', (float) $minPrice);
        }

        if ($maxPrice = $request->query('max_price')) {
            $query->where('price', '<=', (float) $maxPrice);
        }

        $sort = $request->query('sort', 'latest');
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } else {
            $query->latest('id');
        }

        $products = $query->paginate(16)->withQueryString();

        return view('marketplace.index', [
            'currentUser' => $user,
            'categories' => $categories,
            'products' => $products,
            'activeCategory' => $catSlug,
            'searchQuery' => $search,
            'currentSort' => $sort,
        ]);
    }

    /**
     * Display a specific product details page with seller & escrow info.
     */
    public function show(Request $request, int $id): View
    {
        $user = $this->resolveUser($request);

        $product = MarketplaceProduct::with(['seller.profile', 'category'])
            ->findOrFail($id);

        // Increment view count safely
        $product->increment('views_count');

        $similarProducts = MarketplaceProduct::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->limit(4)
            ->get();

        return view('marketplace.show', [
            'currentUser' => $user,
            'product' => $product,
            'similarProducts' => $similarProducts,
        ]);
    }

    /**
     * Display buyer/seller orders & escrow management center.
     */
    public function orders(Request $request): View|Response|RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->guest('/login');
        }

        $activeTab = $request->query('tab', 'purchases');
        $status = $request->query('status');
        $search = $request->query('q') ?: $request->query('search');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $purchases = $this->marketplaceService->getUserOrders($user, 'buyer', $activeTab === 'purchases' ? $status : null, 15, $search, $dateFrom, $dateTo);
        $sales = $this->marketplaceService->getUserOrders($user, 'seller', $activeTab === 'sales' ? $status : null, 15, $search, $dateFrom, $dateTo);
        $summary = $this->marketplaceService->getSellerSalesSummary($user);

        if ($request->query('blade')) {
            return view('marketplace.orders', [
                'currentUser' => $user,
                'activeTab' => $activeTab,
                'purchases' => $purchases,
                'sales' => $sales,
                'currentStatus' => $status,
            ]);
        }

        return Inertia::render('Marketplace/Orders', [
            'initialPurchases' => $purchases,
            'initialSales' => $sales,
            'initialSummary' => $summary,
            'currentTab' => $activeTab,
            'currentStatus' => $status,
            'searchQuery' => $search,
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'is_admin' => $user->isAdmin(),
            ],
        ]);
    }

    /**
     * Display single order invoice / tracking page.
     */
    public function orderDetails(Request $request, int $id): View|Response|RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->guest('/login');
        }

        $order = MarketplaceOrder::with(['buyer.profile', 'seller.profile', 'product.category', 'paymentTransactions', 'dispute.buyer.profile', 'dispute.seller.profile', 'review'])
            ->findOrFail($id);

        if ($order->buyer_id !== $user->id && $order->seller_id !== $user->id && ! ($user->isAdmin())) {
            abort(403, 'এই অর্ডারের বিবরণ দেখার অনুমতি আপনার নেই।');
        }

        if ($request->query('blade')) {
            return view('marketplace.order-details', [
                'currentUser' => $user,
                'order' => $order,
                'isBuyer' => $order->buyer_id === $user->id,
                'isSeller' => $order->seller_id === $user->id,
            ]);
        }

        return Inertia::render('Marketplace/OrderTracking', [
            'order' => $order->toResponseArray($user),
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'is_admin' => $user->isAdmin(),
            ],
            'isBuyer' => $order->buyer_id === $user->id,
            'isSeller' => $order->seller_id === $user->id,
        ]);
    }

    /**
     * Web endpoint: release escrow for delivered order.
     */
    public function releaseEscrowWeb(Request $request, int $id): RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->guest('/login');
        }

        $order = MarketplaceOrder::findOrFail($id);

        try {
            $this->marketplaceService->releaseEscrow($order, $user);

            return back()->with('success', 'এসক্রো সফলভাবে রিলিজ করা হয়েছে! বিক্রেতার ফান্ড আনলক করা হয়েছে।');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Web endpoint: seller updates order fulfillment status.
     */
    public function updateOrderStatusWeb(Request $request, int $id): RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->guest('/login');
        }

        $request->validate([
            'status' => 'required|string|in:pending,processing,confirmed,shipped,delivered,cancelled',
        ]);

        $order = MarketplaceOrder::findOrFail($id);

        try {
            $this->marketplaceService->updateOrderStatus($order, $request->input('status'), $user);

            return back()->with('success', 'অর্ডারের ডেলিভারি স্ট্যাটাস সফলভাবে আপডেট করা হয়েছে।');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Web endpoint: seller ships order with tracking details.
     */
    public function shipOrderWeb(Request $request, int $id): RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->guest('/login');
        }

        $request->validate([
            'carrier' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        $order = MarketplaceOrder::findOrFail($id);

        try {
            $this->marketplaceService->shipOrder($order, $user, $request->only(['carrier', 'tracking_number']));

            return back()->with('success', 'অর্ডার সফলভাবে শিপ করা হয়েছে!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Web endpoint: buyer confirms order delivery.
     */
    public function confirmDeliveryWeb(Request $request, int $id): RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->guest('/login');
        }

        $order = MarketplaceOrder::findOrFail($id);

        try {
            $this->marketplaceService->confirmDelivery($order, $user);

            return back()->with('success', 'ডেলিভারি সফলভাবে নিশ্চিত করা হয়েছে।');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Web endpoint: cancel order.
     */
    public function cancelOrderWeb(Request $request, int $id): RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->guest('/login');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $order = MarketplaceOrder::findOrFail($id);

        try {
            $this->marketplaceService->cancelOrder($order, $user, $request->input('reason'));

            return back()->with('success', 'অর্ডার বাতিল করা হয়েছে।');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Web endpoint: raise dispute.
     */
    public function disputeOrderWeb(Request $request, int $id): RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->guest('/login');
        }

        $request->validate([
            'reason' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
        ]);

        $order = MarketplaceOrder::findOrFail($id);

        try {
            $this->marketplaceService->raiseDispute($order, $user, $request->only(['reason', 'description']));

            return back()->with('success', 'অর্ডারের জন্য বিরোধ দায়ের করা হয়েছে। সহায়তা টিম এটি পর্যালোচনা করছে।');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Web endpoint: buyer leaves review for delivered order.
     */
    public function reviewOrderWeb(Request $request, int $id): RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->guest('/login');
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $order = MarketplaceOrder::with('product')->findOrFail($id);

        try {
            $this->marketplaceService->leaveReview(
                $user,
                $order->product,
                (int) $request->input('rating'),
                $request->input('comment'),
                $order,
                true
            );

            return back()->with('success', 'আপনার রিভিউ সফলভাবে প্রকাশ করা হয়েছে!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Web endpoint: view seller's own listings.
     */
    public function myListings(Request $request): View|RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->guest('/login');
        }

        $status = $request->query('status');
        $listings = $this->marketplaceService->getUserListings($user, $status);

        return view('marketplace.my-listings', [
            'currentUser' => $user,
            'listings' => $listings,
            'currentStatus' => $status,
        ]);
    }

    /**
     * Web endpoint: store new product listing.
     */
    public function storeListingWeb(Request $request): RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->guest('/login');
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:marketplace_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'condition' => 'nullable|string',
            'location' => 'nullable|string',
            'image_url' => 'nullable|url',
        ]);

        $images = [];
        if (! empty($validated['image_url'])) {
            $images[] = $validated['image_url'];
        }

        $productData = [
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'price' => $validated['price'],
            'currency' => $validated['currency'] ?? 'BDT',
            'condition' => $validated['condition'] ?? 'used',
            'location' => $validated['location'] ?? 'Dhaka, Bangladesh',
            'images' => $images,
        ];

        $product = $this->marketplaceService->createProduct($user, $productData);

        return redirect()->route('marketplace.show', ['id' => $product->id])
            ->with('success', 'আপনার পণ্যটি সফলভাবে মার্কেটপ্লেসে যুক্ত হয়েছে!');
    }

    /**
     * Web endpoint: update listing status (active, sold, inactive).
     */
    public function updateListingStatusWeb(Request $request, int $id): RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->guest('/login');
        }

        $request->validate([
            'status' => 'required|string|in:active,sold,inactive,archived',
        ]);

        $product = MarketplaceProduct::findOrFail($id);

        try {
            $this->marketplaceService->updateListingStatus($product, $request->input('status'), $user);

            return back()->with('success', 'লিস্টিংয়ের স্ট্যাটাস সফলভাবে আপডেট করা হয়েছে।');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Web endpoint: delete product listing.
     */
    public function destroyListingWeb(Request $request, int $id): RedirectResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return redirect()->guest('/login');
        }

        $product = MarketplaceProduct::findOrFail($id);

        try {
            $this->marketplaceService->deleteListing($product, $user);

            return back()->with('success', 'বিজ্ঞাপনটি সফলভাবে মুছে ফেলা হয়েছে।');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
