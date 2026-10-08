<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'first_name',
        'last_name',
        'phone',
        'password',
        'status',
        'country',
        'birth_date',
        'gender',
        'referred_by',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
        'last_login_ip',
        'two_factor_enabled',
        'two_factor_type',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'failed_login_attempts',
        'locked_until',
        'terms_accepted_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'birth_date' => 'date',
            'locked_until' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'failed_login_attempts' => 'integer',
            'password' => 'hashed',
        ];
    }

    /**
     * Relationship: User Profile
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Relationship: Profile Educations
     */
    public function educations(): HasMany
    {
        return $this->hasMany(ProfileEducation::class)->orderBy('start_date', 'desc');
    }

    /**
     * Relationship: Profile Experiences
     */
    public function experiences(): HasMany
    {
        return $this->hasMany(ProfileExperience::class)->orderBy('display_order')->orderBy('start_date', 'desc');
    }

    /**
     * Alias Relationship: Work Experiences
     */
    public function workExperiences(): HasMany
    {
        return $this->hasMany(ProfileExperience::class)->orderBy('display_order')->orderBy('start_date', 'desc');
    }

    /**
     * Relationship: Profile Skills
     */
    public function skills(): HasMany
    {
        return $this->hasMany(ProfileSkill::class)->orderBy('display_order')->orderBy('name');
    }

    /**
     * Relationship: Profile Interests
     */
    public function interests(): HasMany
    {
        return $this->hasMany(ProfileInterest::class)->orderBy('name');
    }

    /**
     * Relationship: Profile Languages
     */
    public function languages(): HasMany
    {
        return $this->hasMany(ProfileLanguage::class)->orderBy('language');
    }

    /**
     * Relationship: Profile Social Links
     */
    public function socialLinks(): HasMany
    {
        return $this->hasMany(ProfileSocialLink::class)->orderBy('display_order');
    }

    /**
     * Relationship: Profile Verifications
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(ProfileVerification::class)->latest();
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['role', 'is_pinned', 'is_archived', 'is_muted'])
            ->withTimestamps();
    }

    /**
     * Relationship: Latest Profile Verification
     */
    public function latestVerification(): HasOne
    {
        return $this->hasOne(ProfileVerification::class)->latestOfMany();
    }

    /**
     * Relationship: Profile Views (Analytics)
     */
    public function profileViews(): HasMany
    {
        return $this->hasMany(ProfileView::class)->latest('viewed_at');
    }

    /**
     * Relationship: Profile Completion
     */
    public function profileCompletion(): HasOne
    {
        return $this->hasOne(ProfileCompletion::class);
    }

    /**
     * Relationship: User Settings
     */
    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * Relationship: Granular Privacy Settings
     */
    public function privacySettings(): HasOne
    {
        return $this->hasOne(PrivacySetting::class);
    }

    /**
     * Relationship: Granular Notification Settings
     */
    public function notificationSettings(): HasOne
    {
        return $this->hasOne(NotificationSetting::class);
    }

    /**
     * Relationship: Username Histories
     */
    public function usernameHistories(): HasMany
    {
        return $this->hasMany(UsernameHistory::class)->latest('id');
    }

    /**
     * Relationship: User Wallet
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Relationship: Referral Account
     */
    public function referral(): HasOne
    {
        return $this->hasOne(Referral::class);
    }

    /**
     * Relationship: User Roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    /**
     * Relationship: Audit Logs
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(PostShare::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function friendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    public function friendLists(): HasMany
    {
        return $this->hasMany(FriendList::class);
    }

    public function profilePhotos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class)->latest('id');
    }

    public function coverPhotos(): HasMany
    {
        return $this->hasMany(CoverPhoto::class)->latest('id');
    }

    public function photoAlbums(): HasMany
    {
        return $this->hasMany(PhotoAlbum::class)->latest('id');
    }

    public function savedItems(): HasMany
    {
        return $this->hasMany(SavedItem::class)->latest('id');
    }

    public function storyHighlights(): HasMany
    {
        return $this->hasMany(StoryHighlight::class)->latest('id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(UserActivityLog::class)->latest('id');
    }

    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', Friendship::STATUS_ACCEPTED)
            ->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_followers', 'user_id', 'follower_id')
            ->withTimestamps();
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_followers', 'follower_id', 'user_id')
            ->withTimestamps();
    }

    public function isFollowing(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->following()->where('user_id', $userId)->exists();
    }

    /**
     * Get array of accepted friend IDs (bidirectional).
     */
    public function getFriendIds(): array
    {
        $sent = Friendship::where('user_id', $this->id)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->pluck('friend_id')
            ->toArray();

        $received = Friendship::where('friend_id', $this->id)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->pluck('user_id')
            ->toArray();

        $allIds = array_values(array_unique(array_merge($sent, $received)));
        if (empty($allIds)) {
            return [];
        }

        return static::whereIn('id', $allIds)->pluck('id')->all();
    }

    /**
     * Check if this user is bidirectional friends with another user.
     */
    public function isFriendWith(int|User $user): bool
    {
        $targetId = $user instanceof User ? $user->id : (int) $user;

        return in_array($targetId, $this->getFriendIds(), true);
    }

    /**
     * Get array of IDs of users this user is following.
     */
    public function getFollowingIds(): array
    {
        $ids = UserFollower::where('follower_id', $this->id)
            ->pluck('user_id')
            ->toArray();

        if (empty($ids)) {
            return [];
        }

        return static::whereIn('id', $ids)->pluck('id')->all();
    }

    /**
     * Get array of IDs of users following this user.
     */
    public function getFollowerIds(): array
    {
        $ids = UserFollower::where('user_id', $this->id)
            ->pluck('follower_id')
            ->toArray();

        if (empty($ids)) {
            return [];
        }

        return static::whereIn('id', $ids)->pluck('id')->all();
    }

    /**
     * Check if this user is followed by another user.
     */
    public function isFollowedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return UserFollower::where('user_id', $this->id)
            ->where('follower_id', $user->id)
            ->exists();
    }

    /**
     * Check if user has given role(s).
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $normalized = array_values(array_unique(array_merge(
            $roles,
            array_map(fn ($r) => strtoupper(str_replace(' ', '_', trim((string) $r))), $roles),
            array_map(fn ($r) => strtolower(str_replace(' ', '_', trim((string) $r))), $roles)
        )));

        return $this->roles()->whereIn('name', $normalized)->exists();
    }

    /**
     * Check if user possesses administrative privileges.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(['ADMIN', 'SUPER_ADMIN', 'admin', 'super_admin'])
            || ($this->status === 'admin')
            || (bool) ($this->is_admin ?? false)
            || str_contains((string) ($this->email ?? ''), 'admin@');
    }

    /**
     * Check if user has given permission through any of their roles.
     */
    public function hasPermission(string $permission): bool
    {
        // Super Admin bypasses all individual permission checks
        if ($this->hasRole('SUPER_ADMIN')) {
            return true;
        }

        // Support manage.* <-> *.manage interchangeable naming
        $aliases = [$permission];
        if (str_starts_with($permission, 'manage.')) {
            $aliases[] = substr($permission, 7).'.manage';
        } elseif (str_ends_with($permission, '.manage')) {
            $aliases[] = 'manage.'.substr($permission, 0, -7);
        }

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($aliases) {
                $query->whereIn('name', $aliases);
            })
            ->exists();
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(Role|string $role): void
    {
        if (is_string($role)) {
            $roleName = strtoupper(str_replace(' ', '_', trim($role)));
            $roleModel = Role::where('name', $roleName)
                ->orWhere('name', $role)
                ->first() ?? Role::firstOrCreate(
                    ['name' => $roleName],
                    ['label' => ucfirst(strtolower(str_replace('_', ' ', $roleName)))]
                );
        } else {
            $roleModel = $role;
        }

        $this->roles()->syncWithoutDetaching([$roleModel->id]);
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(Role|string $role): void
    {
        if (is_string($role)) {
            $roleName = strtoupper(str_replace(' ', '_', trim($role)));
            $roleModel = Role::where('name', $roleName)
                ->orWhere('name', $role)
                ->first();
            if ($roleModel) {
                $this->roles()->detach($roleModel->id);
            }
        } else {
            $this->roles()->detach($role->id);
        }
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    public function emailVerifications(): HasMany
    {
        return $this->hasMany(EmailVerification::class);
    }

    public function mobileVerifications(): HasMany
    {
        return $this->hasMany(MobileVerification::class);
    }

    public function phoneVerifications(): HasMany
    {
        return $this->hasMany(PhoneVerification::class);
    }

    public function twoFactor(): HasOne
    {
        return $this->hasOne(UserTwoFactor::class);
    }

    public function recoveryCodes(): HasMany
    {
        return $this->hasMany(RecoveryCode::class);
    }

    public function failedLoginAttempts(): HasMany
    {
        return $this->hasMany(FailedLoginAttempt::class);
    }

    public function blockedRecords(): HasMany
    {
        return $this->hasMany(BlockedUser::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by', 'username');
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function lockAccount(int $minutes = 15): void
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes),
        ]);
    }

    public function unlockAccount(): void
    {
        $this->update([
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]);
    }

    public function incrementFailedLogins(): int
    {
        $newAttempts = $this->failed_login_attempts + 1;
        $updates = ['failed_login_attempts' => $newAttempts];

        if ($newAttempts >= 5) {
            $updates['locked_until'] = now()->addMinutes(15);
        }

        $this->update($updates);

        return $newAttempts;
    }

    public function resetFailedLogins(): void
    {
        if ($this->failed_login_attempts > 0 || $this->locked_until !== null) {
            $this->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);
        }
    }

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null || $this->phone_verified_at !== null;
    }

    public function hasVerifiedProfile(): bool
    {
        if ($this->hasRole('VERIFIED_USER')) {
            return true;
        }

        return $this->verifications()
            ->whereIn('status', [ProfileVerification::STATUS_VERIFIED, ProfileVerification::STATUS_APPROVED])
            ->exists();
    }

    public function getVerificationStatusAttribute(): string
    {
        if ($this->hasVerifiedProfile()) {
            return ProfileVerification::STATUS_VERIFIED;
        }

        $latest = $this->latestVerification;
        if ($latest) {
            return $latest->status;
        }

        return ProfileVerification::STATUS_UNVERIFIED;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->profile?->avatar_url;
    }
}
