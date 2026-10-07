<?php

namespace App\Http\Requests\Profile;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompleteProfileRequest extends FormRequest
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
        if ($this->has('city') && ! $this->has('location')) {
            $this->merge(['location' => $this->input('city')]);
        }

        if ($this->has('relationship_status') && is_string($this->input('relationship_status'))) {
            $normalized = strtolower(str_replace(' ', '_', trim($this->input('relationship_status'))));
            $this->merge(['relationship_status' => $normalized]);
        }

        if ($this->has('social_links') && is_array($this->input('social_links'))) {
            $links = $this->input('social_links');
            $isAssoc = false;
            foreach ($links as $k => $v) {
                if (is_string($k) && ! is_numeric($k)) {
                    $isAssoc = true;
                    break;
                }
            }
            if ($isAssoc) {
                $normalized = [];
                foreach ($links as $platform => $url) {
                    if (is_string($url) && trim($url) !== '') {
                        $normalized[] = [
                            'platform' => (string) $platform,
                            'url' => trim($url),
                            'is_visible' => true,
                        ];
                    }
                }
                $this->merge(['social_links' => $normalized]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request across all 10 sections.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $routeUser = $this->route('user');
        $targetUserId = ($routeUser instanceof User ? $routeUser->id : (is_numeric($routeUser) ? (int) $routeUser : $this->user()?->id)) ?? 0;

        return [
            // Section 1: Basic Information
            'name' => ['nullable', 'string', 'max:100'],
            'first_name' => ['nullable', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'alpha_dash', 'max:100', 'unique:user_profiles,slug,'.$targetUserId.',user_id'],
            'gender' => ['nullable', 'string', 'in:male,female,other,prefer_not_to_say'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'dob_display_format' => ['nullable', 'string', 'in:full,month_day,age,hidden'],
            'relationship_status' => ['nullable', 'string', 'in:single,in_a_relationship,engaged,married,complicated,prefer_not_to_say'],
            'pronouns' => ['nullable', 'string', 'max:50'],
            'religion' => ['nullable', 'string', 'max:50'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_professional_mode' => ['nullable', 'boolean'],

            // Section 2: About
            'bio' => ['nullable', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:191'],
            'intro' => ['nullable', 'string', 'max:191'],
            'introduction' => ['nullable', 'string', 'max:191'],
            'about' => ['nullable', 'string', 'max:5000'],

            // Section 3: Contact Information
            'website' => ['nullable', 'url', 'max:255'],
            'portfolio' => ['nullable', 'url', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'telegram' => ['nullable', 'string', 'max:50'],
            'signal' => ['nullable', 'string', 'max:50'],
            'messenger' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:25', 'unique:users,phone,'.$targetUserId],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$targetUserId],

            // Section 4: Location
            'location' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'division' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'upazila' => ['nullable', 'string', 'max:100'],
            'hometown' => ['nullable', 'string', 'max:100'],

            // Personal Field Privacy Settings
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

            // Legacy / Quick summary fields
            'work' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:255'],

            // Section 5: Education
            'educations' => ['nullable', 'array'],
            'educations.*.id' => ['nullable', 'integer'],
            'educations.*.institution_name' => ['required_with:educations', 'string', 'max:191'],
            'educations.*.degree' => ['nullable', 'string', 'max:100'],
            'educations.*.field_of_study' => ['nullable', 'string', 'max:100'],
            'educations.*.start_date' => ['nullable', 'date'],
            'educations.*.end_date' => ['nullable', 'date'],
            'educations.*.is_current' => ['nullable', 'boolean'],
            'educations.*.grade' => ['nullable', 'string', 'max:50'],
            'educations.*.description' => ['nullable', 'string', 'max:1000'],
            'educations.*.privacy' => ['nullable', 'string', 'in:public,friends,only_me'],

            // Section 6: Work Experience
            'experiences' => ['nullable', 'array'],
            'experiences.*.id' => ['nullable', 'integer'],
            'experiences.*.company_name' => ['required_with:experiences', 'string', 'max:191'],
            'experiences.*.job_title' => ['required_with:experiences', 'string', 'max:150'],
            'experiences.*.employment_type' => ['nullable', 'string', 'max:50'],
            'experiences.*.location' => ['nullable', 'string', 'max:150'],
            'experiences.*.is_remote' => ['nullable', 'boolean'],
            'experiences.*.start_date' => ['nullable', 'date'],
            'experiences.*.end_date' => ['nullable', 'date'],
            'experiences.*.is_current' => ['nullable', 'boolean'],
            'experiences.*.description' => ['nullable', 'string', 'max:1000'],
            'experiences.*.privacy' => ['nullable', 'string', 'in:public,friends,only_me'],

            // Section 7: Skills
            'skills' => ['nullable', 'array'],
            'skills.*.name' => ['required_with:skills', 'string', 'max:100'],
            'skills.*.level' => ['nullable', 'string', 'in:beginner,intermediate,expert'],

            // Section 8: Interests & Hobbies
            'interests' => ['nullable', 'array'],
            'interests.*' => ['nullable'],
            'hobbies' => ['nullable', 'array'],
            'hobbies.*' => ['nullable', 'string', 'max:100'],
            'favorite_music' => ['nullable', 'array'],
            'favorite_music.*' => ['nullable', 'string', 'max:100'],
            'favorite_books' => ['nullable', 'array'],
            'favorite_books.*' => ['nullable', 'string', 'max:100'],
            'favorite_movies' => ['nullable', 'array'],
            'favorite_movies.*' => ['nullable', 'string', 'max:100'],

            // Section 9: Languages
            'languages' => ['nullable', 'array'],
            'languages.*.language' => ['required_with:languages', 'string', 'max:100'],
            'languages.*.proficiency' => ['nullable', 'string', 'in:elementary,conversational,fluent,native'],

            // Section 10: Social Links
            'social_links' => ['nullable', 'array'],
            'social_links.*.platform' => ['required_with:social_links', 'string', 'max:50'],
            'social_links.*.url' => ['required_with:social_links', 'url', 'max:255'],
            'social_links.*.is_visible' => ['nullable', 'boolean'],

            // Cover alignment
            'cover_position_y' => ['nullable', 'integer', 'between:0,100'],
        ];
    }
}
