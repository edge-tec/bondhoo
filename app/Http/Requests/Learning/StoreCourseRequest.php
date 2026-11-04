<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:300'],
            'category_id' => ['nullable', 'integer', 'exists:learning_categories,id'],
            'description' => ['required', 'string'],
            'level' => ['nullable', 'string', 'in:all_levels,beginner,intermediate,expert'],
            'language' => ['nullable', 'string', 'max:30'],
            'thumbnail_url' => ['nullable', 'string', 'max:500'],
            'promotional_video_url' => ['nullable', 'string', 'max:500'],
            'type' => ['nullable', 'string', 'in:free,paid,private,organization'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'discounted_price' => ['nullable', 'numeric', 'min:0'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:100'],
            'learning_objectives' => ['nullable', 'array'],
            'prerequisites' => ['nullable', 'array'],
            'target_audience' => ['nullable', 'array'],
        ];
    }
}
