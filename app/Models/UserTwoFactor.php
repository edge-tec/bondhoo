<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class UserTwoFactor extends Model
{
    use HasFactory;

    protected $table = 'user_two_factor';

    protected $fillable = [
        'user_id',
        'secret',
        'type',
        'is_enabled',
        'confirmed_at',
        'last_used_at',
    ];

    protected $hidden = [
        'secret',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'confirmed_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get decrypted Base32 secret.
     */
    public function getDecryptedSecret(): ?string
    {
        if (! $this->secret) {
            return null;
        }

        try {
            return Crypt::decryptString($this->secret);
        } catch (\Throwable) {
            return $this->secret;
        }
    }
}
