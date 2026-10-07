<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'title',
        'slug',
        'description',
        'price',
        'currency',
        'stock_quantity',
        'status',
        'media_urls',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'media_urls' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
