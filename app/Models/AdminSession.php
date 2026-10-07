<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminSession extends Model
{
    use HasFactory;

    protected $table = 'admin_sessions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'admin_id',
        'ip_address',
        'user_agent',
        'device_name',
        'payload',
        'last_activity',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
