<?php

namespace App\Http\Requests\Profile;

use App\Models\ProfileLanguage;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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

        return [
            'language' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) use ($userId) {
                    if (! $userId) {
                        return;
                    }
                    $normalized = strtolower(trim((string) $value));
                    $exists = ProfileLanguage::where('user_id', $userId)
                        ->whereRaw('LOWER(language) = ?', [$normalized])
                        ->exists();

                    if ($exists) {
                        $fail('ভাষাটি ইতিমধ্যে আপনার প্রোফাইলে যুক্ত রয়েছে।');
                    }
                },
            ],
            'proficiency' => [
                'required',
                'string',
                'in:basic,conversational,professional,fluent,native',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'language.required' => 'ভাষার নাম প্রদান করা আবশ্যক।',
            'proficiency.required' => 'ভাষার পারদর্শিতার স্তর (Proficiency) নির্বাচন করা আবশ্যক।',
            'proficiency.in' => 'পারদর্শিতার স্তর অবশ্যই Basic, Conversational, Professional, Fluent অথবা Native হতে হবে।',
        ];
    }
}
