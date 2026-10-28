<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZeroTrustPolicy extends Model
{
    public const EFFECT_ALLOW = 'allow';

    public const EFFECT_DENY = 'deny';

    protected $fillable = [
        'policy_code',
        'policy_key',
        'name',
        'version',
        'operation_key',
        'effect',
        'risk_level',
        'required_permissions',
        'require_mfa',
        'require_jit',
        'require_fresh_auth_minutes',
        'require_dual_approval',
        'prevent_self_approval',
        'max_risk_score',
        'require_trusted_device',
        'conditions',
        'is_active',
        'change_history',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::saving(function (ZeroTrustPolicy $policy) {
            if (! empty($policy->policy_code) && empty($policy->attributes['policy_key'])) {
                $policy->attributes['policy_key'] = $policy->policy_code;
            } elseif (! empty($policy->attributes['policy_key']) && empty($policy->policy_code)) {
                $policy->policy_code = $policy->attributes['policy_key'];
            }
        });
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'required_permissions' => 'array',
            'require_mfa' => 'boolean',
            'require_jit' => 'boolean',
            'require_fresh_auth_minutes' => 'integer',
            'require_dual_approval' => 'boolean',
            'prevent_self_approval' => 'boolean',
            'max_risk_score' => 'integer',
            'require_trusted_device' => 'boolean',
            'conditions' => 'array',
            'is_active' => 'boolean',
            'change_history' => 'array',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getPolicyKeyAttribute(): string
    {
        return (string) ($this->attributes['policy_key'] ?? $this->policy_code);
    }

    public function setPolicyKeyAttribute(string $value): void
    {
        $this->attributes['policy_key'] = $value;
        $this->attributes['policy_code'] = $value;
    }

    public function getResourceAttribute(): string
    {
        $parts = explode('.', (string) $this->operation_key);

        return $parts[0] ?? '';
    }

    public function getActionAttribute(): string
    {
        $parts = explode('.', (string) $this->operation_key);

        return $parts[1] ?? '';
    }
}
