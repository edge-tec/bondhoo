<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class RecoveryCode extends Model
{
    use HasFactory;

    protected $table = 'recovery_codes';

    protected $fillable = [
        'user_id',
        'code_hash',
        'used_at',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this code has already been consumed.
     */
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * Verify if given plain text recovery code matches this record.
     */
    public function matches(string $plainCode): bool
    {
        return Hash::check(strtoupper(trim($plainCode)), $this->code_hash);
    }

    /**
     * Scope query to only unused recovery codes.
     */
    public function scopeUnused($query)
    {
        return $query->whereNull('used_at');
    }
}
