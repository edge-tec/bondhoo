<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkScanResult extends Model
{
    use HasFactory;

    public const STATUS_SAFE = 'safe';

    public const STATUS_SUSPICIOUS = 'suspicious';

    public const STATUS_MALICIOUS = 'malicious';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'url',
        'normalized_url',
        'final_url',
        'domain',
        'status',
        'http_status',
        'title',
        'description',
        'is_ssrf_blocked',
        'blocked_ip',
        'redirect_hops',
        'flagged_categories',
        'scanned_at',
    ];

    protected $casts = [
        'is_ssrf_blocked' => 'boolean',
        'flagged_categories' => 'array',
        'scanned_at' => 'datetime',
    ];
}
