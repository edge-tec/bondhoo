<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningQuizQuestion extends Model
{
    use HasFactory;

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const TYPE_MULTIPLE_SELECT = 'multiple_select';

    public const TYPE_TRUE_FALSE = 'true_false';

    public const TYPE_SHORT_ANSWER = 'short_answer';

    protected $fillable = [
        'quiz_id',
        'question',
        'type',
        'points',
        'explanation',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(LearningQuiz::class, 'quiz_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(LearningQuizOption::class, 'question_id')->orderBy('sort_order');
    }
}
