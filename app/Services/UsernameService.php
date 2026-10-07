<?php

namespace App\Services;

use App\Events\ProfileUpdatedEvent;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UsernameHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UsernameService
{
    /**
     * Reserved and protected system usernames.
     */
    public const RESERVED_USERNAMES = [
        'admin', 'administrator', 'superadmin', 'root', 'bondhoo', 'jugajug', 'system',
        'support', 'help', 'moderator', 'manager', 'staff', 'security',
        'api', 'auth', 'null', 'undefined', 'login', 'register', 'settings',
        'profile', 'user', 'users', 'timeline', 'feed', 'explore', 'search',
        'messages', 'notifications', 'privacy', 'terms', 'official', 'contact',
        'helpdesk', 'mail', 'email', 'status', 'verify', 'verification',
        'webmaster', 'postmaster', 'hostmaster', 'groups', 'pages', 'marketplace',
        'market', 'live', 'about', 'blog', 'news', 'dashboard', 'devices', 'friends',
        'password', 'sessions', 'wallet', 'referral', 'checkout', 'billing', 'me',
    ];

    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Check availability of a given username.
     */
    public function checkAvailability(string $rawUsername, ?User $currentUser = null): array
    {
        $username = trim($rawUsername);
        $length = mb_strlen($username);

        if ($length < 3) {
            return [
                'username' => $username,
                'is_available' => false,
                'reason' => 'too_short',
                'message' => 'ইউজারনেম সর্বনিম্ন ৩ অক্ষরের হতে হবে।',
            ];
        }

        if ($length > 30) {
            return [
                'username' => $username,
                'is_available' => false,
                'reason' => 'too_long',
                'message' => 'ইউজারনেম সর্বোচ্চ ৩০ অক্ষরের মধ্যে হতে হবে।',
            ];
        }

        if (str_contains($username, '..')) {
            return [
                'username' => $username,
                'is_available' => false,
                'reason' => 'consecutive_dots',
                'message' => 'ইউজারনেমে পরপর একাধিক ডট (..) ব্যবহার করা যাবে না।',
            ];
        }

        if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.-]*[a-zA-Z0-9]$/', $username)) {
            return [
                'username' => $username,
                'is_available' => false,
                'reason' => 'invalid_characters',
                'message' => 'ইউজারনেমে শুধুমাত্র ইংরেজি অক্ষর, সংখ্যা, আন্ডারস্কোর, ডট ও হাইফেন ব্যবহার করা যাবে এবং অক্ষর বা সংখ্যা দিয়ে শুরু ও শেষ হতে হবে।',
            ];
        }

        $normalized = strtolower($username);

        // Reserved usernames
        if (in_array($normalized, self::RESERVED_USERNAMES, true)) {
            return [
                'username' => $normalized,
                'is_available' => false,
                'reason' => 'reserved',
                'message' => 'এই ইউজারনেমটি সিস্টেমের জন্য সংরক্ষিত। অনুগ্রহ করে অন্য ইউজারনেম বেছে নিন।',
            ];
        }

        // Active user existence (case-insensitive)
        $userQuery = User::whereRaw('LOWER(username) = ?', [$normalized]);
        if ($currentUser) {
            $userQuery->where('id', '!=', $currentUser->id);
        }

        if ($userQuery->exists()) {
            return [
                'username' => $normalized,
                'is_available' => false,
                'reason' => 'taken',
                'message' => 'এই ইউজারনেমটি ইতিমধ্যে অন্য একজন ব্যবহারকারী গ্রহণ করেছেন।',
            ];
        }

        // Historical username existence by another user
        $historyQuery = UsernameHistory::whereRaw('LOWER(username) = ?', [$normalized]);
        if ($currentUser) {
            $historyQuery->where('user_id', '!=', $currentUser->id);
        }

        if ($historyQuery->exists()) {
            return [
                'username' => $normalized,
                'is_available' => false,
                'reason' => 'historically_reserved',
                'message' => 'এই ইউজারনেমটি পূর্বে ব্যবহৃত হয়েছে এবং বর্তমানে সংরক্ষিত।',
            ];
        }

        return [
            'username' => $normalized,
            'is_available' => true,
            'reason' => null,
            'message' => 'ইউজারনেমটি ব্যবহারের জন্য উন্মুক্ত রয়েছে।',
        ];
    }

    /**
     * Change username for target user, preserving history and invalidating caches.
     */
    public function changeUsername(
        User $targetUser,
        string $newUsername,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): array {
        $normalized = strtolower(trim($newUsername));
        $oldUsername = strtolower($targetUser->username);

        if ($normalized === $oldUsername) {
            return [
                'user_id' => $targetUser->id,
                'old_username' => $oldUsername,
                'username' => $normalized,
                'profile_url' => "/@{$normalized}",
                'message' => 'ইউজারনেমে কোনো পরিবর্তন করা হয়নি।',
                'previous_usernames' => $targetUser->usernameHistories()->pluck('username')->toArray(),
            ];
        }

        // Validate availability
        $availability = $this->checkAvailability($normalized, $targetUser);
        if (! $availability['is_available']) {
            throw ValidationException::withMessages([
                'username' => [$availability['message']],
            ]);
        }

        return DB::transaction(function () use ($targetUser, $oldUsername, $normalized, $actor, $ip, $userAgent) {
            // 1. Preserve previous username in history
            UsernameHistory::create([
                'user_id' => $targetUser->id,
                'username' => $oldUsername,
            ]);

            // 2. Update user table
            $targetUser->update([
                'username' => $normalized,
            ]);

            // 3. Update profile slug if profile exists
            if ($targetUser->profile) {
                $targetUser->profile->update([
                    'slug' => $normalized,
                ]);
            }

            // 4. Invalidate both old and new username caches
            $this->profileService->invalidateProfileCache($oldUsername);
            $this->profileService->invalidateProfileCache($normalized);

            // 5. Record Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'USERNAME_CHANGED',
                'entity_type' => User::class,
                'entity_id' => $targetUser->id,
                'old_values' => [
                    'username' => $oldUsername,
                    'profile_url' => "/@{$oldUsername}",
                    'slug' => $oldUsername,
                ],
                'new_values' => [
                    'username' => $normalized,
                    'profile_url' => "/@{$normalized}",
                    'slug' => $normalized,
                ],
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // 6. Dispatch event
            event(new ProfileUpdatedEvent(
                user: $targetUser->fresh(),
                updatedSections: ['username'],
                actor: $actor
            ));

            $histories = UsernameHistory::where('user_id', $targetUser->id)
                ->pluck('username')
                ->toArray();

            return [
                'user_id' => $targetUser->id,
                'old_username' => $oldUsername,
                'username' => $normalized,
                'profile_url' => "/@{$normalized}",
                'slug' => $normalized,
                'previous_usernames' => $histories,
                'message' => 'ইউজারনেম সফলভাবে পরিবর্তন করা হয়েছে।',
            ];
        });
    }

    /**
     * Resolve user by username or fallback to historical username for redirection.
     */
    public function resolveUserByUsernameOrHistory(string $username): ?array
    {
        $normalized = strtolower(trim($username));

        // 1. Direct match
        $user = User::whereRaw('LOWER(username) = ?', [$normalized])->first();
        if ($user) {
            return [
                'user' => $user,
                'is_redirect' => false,
                'redirect_to' => null,
            ];
        }

        // 2. Historical match
        $history = UsernameHistory::whereRaw('LOWER(username) = ?', [$normalized])
            ->latest('id')
            ->first();

        if ($history && $history->user) {
            return [
                'user' => $history->user,
                'is_redirect' => true,
                'redirect_to' => $history->user->username,
            ];
        }

        return null;
    }
}
