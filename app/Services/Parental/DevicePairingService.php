<?php

declare(strict_types=1);

namespace App\Services\Parental;

use App\Models\Parental\ChildDevice;
use App\Models\Parental\DevicePairingCode;
use App\Models\Parental\Family;
use App\Models\Parental\FamilyMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class DevicePairingService
 *
 * Provides cryptographic pairing code generation and atomic race-free redemption.
 */
class DevicePairingService
{
    public function __construct(
        protected FamilyService $familyService
    ) {}

    /**
     * Generate a new 6-digit cryptographic pairing code with a strict 10-minute TTL.
     *
     * @return array{code: string, expires_at: string}
     */
    public function generatePairingCode(Family $family, FamilyMember $childMember, User $createdBy): array
    {
        if ($childMember->family_id !== $family->id) {
            throw new InvalidArgumentException('Child member does not belong to the given family.');
        }

        // Generate 6-digit numeric pairing code (100000 to 999999)
        $rawCode = (string) random_int(100000, 999999);
        $codeHash = hash('sha256', $rawCode);
        $expiresAt = now()->addMinutes(10);

        DevicePairingCode::create([
            'family_id' => $family->id,
            'child_member_id' => $childMember->id,
            'code_hash' => $codeHash,
            'expires_at' => $expiresAt,
            'used_at' => null,
            'failed_attempts' => 0,
            'created_by' => $createdBy->id,
            'created_at' => now(),
        ]);

        $this->familyService->logAudit(
            family: $family,
            actorUserId: $createdBy->id,
            role: 'parent',
            action: 'device.pairing_code_generated',
            resourceType: 'device_pairing_code',
            resourceId: null,
            metadata: ['child_member_id' => $childMember->id, 'expires_at' => $expiresAt->toIso8601String()]
        );

        return [
            'code' => $rawCode,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Atomically redeem a pairing code and enroll a child device.
     *
     * @return array{device: ChildDevice, device_token: string}
     */
    public function pairDevice(
        string $plainCode,
        string $deviceUuid,
        string $deviceName,
        string $platform = 'android',
        ?string $androidVersion = null,
        ?string $appVersion = null
    ): array {
        $codeHash = hash('sha256', trim($plainCode));

        return DB::transaction(function () use (
            $codeHash,
            $deviceUuid,
            $deviceName,
            $platform,
            $androidVersion,
            $appVersion
        ): array {
            /** @var DevicePairingCode|null $pairingRecord */
            $pairingRecord = DevicePairingCode::where('code_hash', $codeHash)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $pairingRecord) {
                throw new RuntimeException('Invalid or expired pairing code.');
            }

            if ($pairingRecord->failed_attempts >= 5) {
                throw new RuntimeException('Pairing code has exceeded maximum verification attempts.');
            }

            // Generate high-entropy API token for child device communication
            $rawDeviceToken = 'cpd_'.Str::random(48);
            $tokenHash = hash('sha256', $rawDeviceToken);

            // Register or update child device record
            $device = ChildDevice::updateOrCreate(
                ['device_uuid' => $deviceUuid],
                [
                    'family_id' => $pairingRecord->family_id,
                    'child_member_id' => $pairingRecord->child_member_id,
                    'device_name' => $deviceName,
                    'platform' => $platform,
                    'android_version' => $androidVersion,
                    'app_version' => $appVersion,
                    'device_token_hash' => $tokenHash,
                    'device_status' => 'active',
                    'last_seen_at' => now(),
                    'enrolled_at' => now(),
                    'revoked_at' => null,
                ]
            );

            // Mark pairing code as atomically consumed
            $pairingRecord->update([
                'used_at' => now(),
            ]);

            $this->familyService->logAudit(
                family: $pairingRecord->family,
                actorUserId: null,
                role: 'child_device',
                action: 'device.paired',
                resourceType: 'child_device',
                resourceId: (string) $device->id,
                metadata: [
                    'device_uuid' => $deviceUuid,
                    'device_name' => $deviceName,
                    'platform' => $platform,
                ]
            );

            return [
                'device' => $device,
                'device_token' => $rawDeviceToken,
            ];
        });
    }

    /**
     * Authenticate an incoming request from a child device by its raw bearer token.
     */
    public function authenticateDevice(string $rawToken): ?ChildDevice
    {
        $tokenHash = hash('sha256', trim($rawToken));

        $device = ChildDevice::where('device_token_hash', $tokenHash)
            ->where('device_status', 'active')
            ->first();

        if ($device && ! hash_equals($device->device_token_hash ?? '', $tokenHash)) {
            return null;
        }

        return $device;
    }

    /**
     * Rotate the cryptographic authentication token for an active child device.
     */
    public function rotateDeviceToken(ChildDevice $device): string
    {
        if ($device->device_status !== 'active') {
            throw new RuntimeException('Cannot rotate credentials for an inactive or revoked child device.');
        }

        $newRawToken = 'cpd_'.Str::random(48);
        $newTokenHash = hash('sha256', $newRawToken);

        $device->update([
            'device_token_hash' => $newTokenHash,
            'last_seen_at' => now(),
        ]);

        $this->familyService->logAudit(
            family: $device->family,
            actorUserId: null,
            role: 'child_device',
            action: 'device.token_rotated',
            resourceType: 'child_device',
            resourceId: (string) $device->id,
            metadata: ['device_uuid' => $device->device_uuid]
        );

        return $newRawToken;
    }
}
