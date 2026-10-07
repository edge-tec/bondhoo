<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Models\NotificationSetting;
use App\Models\PrivacySetting;
use App\Models\Referral;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use App\Models\Wallet;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user with default profile and settings.
     */
    public function register(array $data, ?string $ip = null, ?string $userAgent = null): array
    {
        return DB::transaction(function () use ($data, $ip, $userAgent) {
            $firstName = $data['first_name'] ?? null;
            $lastName = $data['last_name'] ?? null;
            $fullName = $data['name'] ?? trim("{$firstName} {$lastName}");

            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $fullName ?: null,
                'username' => strtolower($data['username']),
                'email' => isset($data['email']) ? strtolower($data['email']) : null,
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'], // Casts to hashed in User model
                'status' => 'active',
                'last_login_at' => now(),
                'last_login_ip' => $ip,
            ]);

            // Assign default USER role
            $userRole = Role::firstOrCreate(
                ['name' => 'USER'],
                ['label' => 'Standard User', 'description' => 'Regular platform user']
            );
            $user->roles()->attach($userRole->id);

            // Create initial Profile
            $profile = UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'display_name' => $user->name ?? $user->username,
                'bio' => $data['bio'] ?? null,
                'location' => $data['location'] ?? null,
                'joined_date' => now(),
            ]);

            // Create initial Privacy Settings
            PrivacySetting::create([
                'user_id' => $user->id,
                'profile_visibility' => 'public',
                'post_default_privacy' => 'public',
                'friends_list_visibility' => 'public',
                'search_engine_indexing' => true,
                'phone_visibility' => 'friends',
                'email_visibility' => 'only_me',
                'birthday_visibility' => 'friends',
            ]);

            // Create initial Notification Settings
            NotificationSetting::create([
                'user_id' => $user->id,
                'email_notifications' => true,
                'sms_notifications' => false,
                'push_notifications' => true,
                'friend_request_alerts' => true,
                'comment_alerts' => true,
                'mention_alerts' => true,
                'security_alerts' => true,
            ]);

            // Create initial Settings (backward compatibility)
            $settings = UserSetting::create([
                'user_id' => $user->id,
                'who_can_see_posts' => 'public',
                'who_can_send_friend_requests' => 'everyone',
                'who_can_follow' => 'everyone',
                'who_can_message' => 'everyone',
                'who_can_see_friends' => 'public',
                'story_visibility' => 'friends',
            ]);

            // Create initial Wallet
            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0.00,
                'pending_balance' => 0.00,
                'currency' => 'BDT',
                'status' => 'active',
            ]);

            // Create initial Referral
            Referral::create([
                'user_id' => $user->id,
                'referrer_id' => null,
                'referral_code' => Referral::generateUniqueCode(strtoupper(substr($user->username, 0, 3))),
                'reward_claimed' => false,
                'reward_amount' => 0.00,
                'status' => 'active',
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'auth.registered',
                'entity_type' => User::class,
                'entity_id' => $user->id,
                'new_values' => ['username' => $user->username, 'email' => $user->email],
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);

            $deviceName = $data['device_name'] ?? 'web';
            $token = $user->createToken($deviceName)->plainTextToken;

            return [
                'user' => $user->load(['profile', 'settings', 'privacySettings', 'notificationSettings', 'wallet', 'referral', 'roles']),
                'token' => $token,
            ];
        });
    }

    /**
     * Authenticate a user by email, username, or phone.
     */
    public function login(string $identifier, string $password, string $deviceName = 'auth-token', ?string $ip = null, ?string $userAgent = null): array
    {
        // Check if account was soft-deleted
        $trashedUser = User::onlyTrashed()
            ->where(function ($query) use ($identifier) {
                $query->where('email', strtolower($identifier))
                    ->orWhere('username', strtolower($identifier))
                    ->orWhere('phone', $identifier);
            })
            ->first();

        if ($trashedUser) {
            throw ValidationException::withMessages([
                'identifier' => ['This account has been deleted. Please contact support.'],
            ]);
        }

        $user = User::query()
            ->where(function ($query) use ($identifier) {
                $query->where('email', strtolower($identifier))
                    ->orWhere('username', strtolower($identifier))
                    ->orWhere('phone', $identifier);
            })
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            if ($user) {
                $user->increment('failed_login_attempts');
            }

            LoginHistory::create([
                'user_id' => $user?->id,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'status' => 'failed',
                'failure_reason' => 'Invalid credentials',
            ]);

            throw ValidationException::withMessages([
                'identifier' => ['Invalid login credentials.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw new AuthenticationException("Your account is currently {$user->status}. Please contact support.");
        }

        // Update last login info & reset failed attempts
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'failed_login_attempts' => 0,
        ]);

        LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'status' => 'success',
        ]);

        // Audit log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.login',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        if ($user->two_factor_enabled) {
            $challengeToken = Str::random(40);
            Cache::put('2fa_challenge_'.$challengeToken, $user->id, now()->addMinutes(10));

            return [
                'requires_2fa' => true,
                'challenge_token' => $challengeToken,
                'message' => 'Two-factor authentication code required.',
            ];
        }

        $token = $user->createToken($deviceName)->plainTextToken;

        $warning = null;
        if ($user->email_verified_at === null) {
            $warning = 'Your email address has not been verified yet.';
        }

        return [
            'user' => $user->load(['profile', 'settings', 'roles']),
            'token' => $token,
            'warning' => $warning,
            'email_verified' => $user->email_verified_at !== null,
        ];
    }

    /**
     * Logout from the current device / revoke current token.
     */
    public function logout(User $user): void
    {
        $currentToken = $user->currentAccessToken();
        if ($currentToken) {
            $currentToken->delete();
        }
    }

    /**
     * Logout from all devices / revoke all active tokens.
     */
    public function logoutAllDevices(User $user): void
    {
        $user->tokens()->delete();
    }
}
