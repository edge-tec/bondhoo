<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(PageCategory::class)->orderBy('sort_order');
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
