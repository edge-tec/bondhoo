<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class StoreEducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $merges = [];

        if (! $this->has('institution_name') && $this->has('institution')) {
            $merges['institution_name'] = $this->input('institution');
        }

        if (! $this->has('is_current') && $this->has('currently_studying')) {
            $merges['is_current'] = filter_var($this->input('currently_studying'), FILTER_VALIDATE_BOOLEAN);
        }

        if (! empty($merges)) {
            $this->merge($merges);
        }
    }

    public function rules(): array
    {
        $isCurrent = filter_var($this->input('is_current') ?? $this->input('currently_studying'), FILTER_VALIDATE_BOOLEAN);

        return [
            'institution_name' => ['required', 'string', 'max:191'],
            'institution' => ['nullable', 'string', 'max:191'],
            'degree' => ['nullable', 'string', 'max:100'],
            'field_of_study' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date', 'before_or_equal:today'],
            'end_date' => [
                'nullable',
                'date',
                $isCurrent ? 'nullable' : 'after_or_equal:start_date',
            ],
            'is_current' => ['nullable', 'boolean'],
            'currently_studying' => ['nullable', 'boolean'],
            'grade' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:191'],
            'privacy' => ['nullable', 'string', 'in:public,friends,followers,only_me'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'institution_name.required' => 'শিক্ষা প্রতিষ্ঠানের নাম প্রদান করা আবশ্যক।',
            'start_date.before_or_equal' => 'শুরুর তারিখ আজকের বা পূর্ববর্তী কোনো তারিখ হতে হবে।',
            'end_date.after_or_equal' => 'সমাপ্তির তারিখ অবশ্যই শুরুর তারিখের পরবর্তী বা সমান হতে হবে।',
            'privacy.in' => 'গোপনীয়তা স্তর অবশ্যই public, friends, followers অথবা only_me হতে হবে।',
        ];
    }
}
