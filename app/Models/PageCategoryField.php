<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageCategoryField extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_type_id',
        'page_category_id',
        'page_subcategory_id',
        'field_key',
        'label',
        'field_type',
        'placeholder',
        'description',
        'options',
        'is_required',
        'default_value',
        'validation_rules',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function pageType(): BelongsTo
    {
        return $this->belongsTo(PageType::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PageCategory::class, 'page_category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(PageSubcategory::class, 'page_subcategory_id');
    }
}
