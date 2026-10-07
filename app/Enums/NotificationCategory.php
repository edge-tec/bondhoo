<?php

namespace App\Enums;

enum NotificationCategory: string
{
    case SOCIAL = 'social';
    case MESSAGING = 'messaging';
    case GROUP = 'group';
    case SECURITY = 'security';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::SOCIAL => 'সোশ্যাল',
            self::MESSAGING => 'বার্তা',
            self::GROUP => 'গ্রুপ',
            self::SECURITY => 'নিরাপত্তা',
            self::SYSTEM => 'সিস্টেম',
        };
    }
}
