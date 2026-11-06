<?php

declare(strict_types=1);

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class CreateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'nullable|integer|exists:companies,id',
            'category_id' => 'nullable|integer|exists:job_categories,id',
            'page_id' => 'nullable|integer|exists:pages,id',
            'title' => 'required|string|max:200',
            'company_name' => 'nullable|string|max:150',
            'company_logo' => 'nullable|string|max:255',
            'location' => 'required|string|max:150',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'workplace_type' => 'nullable|string|in:onsite,remote,hybrid',
            'location_type' => 'nullable|string|in:onsite,remote,hybrid',
            'employment_type' => 'required|string|in:full_time,part_time,contract,internship,freelance,temporary',
            'experience_level' => 'nullable|string|in:entry,mid,senior,lead,any',
            'salary_min' => 'nullable|integer|min:0',
            'salary_max' => 'nullable|integer|min:0|gte:salary_min',
            'salary_currency' => 'nullable|string|max:10',
            'salary_negotiable' => 'nullable|boolean',
            'salary_visibility' => 'nullable|string|in:visible,hidden,range_only',
            'vacancies' => 'nullable|integer|min:1|max:1000',
            'visibility' => 'nullable|string|in:public,unlisted,internal',
            'application_method' => 'nullable|string|in:in_app,external_url,email',
            'external_application_url' => 'nullable|string|max:255',
            'description' => 'required|string|max:10000',
            'responsibilities' => 'nullable|string|max:5000',
            'requirements' => 'nullable|string|max:5000',
            'qualifications' => 'nullable|string|max:5000',
            'benefits' => 'nullable|string|max:3000',
            'deadline' => 'nullable|date|after_or_equal:today',
            'is_draft' => 'nullable|boolean',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'questions' => 'nullable|array',
            'questions.*.question' => 'required|string|max:500',
            'questions.*.type' => 'required|string|in:short_answer,long_answer,yes_no,single_choice,multiple_choice',
            'questions.*.options' => 'nullable|array',
            'questions.*.is_required' => 'nullable|boolean',
        ];
    }
}
