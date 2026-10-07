<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class MarketplaceWebController extends Controller
{
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
}
