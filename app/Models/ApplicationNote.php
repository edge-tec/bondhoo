<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'author_user_id',
        'note',
        'visibility',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'note' => $this->note,
            'visibility' => $this->visibility,
            'author' => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
                'avatar' => $this->author?->avatar_url ?? $this->author?->profile?->avatar_url,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
