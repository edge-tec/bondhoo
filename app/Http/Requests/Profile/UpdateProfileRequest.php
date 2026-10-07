<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],
            'work' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:255'],
            'interests' => ['nullable', 'array'],
            'avatar_url' => ['nullable', 'string', 'max:500'],
            'cover_url' => ['nullable', 'string', 'max:500'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'division' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'upazila' => ['nullable', 'string', 'max:100'],
            'pronouns' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:100'],
            'portfolio' => ['nullable', 'url', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'telegram' => ['nullable', 'string', 'max:50'],
            'signal' => ['nullable', 'string', 'max:50'],
            'messenger' => ['nullable', 'string', 'max:50'],
            'hobbies' => ['nullable', 'array'],
            'favorite_music' => ['nullable', 'array'],
            'favorite_books' => ['nullable', 'array'],
            'favorite_movies' => ['nullable', 'array'],
            'is_professional_mode' => ['nullable', 'boolean'],
        ];
    }
}
