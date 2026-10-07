<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupGuide extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'creator_id',
        'title',
        'description',
        'cover_image_url',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
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

    public function sections(): HasMany
    {
        return $this->hasMany(GroupGuideSection::class, 'guide_id')->orderBy('sort_order');
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'title' => $this->title,
            'description' => $this->description,
            'cover_image_url' => $this->cover_image_url,
            'sections' => $this->sections->map(fn (GroupGuideSection $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'content' => $s->content,
                'resources' => $s->resources ?? [],
            ])->values(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
