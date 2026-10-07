<?php

namespace App\Http\Requests\Profile;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonalInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $routeUser = $this->route('user');
        if (is_numeric($routeUser)) {
            $targetUser = User::find($routeUser) ?: $this->user();
        } elseif ($routeUser instanceof User) {
            $targetUser = $routeUser;
        } else {
            $targetUser = $this->user();
        }

        return $this->user() && $this->user()->can('update', [UserProfile::class, $targetUser]);
    }

    /**
     * Prepare inputs before validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize privacy values to lowercase
        if ($this->has('privacy') && is_array($this->input('privacy'))) {
            $privacy = [];
            foreach ($this->input('privacy') as $field => $level) {
                if (is_string($level)) {
                    $privacy[$field] = strtolower(trim($level));
                }
            }
            $this->merge(['privacy' => $privacy]);
        }

        if ($this->has('dob_display_format') && is_string($this->input('dob_display_format'))) {
            $this->merge(['dob_display_format' => strtolower(trim($this->input('dob_display_format')))]);
        }

        if ($this->has('gender') && is_string($this->input('gender'))) {
            $this->merge(['gender' => strtolower(trim($this->input('gender')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $routeUser = $this->route('user');
        $targetUserId = ($routeUser instanceof User ? $routeUser->id : (is_numeric($routeUser) ? (int) $routeUser : $this->user()?->id)) ?? 0;

        return [
            // Personal Information fields
            'first_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'in:male,female,other,prefer_not_to_say'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:25', 'unique:users,phone,'.$targetUserId],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$targetUserId],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],

            // Birth date display format
            'dob_display_format' => ['nullable', 'string', 'in:full,month_day,age,hidden'],

            // Granular Privacy configuration
            'privacy' => ['nullable', 'array'],
            'privacy.first_name' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'privacy.last_name' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'privacy.gender' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'privacy.birth_date' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'privacy.phone' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'privacy.email' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'privacy.country' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'privacy.city' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'privacy.address' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
        ];
    }
}
