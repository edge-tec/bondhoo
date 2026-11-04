<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobView extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'job_id',
        'viewer_user_id',
        'ip_address',
        'source',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_id');
    }

    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_user_id');
    }
}
