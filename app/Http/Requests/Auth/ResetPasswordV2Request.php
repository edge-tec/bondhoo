<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordV2Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string'],
            'otp' => ['required_without:token', 'nullable', 'string'],
            'token' => ['required_without:otp', 'nullable', 'string'],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
            ],
            'password_confirmation' => ['required', 'same:password'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'ইমেইল বা মোবাইল নম্বর আবশ্যক।',
            'otp.required' => 'ওটিপি কোড প্রদান করুন।',
            'password.min' => 'নতুন পাসওয়ার্ড অবশ্যই কমপক্ষে ১২ অক্ষরের হতে হবে।',
            'password.regex' => 'নতুন পাসওয়ার্ডে বড় হাত, ছোট হাত, সংখ্যা এবং স্পেশাল ক্যারেক্টার আবশ্যক।',
            'password_confirmation.same' => 'পাসওয়ার্ড নিশ্চিতকরণ মেলেনি।',
        ];
    }
}
