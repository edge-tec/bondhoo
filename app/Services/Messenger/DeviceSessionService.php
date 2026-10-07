<?php

namespace App\Services\Messenger;

use App\Models\DeviceSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DeviceSessionService
{
    /**
     * Register or update an active device session.
     */
    public function registerDevice(User $user, array $data): DeviceSession
    {
        $deviceId = (string) ($data['device_id'] ?? 'device_'.md5($user->id.request()->userAgent()));

        $session = DeviceSession::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_id' => $deviceId,
            ],
            [
                'device_name' => $data['device_name'] ?? 'Web Browser',
                'platform' => $data['platform'] ?? 'web',
                'app_version' => $data['app_version'] ?? '1.0.0',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'push_token' => $data['push_token'] ?? null,
                'last_active_at' => now(),
                'is_revoked' => false,
            ]
        );

        return $session;
    }

    /**
     * List active sessions for user.
     */
    public function getActiveSessions(User $user): Collection
    {
        return DeviceSession::where('user_id', $user->id)
            ->where('is_revoked', false)
            ->orderBy('last_active_at', 'desc')
            ->get();
    }

    /**
     * Revoke a specific session.
     */
    public function revokeSession(User $user, int $sessionId): bool
    {
        $session = DeviceSession::where('user_id', $user->id)
            ->where('id', $sessionId)
            ->first();

        if (! $session) {
            return false;
        }

        return $session->update(['is_revoked' => true]);
    }

    /**
     * Revoke all other sessions.
     */
    public function revokeOtherSessions(User $user, string $currentDeviceId): int
    {
        return DeviceSession::where('user_id', $user->id)
            ->where('device_id', '!=', $currentDeviceId)
            ->where('is_revoked', false)
            ->update(['is_revoked' => true]);
    }
}
