<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditCheckpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkpoint_number',
        'record_start_id',
        'record_end_id',
        'record_count',
        'root_hash',
        'signature',
        'key_version',
        'created_by',
        'checkpoint_at',
    ];

    protected function casts(): array
    {
        return [
            'checkpoint_number' => 'integer',
            'record_start_id' => 'integer',
            'record_end_id' => 'integer',
            'record_count' => 'integer',
            'checkpoint_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSequenceEndAttribute(): int
    {
        return (int) ($this->record_end_id ?: $this->checkpoint_number);
    }

    /**
     * Verify the checkpoint's signature against the application key.
     */
    public function verifySignature(): bool
    {
        $payload = json_encode([
            'checkpoint_number' => $this->checkpoint_number,
            'record_start_id' => $this->record_start_id,
            'record_end_id' => $this->record_end_id,
            'record_count' => $this->record_count,
            'root_hash' => $this->root_hash,
            'checkpoint_at' => $this->checkpoint_at?->toIso8601String(),
        ], JSON_UNESCAPED_SLASHES);

        $secret = config('app.key', 'audit_integrity_secret_key');
        $expected = hash_hmac('sha256', (string) $payload, $secret);

        return hash_equals($expected, (string) $this->signature);
    }
}
