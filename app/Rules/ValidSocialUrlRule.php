<?php

namespace App\Rules;

use App\Services\Security\SocialUrlSanitizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidSocialUrlRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('সোশ্যাল প্রোফাইল লিঙ্ক প্রদান করা আবশ্যক।');

            return;
        }

        if (SocialUrlSanitizer::isMalicious($value)) {
            $fail('প্রদত্ত ইউআরএলটি বিপজ্জনক বা অননুমোদিত স্কিম (যেমন javascript:, data:) ধারণ করে।');

            return;
        }

        $normalized = SocialUrlSanitizer::normalize($value);
        if ($normalized === null) {
            $fail('অনুগ্রহ করে একটি সঠিক ও বৈধ সোশ্যাল প্রোফাইল ইউআরএল প্রদান করুন।');

            return;
        }

        if (strlen($normalized) > 255) {
            $fail('সোশ্যাল প্রোফাইল ইউআরএল সর্বোচ্চ ২৫৫ অক্ষরের হতে পারে।');
        }
    }
}
