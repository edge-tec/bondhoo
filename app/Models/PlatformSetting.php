<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlatformSetting extends Model
{
    use HasFactory;

    protected $table = 'platform_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_public',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get a setting by key with caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("platform_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            if (! $setting) {
                return $default;
            }

            return match ($setting->type) {
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $setting->value,
                'json', 'array' => json_decode((string) $setting->value, true) ?? $default,
                default => $setting->value ?? $default,
            };
        });
    }

    /**
     * Set a setting value by key, record history, and refresh cache.
     */
    public static function set(string $key, mixed $value, ?int $updatedById = null, ?string $reason = null): static
    {
        $setting = DB::transaction(function () use ($key, $value, $updatedById, $reason) {
            $setting = static::where('key', $key)->lockForUpdate()->first() ?? new static(['key' => $key]);
            $oldValue = $setting->exists ? $setting->value : null;

            $newValue = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;
            $setting->value = $newValue;
            if ($updatedById) {
                $setting->updated_by = $updatedById;
            }
            $setting->save();

            // Record history snapshot for instant rollback
            if ($oldValue !== $newValue) {
                PlatformSettingHistory::create([
                    'platform_setting_id' => $setting->id,
                    'key' => $key,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'changed_by' => $updatedById,
                    'reason' => $reason,
                ]);
            }

            return $setting;
        });

        Cache::forget("platform_setting_{$key}");
        Cache::forget('platform_settings_all');

        return $setting;
    }

    /**
     * Rollback a setting to its previous value from history.
     */
    public static function rollback(string $key, ?int $adminId = null): ?static
    {
        $setting = DB::transaction(function () use ($key, $adminId) {
            $setting = static::where('key', $key)->lockForUpdate()->first();
            if (! $setting) {
                return null;
            }

            $latestHistory = PlatformSettingHistory::where('platform_setting_id', $setting->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $latestHistory || $latestHistory->old_value === null) {
                return null;
            }

            $restoredValue = $latestHistory->old_value;

            // Apply restored value
            $setting->value = $restoredValue;
            $setting->updated_by = $adminId;
            $setting->save();

            // Log rollback event in history
            PlatformSettingHistory::create([
                'platform_setting_id' => $setting->id,
                'key' => $key,
                'old_value' => $latestHistory->new_value,
                'new_value' => $restoredValue,
                'changed_by' => $adminId,
                'reason' => 'Rollback to previous revision #'.$latestHistory->id,
            ]);

            return $setting;
        });

        if ($setting) {
            Cache::forget("platform_setting_{$key}");
            Cache::forget('platform_settings_all');
        }

        return $setting;
    }

    /**
     * Get all settings grouped by their group.
     */
    public static function getAllGrouped(): array
    {
        return Cache::remember('platform_settings_all', 3600, function () {
            return static::all()->groupBy('group')->toArray();
        });
    }
}
