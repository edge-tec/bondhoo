<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'question',
        'type',
        'options',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(GroupQuestionAnswer::class, 'question_id');
    }
}
