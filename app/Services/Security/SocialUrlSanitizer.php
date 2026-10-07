<?php

namespace App\Services\Security;

class SocialUrlSanitizer
{
    /**
     * Dangerous URL schemes that are strictly forbidden.
     */
    protected const DISALLOWED_SCHEMES = [
        'javascript:',
        'vbscript:',
        'data:',
        'file:',
        'blob:',
        'about:',
        'chrome:',
    ];

    /**
     * Check if a URL contains dangerous schemes or malicious patterns.
     */
    public static function isMalicious(string $url): bool
    {
        $clean = strtolower(trim($url));

        // Remove whitespace and control characters within scheme
        $cleanWithoutControl = preg_replace('/[\x00-\x1F\x7F]/', '', $clean);
        $cleanWithoutWhitespace = preg_replace('/\s+/', '', $cleanWithoutControl);

        foreach (self::DISALLOWED_SCHEMES as $scheme) {
            if (str_starts_with($cleanWithoutWhitespace, $scheme)) {
                return true;
            }
            if (str_contains($cleanWithoutWhitespace, $scheme)) {
                return true;
            }
        }

        // Detect HTML tags or quote injection
        if (preg_match('/[<>"\'`]/', $url)) {
            return true;
        }

        return false;
    }

    /**
     * Normalize URL:
     * - Trims whitespace
     * - Auto-prepends https:// if scheme is omitted
     * - Validates scheme and host
     */
    public static function normalize(string $url): ?string
    {
        $url = trim($url);

        if (self::isMalicious($url)) {
            return null;
        }

        // If user provided a URL without scheme e.g. "github.com/myname" or "www.example.com"
        if (! preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $url)) {
            $url = 'https://'.ltrim($url, '/');
        }

        $parts = parse_url($url);
        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        // Ensure host has at least one dot or is valid domain
        if (! str_contains($parts['host'], '.')) {
            return null;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $url;
    }
}
