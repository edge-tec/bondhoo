<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $merges = [];

        if (! $this->has('company_name') && $this->has('company')) {
            $merges['company_name'] = $this->input('company');
        }

        if (! $this->has('job_title') && $this->has('position')) {
            $merges['job_title'] = $this->input('position');
        }

        if (! $this->has('is_current') && $this->has('currently_working')) {
            $merges['is_current'] = filter_var($this->input('currently_working'), FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->has('employment_type') && is_string($this->input('employment_type'))) {
            $rawType = strtolower(trim($this->input('employment_type')));
            $rawType = str_replace(['-', ' '], '_', $rawType);
            $merges['employment_type'] = $rawType;
        }

        if (! empty($merges)) {
            $this->merge($merges);
        }
    }

    public function rules(): array
    {
        $isCurrent = filter_var(
            $this->input('is_current') ?? $this->input('currently_working'),
            FILTER_VALIDATE_BOOLEAN
        );

        return [
            'company_name' => ['required', 'string', 'max:191'],
            'company' => ['nullable', 'string', 'max:191'],
            'job_title' => ['required', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:150'],
            'employment_type' => [
                'nullable',
                'string',
                'in:full_time,part_time,self_employed,freelance,contract,internship',
            ],
            'location' => ['nullable', 'string', 'max:150'],
            'is_remote' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date', 'before_or_equal:today'],
            'end_date' => [
                'nullable',
                'date',
                $isCurrent ? 'nullable' : 'after_or_equal:start_date',
            ],
            'is_current' => ['nullable', 'boolean'],
            'currently_working' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:3000'],
            'privacy' => ['nullable', 'string', 'in:public,friends,followers,only_me'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'প্রতিষ্ঠানের নাম প্রদান করা আবশ্যক।',
            'job_title.required' => 'পদবী (Position / Job Title) প্রদান করা আবশ্যক।',
            'employment_type.in' => 'চাকরির ধরন অবশ্যই Full Time, Part Time, Self Employed, Freelance, Contract অথবা Internship হতে হবে।',
            'start_date.before_or_equal' => 'শুরুর তারিখ আজকের বা পূর্ববর্তী কোনো তারিখ হতে হবে।',
            'end_date.after_or_equal' => 'সমাপ্তির তারিখ অবশ্যই শুরুর তারিখের পরবর্তী বা সমান হতে হবে।',
            'privacy.in' => 'গোপনীয়তা স্তর অবশ্যই public, friends, followers অথবা only_me হতে হবে।',
        ];
    }
}
