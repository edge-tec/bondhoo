<?php

namespace App\Http\Requests\Profile;

use App\Models\ProfileInterest;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreInterestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $merges = [];
        if ($this->has('name') && is_string($this->input('name'))) {
            $merges['name'] = trim(preg_replace('/\s+/', ' ', $this->input('name')));
        }
        if ($this->has('category') && is_string($this->input('category'))) {
            $merges['category'] = trim(preg_replace('/\s+/', ' ', $this->input('category')));
        }
        if (! empty($merges)) {
            $this->merge($merges);
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
                    $exists = ProfileInterest::where('user_id', $userId)
                        ->whereRaw('LOWER(name) = ?', [$normalized])
                        ->exists();

                    if ($exists) {
                        $fail('আগ্রহের বিষয়টি ইতিমধ্যে আপনার প্রোফাইলে যুক্ত রয়েছে।');
                    }
                },
            ],
            'category' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'আগ্রহের বিষয়ের নাম প্রদান করা আবশ্যক।',
            'name.max' => 'আগ্রহের বিষয়ের নাম সর্বোচ্চ ১০০ অক্ষরের হতে পারে।',
        ];
    }
}
