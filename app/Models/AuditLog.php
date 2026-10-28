<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * Sensitive payload keys that must always be redacted before persistence and hashing.
     */
    public const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'secret',
        'client_secret',
        'token',
        'access_token',
        'refresh_token',
        'remember_token',
        'api_key',
        'private_key',
        'encryption_key',
        'master_key',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'recovery_codes',
        'cookie',
        'authorization',
        'smtp_password',
    ];

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'checksum',
        'previous_hash',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log) {
            if (is_array($log->old_values)) {
                $log->old_values = static::redactSensitiveData($log->old_values);
            }

            if (is_array($log->new_values)) {
                $log->new_values = static::redactSensitiveData($log->new_values);
            }

            // Find previous log's checksum to build cryptographic chain (with row lock when in transaction)
            $previous = static::latest('id')->lockForUpdate()->first();
            $log->previous_hash = $previous?->checksum ?? hash('sha256', 'GENESIS_BLOCK_JUGAJUG_ADMIN');

            // Generate HMAC checksum with RFC 8785-compliant recursive key canonicalization
            $payload = json_encode([
                'user_id' => $log->user_id,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'old_values' => static::canonicalizeData($log->old_values),
                'new_values' => static::canonicalizeData($log->new_values),
                'ip_address' => $log->ip_address,
                'previous_hash' => $log->previous_hash,
            ]);

            $secret = config('app.key', 'audit_integrity_secret_key');
            $log->checksum = hash_hmac('sha256', (string) $payload, $secret);
        });

        static::updating(function () {
            throw new \InvalidArgumentException('অডিট লগ রেকর্ড অপরিবর্তনীয় (Immutable); কোনো অডিট লগ পরিবর্তন করা সম্পূর্ণ নিষিদ্ধ।');
        });

        static::deleting(function () {
            throw new \InvalidArgumentException('অডিট লগ রেকর্ড অপরিবর্তনীয় (Immutable); কোনো অডিট লগ মুছে ফেলা সম্পূর্ণ নিষিদ্ধ।');
        });
    }

    /**
     * Recursively redact credentials, tokens, keys, and secrets from audit log payloads.
     */
    public static function redactSensitiveData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            $isSensitive = in_array($normalizedKey, self::SENSITIVE_KEYS, true)
                || str_contains($normalizedKey, 'password')
                || str_contains($normalizedKey, 'secret')
                || str_contains($normalizedKey, 'private_key')
                || str_contains($normalizedKey, 'access_token')
                || str_contains($normalizedKey, 'recovery_code')
                || str_contains($normalizedKey, 'authorization');

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = static::redactSensitiveData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Helper to create an immutable audit log with optional metadata.
     */
    public static function record(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?int $userId = null
    ): static {
        $finalNewValues = $newValues ?? [];
        if ($metadata !== null) {
            $finalNewValues['_metadata'] = $metadata;
        }

        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $finalNewValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public function getMetadataAttribute(): array
    {
        return $this->new_values['_metadata'] ?? [];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Recursively sort associative array keys for deterministic RFC 8785 JSON canonicalization.
     */
    public static function canonicalizeData(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        if (! array_is_list($data)) {
            ksort($data);
        }

        foreach ($data as $k => $v) {
            $data[$k] = static::canonicalizeData($v);
        }

        return $data;
    }

    /**
     * Verify the cryptographic checksum of this audit entry.
     */
    public function verifyChecksum(): bool
    {
        $payload = json_encode([
            'user_id' => $this->user_id,
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'old_values' => static::canonicalizeData($this->old_values),
            'new_values' => static::canonicalizeData($this->new_values),
            'ip_address' => $this->ip_address,
            'previous_hash' => $this->previous_hash,
        ]);

        $secret = config('app.key', 'audit_integrity_secret_key');
        $expected = hash_hmac('sha256', (string) $payload, $secret);

        return hash_equals($expected, (string) $this->checksum);
    }
}
