<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class ReorderSocialLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'orders' => ['nullable', 'array'],
            'orders.*.id' => ['required_with:orders', 'integer', 'exists:profile_social_links,id'],
            'orders.*.display_order' => ['required_with:orders', 'integer', 'min:0'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required_with:items', 'integer', 'exists:profile_social_links,id'],
            'items.*.display_order' => ['required_with:items', 'integer', 'min:0'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'exists:profile_social_links,id'],
        ];
    }
}
