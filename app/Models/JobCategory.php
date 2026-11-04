<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class JobCategory extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'status',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (JobCategory $category): void {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(JobPosting::class, 'category_id');
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'subcategories' => $this->children->map(fn (JobCategory $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ])->values()->all(),
        ];
    }
}
