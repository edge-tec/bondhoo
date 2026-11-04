<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuizAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
        ];
    }
}
