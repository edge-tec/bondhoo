<?php

namespace App\Http\Requests\Profile;

use App\Models\ProfileLanguage;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $language = $this->route('language') ?? $this->route('id');
        if (is_numeric($language)) {
            $language = ProfileLanguage::find($language);
        }

        if (! $language instanceof ProfileLanguage) {
            return false;
        }

        return $this->user() && $this->user()->can('update', $language);
    }

    protected function prepareForValidation(): void
    {
        $merges = [];
        if ($this->has('language') && is_string($this->input('language'))) {
            $merges['language'] = trim(preg_replace('/\s+/', ' ', $this->input('language')));
        }
        if ($this->has('proficiency') && is_string($this->input('proficiency'))) {
            $merges['proficiency'] = strtolower(trim($this->input('proficiency')));
        }
        if (! empty($merges)) {
            $this->merge($merges);
        }
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;
        $currentId = $this->route('language') ?? $this->route('id');
        if ($currentId instanceof ProfileLanguage) {
            $currentId = $currentId->id;
        }

        return [
            'language' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) use ($userId, $currentId) {
                    if (! $userId) {
                        return;
                    }
                    $normalized = strtolower(trim((string) $value));
                    $exists = ProfileLanguage::where('user_id', $userId)
                        ->where('id', '!=', $currentId)
                        ->whereRaw('LOWER(language) = ?', [$normalized])
                        ->exists();

                    if ($exists) {
                        $fail('ভাষাটি ইতিমধ্যে আপনার প্রোফাইলে যুক্ত রয়েছে।');
                    }
                },
            ],
            'proficiency' => [
                'sometimes',
                'required',
                'string',
                'in:basic,conversational,professional,fluent,native',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'proficiency.in' => 'পারদর্শিতার স্তর অবশ্যই Basic, Conversational, Professional, Fluent অথবা Native হতে হবে।',
        ];
    }
}
