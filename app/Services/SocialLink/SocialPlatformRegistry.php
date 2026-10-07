<?php

namespace App\Services\SocialLink;

class SocialPlatformRegistry
{
    /**
     * Predefined supported platforms with metadata.
     */
    public const PLATFORMS = [
        'website' => [
            'key' => 'website',
            'name' => 'Website',
            'icon' => 'globe',
            'domain_hint' => '',
        ],
        'facebook' => [
            'key' => 'facebook',
            'name' => 'Facebook',
            'icon' => 'facebook',
            'domain_hint' => 'facebook.com',
        ],
        'instagram' => [
            'key' => 'instagram',
            'name' => 'Instagram',
            'icon' => 'instagram',
            'domain_hint' => 'instagram.com',
        ],
        'youtube' => [
            'key' => 'youtube',
            'name' => 'YouTube',
            'icon' => 'youtube',
            'domain_hint' => 'youtube.com',
        ],
        'linkedin' => [
            'key' => 'linkedin',
            'name' => 'LinkedIn',
            'icon' => 'linkedin',
            'domain_hint' => 'linkedin.com',
        ],
        'x' => [
            'key' => 'x',
            'name' => 'X',
            'icon' => 'x',
            'domain_hint' => 'x.com',
        ],
        'tiktok' => [
            'key' => 'tiktok',
            'name' => 'TikTok',
            'icon' => 'tiktok',
            'domain_hint' => 'tiktok.com',
        ],
        'github' => [
            'key' => 'github',
            'name' => 'GitHub',
            'icon' => 'github',
            'domain_hint' => 'github.com',
        ],
    ];

    /**
     * Normalize platform identifier.
     */
    public static function normalizePlatform(string $platform): string
    {
        $normalized = strtolower(trim($platform));

        if ($normalized === 'twitter') {
            return 'x';
        }

        return $normalized;
    }

    /**
     * Get platform display title.
     */
    public static function getPlatformName(string $platform): string
    {
        $key = self::normalizePlatform($platform);

        return self::PLATFORMS[$key]['name'] ?? ucfirst($platform);
    }

    /**
     * Get all supported platforms list.
     */
    public static function getSupportedPlatforms(): array
    {
        return self::PLATFORMS;
    }
}
