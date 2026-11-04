<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class JobSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (JobSkill $skill): void {
            if (empty($skill->slug)) {
                $skill->slug = Str::slug($skill->name);
            }
        });
    }

    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(JobPosting::class, 'job_job_skill', 'job_skill_id', 'job_posting_id');
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }
}
