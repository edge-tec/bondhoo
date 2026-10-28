<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformFeatureFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'is_enabled',
        'rollout_percentage',
        'target_roles',
        'target_users',
        'rules',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'rollout_percentage' => 'integer',
            'target_roles' => 'array',
            'target_users' => 'array',
            'rules' => 'array',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Determine if feature flag is active for a given user or globally.
     */
    public function isApplicableFor(?User $user = null): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        // 100% rollout is globally enabled
        if ($this->rollout_percentage >= 100) {
            return true;
        }

        if ($this->rollout_percentage <= 0 && empty($this->target_roles) && empty($this->target_users)) {
            return false;
        }

        if ($user) {
            // Check specific target user ID
            if (! empty($this->target_users) && in_array($user->id, $this->target_users, true)) {
                return true;
            }

            // Check target roles
            if (! empty($this->target_roles)) {
                $userRoleNames = $user->roles->pluck('name')->toArray();
                if (! empty(array_intersect($userRoleNames, $this->target_roles))) {
                    return true;
                }
            }

            // Percentage-based deterministic hash rollout
            if ($this->rollout_percentage > 0) {
                $hash = hexdec(substr(md5($this->key.':'.$user->id), 0, 4));
                $bucket = $hash % 100;

                return $bucket < $this->rollout_percentage;
            }
        }

        return false;
    }
}
