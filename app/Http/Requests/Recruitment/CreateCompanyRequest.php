<?php

declare(strict_types=1);

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class CreateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'slug' => 'nullable|string|max:220|unique:companies,slug',
            'logo' => 'nullable|string|max:255',
            'cover_image' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|string|max:50',
            'founded_year' => 'nullable|integer|min:1800|max:'.(date('Y') + 1),
            'website' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'headquarters' => 'nullable|string|max:150',
            'locations' => 'nullable|array',
            'visibility' => 'nullable|string|in:public,unlisted,private',
        ];
    }
}
