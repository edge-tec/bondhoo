<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStageHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'application_stage_history';

    protected $fillable = [
        'application_id',
        'stage',
        'previous_stage',
        'actor_user_id',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'stage' => $this->stage,
            'previous_stage' => $this->previous_stage,
            'actor_name' => $this->actor?->name ?? 'System',
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
