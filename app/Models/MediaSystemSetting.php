<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * মিডিয়া সিস্টেম সেটিংস মডেল:
 * অ্যাডমিন কনফিগারযোগ্য মিডিয়া সাইজ, কোটা, ওয়ার্কার কনকারেন্সি এবং স্টোরেজ লিমিট।
 */
class MediaSystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * সেটিং এর ভ্যালু ক্যাশ থেকে বা ডাটাবেজ থেকে রিটার্ন করে।
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("media_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            if (! $setting) {
                return $default;
            }

            return match ($setting->type) {
                'integer' => (int) $setting->value,
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($setting->value, true) ?? $default,
                default => $setting->value,
            };
        });
    }

    /**
     * সেটিং সেট করা এবং ক্যাশ ফ্লাশ করা।
     */
    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $description = null): self
    {
        $serialized = match ($type) {
            'json' => is_array($value) ? json_encode($value) : $value,
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $serialized,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        Cache::forget("media_setting_{$key}");

        return $setting;
    }
}
