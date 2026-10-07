<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class CreateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('body') && $this->has('content')) {
            $this->merge([
                'body' => $this->input('content'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }
}
