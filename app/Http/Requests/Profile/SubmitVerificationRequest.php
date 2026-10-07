<?php

namespace App\Http\Requests\Profile;

use App\Models\ProfileVerification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('document_type') && ! $this->has('verification_type')) {
            $this->merge([
                'verification_type' => $this->input('document_type'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'verification_type' => [
                'required',
                'string',
                Rule::in([
                    ProfileVerification::TYPE_NID,
                    ProfileVerification::TYPE_PASSPORT,
                    ProfileVerification::TYPE_DRIVING_LICENSE,
                    ProfileVerification::TYPE_ORGANIZATIONAL,
                ]),
            ],
            'document_number' => ['nullable', 'string', 'max:100'],
            'document_front' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:10240'],
            'document_back' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:10240'],
            'selfie' => ['nullable', 'file', 'mimes:jpeg,png,jpg', 'max:10240'],
            'document_front_key' => ['nullable', 'string', 'max:255'],
            'document_back_key' => ['nullable', 'string', 'max:255'],
            'selfie_key' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'verification_type.required' => 'ভেরিফিকেশনের ধরন (NID, Passport, ইত্যাদি) নির্বাচন করা আবশ্যক।',
            'verification_type.in' => 'ভেরিফিকেশনের ধরনটি গ্রহণযোগ্য নয়।',
            'document_front.max' => 'ডকুমেন্টের সামনের পাতার ফাইল সাইজ সর্বোচ্চ ১০ মেগাবাইট হতে পারে।',
            'document_back.max' => 'ডকুমেন্টের পেছনের পাতার ফাইল সাইজ সর্বোচ্চ ১০ মেগাবাইট হতে পারে।',
            'selfie.max' => 'সেলফি ফাইলের সাইজ সর্বোচ্চ ১০ মেগাবাইট হতে পারে।',
        ];
    }
}
