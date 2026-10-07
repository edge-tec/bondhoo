<?php

namespace App\Http\Requests\Auth;

use App\Services\AuthServiceV2;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class RegisterV2Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation: support both (first_name, last_name) and (name).
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('first_name') || $this->filled('last_name')) {
            $firstName = trim((string) $this->input('first_name', ''));
            $lastName = trim((string) $this->input('last_name', ''));
            if (! $this->filled('name')) {
                $this->merge(['name' => trim("{$firstName} {$lastName}")]);
            }
        } elseif ($this->filled('name')) {
            $parts = explode(' ', trim((string) $this->input('name')), 2);
            $this->merge([
                'first_name' => $parts[0] ?? '',
                'last_name' => $parts[1] ?? '',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'alpha_dash',
                'unique:users,username',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (in_array(strtolower((string) $value), AuthServiceV2::RESERVED_USERNAMES, true)) {
                        $fail('এই ইউজারনেমটি সিস্টেমের জন্য সংরক্ষিত। অনুগ্রহ করে অন্য ইউজারনেম বেছে নিন।');
                    }
                },
            ],
            'email' => [
                'nullable',
                'required_without:phone',
                'email',
                'max:255',
                'unique:users,email',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! empty($value)) {
                        $domain = strtolower(substr(strrchr((string) $value, '@'), 1));
                        if (in_array($domain, AuthServiceV2::DISPOSABLE_EMAIL_DOMAINS, true)) {
                            $fail('ডিসপোজেবল বা অস্থায়ী ইমেইল অ্যাড্রেস গ্রহণ করা হয় না। অনুগ্রহ করে একটি স্থায়ী ইমেইল ব্যবহার করুন।');
                        }
                    }
                },
            ],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:25', 'unique:users,phone'],
            'country' => ['nullable', 'string', 'max:10'],
            'birth_date' => [
                'nullable',
                'date',
                'before_or_equal:'.now()->subYears(13)->format('Y-m-d'),
            ],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',      // at least one lowercase
                'regex:/[A-Z]/',      // at least one uppercase
                'regex:/[0-9]/',      // at least one number
                'regex:/[@$!%*#?&^_-]/', // at least one special character
            ],
            'password_confirmation' => ['required', 'same:password'],
            'referred_by' => ['nullable', 'string', 'exists:users,username'],
            'terms' => ['accepted'],
            'website_hp' => ['nullable', 'max:0'],
            'hp_company' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'আপনার পূর্ণ নাম প্রদান করুন।',
            'username.required' => 'ইউনিক ইউজারনেম আবশ্যক।',
            'username.unique' => 'এই ইউজারনেমটি ইতিমধ্যে ব্যবহৃত হয়েছে।',
            'email.unique' => 'এই ইমেইল ঠিকানা দিয়ে আগেই অ্যাকাউন্ট খোলা হয়েছে।',
            'email.required_without' => 'ইমেইল অথবা মোবাইল নম্বর প্রদান করা আবশ্যক।',
            'phone.unique' => 'এই মোবাইল নম্বরটি ইতিমধ্যে নিবন্ধিত রয়েছে।',
            'phone.required_without' => 'মোবাইল নম্বর অথবা ইমেইল প্রদান করা আবশ্যক।',
            'birth_date.before_or_equal' => 'ব্যবহারকারীর বয়স ন্যূনতম ১৩ বছর হতে হবে।',
            'password.min' => 'পাসওয়ার্ড অবশ্যই কমপক্ষে ৮ অক্ষরের হতে হবে।',
            'password.regex' => 'পাসওয়ার্ডে ছোট হাত, বড় হাত, সংখ্যা এবং স্পেশাল ক্যারেক্টার (@$!%*#?&^_-) থাকতে হবে।',
            'password_confirmation.same' => 'পাসওয়ার্ড নিশ্চিতকরণ মেলেনি।',
            'referred_by.exists' => 'রেফারেল ইউজারনেমটি সঠিক নয়।',
            'terms.accepted' => 'আমাদের শর্তাবলী ও প্রাইভেসী পলিসিতে সম্মত হওয়া বাধ্যতামূলক।',
        ];
    }
}
