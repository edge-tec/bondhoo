<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileVerification extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_UNVERIFIED = 'unverified';

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_NEEDS_INFO = 'needs_info';

    public const TYPE_NID = 'nid';

    public const TYPE_PASSPORT = 'passport';

    public const TYPE_DRIVING_LICENSE = 'driving_license';

    public const TYPE_ORGANIZATIONAL = 'organizational';

    protected $table = 'profile_verifications';

    protected $fillable = [
        'user_id',
        'verification_type',
        'document_number',
        'document_front_key',
        'document_back_key',
        'selfie_key',
        'media_meta',
        'status',
        'admin_id',
        'rejection_reason',
        'admin_notes',
        'submitted_at',
        'reviewed_at',
    ];

    protected $hidden = [
        'document_front_key',
        'document_back_key',
        'selfie_key',
        'media_meta',
    ];

    protected function casts(): array
    {
        return [
            'media_meta' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isVerified(): bool
    {
        return in_array($this->status, [self::STATUS_VERIFIED, self::STATUS_APPROVED], true);
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function needsInfo(): bool
    {
        return $this->status === self::STATUS_NEEDS_INFO;
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeVerified($query)
    {
        return $query->whereIn('status', [self::STATUS_VERIFIED, self::STATUS_APPROVED]);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeNeedsInfo($query)
    {
        return $query->where('status', self::STATUS_NEEDS_INFO);
    }
}
