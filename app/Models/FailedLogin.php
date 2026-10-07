<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedLogin extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'identifier',
        'ip_address',
        'user_agent',
        'reason',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
        ];
    }
}
