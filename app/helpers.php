<?php

use App\Models\User;
use App\Support\ProfileResolver;

if (! function_exists('getUserProfileUrl')) {
    /**
     * Centralized canonical profile URL generator.
     * Never exposes raw internal database ID to the public UI.
     */
    function getUserProfileUrl(object|array|string|null $user): string
    {
        return ProfileResolver::url($user);
    }
}

if (! function_exists('resolveUserProfile')) {
    /**
     * Centralized profile identity resolver.
     */
    function resolveUserProfile(string|int|null $identity): ?User
    {
        return ProfileResolver::resolve($identity);
    }
}
