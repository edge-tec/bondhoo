<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FederatedActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'actor_uri',
        'type',
        'object_uri',
        'target_inbox_url',
        'payload',
        'direction',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
