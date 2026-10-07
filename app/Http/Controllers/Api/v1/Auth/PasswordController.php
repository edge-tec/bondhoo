<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Services\PasswordHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function __construct(
        protected PasswordHistoryService $passwordHistoryService
    ) {}

    /**
     * PUT /api/v1/auth/password
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        /** @var User $user */
        $user = $request->user();

        // 1. Verify current password
        if (! Hash::check($validated['current_password'], $user->password)) {
            return $this->errorResponse('বর্তমান পাসওয়ার্ডটি সঠিক নয়।', 422, [
                'current_password' => ['বর্তমান পাসওয়ার্ডটি সঠিক নয়।'],
            ]);
        }

        // 2. Enforce password history prevention
        $this->passwordHistoryService->assertNotRecentlyUsed($user, $validated['password']);

        // 3. Update password and record in history
        $hashed = Hash::make($validated['password']);
        $user->update(['password' => $hashed]);
        $this->passwordHistoryService->recordPassword($user, $hashed);

        // 4. Send security notification
        $user->notify(new PasswordChangedNotification);

        // 5. Create audit log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'AUTH_PASSWORD_UPDATED',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->successResponse(
            message: 'পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে।'
        );
    }
}
