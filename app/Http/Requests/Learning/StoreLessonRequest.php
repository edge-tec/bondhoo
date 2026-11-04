<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'type' => ['nullable', 'string', 'in:video,article,quiz,assignment,resource'],
            'content' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'video_duration_seconds' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer'],
            'is_preview' => ['nullable', 'boolean'],
            'is_mandatory' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
