<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'location_type',
        'address',
        'online_link',
        'cover_image',
        'max_attendees',
        'ticket_price',
        'going_count',
        'interested_count',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'max_attendees' => 'integer',
            'ticket_price' => 'decimal:2',
            'going_count' => 'integer',
            'interested_count' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }
}
