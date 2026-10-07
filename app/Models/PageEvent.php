<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'title',
        'slug',
        'description',
        'cover_image_url',
        'location',
        'start_time',
        'end_time',
        'is_online',
        'rsvp_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'is_online' => 'boolean',
            'rsvp_count' => 'integer',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(PageEventRsvp::class, 'event_id');
    }
}
