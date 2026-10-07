<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiPrompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'version',
        'template',
        'parameters',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
