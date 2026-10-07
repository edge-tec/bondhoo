<?php

namespace App\Http\Requests\Profile;

use App\Rules\ValidSocialUrlRule;
use App\Services\SocialLink\SocialPlatformRegistry;
use Illuminate\Foundation\Http\FormRequest;

class StoreSocialLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $merges = [];

        if ($this->has('platform') && is_string($this->input('platform'))) {
            $merges['platform'] = SocialPlatformRegistry::normalizePlatform($this->input('platform'));
        }

        if (! empty($merges)) {
            $this->merge($merges);
        }
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', 'max:50'],
            'url' => ['required', 'string', 'max:255', new ValidSocialUrlRule],
            'privacy' => ['nullable', 'string', 'in:public,friends,followers,only_me'],
            'is_visible' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'platform.required' => 'সোশ্যাল প্ল্যাটফর্ম নির্বাচন করা আবশ্যক।',
            'url.required' => 'প্রোফাইল ইউআরএল প্রদান করা আবশ্যক।',
            'privacy.in' => 'গোপনীয়তা স্তর অবশ্যই public, friends, followers অথবা only_me হতে হবে।',
        ];
    }
}
