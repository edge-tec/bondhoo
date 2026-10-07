<?php

namespace App\Services\Security;

use App\Models\SecurityRiskProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EnterpriseSecurityService
{
    /**
     * Evaluate login risk for incoming authentication request.
     *
     * @return array{risk_score: float, risk_level: string, requires_mfa: bool, flags: string[]}
     */
    public function evaluateLoginRisk(User $user, Request $request): array
    {
        $fingerprint = $this->generateFingerprint($request);
        $ip = $request->ip() ?? '127.0.0.1';
        $country = $request->header('CF-IPCountry', 'BD');

        $profile = SecurityRiskProfile::firstOrNew(
            ['user_id' => $user->id, 'device_fingerprint' => $fingerprint]
        );

        $flags = [];
        $riskScore = 5.0; // Base score

        // 1. Is this a brand new unseen device?
        if (! $profile->exists) {
            $flags[] = 'new_device_detected';
            $riskScore += 25.0;
        }

        // 2. Geo anomaly check (did country change rapidly?)
        if ($profile->country_code && $profile->country_code !== $country) {
            $flags[] = "geo_anomaly:{$profile->country_code}->{$country}";
            $riskScore += 40.0;
        }

        // 3. Credential stuffing frequency check on IP
        $ipAttemptsKey = "sec:ip_attempts:{$ip}";
        $attempts = (int) Cache::get($ipAttemptsKey, 0);
        if ($attempts > 10) {
            $flags[] = 'credential_stuffing_suspected';
            $riskScore += 35.0;
        }

        $riskScore = min(100.0, round($riskScore, 2));

        $riskLevel = match (true) {
            $riskScore >= 70.0 => 'critical',
            $riskScore >= 45.0 => 'high',
            $riskScore >= 20.0 => 'medium',
            default => 'low',
        };

        $requiresMfa = ($riskScore >= 45.0);

        // Update risk profile ledger
        $profile->fill([
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'last_ip' => $ip,
            'country_code' => $country,
            'is_suspicious' => ($riskScore >= 45.0),
            'mfa_required' => $requiresMfa,
            'anomaly_flags' => $flags,
            'last_assessed_at' => now(),
        ])->save();

        return [
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'requires_mfa' => $requiresMfa,
            'flags' => $flags,
        ];
    }

    /**
     * Compute stable device fingerprint from headers.
     */
    public function generateFingerprint(Request $request): string
    {
        $raw = implode('|', [
            $request->userAgent() ?? 'unknown-agent',
            $request->header('Accept-Language', 'bn,en'),
            $request->header('Sec-Ch-Ua-Platform', 'unknown-os'),
        ]);

        return hash('sha256', $raw);
    }

    /**
     * Check if the device making the request is recognized as a trusted device for the user.
     */
    public function isTrustedDevice(User $user, Request $request): bool
    {
        $fingerprint = $this->generateFingerprint($request);

        return $user->trustedDevices()
            ->where('device_fingerprint', $fingerprint)
            ->where(function ($query) {
                $query->whereNull('trusted_until')
                    ->orWhere('trusted_until', '>', now());
            })
            ->exists();
    }
}
