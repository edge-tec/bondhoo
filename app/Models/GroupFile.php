<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'user_id',
        'name',
        'file_url',
        'file_type',
        'file_size',
        'downloads_count',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'downloads_count' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'name' => $this->name,
            'file_url' => $this->file_url,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'downloads_count' => $this->downloads_count,
            'uploader' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'username' => $this->user?->username,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
