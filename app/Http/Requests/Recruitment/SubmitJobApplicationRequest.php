<?php

declare(strict_types=1);

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class SubmitJobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'required|string|max:50',
            'resume_id' => 'nullable|integer|exists:candidate_resumes,id',
            'resume_url' => 'nullable|string|max:255',
            'portfolio_url' => 'nullable|string|max:255',
            'cover_letter' => 'nullable|string|max:5000',
            'expected_salary' => 'nullable|integer|min:0',
            'answers' => 'nullable|array',
            'source' => 'nullable|string|max:50',
        ];
    }
}
