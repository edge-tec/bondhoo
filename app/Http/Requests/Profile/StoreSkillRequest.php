<?php

namespace App\Http\Requests\Profile;

use App\Models\ProfileSkill;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name') && is_string($this->input('name'))) {
            $this->merge([
                'name' => trim(preg_replace('/\s+/', ' ', $this->input('name'))),
            ]);
        }
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) use ($userId) {
                    if (! $userId) {
                        return;
                    }
                    $normalized = strtolower(trim((string) $value));
                    $exists = ProfileSkill::where('user_id', $userId)
                        ->whereRaw('LOWER(name) = ?', [$normalized])
                        ->exists();

                    if ($exists) {
                        $fail('দক্ষতাটি ইতিমধ্যে আপনার প্রোফাইলে যুক্ত রয়েছে।');
                    }
                },
            ],
            'level' => ['nullable', 'string', 'max:50'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'দক্ষতার নাম প্রদান করা আবশ্যক।',
            'name.max' => 'দক্ষতার নাম সর্বোচ্চ ১০০ অক্ষরের হতে পারে।',
        ];
    }
}
