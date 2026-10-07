<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class DirectUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:jpeg,png,webp,gif,avif,mp4,webm,pdf',
                'max:51200', // 50MB
            ],
            'collection' => ['required', 'string', 'in:profile,cover,post,story,message,group,page,general'],
            'entity_id' => ['nullable', 'integer'],
        ];
    }
}
