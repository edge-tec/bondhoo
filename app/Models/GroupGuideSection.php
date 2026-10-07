<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupGuideSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'guide_id',
        'title',
        'content',
        'resources',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'resources' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(GroupGuide::class, 'guide_id');
    }
}
