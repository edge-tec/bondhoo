<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'display_order',
        'is_system',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_system' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
