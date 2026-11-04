<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyVerification extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_MORE_INFO = 'more_info_required';

    protected $fillable = [
        'company_id',
        'user_id',
        'documents',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'documents' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company_name' => $this->company?->name,
            'user_id' => $this->user_id,
            'documents' => $this->documents ?? [],
            'status' => $this->status,
            'admin_notes' => $this->admin_notes,
            'reviewed_by' => $this->reviewer?->name,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
