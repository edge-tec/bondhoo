<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SavedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'item_type',
        'item_id',
        'collection_name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'item_type', 'item_id');
    }
}
