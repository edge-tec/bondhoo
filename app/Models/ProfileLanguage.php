<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileLanguage extends Model
{
    use HasFactory;

    public const PROFICIENCIES = [
        'basic',
        'conversational',
        'professional',
        'fluent',
        'native',
    ];

    protected $table = 'profile_languages';

    protected $fillable = [
        'user_id',
        'language',
        'proficiency',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setLanguageAttribute(string $value): void
    {
        $this->attributes['language'] = trim(preg_replace('/\s+/', ' ', $value));
    }

    public function setProficiencyAttribute(string $value): void
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $value)));
        $this->attributes['proficiency'] = in_array($normalized, self::PROFICIENCIES, true) ? $normalized : 'conversational';
    }

    public function scopeSearch($query, string $term)
    {
        return $query->whereRaw('LOWER(language) LIKE ?', ['%'.strtolower(trim($term)).'%']);
    }
}
