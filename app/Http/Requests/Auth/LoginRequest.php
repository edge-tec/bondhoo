<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'ইমেইল, ইউজারনেম বা মোবাইল নম্বর প্রদান করা আবশ্যক।',
            'password.required' => 'পাসওয়ার্ড প্রদান করা আবশ্যক।',
            'password.min' => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।',
        ];
    }
}
