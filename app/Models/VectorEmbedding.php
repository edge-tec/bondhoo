<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VectorEmbedding extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'model',
        'dimension',
        'vector',
        'content_snippet',
    ];

    protected function casts(): array
    {
        return [
            'dimension' => 'integer',
            'vector' => 'array',
        ];
    }
}
