<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'name',
        'street_address',
        'city',
        'state',
        'district',
        'country',
        'zip_code',
        'phone',
        'email',
        'website',
        'latitude',
        'longitude',
        'business_hours',
        'is_headquarters',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'business_hours' => 'array',
            'is_headquarters' => 'boolean',
            'is_public' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
