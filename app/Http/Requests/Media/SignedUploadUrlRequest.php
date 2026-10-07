<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class SignedUploadUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filename' => ['required', 'string', 'max:255'],
            'mime_type' => [
                'required',
                'string',
                'in:image/jpeg,image/png,image/webp,image/gif,image/avif,video/mp4,video/webm,application/pdf',
            ],
            'size' => ['required', 'integer', 'min:1', 'max:104857600'], // 100MB limit
            'collection' => ['required', 'string', 'in:profile,cover,post,story,message,group,page,general'],
            'entity_id' => ['nullable', 'integer'],
        ];
    }
}
