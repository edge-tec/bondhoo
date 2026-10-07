<?php

namespace App\Http\Requests\Profile;

use App\Models\User;
use App\Models\UserProfile;
use App\Rules\ValidUsernameRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUsernameRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $routeUser = $this->route('user');

        if (is_string($routeUser) && ! is_numeric($routeUser)) {
            $targetUser = User::where('username', strtolower($routeUser))->first();
        } elseif ($routeUser instanceof User) {
            $targetUser = $routeUser;
        } elseif (is_numeric($routeUser)) {
            $targetUser = User::find($routeUser);
        } else {
            $targetUser = $this->user();
        }

        if (! $targetUser) {
            return false;
        }

        return $this->user() && $this->user()->can('update', [UserProfile::class, $targetUser]);
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge([
                'username' => strtolower(trim((string) $this->input('username'))),
            ]);
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
            'username' => [
                'required',
                'string',
                new ValidUsernameRule($targetUserId),
            ],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'একটি নতুন ইউজারনেম প্রদান করা আবশ্যক।',
        ];
    }
}
