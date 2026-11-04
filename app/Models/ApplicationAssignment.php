<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'assigned_to_user_id',
        'assigned_by_user_id',
        'notes',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
