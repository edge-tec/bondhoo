<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SmtpSetting extends Model
{
    use HasFactory;

    protected $table = 'smtp_settings';

    protected $fillable = [
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'mail_reply_to',
        'smtp_auth',
        'timeout',
        'rate_limit_per_minute',
        'is_enabled',
    ];

    protected $hidden = [
        'mail_password',
    ];

    protected function casts(): array
    {
        return [
            'mail_port' => 'integer',
            'smtp_auth' => 'boolean',
            'timeout' => 'integer',
            'rate_limit_per_minute' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * Mutator: Encrypt password when saving to database.
     */
    public function setMailPasswordAttribute(?string $value): void
    {
        if (! empty($value)) {
            $this->attributes['mail_password'] = Crypt::encryptString($value);
        } else {
            $this->attributes['mail_password'] = null;
        }
    }

    /**
     * Helper: Decrypt password safely for SMTP transport connection.
     */
    public function getDecryptedPassword(): ?string
    {
        $raw = $this->attributes['mail_password'] ?? null;
        if (empty($raw)) {
            return null;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            return $raw; // Fallback in case raw plaintext was seeded
        }
    }

    /**
     * Accessor: Safe masked password for admin UI.
     */
    public function getMaskedPasswordAttribute(): string
    {
        return ! empty($this->attributes['mail_password']) ? '••••••••' : '';
    }
}
