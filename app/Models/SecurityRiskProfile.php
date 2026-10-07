<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityRiskProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'risk_score',
        'risk_level',
        'last_ip',
        'country_code',
        'is_suspicious',
        'mfa_required',
        'anomaly_flags',
        'last_assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'risk_score' => 'float',
            'is_suspicious' => 'boolean',
            'mfa_required' => 'boolean',
            'anomaly_flags' => 'array',
            'last_assessed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
