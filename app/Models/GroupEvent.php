<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupEvent extends Model
{
    use HasFactory;

    public const STATUS_GOING = 'going';

    public const STATUS_INTERESTED = 'interested';

    public const STATUS_NOT_GOING = 'not_going';

    protected $fillable = [
        'group_id',
        'creator_id',
        'title',
        'description',
        'cover_image_url',
        'location',
        'is_online',
        'meeting_url',
        'start_time',
        'end_time',
        'timezone',
        'attendees_count',
    ];

    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'attendees_count' => 'integer',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(GroupEventAttendee::class, 'event_id');
    }

    public function toResponseArray(?User $viewer = null): array
    {
        $viewerStatus = null;
        if ($viewer) {
            $att = $this->attendees()->where('user_id', $viewer->id)->first();
            $viewerStatus = $att?->status;
        }

        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'title' => $this->title,
            'description' => $this->description,
            'cover_image_url' => $this->cover_image_url,
            'location' => $this->location,
            'is_online' => $this->is_online,
            'meeting_url' => $this->meeting_url,
            'start_time' => $this->start_time?->toIso8601String(),
            'end_time' => $this->end_time?->toIso8601String(),
            'timezone' => $this->timezone,
            'attendees_count' => $this->attendees_count,
            'creator' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
                'username' => $this->creator?->username,
            ],
            'viewer_status' => $viewerStatus,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
