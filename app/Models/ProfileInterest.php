<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileInterest extends Model
{
    use HasFactory;

    protected $table = 'profile_interests';

    protected $fillable = [
        'user_id',
        'name',
        'category',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = trim(preg_replace('/\s+/', ' ', $value));
    }

    public function setCategoryAttribute(?string $value): void
    {
        $this->attributes['category'] = $value ? trim(preg_replace('/\s+/', ' ', $value)) : null;
    }

    public function scopeSearch($query, string $term)
    {
        return $query->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower(trim($term)).'%']);
    }
}
