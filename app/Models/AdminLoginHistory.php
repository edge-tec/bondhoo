<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLoginHistory extends Model
{
    use HasFactory;

    protected $table = 'admin_login_histories';

    protected $fillable = [
        'admin_id',
        'identifier',
        'ip',
        'browser',
        'device',
        'platform',
        'login_at',
        'logout_at',
        'success',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'login_at' => 'datetime',
            'logout_at' => 'datetime',
            'success' => 'boolean',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
