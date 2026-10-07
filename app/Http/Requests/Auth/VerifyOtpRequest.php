<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('identifier') && $this->has('email')) {
            $this->merge(['identifier' => $this->input('email')]);
        }
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string'],
            'otp' => ['nullable', 'required_without:token', 'string', 'max:6'],
            'token' => ['nullable', 'required_without:otp', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'যাচাইকরণের ইমেইল বা ফোন নম্বর প্রদান করুন।',
            'otp.required_without' => '৬ সংখ্যার ওটিপি কোড অথবা ভেরিফিকেশন টোকেন প্রদান করুন।',
            'token.required_without' => 'ভেরিফিকেশন টোকেন অথবা ওটিপি কোড প্রদান করুন।',
        ];
    }
}
