<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketplaceProduct extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SOLD = 'sold';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'seller_id',
        'category_id',
        'subcategory_id',
        'title',
        'description',
        'price',
        'stock',
        'original_price',
        'discount_percent',
        'currency',
        'condition',
        'brand',
        'model',
        'is_negotiable',
        'delivery_type',
        'delivery_charge',
        'location',
        'status',
        'images',
        'variants',
        'views_count',
        'ai_fraud_score',
        'is_boosted',
    ];

    protected $appends = [
        'primary_image_url',
        'seller_name',
        'time_ago',
    ];

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $images = is_array($this->images) ? array_values(array_filter($this->images)) : [];
        $first = $images[0] ?? null;

        if (! $first) {
            return 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80';
        }

        if (str_starts_with($first, 'http://') || str_starts_with($first, 'https://')) {
            return $first;
        }

        return url($first);
    }

    public function getSellerNameAttribute(): string
    {
        return $this->seller?->name ?? 'Community Seller';
    }

    protected function casts(): array
    {
        return [
            'seller_id' => 'integer',
            'category_id' => 'integer',
            'subcategory_id' => 'integer',
            'price' => 'decimal:2',
            'stock' => 'integer',
            'original_price' => 'decimal:2',
            'discount_percent' => 'integer',
            'delivery_charge' => 'decimal:2',
            'is_negotiable' => 'boolean',
            'images' => 'array',
            'variants' => 'array',
            'views_count' => 'integer',
            'ai_fraud_score' => 'float',
            'is_boosted' => 'boolean',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'subcategory_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(MarketplaceOffer::class, 'product_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class, 'product_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MarketplaceReview::class, 'product_id');
    }

    public function isSavedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return SavedItem::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('item_type', 'marketplace_product')
                    ->orWhere('item_type', self::class);
            })
            ->where('item_id', $this->id)
            ->exists();
    }

    /**
     * Format time difference for human readability (e.g. 2 hours ago, 1 day ago).
     */
    public function getTimeAgoAttribute(): string
    {
        if (! $this->created_at) {
            return 'Just now';
        }

        $diffHours = $this->created_at->diffInHours(now());
        if ($diffHours < 1) {
            $mins = max(1, $this->created_at->diffInMinutes(now()));

            return "{$mins}m ago";
        }
        if ($diffHours < 24) {
            return "{$diffHours} hours ago";
        }
        $diffDays = $this->created_at->diffInDays(now());
        if ($diffDays < 7) {
            return $diffDays === 1 ? '1 day ago' : "{$diffDays} days ago";
        }

        return $this->created_at->format('M d, Y');
    }

    /**
     * Calculate genuine seller rating and total reviews.
     *
     * @return array{rating: float, count: int}
     */
    public function getSellerRatingInfo(): array
    {
        if ($this->relationLoaded('seller') && $this->seller && (int) ($this->seller->seller_reviews_count ?? 0) > 0) {
            return [
                'rating' => round((float) $this->seller->seller_rating, 1),
                'count' => (int) $this->seller->seller_reviews_count,
            ];
        }

        $reviewsQuery = MarketplaceReview::where('seller_id', $this->seller_id);
        $count = $reviewsQuery->count();

        if ($count === 0) {
            return [
                'rating' => 0.0,
                'count' => 0,
            ];
        }

        $avg = round((float) $reviewsQuery->avg('rating'), 1);

        return [
            'rating' => $avg,
            'count' => $count,
        ];
    }

    /**
     * Standardized array structure for API responses and Inertia views.
     */
    public function toResponseArray(?User $viewer = null): array
    {
        $seller = $this->seller;
        $ratingInfo = $this->getSellerRatingInfo();

        $sellerAvatar = $seller?->profile?->avatar_url
            ? UserProfile::normalizeStorageUrl($seller->profile->avatar_url)
            : ($seller?->avatar_url ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80');

        $sellerVerified = (bool) ($seller?->profile?->is_verified ?? false);

        $currency = $this->currency ?: 'BDT';
        $currencySymbol = ($currency === 'USD' || $currency === '$') ? '$' : '৳';
        $formattedPrice = $currencySymbol.number_format((float) $this->price, (float) $this->price == (int) $this->price ? 0 : 2);

        $images = is_array($this->images) ? array_values(array_filter($this->images)) : [];
        if (empty($images)) {
            $images = ['https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80'];
        }

        $formattedImages = array_map(function ($img) {
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                return $img;
            }

            return url($img);
        }, $images);

        $isSaved = array_key_exists('preloaded_is_saved', $this->attributes)
            ? (bool) $this->attributes['preloaded_is_saved']
            : $this->isSavedBy($viewer);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description ?: '',
            'price' => (float) $this->price,
            'stock' => $this->stock !== null ? (int) $this->stock : 1,
            'original_price' => $this->original_price !== null ? (float) $this->original_price : null,
            'discount_percent' => $this->discount_percent !== null ? (int) $this->discount_percent : null,
            'currency' => $currency,
            'currency_symbol' => $currencySymbol,
            'formatted_price' => $formattedPrice,
            'condition' => $this->condition ?: 'used',
            'brand' => $this->brand ?: null,
            'model' => $this->model ?: null,
            'is_negotiable' => (bool) $this->is_negotiable,
            'delivery_type' => $this->delivery_type ?: 'pickup',
            'delivery_charge' => (float) $this->delivery_charge,
            'location' => $this->location ?: 'Dhaka, Bangladesh',
            'status' => $this->status ?: 'active',
            'images' => $formattedImages,
            'primary_image' => $formattedImages[0] ?? null,
            'primary_image_url' => $formattedImages[0] ?? null,
            'seller_name' => $seller?->name ?? 'Community Seller',
            'is_boosted' => (bool) $this->is_boosted,
            'is_featured' => (bool) $this->is_boosted,
            'views_count' => (int) $this->views_count,
            'time_ago' => $this->time_ago,
            'created_at' => $this->created_at?->toIso8601String(),
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name ?? 'General',
                'slug' => $this->category?->slug ?? 'general',
                'icon' => $this->category?->icon ?? '📦',
            ],
            'subcategory' => $this->subcategory ? [
                'id' => $this->subcategory->id,
                'name' => $this->subcategory->name,
                'slug' => $this->subcategory->slug,
            ] : null,
            'seller' => [
                'id' => $seller?->id,
                'name' => $seller?->name ?? 'Community Seller',
                'username' => $seller?->username ?? 'seller',
                'avatar_url' => $sellerAvatar,
                'is_verified' => (bool) $sellerVerified,
                'rating' => (float) ($ratingInfo['rating'] ?? 0.0),
                'reviews_count' => (int) ($ratingInfo['count'] ?? 0),
                'member_since' => $seller?->created_at ? $seller->created_at->format('M Y') : '2024',
            ],
            'is_favorited' => (bool) $isSaved,
            'is_saved' => (bool) $isSaved,
            'is_owner' => (bool) ($viewer && (int) $viewer->id === (int) $this->seller_id),
        ];
    }
}
