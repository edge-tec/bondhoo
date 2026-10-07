<?php

namespace App\Models;

use App\Services\ProfileCompletionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'display_name',
        'slug',
        'first_name',
        'last_name',
        'avatar_url',
        'cover_url',
        'cover_media_id',
        'bio',
        'headline',
        'about',
        'location',
        'country',
        'city',
        'address',
        'hometown',
        'website',
        'birth_date',
        'gender',
        'relationship_status',
        'work',
        'education',
        'interests',
        'social_links',
        'cover_position_y',
        'joined_date',
        'middle_name',
        'religion',
        'blood_group',
        'division',
        'district',
        'upazila',
        'pronouns',
        'category',
        'portfolio',
        'whatsapp',
        'telegram',
        'signal',
        'messenger',
        'hobbies',
        'favorite_music',
        'favorite_books',
        'favorite_movies',
        'is_professional_mode',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'joined_date' => 'datetime',
            'interests' => 'array',
            'social_links' => 'array',
            'hobbies' => 'array',
            'favorite_music' => 'array',
            'favorite_books' => 'array',
            'favorite_movies' => 'array',
            'cover_position_y' => 'integer',
            'is_professional_mode' => 'boolean',
        ];
    }

    /**
     * Calculate dynamic profile completion percentage (0-100%).
     */
    public function calculateCompletionPercentage(): int
    {
        $user = $this->user ?? User::find($this->user_id);
        if ($user) {
            return app(ProfileCompletionService::class)->getCompletion($user)['percentage'];
        }

        return 0;
    }

    protected $appends = [
        'city',
    ];

    public function getCityAttribute(): ?string
    {
        return $this->attributes['city'] ?? $this->location;
    }

    public function setCityAttribute(?string $value): void
    {
        $this->attributes['city'] = $value;
        if (empty($this->attributes['location'])) {
            $this->attributes['location'] = $value;
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(ProfileEducation::class, 'user_id', 'user_id');
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(ProfileExperience::class, 'user_id', 'user_id');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(ProfileSkill::class, 'user_id', 'user_id');
    }

    public function interests(): HasMany
    {
        return $this->hasMany(ProfileInterest::class, 'user_id', 'user_id');
    }

    public function languages(): HasMany
    {
        return $this->hasMany(ProfileLanguage::class, 'user_id', 'user_id');
    }

    public function socialLinks(): HasMany
    {
        return $this->hasMany(ProfileSocialLink::class, 'user_id', 'user_id');
    }

    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function getIntroAttribute(): ?string
    {
        return $this->headline;
    }

    public function setIntroAttribute(?string $value): void
    {
        $this->attributes['headline'] = $value;
    }

    public function getIntroductionAttribute(): ?string
    {
        return $this->headline;
    }

    public function setIntroductionAttribute(?string $value): void
    {
        $this->attributes['headline'] = $value;
    }
}
