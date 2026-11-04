<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationQuestion extends Model
{
    use HasFactory;

    public const TYPE_SHORT_ANSWER = 'short_answer';

    public const TYPE_LONG_ANSWER = 'long_answer';

    public const TYPE_YES_NO = 'yes_no';

    public const TYPE_SINGLE_CHOICE = 'single_choice';

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    protected $fillable = [
        'job_posting_id',
        'question',
        'type',
        'options',
        'is_required',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }
}
