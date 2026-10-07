<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
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
            'location' => ['nullable', 'string', 'max:255'],
            'feeling_activity' => ['nullable', 'string', 'max:255'],
            'comments_disabled' => ['nullable', 'boolean'],
        ];
    }
}
