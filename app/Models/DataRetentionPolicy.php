<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataRetentionPolicy extends Model
{
    use HasFactory;

    public const ACTION_SOFT_DELETE = 'soft_delete';

    public const ACTION_HARD_DELETE = 'hard_delete';

    public const ACTION_ANONYMIZE = 'anonymize';

    protected $fillable = [
        'data_category',
        'name',
        'description',
        'retention_days',
        'action',
        'is_enabled',
        'last_run_at',
        'last_records_affected',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'retention_days' => 'integer',
            'is_enabled' => 'boolean',
            'last_run_at' => 'datetime',
            'last_records_affected' => 'integer',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
