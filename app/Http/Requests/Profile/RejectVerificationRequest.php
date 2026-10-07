<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class RejectVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->hasRole('SUPER_ADMIN') || $user->hasRole('ADMIN') || $user->hasPermission('manage.verification'));
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:5', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'বাতিল করার কারণ উল্লেখ করা আবশ্যক।',
            'rejection_reason.min' => 'বাতিল করার কারণ কমপক্ষে ৫ অক্ষরের হতে হবে।',
        ];
    }
}
