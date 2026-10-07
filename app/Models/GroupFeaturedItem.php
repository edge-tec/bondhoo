<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupFeaturedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'item_type',
        'item_id',
        'sort_order',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
