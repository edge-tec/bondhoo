<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FailedLoginAttempt extends Model
{
    use HasFactory;

    protected $table = 'failed_login_attempts';

    protected $fillable = [
        'user_id',
        'identifier',
        'ip_address',
        'user_agent',
        'failure_reason',
        'attempted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
        ];
    }

    /**
     * Get the user associated with this failed login attempt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
