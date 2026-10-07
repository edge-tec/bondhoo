<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileCompletion extends Model
{
    use HasFactory;

    protected $table = 'profile_completions';

    public const SECTION_NAME = 'name';

    public const SECTION_USERNAME = 'username';

    public const SECTION_AVATAR = 'avatar';

    public const SECTION_COVER = 'cover';

    public const SECTION_BIO = 'bio';

    public const SECTION_ABOUT = 'about';

    public const SECTION_LOCATION = 'location';

    public const SECTION_EDUCATION = 'education';

    public const SECTION_WORK = 'work';

    public const SECTION_SKILLS = 'skills';

    public const SECTION_INTERESTS = 'interests';

    public const SECTION_LANGUAGES = 'languages';

    public const SECTION_SOCIAL_LINKS = 'social_links';

    public const TOTAL_SECTIONS = 13;

    protected $fillable = [
        'user_id',
        'completion_percentage',
        'has_name',
        'has_username',
        'has_avatar',
        'has_cover',
        'has_bio',
        'has_about',
        'has_education',
        'has_experience',
        'has_skills',
        'has_interests',
        'has_languages',
        'has_social_links',
        'has_location',
        'missing_sections',
        'completed_sections',
        'total_sections',
        'completed_count',
        'last_calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'completion_percentage' => 'integer',
            'has_name' => 'boolean',
            'has_username' => 'boolean',
            'has_avatar' => 'boolean',
            'has_cover' => 'boolean',
            'has_bio' => 'boolean',
            'has_about' => 'boolean',
            'has_education' => 'boolean',
            'has_experience' => 'boolean',
            'has_skills' => 'boolean',
            'has_interests' => 'boolean',
            'has_languages' => 'boolean',
            'has_social_links' => 'boolean',
            'has_location' => 'boolean',
            'missing_sections' => 'array',
            'completed_sections' => 'array',
            'total_sections' => 'integer',
            'completed_count' => 'integer',
            'last_calculated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
