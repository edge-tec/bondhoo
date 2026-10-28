<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BackupRestoreDrill — বিচ্ছিন্ন (Isolated) পরিবেশে ব্যাকআপ রিস্টোর ড্রিল ও যাচাইকরণ রেকর্ড
 */
class BackupRestoreDrill extends Model
{
    use HasFactory;

    public const STATUS_RUNNING = 'running';

    public const STATUS_PASSED = 'passed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'drill_uid',
        'backup_id',
        'status',
        'isolated_env_path',
        'isolated_resources_cleaned',
        'checksum_verified',
        'decryption_verified',
        'schema_verified',
        'relationships_verified',
        'restored_tables_count',
        'restored_records_count',
        'duration_ms',
        'verification_report',
        'error_message',
        'triggered_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'isolated_resources_cleaned' => 'boolean',
            'checksum_verified' => 'boolean',
            'decryption_verified' => 'boolean',
            'schema_verified' => 'boolean',
            'relationships_verified' => 'boolean',
            'restored_tables_count' => 'integer',
            'restored_records_count' => 'integer',
            'duration_ms' => 'integer',
            'verification_report' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
