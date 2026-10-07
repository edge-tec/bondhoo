<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class RequestInfoVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->hasRole('SUPER_ADMIN') || $user->hasRole('ADMIN') || $user->hasPermission('manage.verification'));
    }

    public function rules(): array
    {
        return [
            'instructions' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'instructions.required' => 'প্রয়োজনীয় অতিরিক্ত তথ্যের নির্দেশনা দেওয়া আবশ্যক।',
            'instructions.min' => 'নির্দেশনা কমপক্ষে ৫ অক্ষরের হতে হবে।',
        ];
    }
}
