<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\PlatformFeatureFlag;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FeatureFlagService
{
    /**
     * Get all registered feature flags.
     */
    public function getAllFlags(): Collection
    {
        return PlatformFeatureFlag::with('updater:id,name')->orderBy('name')->get();
    }

    /**
     * Determine if a feature is enabled for a specific user.
     */
    public function isEnabled(string $key, ?User $user = null): bool
    {
        /** @var PlatformFeatureFlag|null $flag */
        $flag = PlatformFeatureFlag::where('key', $key)->first();

        if (! $flag) {
            return false;
        }

        return $flag->isApplicableFor($user);
    }

    /**
     * Update feature flag rollout and configuration.
     */
    public function updateFlag(string $key, array $data, User $admin): PlatformFeatureFlag
    {
        return DB::transaction(function () use ($key, $data, $admin) {
            $flag = PlatformFeatureFlag::where('key', $key)->lockForUpdate()->firstOrFail();
            $oldValues = $flag->only(['is_enabled', 'rollout_percentage', 'target_roles', 'target_users']);

            $flag->update([
                'is_enabled' => $data['is_enabled'] ?? $flag->is_enabled,
                'rollout_percentage' => isset($data['rollout_percentage']) ? (int) $data['rollout_percentage'] : $flag->rollout_percentage,
                'target_roles' => $data['target_roles'] ?? $flag->target_roles,
                'target_users' => $data['target_users'] ?? $flag->target_users,
                'rules' => $data['rules'] ?? $flag->rules,
                'description' => $data['description'] ?? $flag->description,
                'updated_by' => $admin->id,
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_feature_flag_updated',
                'entity_type' => PlatformFeatureFlag::class,
                'entity_id' => $flag->id,
                'old_values' => $oldValues,
                'new_values' => $flag->only(['is_enabled', 'rollout_percentage', 'target_roles', 'target_users']),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $flag->fresh();
        });
    }

    /**
     * Create a new feature flag.
     */
    public function createFlag(array $data, User $admin): PlatformFeatureFlag
    {
        return DB::transaction(function () use ($data, $admin) {
            $flag = PlatformFeatureFlag::create([
                'key' => $data['key'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_enabled' => $data['is_enabled'] ?? false,
                'rollout_percentage' => (int) ($data['rollout_percentage'] ?? 0),
                'target_roles' => $data['target_roles'] ?? null,
                'target_users' => $data['target_users'] ?? null,
                'rules' => $data['rules'] ?? null,
                'updated_by' => $admin->id,
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_feature_flag_created',
                'entity_type' => PlatformFeatureFlag::class,
                'entity_id' => $flag->id,
                'new_values' => ['key' => $flag->key, 'name' => $flag->name],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $flag;
        });
    }
}
