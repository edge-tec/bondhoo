<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorV2Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:6'],
            'type' => ['nullable', 'string', 'in:app,email,sms'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'টু-ফ্যাক্টর অথেন্টিকেশন কোড আবশ্যক।',
        ];
    }
}
