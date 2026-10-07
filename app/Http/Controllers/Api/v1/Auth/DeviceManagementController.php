<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceManagementController extends Controller
{
    /**
     * GET /api/v1/auth/devices
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $sessions = $user->sessions()->latest('last_active_at')->get();

        return $this->successResponse(
            data: $sessions,
            message: 'সক্রিয় ডিভাইসের তালিকা লোড হয়েছে।'
        );
    }

    /**
     * DELETE /api/v1/auth/device/{id}
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $session = $user->sessions()->where('id', $id)->first();
        if (! $session) {
            return $this->errorResponse('ডিভাইস সেশন পাওয়া যায়নি।', 404);
        }

        // Revoke associated Sanctum PersonalAccessToken if token_id is set
        if ($session->token_id) {
            $user->tokens()->where('id', $session->token_id)->delete();
        }

        // Update corresponding LoginHistory if found
        LoginHistory::where('user_id', $user->id)
            ->whereNull('logout_at')
            ->where(function ($q) use ($session) {
                if ($session->ip_address) {
                    $q->where('ip_address', $session->ip_address);
                }
            })
            ->latest('id')
            ->first()
            ?->update(['logout_at' => now()]);

        $session->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'AUTH_DEVICE_REVOKED',
            'entity_type' => UserSession::class,
            'entity_id' => $session->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->successResponse(
            message: 'ডিভাইস সেশন সফলভাবে লগআউট করা হয়েছে।'
        );
    }

    /**
     * GET /api/v1/auth/login-history
     */
    public function history(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $limit = (int) $request->query('limit', 20);

        $history = $user->loginHistories()
            ->latest('login_at')
            ->latest('id')
            ->limit($limit)
            ->get();

        return $this->successResponse(
            data: $history,
            message: 'ডিভাইস লগইন হিস্টোরি লোড হয়েছে।'
        );
    }
}
