<?php

namespace App\Http\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CropAvatarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'crop_x' => ['required', 'integer', 'min:0'],
            'crop_y' => ['required', 'integer', 'min:0'],
            'crop_width' => ['required', 'integer', 'min:50'],
            'crop_height' => ['required', 'integer', 'min:50'],
        ];
    }
}
