<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileSkill extends Model
{
    use HasFactory;

    protected $table = 'profile_skills';

    protected $fillable = [
        'user_id',
        'name',
        'level',
        'endorsements_count',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'endorsements_count' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = trim(preg_replace('/\s+/', ' ', $value));
    }

    public function scopeSearch($query, string $term)
    {
        return $query->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower(trim($term)).'%']);
    }
}
