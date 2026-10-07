<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageCreationDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'current_step',
        'form_data',
        'autosaved_at',
    ];

    protected function casts(): array
    {
        return [
            'current_step' => 'integer',
            'form_data' => 'array',
            'autosaved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
