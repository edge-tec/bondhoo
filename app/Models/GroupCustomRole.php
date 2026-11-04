<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupCustomRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'name',
        'display_name',
        'description',
        'color',
        'permissions',
        'is_system',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'name' => $this->name,
            'display_name' => $this->display_name ?? ucfirst(str_replace('_', ' ', $this->name)),
            'description' => $this->description,
            'color' => $this->color ?? '#6366f1',
            'permissions' => $this->permissions ?? [],
            'is_system' => (bool) $this->is_system,
            'sort_order' => (int) $this->sort_order,
            'members_count' => GroupMember::where('group_id', $this->group_id)->where('role', $this->name)->count(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
