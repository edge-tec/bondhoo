<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleaseVerification extends Model
{
    use HasFactory;

    public const STATUS_APPROVED_READY = 'approved_ready';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_FAILED = 'failed';

    public const STATUS_ROLLED_BACK = 'rolled_back';

    protected $fillable = [
        'release_uid',
        'version_tag',
        'commit_sha',
        'build_id',
        'environment',
        'gates',
        'sbom_summary',
        'overall_status',
        'blockers',
        'authorized_by',
        'authorization_notes',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'gates' => 'array',
            'sbom_summary' => 'array',
            'blockers' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
