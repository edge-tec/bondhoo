<?php

namespace App\Support;

use App\Models\User;
use App\Models\UsernameHistory;

class ProfileResolver
{
    /**
     * Get the canonical public profile URL for a user.
     * Never exposes raw internal database ID.
     */
    public static function url(object|array|string|null $user): string
    {
        if (! $user) {
            return '/profile';
        }

        if (is_string($user)) {
            $clean = ltrim(trim($user), '@');

            return $clean !== '' ? '/u/'.rawurlencode($clean) : '/profile';
        }

        if ($user instanceof User) {
            $username = $user->username;
        } elseif (is_array($user)) {
            $username = $user['username'] ?? $user['handle'] ?? null;
        } elseif (is_object($user)) {
            $username = $user->username ?? $user->handle ?? null;
        } else {
            $username = null;
        }

        if ($username) {
            return '/u/'.rawurlencode(ltrim($username, '@'));
        }

        // If only ID is present on a user object, resolve to username if possible
        $id = is_array($user) ? ($user['id'] ?? null) : (is_object($user) ? ($user->id ?? null) : null);
        if ($id && is_numeric($id)) {
            $resolvedUser = User::find((int) $id);
            if ($resolvedUser && $resolvedUser->username) {
                return '/u/'.rawurlencode($resolvedUser->username);
            }
        }

        return '/profile';
    }

    /**
     * Resolve a user from a given identity (username or slug or history or fallback ID).
     */
    public static function resolve(string|int|null $identity): ?User
    {
        if ($identity === null) {
            return null;
        }

        $clean = is_string($identity) ? ltrim(trim(strtolower($identity)), '@') : (string) $identity;
        if ($clean === '') {
            return null;
        }

        $user = User::where('username', $clean)->first();
        if ($user) {
            return $user;
        }

        // Check username change history
        $history = UsernameHistory::where('username', $clean)->latest('id')->first();
        if ($history && $history->user) {
            return $history->user;
        }

        // Fallback for internal numeric ID
        if (is_numeric($clean)) {
            return User::find((int) $clean);
        }

        return null;
    }
}
