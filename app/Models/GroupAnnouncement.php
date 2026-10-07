<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupAnnouncement extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'author_id',
        'title',
        'content',
        'cta_text',
        'cta_url',
        'is_pinned',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'title' => $this->title,
            'content' => $this->content,
            'cta_text' => $this->cta_text,
            'cta_url' => $this->cta_url,
            'is_pinned' => $this->is_pinned,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'author' => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
                'username' => $this->author?->username,
                'avatar_url' => $this->author?->profile?->avatar_url,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
