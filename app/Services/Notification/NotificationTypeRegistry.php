<?php

namespace App\Services\Notification;

use App\Enums\NotificationCategory;

/**
 * Enterprise Notification Registry:
 * Maps and normalizes domain notification types into categories, priorities,
 * deep links, and accessible SVG icon templates.
 */
class NotificationTypeRegistry
{
    /**
     * Map of supported notification types with metadata.
     *
     * @var array<string, array<string, mixed>>
     */
    protected static array $registry = [
        // Social Activities
        'live.started' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'video',
            'priority' => 'high',
            'default_title' => 'লাইভ ভিডিও সম্প্রচার',
            'action_url' => '/live',
        ],
        'social.friend_request' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'user_plus',
            'priority' => 'normal',
            'default_title' => 'নতুন ফ্রেন্ড রিকোয়েস্ট',
            'action_url' => '/friends',
        ],
        'friend.request' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'user_plus',
            'priority' => 'normal',
            'default_title' => 'নতুন ফ্রেন্ড রিকোয়েস্ট',
            'action_url' => '/friends',
        ],
        'social.friend_accept' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'user_check',
            'priority' => 'normal',
            'default_title' => 'ফ্রেন্ড রিকোয়েস্ট গৃহীত হয়েছে',
            'action_url' => '/friends',
        ],
        'friend.accepted' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'user_check',
            'priority' => 'normal',
            'default_title' => 'ফ্রেন্ড রিকোয়েস্ট গৃহীত হয়েছে',
            'action_url' => '/friends',
        ],
        'social.follow' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'user_plus',
            'priority' => 'normal',
            'default_title' => 'নতুন ফলোয়ার',
            'action_url' => '/profile',
        ],
        'page.followed' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'user_plus',
            'priority' => 'normal',
            'default_title' => 'পেইজে নতুন ফলোয়ার',
            'action_url' => '/pages',
        ],
        'page.role_assigned' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'shield',
            'priority' => 'high',
            'default_title' => 'পেইজে নতুন দায়িত্ব অর্পণ',
            'action_url' => '/pages',
        ],
        'page.post_published' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'file_text',
            'priority' => 'normal',
            'default_title' => 'পেইজে নতুন পোস্ট',
            'action_url' => '/pages',
        ],
        'social.like' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'heart',
            'priority' => 'normal',
            'default_title' => 'পোস্টে লাইক',
            'action_url' => '/',
        ],
        'social.reaction' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'reaction',
            'priority' => 'normal',
            'default_title' => 'নতুন প্রতিক্রিয়া',
            'action_url' => '/',
        ],
        'notification.reaction' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'reaction',
            'priority' => 'normal',
            'default_title' => 'নতুন প্রতিক্রিয়া',
            'action_url' => '/',
        ],
        'social.comment' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'message_circle',
            'priority' => 'normal',
            'default_title' => 'পোস্টে নতুন মন্তব্য',
            'action_url' => '/',
        ],
        'notification.comment' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'message_circle',
            'priority' => 'normal',
            'default_title' => 'পোস্টে নতুন মন্তব্য',
            'action_url' => '/',
        ],
        'social.reply' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'message_square',
            'priority' => 'normal',
            'default_title' => 'মন্তব্যের উত্তর',
            'action_url' => '/',
        ],
        'social.mention' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'at_sign',
            'priority' => 'important',
            'default_title' => 'আপনাকে উল্লেখ (মেনশন) করা হয়েছে',
            'action_url' => '/',
        ],
        'social.share' => [
            'category' => NotificationCategory::SOCIAL,
            'icon' => 'share',
            'priority' => 'normal',
            'default_title' => 'পোস্ট শেয়ার করা হয়েছে',
            'action_url' => '/',
        ],

        // Messaging
        'messaging.new_message' => [
            'category' => NotificationCategory::MESSAGING,
            'icon' => 'mail',
            'priority' => 'normal',
            'default_title' => 'নতুন বার্তা',
            'action_url' => '/messages',
        ],

        // Groups
        'group.invitation' => [
            'category' => NotificationCategory::GROUP,
            'icon' => 'users',
            'priority' => 'normal',
            'default_title' => 'গ্রুপে আমন্ত্রণ',
            'action_url' => '/groups',
        ],
        'group.join_request' => [
            'category' => NotificationCategory::GROUP,
            'icon' => 'user_plus',
            'priority' => 'important',
            'default_title' => 'গ্রুপে যুক্ত হওয়ার অনুরোধ',
            'action_url' => '/groups',
        ],
        'group.member_activity' => [
            'category' => NotificationCategory::GROUP,
            'icon' => 'users',
            'priority' => 'normal',
            'default_title' => 'গ্রুপ কার্যক্রম',
            'action_url' => '/groups',
        ],
        'group.post_activity' => [
            'category' => NotificationCategory::GROUP,
            'icon' => 'file_text',
            'priority' => 'normal',
            'default_title' => 'গ্রুপে নতুন পোস্ট',
            'action_url' => '/groups',
        ],

        // Security Alerts
        'security.login' => [
            'category' => NotificationCategory::SECURITY,
            'icon' => 'shield',
            'priority' => 'security',
            'default_title' => 'নতুন ডিভাইস থেকে লগইন সতর্কতা',
            'action_url' => '/devices',
        ],
        'security.password_change' => [
            'category' => NotificationCategory::SECURITY,
            'icon' => 'lock',
            'priority' => 'security',
            'default_title' => 'পাসওয়ার্ড পরিবর্তন করা হয়েছে',
            'action_url' => '/settings',
        ],
        'security.new_device' => [
            'category' => NotificationCategory::SECURITY,
            'icon' => 'shield',
            'priority' => 'security',
            'default_title' => 'নতুন ডিভাইস শনাক্ত হয়েছে',
            'action_url' => '/devices',
        ],
        'security.alert' => [
            'category' => NotificationCategory::SECURITY,
            'icon' => 'shield_alert',
            'priority' => 'critical',
            'default_title' => 'জরুরি নিরাপত্তা সতর্কতা',
            'action_url' => '/settings',
        ],

        // System & Accounts
        'system.announcement' => [
            'category' => NotificationCategory::SYSTEM,
            'icon' => 'bell',
            'priority' => 'important',
            'default_title' => 'সিস্টেম ঘোষণা',
            'action_url' => '/',
        ],
        'system.account' => [
            'category' => NotificationCategory::SYSTEM,
            'icon' => 'bell',
            'priority' => 'normal',
            'default_title' => 'অ্যাকাউন্ট সংক্রান্ত বার্তা',
            'action_url' => '/settings',
        ],
        'notification.welcome' => [
            'category' => NotificationCategory::SYSTEM,
            'icon' => 'bell',
            'priority' => 'normal',
            'default_title' => 'যুগাজুগে স্বাগতম!',
            'action_url' => '/',
        ],
    ];

    /**
     * Resolve category for any notification type string.
     */
    public static function resolveCategory(string $type): NotificationCategory
    {
        if (isset(self::$registry[$type]['category'])) {
            return self::$registry[$type]['category'];
        }

        $lower = strtolower($type);
        if (str_contains($lower, 'security') || str_contains($lower, 'login') || str_contains($lower, 'password') || str_contains($lower, 'device') || str_contains($lower, '2fa') || str_contains($lower, 'suspicious')) {
            return NotificationCategory::SECURITY;
        }

        if (str_contains($lower, 'group')) {
            return NotificationCategory::GROUP;
        }

        if (str_contains($lower, 'message') || str_contains($lower, 'call') || str_contains($lower, 'chat')) {
            return NotificationCategory::MESSAGING;
        }

        if (str_contains($lower, 'system') || str_contains($lower, 'welcome') || str_contains($lower, 'announcement') || str_contains($lower, 'wallet') || str_contains($lower, 'payment')) {
            return NotificationCategory::SYSTEM;
        }

        return NotificationCategory::SOCIAL;
    }

    /**
     * Resolve icon identifier for any notification type.
     */
    public static function resolveIcon(string $type): string
    {
        if (isset(self::$registry[$type]['icon'])) {
            return self::$registry[$type]['icon'];
        }

        $lower = strtolower($type);
        if (str_contains($lower, 'friend') && (str_contains($lower, 'accept') || str_contains($lower, 'connected'))) {
            return 'user_check';
        }
        if (str_contains($lower, 'friend') || str_contains($lower, 'follow')) {
            return 'user_plus';
        }
        if (str_contains($lower, 'like')) {
            return 'heart';
        }
        if (str_contains($lower, 'reaction')) {
            return 'reaction';
        }
        if (str_contains($lower, 'reply')) {
            return 'message_square';
        }
        if (str_contains($lower, 'comment')) {
            return 'message_circle';
        }
        if (str_contains($lower, 'mention')) {
            return 'at_sign';
        }
        if (str_contains($lower, 'share')) {
            return 'share';
        }
        if (str_contains($lower, 'group')) {
            return 'users';
        }
        if (str_contains($lower, 'security') || str_contains($lower, 'login') || str_contains($lower, 'suspicious')) {
            return 'shield';
        }
        if (str_contains($lower, 'lock') || str_contains($lower, 'password')) {
            return 'lock';
        }
        if (str_contains($lower, 'message')) {
            return 'mail';
        }

        return 'bell';
    }

    /**
     * Resolve priority for any notification type.
     */
    public static function resolvePriority(string $type): string
    {
        return self::$registry[$type]['priority'] ?? 'normal';
    }

    /**
     * Generate modern SVG icon markup for the specified icon key.
     * Pure SVG, optical sizing, accessible stroke language, no emojis.
     */
    public static function renderSvgIcon(string $icon, int $size = 20, ?string $strokeColor = 'currentColor'): string
    {
        return match ($icon) {
            'user_plus' => <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 24 24" fill="none" stroke="{$strokeColor}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
    <circle cx="8.5" cy="7" r="4"></circle>
    <line x1="20" y1="8" x2="20" y2="14"></line>
    <line x1="23" y1="11" x2="17" y2="11"></line>
</svg>
SVG,
            'user_check' => <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 24 24" fill="none" stroke="{$strokeColor}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
    <circle cx="8.5" cy="7" r="4"></circle>
    <polyline points="17 11 19 13 23 9"></polyline>
</svg>
SVG,
            'heart' => <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true">
    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
</svg>
SVG,
            'reaction' => <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 24 24" fill="none" stroke="{$strokeColor}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <circle cx="12" cy="12" r="10"></circle>
    <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
    <line x1="9" y1="9" x2="9.01" y2="9"></line>
    <line x1="15" y1="9" x2="15.01" y2="9"></line>
</svg>
SVG,
            'message_circle' => <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 24 24" fill="none" stroke="{$strokeColor}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
</svg>
SVG,
            'message_square' => <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 24 24" fill="none" stroke="{$strokeColor}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
</svg>
SVG,
            'at_sign' => <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 24 24" fill="none" stroke="{$strokeColor}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <circle cx="12" cy="12" r="4"></circle>
    <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"></path>
</svg>
SVG,
            'share' => <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 24 24" fill="none" stroke="{$strokeColor}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <circle cx="18" cy="5" r="3"></circle>
    <circle cx="6" cy="12" r="3"></circle>
    <circle cx="18" cy="19" r="3"></circle>
    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
</svg>
SVG,
            'users' => <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 24 24" fill="none" stroke="{$strokeColor}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
    <circle cx="9" cy="7" r="4"></circle>
    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
</svg>
SVG,
            'shield', 'shield_alert' => <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 24 24" fill="none" stroke="{$strokeColor}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
    <line x1="12" y1="8" x2="12" y2="12"></line>
    <line x1="12" y1="16" x2="12.01" y2="16"></line>
</svg>
SVG,
            'lock' => <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 24 24" fill="none" stroke="{$strokeColor}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
</svg>
SVG,
            'mail' => <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 24 24" fill="none" stroke="{$strokeColor}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
    <polyline points="22,6 12,13 2,6"></polyline>
</svg>
SVG,
            default => <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 24 24" fill="none" stroke="{$strokeColor}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
</svg>
SVG,
        };
    }
}
