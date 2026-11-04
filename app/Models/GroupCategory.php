<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GroupCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'sort_order',
        'is_active',
        'is_system',
        'groups_count',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'groups_count' => 'integer',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function (GroupCategory $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class, 'category', 'name');
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon ?: 'folder-outline',
            'description' => $this->description,
            'groups_count' => $this->groups_count,
            'is_system' => $this->is_system,
        ];
    }
}
