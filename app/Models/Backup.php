<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Backup — ব্যাকআপ ও ডিজাস্টার রিকভারি ইনভেন্টরি মডেল (Phase 5 Enhanced)
 */
class Backup extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const VERIFICATION_UNVERIFIED = 'unverified';

    public const VERIFICATION_VERIFIED = 'verified';

    public const VERIFICATION_FAILED = 'failed';

    public const VERIFICATION_CORRUPTED = 'corrupted';

    public const DRILL_NOT_TESTED = 'not_tested';

    public const DRILL_PASSED = 'passed';

    public const DRILL_FAILED = 'failed';

    protected $fillable = [
        'backup_uid',
        'filename',
        'disk',
        'type',
        'size_bytes',
        'status',
        'checksum',
        'encrypted',
        'started_at',
        'completed_at',
        'db_engine',
        'db_version',
        'app_version',
        'key_version',
        'cipher',
        'manifest',
        'expires_at',
        'verification_status',
        'last_verified_at',
        'restore_drill_status',
        'last_restore_drill_at',
        'error_message',
        'error_category',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'encrypted' => 'boolean',
            'size_bytes' => 'integer',
            'manifest' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_verified_at' => 'datetime',
            'last_restore_drill_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function restoreDrills(): HasMany
    {
        return $this->hasMany(BackupRestoreDrill::class);
    }
}
