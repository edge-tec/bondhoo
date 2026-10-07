<?php

namespace App\Http\Requests\Profile;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBioAboutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $routeUser = $this->route('user');

        if (is_string($routeUser) && ! is_numeric($routeUser)) {
            $targetUser = User::where('username', strtolower($routeUser))->first();
        } elseif ($routeUser instanceof User) {
            $targetUser = $routeUser;
        } elseif (is_numeric($routeUser)) {
            $targetUser = User::find($routeUser);
        } else {
            $targetUser = $this->user();
        }

        if (! $targetUser) {
            return false;
        }

        return $this->user() && $this->user()->can('update', [UserProfile::class, $targetUser]);
    }

    /**
     * Prepare inputs before validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('headline')) {
            if ($this->has('intro')) {
                $this->merge(['headline' => $this->input('intro')]);
            } elseif ($this->has('introduction')) {
                $this->merge(['headline' => $this->input('introduction')]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'bio' => ['nullable', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:191'],
            'intro' => ['nullable', 'string', 'max:191'],
            'introduction' => ['nullable', 'string', 'max:191'],
            'about' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Custom validation error messages in Bengali.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bio.max' => 'বায়ো সর্বোচ্চ ২৫৫ অক্ষরের মধ্যে হতে হবে।',
            'headline.max' => 'ভূমিকা বা হেডলাইন সর্বোচ্চ ১৯১ অক্ষরের মধ্যে হতে হবে।',
            'intro.max' => 'ভূমিকা সর্বোচ্চ ১৯১ অক্ষরের মধ্যে হতে হবে।',
            'introduction.max' => 'ভূমিকা সর্বোচ্চ ১৯১ অক্ষরের মধ্যে হতে হবে।',
            'about.max' => 'অ্যাবাউট বিবরণ সর্বোচ্চ ৫,০০০ অক্ষরের মধ্যে হতে হবে।',
        ];
    }
}
