<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'who_can_see_posts' => ['nullable', 'in:public,friends,only_me'],
            'who_can_send_friend_requests' => ['nullable', 'in:everyone,friends_of_friends'],
            'who_can_follow' => ['nullable', 'in:everyone,friends'],
            'who_can_message' => ['nullable', 'in:everyone,friends'],
            'who_can_see_friends' => ['nullable', 'in:public,friends,only_me'],
            'find_by_email' => ['nullable', 'in:everyone,friends,no_one'],
            'find_by_phone' => ['nullable', 'in:everyone,friends,no_one'],
            'story_visibility' => ['nullable', 'in:public,friends,only_me'],
            'notification_email' => ['nullable', 'boolean'],
            'notification_push' => ['nullable', 'boolean'],
            'notification_sms' => ['nullable', 'boolean'],
            'dark_mode' => ['nullable', 'boolean'],
            'language' => ['nullable', 'string', 'max:10'],
        ];
    }
}
