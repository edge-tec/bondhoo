<?php

namespace App\Services;

use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordHistoryService
{
    /**
     * Check whether the new password was previously used by the user.
     *
     * @throws ValidationException
     */
    public function assertNotRecentlyUsed(User $user, string $newPlainPassword, int $historyLimit = 5): void
    {
        // 1. Check against current password
        if (Hash::check($newPlainPassword, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['আপনি আপনার বর্তমান পাসওয়ার্ডটি পুনরায় ব্যবহার করতে পারবেন না। নতুন একটি পাসওয়ার্ড দিন।'],
            ]);
        }

        // 2. Check against recent historical passwords
        $histories = PasswordHistory::where('user_id', $user->id)
            ->latest('created_at')
            ->take($historyLimit)
            ->get();

        foreach ($histories as $history) {
            if (Hash::check($newPlainPassword, $history->password_hash)) {
                throw ValidationException::withMessages([
                    'password' => ['আপনি সম্প্রতি ব্যবহৃত পাসওয়ার্ডগুলোর মধ্যে একটি দিয়েছেন। নিরাপত্তার স্বার্থে সম্পূর্ণ নতুন পাসওয়ার্ড নির্বাচন করুন।'],
                ]);
            }
        }
    }

    /**
     * Record the password in password history.
     */
    public function recordPassword(User $user, string $hashedPassword): void
    {
        PasswordHistory::create([
            'user_id' => $user->id,
            'password_hash' => $hashedPassword,
            'created_at' => now(),
        ]);

        // Keep maximum 10 history records per user
        $keepIds = PasswordHistory::where('user_id', $user->id)
            ->latest('created_at')
            ->take(10)
            ->pluck('id');

        PasswordHistory::where('user_id', $user->id)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
