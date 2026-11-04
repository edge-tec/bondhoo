<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'content' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
