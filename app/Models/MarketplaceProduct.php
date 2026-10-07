<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'category_id',
        'title',
        'description',
        'price',
        'currency',
        'condition',
        'location',
        'status',
        'images',
        'variants',
        'views_count',
        'ai_fraud_score',
        'is_boosted',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
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

    public function orders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class, 'product_id');
    }
}
