<?php

namespace App\Http\Requests\Reaction;

use App\Models\Reaction;
use Illuminate\Foundation\Http\FormRequest;

class ReactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:'.implode(',', Reaction::TYPES)],
        ];
    }
}
