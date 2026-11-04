<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_text' => ['nullable', 'string'],
            'file_url' => ['nullable', 'string', 'max:500'],
        ];
    }
}
