<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

/**
 * SocSecurityService — সিকিউরিটি অপারেশন্স সেন্টার (SOC) সার্ভিস
 *
 * এই সার্ভিসটি অডিট লগিং, ম্যালওয়্যার স্ক্যানিং এবং অ্যানোমালি ডিটেকশন
 * (যেমন: ইম্পসিবল ট্রাভেল, সেশন হাইজ্যাকিং) পর্যবেক্ষণ করে।
 */
class SocSecurityService
{
    /**
     * EICAR স্ট্যান্ডার্ড অ্যান্টি-ভাইরাস টেস্ট স্ট্রিং
     */
    public const EICAR_SIGNATURE = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';

    /**
     * ক্ষতিকর এক্সটেনশন তালিকা
     */
    public const BLOCKED_EXTENSIONS = ['php', 'exe', 'sh', 'bat', 'vbs', 'phtml', 'phar'];

    /**
     * অডিট লগ রেকর্ড করা।
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function logAction(
        ?int $userId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ip ?: request()->ip(),
            'user_agent' => $userAgent ?: request()->userAgent(),
        ]);
    }

    /**
     * আপলোডকৃত ফাইল ম্যালওয়্যার ও ভাইরাস মুক্ত কিনা স্ক্যান করা।
     *
     * @return array{is_safe: bool, threat_detected: ?string}
     */
    public function scanFile(UploadedFile|string $file): array
    {
        $extension = '';
        $content = '';

        if ($file instanceof UploadedFile) {
            $extension = strtolower($file->getClientOriginalExtension());
            $content = file_get_contents($file->getRealPath());
        } elseif (is_string($file) && file_exists($file)) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $content = file_get_contents($file);
        } else {
            $content = (string) $file;
        }

        // ১. বিপজ্জনক এক্সটেনশন চেক
        if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            return [
                'is_safe' => false,
                'threat_detected' => "Blocked malicious file extension: .{$extension}",
            ];
        }

        // ২. EICAR ভাইরাস টেস্ট স্ট্রিং ম্যাচিং
        if (str_contains($content, self::EICAR_SIGNATURE)) {
            return [
                'is_safe' => false,
                'threat_detected' => 'Malware signature detected (EICAR Test Signature)',
            ];
        }

        return [
            'is_safe' => true,
            'threat_detected' => null,
        ];
    }

    /**
     * ইম্পসিবল ট্রাভেল অ্যানোমালি ডিটেকশন (ভিন্ন দেশ থেকে অবিশ্বাস্য দ্রুত সময়ের মধ্যে লগইন)।
     *
     * @return array{anomaly_detected: bool, reason: ?string}
     */
    public function detectImpossibleTravel(User $user, string $currentCountry, string $currentIp): array
    {
        $lastLogin = SecurityEvent::where('user_id', $user->id)
            ->where('event_type', 'login')
            ->latest()
            ->first();

        if ($lastLogin && $lastLogin->country_code && $lastLogin->country_code !== $currentCountry) {
            $diffMinutes = $lastLogin->created_at->diffInMinutes(now());

            // যদি ১ ঘণ্টার কম সময়ে ভিন্ন দেশ থেকে লগইন হয়, তা অসম্ভব ভ্রমণ হিসেবে গণ্য হবে
            if ($diffMinutes < 60) {
                SecurityEvent::create([
                    'user_id' => $user->id,
                    'event_type' => 'impossible_travel',
                    'severity' => 'critical',
                    'ip_address' => $currentIp,
                    'country_code' => $currentCountry,
                    'user_agent' => request()->userAgent(),
                    'details' => [
                        'previous_country' => $lastLogin->country_code,
                        'current_country' => $currentCountry,
                        'time_difference_minutes' => $diffMinutes,
                    ],
                ]);

                return [
                    'anomaly_detected' => true,
                    'reason' => "Impossible travel detected: {$lastLogin->country_code} to {$currentCountry} in {$diffMinutes} mins",
                ];
            }
        }

        return [
            'anomaly_detected' => false,
            'reason' => null,
        ];
    }

    /**
     * সাম্প্রতিক সিকিউরিটি ইভেন্টসমূহ সংগ্রহ।
     *
     * @return Collection<int, SecurityEvent>
     */
    public function getRecentEvents(int $limit = 15)
    {
        return SecurityEvent::with('user')->latest()->take($limit)->get();
    }
}
