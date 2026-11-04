<?php

declare(strict_types=1);

namespace App\Services\Recruitment;

use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\CompanyVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyService
{
    /**
     * Create a new company profile and set creator as Owner.
     */
    public function createCompany(User $owner, array $data): Company
    {
        return DB::transaction(function () use ($owner, $data) {
            $company = Company::create([
                'owner_user_id' => $owner->id,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null,
                'logo' => $data['logo'] ?? null,
                'cover_image' => $data['cover_image'] ?? null,
                'description' => $data['description'] ?? null,
                'industry' => $data['industry'] ?? null,
                'company_size' => $data['company_size'] ?? null,
                'founded_year' => isset($data['founded_year']) ? (int) $data['founded_year'] : null,
                'website' => $data['website'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'headquarters' => $data['headquarters'] ?? null,
                'locations' => $data['locations'] ?? [],
                'verification_status' => Company::VERIFICATION_UNVERIFIED,
                'visibility' => $data['visibility'] ?? Company::VISIBILITY_PUBLIC,
            ]);

            CompanyMember::create([
                'company_id' => $company->id,
                'user_id' => $owner->id,
                'role' => CompanyMember::ROLE_OWNER,
                'invitation_status' => CompanyMember::STATUS_ACCEPTED,
                'joined_at' => now(),
            ]);

            return $company;
        });
    }

    /**
     * Update an existing company profile.
     */
    public function updateCompany(Company $company, array $data): Company
    {
        $company->update(array_filter([
            'name' => $data['name'] ?? $company->name,
            'logo' => array_key_exists('logo', $data) ? $data['logo'] : $company->logo,
            'cover_image' => array_key_exists('cover_image', $data) ? $data['cover_image'] : $company->cover_image,
            'description' => array_key_exists('description', $data) ? $data['description'] : $company->description,
            'industry' => array_key_exists('industry', $data) ? $data['industry'] : $company->industry,
            'company_size' => array_key_exists('company_size', $data) ? $data['company_size'] : $company->company_size,
            'founded_year' => array_key_exists('founded_year', $data) ? (int) $data['founded_year'] : $company->founded_year,
            'website' => array_key_exists('website', $data) ? $data['website'] : $company->website,
            'email' => array_key_exists('email', $data) ? $data['email'] : $company->email,
            'phone' => array_key_exists('phone', $data) ? $data['phone'] : $company->phone,
            'headquarters' => array_key_exists('headquarters', $data) ? $data['headquarters'] : $company->headquarters,
            'locations' => array_key_exists('locations', $data) ? $data['locations'] : $company->locations,
            'visibility' => $data['visibility'] ?? $company->visibility,
        ], fn ($val) => $val !== null));

        return $company->fresh();
    }

    /**
     * Invite a member to the company.
     */
    public function inviteMember(Company $company, User $inviter, string $identifier, string $role): CompanyMember
    {
        $targetUser = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (! $targetUser) {
            throw ValidationException::withMessages([
                'email' => ['প্রদত্ত ইমেইল বা ইউজারনেমের কোনো ব্যবহারকারী পাওয়া যায়নি।'],
            ]);
        }

        $existing = CompanyMember::where('company_id', $company->id)
            ->where('user_id', $targetUser->id)
            ->first();

        if ($existing) {
            if ($existing->invitation_status === CompanyMember::STATUS_ACCEPTED) {
                throw ValidationException::withMessages([
                    'email' => ['এই ব্যবহারকারী ইতিমধ্যে কোম্পানির সদস্য হিসেবে আছেন।'],
                ]);
            }
            $existing->update([
                'role' => $role,
                'invited_by' => $inviter->id,
                'invitation_status' => CompanyMember::STATUS_PENDING,
            ]);

            return $existing;
        }

        return CompanyMember::create([
            'company_id' => $company->id,
            'user_id' => $targetUser->id,
            'role' => $role,
            'invitation_status' => CompanyMember::STATUS_PENDING,
            'invited_by' => $inviter->id,
        ]);
    }

    /**
     * Accept or decline company invitation.
     */
    public function respondToInvitation(CompanyMember $member, User $user, bool $accept): CompanyMember
    {
        if ($member->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'member' => ['এই আমন্ত্রণটি গ্রহণ বা প্রত্যাখ্যান করার অনুমতি নেই।'],
            ]);
        }

        $member->update([
            'invitation_status' => $accept ? CompanyMember::STATUS_ACCEPTED : CompanyMember::STATUS_DECLINED,
            'joined_at' => $accept ? now() : null,
        ]);

        return $member;
    }

    /**
     * Request verification for a company.
     */
    public function requestVerification(Company $company, User $user, array $documentPaths, ?string $notes = null): CompanyVerification
    {
        return DB::transaction(function () use ($company, $user, $documentPaths, $notes) {
            $verification = CompanyVerification::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'documents' => $documentPaths,
                'status' => CompanyVerification::STATUS_PENDING,
                'admin_notes' => $notes,
            ]);

            $company->update(['verification_status' => Company::VERIFICATION_PENDING]);

            return $verification;
        });
    }
}
