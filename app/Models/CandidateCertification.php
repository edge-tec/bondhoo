<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateCertification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'issuing_organization',
        'issue_date',
        'expiry_date',
        'credential_id',
        'credential_url',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'issuing_organization' => $this->issuing_organization,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'credential_id' => $this->credential_id,
            'credential_url' => $this->credential_url,
        ];
    }
}
