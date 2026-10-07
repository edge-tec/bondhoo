<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupQuestionAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'question_id',
        'user_id',
        'answer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(GroupQuestion::class, 'question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
