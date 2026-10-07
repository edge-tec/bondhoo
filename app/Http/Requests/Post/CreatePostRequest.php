<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string', 'max:5000'],
            'audience' => ['nullable', 'string', 'in:public,friends,followers,only_me'],
            'type' => ['nullable', 'string', 'in:text,media,link,poll,life_event,question'],
            'location' => ['nullable', 'string', 'max:255'],
            'feeling_activity' => ['nullable', 'string', 'max:255'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
            'media_meta' => ['nullable', 'array'],
            'link_preview' => ['nullable', 'array'],
            'poll_data' => ['nullable', 'array'],
            'comments_disabled' => ['nullable', 'boolean'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'page_id' => ['nullable', 'integer', 'exists:pages,id'],
            'collaborator_id' => ['nullable', 'integer', 'exists:users,id'],
            'tagged_user_ids' => ['nullable', 'array'],
            'tagged_user_ids.*' => ['integer', 'exists:users,id'],
            'background_style' => ['nullable', 'string', 'max:60'],
            'is_ai_generated' => ['nullable', 'boolean'],
            'content_warning' => ['nullable', 'string', 'max:150'],
            'shared_to_story' => ['nullable', 'boolean'],
            'share_to_story' => ['nullable', 'boolean'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:published,scheduled,draft'],
        ];
    }
}
