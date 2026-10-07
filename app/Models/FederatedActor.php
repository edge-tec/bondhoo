<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FederatedActor extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_uri',
        'username',
        'domain',
        'name',
        'summary',
        'avatar_url',
        'inbox_url',
        'outbox_url',
        'shared_inbox_url',
        'public_key_pem',
        'public_key_id',
        'last_fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'last_fetched_at' => 'datetime',
        ];
    }
}
