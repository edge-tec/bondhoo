<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStorageQuota extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tier',
        'max_storage_bytes',
        'used_storage_bytes',
        'max_video_size_bytes',
        'max_image_size_bytes',
        'max_daily_uploads',
        'today_uploads_count',
        'last_upload_date',
        'is_unlimited',
    ];

    protected function casts(): array
    {
        return [
            'max_storage_bytes' => 'integer',
            'used_storage_bytes' => 'integer',
            'max_video_size_bytes' => 'integer',
            'max_image_size_bytes' => 'integer',
            'max_daily_uploads' => 'integer',
            'today_uploads_count' => 'integer',
            'last_upload_date' => 'date',
            'is_unlimited' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create quota for a user.
     */
    public static function forUser(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            [
                'tier' => 'free',
                'max_storage_bytes' => 1073741824, // 1 GB
                'used_storage_bytes' => 0,
                'max_video_size_bytes' => 104857600, // 100 MB
                'max_image_size_bytes' => 20971520, // 20 MB
                'max_daily_uploads' => 50,
                'today_uploads_count' => 0,
                'last_upload_date' => now()->toDateString(),
                'is_unlimited' => false,
            ]
        );
    }

    public function hasSufficientQuota(int $newFileBytes): bool
    {
        if ($this->is_unlimited) {
            return true;
        }

        return ($this->used_storage_bytes + $newFileBytes) <= $this->max_storage_bytes;
    }

    public function canUploadToday(): bool
    {
        if ($this->is_unlimited) {
            return true;
        }

        $today = now()->toDateString();
        if ($this->last_upload_date !== $today) {
            $this->update([
                'last_upload_date' => $today,
                'today_uploads_count' => 0,
            ]);

            return true;
        }

        return $this->today_uploads_count < $this->max_daily_uploads;
    }

    public function recordUpload(int $bytes): void
    {
        $today = now()->toDateString();
        $todayCount = ($this->last_upload_date === $today) ? ($this->today_uploads_count + 1) : 1;

        $this->update([
            'used_storage_bytes' => $this->used_storage_bytes + $bytes,
            'today_uploads_count' => $todayCount,
            'last_upload_date' => $today,
        ]);
    }

    public function releaseStorage(int $bytes): void
    {
        $newUsed = max(0, $this->used_storage_bytes - $bytes);
        $this->update(['used_storage_bytes' => $newUsed]);
    }
}
