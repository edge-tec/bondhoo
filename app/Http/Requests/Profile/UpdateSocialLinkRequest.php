<?php

namespace App\Http\Requests\Profile;

use App\Models\ProfileSocialLink;
use App\Rules\ValidSocialUrlRule;
use App\Services\SocialLink\SocialPlatformRegistry;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $link = $this->route('social_link') ?? $this->route('id');
        if (is_numeric($link)) {
            $link = ProfileSocialLink::find($link);
        }

        if (! $link instanceof ProfileSocialLink) {
            return false;
        }

        return $this->user() && $this->user()->can('update', $link);
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
            'platform' => ['sometimes', 'required', 'string', 'max:50'],
            'url' => ['sometimes', 'required', 'string', 'max:255', new ValidSocialUrlRule],
            'privacy' => ['nullable', 'string', 'in:public,friends,followers,only_me'],
            'is_visible' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'privacy.in' => 'গোপনীয়তা স্তর অবশ্যই public, friends, followers অথবা only_me হতে হবে।',
        ];
    }
}
