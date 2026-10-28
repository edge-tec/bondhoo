<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThreatDetectionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_code',
        'rule_id',
        'name',
        'version',
        'data_source',
        'event_pattern',
        'threshold_count',
        'window_minutes',
        'severity',
        'suppression_minutes',
        'recommended_response',
        'requires_human_approval',
        'is_enabled',
        'false_positive_count',
        'true_positive_count',
        'version_history',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::saving(function (ThreatDetectionRule $rule) {
            if (! empty($rule->rule_code) && empty($rule->rule_id)) {
                $rule->rule_id = $rule->rule_code;
            } elseif (! empty($rule->rule_id) && empty($rule->rule_code)) {
                $rule->rule_code = $rule->rule_id;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'threshold_count' => 'integer',
            'window_minutes' => 'integer',
            'suppression_minutes' => 'integer',
            'requires_human_approval' => 'boolean',
            'is_enabled' => 'boolean',
            'false_positive_count' => 'integer',
            'true_positive_count' => 'integer',
            'version_history' => 'array',
        ];
    }

    public function getRuleIdAttribute(): string
    {
        return (string) ($this->attributes['rule_id'] ?? $this->attributes['rule_code'] ?? '');
    }

    public function setRuleIdAttribute(string $value): void
    {
        $this->attributes['rule_id'] = $value;
        $this->attributes['rule_code'] = $value;
    }

    public function scopeWhereRuleId($query, string $ruleId)
    {
        return $query->where('rule_code', $ruleId);
    }

    public function detections(): HasMany
    {
        return $this->hasMany(ThreatDetection::class, 'rule_code', 'rule_code');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
