<?php

namespace App\Rules;

use App\Models\User;
use App\Models\UsernameHistory;
use App\Services\UsernameService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidUsernameRule implements ValidationRule
{
    /**
     * @param  int|null  $ignoreUserId  The user ID to ignore when checking uniqueness (e.g. current user)
     */
    public function __construct(
        protected ?int $ignoreUserId = null
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('ইউজারনেম অবশ্যই একটি বৈধ টেক্সট হতে হবে।');

            return;
        }

        $username = trim($value);
        $length = mb_strlen($username);

        // 1. Length boundaries
        if ($length < 3) {
            $fail('ইউজারনেম সর্বনিম্ন ৩ অক্ষরের হতে হবে।');

            return;
        }

        if ($length > 30) {
            $fail('ইউজারনেম সর্বোচ্চ ৩০ অক্ষরের মধ্যে হতে হবে।');

            return;
        }

        // 2. Disallow consecutive dots
        if (str_contains($username, '..')) {
            $fail('ইউজারনেমে পরপর একাধিক ডট (..) ব্যবহার করা যাবে না।');

            return;
        }

        // 3. Allowed characters regex: alphanumeric, _, -, . (must start and end with alphanumeric)
        if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.-]*[a-zA-Z0-9]$/', $username)) {
            $fail('ইউজারনেমে শুধুমাত্র ইংরেজি অক্ষর, সংখ্যা, আন্ডারস্কোর, ডট ও হাইফেন ব্যবহার করা যাবে এবং অক্ষর বা সংখ্যা দিয়ে শুরু ও শেষ হতে হবে।');

            return;
        }

        $normalized = strtolower($username);

        // 4. Reserved usernames check
        if (in_array($normalized, UsernameService::RESERVED_USERNAMES, true)) {
            $fail('এই ইউজারনেমটি সিস্টেমের জন্য সংরক্ষিত। অনুগ্রহ করে অন্য ইউজারনেম বেছে নিন।');

            return;
        }

        // 5. Case-insensitive uniqueness in users table
        $query = User::whereRaw('LOWER(username) = ?', [$normalized]);
        if ($this->ignoreUserId) {
            $query->where('id', '!=', $this->ignoreUserId);
        }

        if ($query->exists()) {
            $fail('এই ইউজারনেমটি ইতিমধ্যে অন্য একজন ব্যবহারকারী গ্রহণ করেছেন।');

            return;
        }

        // 6. Check historical usernames belonging to other users
        $historyQuery = UsernameHistory::whereRaw('LOWER(username) = ?', [$normalized]);
        if ($this->ignoreUserId) {
            $historyQuery->where('user_id', '!=', $this->ignoreUserId);
        }

        if ($historyQuery->exists()) {
            $fail('এই ইউজারনেমটি পূর্বে ব্যবহৃত হয়েছে এবং বর্তমানে সংরক্ষিত।');
        }
    }
}
