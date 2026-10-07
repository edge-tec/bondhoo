<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileV2Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('city') && ! $this->has('location')) {
            $this->merge(['location' => $this->input('city')]);
        }

        if ($this->has('relationship_status') && is_string($this->input('relationship_status'))) {
            $normalized = strtolower(str_replace(' ', '_', trim($this->input('relationship_status'))));
            $this->merge(['relationship_status' => $normalized]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'hometown' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'work' => ['nullable', 'string', 'max:150'],
            'education' => ['nullable', 'string', 'max:150'],
            'relationship_status' => ['nullable', 'string', 'in:single,in_a_relationship,engaged,married,complicated,prefer_not_to_say'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'interests' => ['nullable', 'array'],
            'interests.*' => ['string', 'max:50'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'string', 'max:255'],
            'cover_position_y' => ['nullable', 'integer', 'between:0,100'],
        ];
    }
}
