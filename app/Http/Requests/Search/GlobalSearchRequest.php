<?php

namespace App\Http\Requests\Search;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GlobalSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('suggest')) {
            $this->merge([
                'suggest' => filter_var($this->suggest, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:150'],
            'query' => ['nullable', 'string', 'max:150'],
            'search' => ['nullable', 'string', 'max:150'],
            'type' => ['nullable', 'string', 'in:all,users,posts,groups,pages,events,jobs,reels,hashtags,courses'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'suggest' => ['nullable', 'boolean'],
            'location' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'skills' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'job_type' => ['nullable', 'string', 'max:50'],
            'sort_by' => ['nullable', 'string', 'in:relevance,popularity,newest,date'],
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Search validation failed.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
