<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_type_id',
        'name',
        'slug',
        'description',
        'icon',
        'is_popular',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function pageType(): BelongsTo
    {
        return $this->belongsTo(PageType::class);
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(PageSubcategory::class)->orderBy('sort_order');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(PageCategoryField::class)->orderBy('sort_order');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }
}
