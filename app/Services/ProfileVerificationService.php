<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ProfileVerification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfileVerificationService
{
    public function __construct(
        protected ProfileService $profileService,
        protected RbacService $rbacService
    ) {}

    /**
     * Check if a user is eligible to submit a verification request.
     *
     * @return array{eligible: bool, reasons: array<string>, current_status: string, can_resubmit: bool, latest_request: ?ProfileVerification}
     */
    public function checkEligibility(User $user): array
    {
        $reasons = [];
        $latest = $user->verifications()->latest()->first();
        $currentStatus = $user->verification_status;

        // 1. Already verified
        if ($user->hasVerifiedProfile() || $currentStatus === ProfileVerification::STATUS_VERIFIED) {
            return [
                'eligible' => false,
                'reasons' => ['Profile is already identity verified.'],
                'current_status' => ProfileVerification::STATUS_VERIFIED,
                'can_resubmit' => false,
                'latest_request' => $latest,
            ];
        }

        // 2. Pending request check
        if ($latest && $latest->isPending()) {
            return [
                'eligible' => false,
                'reasons' => ['A verification request is already submitted and pending review.'],
                'current_status' => ProfileVerification::STATUS_PENDING,
                'can_resubmit' => false,
                'latest_request' => $latest,
            ];
        }

        // 3. User account status
        if ($user->status !== 'active') {
            $reasons[] = 'Account must be active to apply for verification.';
        }

        // 4. Contact verification (email or phone verified)
        if (! $user->isVerified()) {
            $reasons[] = 'Email or phone must be verified before applying for identity verification.';
        }

        // 5. Basic profile completeness (name / username)
        if (empty($user->name) && empty($user->username)) {
            $reasons[] = 'Profile must have a valid name or username.';
        }

        $canResubmit = $latest ? in_array($latest->status, [ProfileVerification::STATUS_REJECTED, ProfileVerification::STATUS_NEEDS_INFO], true) : true;

        return [
            'eligible' => empty($reasons),
            'reasons' => $reasons,
            'current_status' => $latest ? $latest->status : ProfileVerification::STATUS_UNVERIFIED,
            'can_resubmit' => $canResubmit,
            'latest_request' => $latest,
        ];
    }

    /**
     * Submit an identity verification request with documents.
     */
    public function submit(
        User $user,
        array $data,
        ?UploadedFile $frontDoc = null,
        ?UploadedFile $backDoc = null,
        ?UploadedFile $selfie = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileVerification {
        $eligibility = $this->checkEligibility($user);
        if (! $eligibility['eligible']) {
            throw ValidationException::withMessages([
                'verification' => $eligibility['reasons'],
            ]);
        }

        return DB::transaction(function () use ($user, $data, $frontDoc, $backDoc, $selfie, $ip, $userAgent) {
            $mediaMeta = [];
            $frontKey = null;
            $backKey = null;
            $selfieKey = null;

            $storageDisk = config('filesystems.default', 'local');

            // Handle front document
            if ($frontDoc) {
                $ext = $frontDoc->getClientOriginalExtension() ?: 'jpg';
                $frontKey = "verifications/{$user->id}/front_".Str::random(24).".{$ext}";
                Storage::disk($storageDisk)->put($frontKey, file_get_contents($frontDoc->getRealPath()));
                $mediaMeta['front'] = [
                    'original_name' => $frontDoc->getClientOriginalName(),
                    'mime_type' => $frontDoc->getMimeType(),
                    'size' => $frontDoc->getSize(),
                    'path' => $frontKey,
                ];
            }

            // Handle back document
            if ($backDoc) {
                $ext = $backDoc->getClientOriginalExtension() ?: 'jpg';
                $backKey = "verifications/{$user->id}/back_".Str::random(24).".{$ext}";
                Storage::disk($storageDisk)->put($backKey, file_get_contents($backDoc->getRealPath()));
                $mediaMeta['back'] = [
                    'original_name' => $backDoc->getClientOriginalName(),
                    'mime_type' => $backDoc->getMimeType(),
                    'size' => $backDoc->getSize(),
                    'path' => $backKey,
                ];
            }

            // Handle selfie document
            if ($selfie) {
                $ext = $selfie->getClientOriginalExtension() ?: 'jpg';
                $selfieKey = "verifications/{$user->id}/selfie_".Str::random(24).".{$ext}";
                Storage::disk($storageDisk)->put($selfieKey, file_get_contents($selfie->getRealPath()));
                $mediaMeta['selfie'] = [
                    'original_name' => $selfie->getClientOriginalName(),
                    'mime_type' => $selfie->getMimeType(),
                    'size' => $selfie->getSize(),
                    'path' => $selfieKey,
                ];
            }

            // If front_key/back_key/selfie_key provided as paths/keys in data
            if (! $frontKey && ! empty($data['document_front_key'])) {
                $frontKey = $data['document_front_key'];
            }
            if (! $backKey && ! empty($data['document_back_key'])) {
                $backKey = $data['document_back_key'];
            }
            if (! $selfieKey && ! empty($data['selfie_key'])) {
                $selfieKey = $data['selfie_key'];
            }

            $verification = ProfileVerification::create([
                'user_id' => $user->id,
                'verification_type' => $data['verification_type'],
                'document_number' => $data['document_number'] ?? null,
                'document_front_key' => $frontKey,
                'document_back_key' => $backKey,
                'selfie_key' => $selfieKey,
                'media_meta' => $mediaMeta ?: null,
                'status' => ProfileVerification::STATUS_PENDING,
                'submitted_at' => now(),
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'profile.verification_submitted',
                'entity_type' => ProfileVerification::class,
                'entity_id' => $verification->id,
                'old_values' => null,
                'new_values' => [
                    'verification_id' => $verification->id,
                    'type' => $verification->verification_type,
                    'status' => ProfileVerification::STATUS_PENDING,
                ],
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            $this->profileService->invalidateProfileCache($user);

            return $verification;
        });
    }

    /**
     * Get paginated verification requests for admin review.
     */
    public function getRequests(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ProfileVerification::with(['user.profile', 'admin']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['verification_type'])) {
            $query->where('verification_type', $filters['verification_type']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('document_number', 'like', "%{$term}%")
                    ->orWhereHas('user', function ($uq) use ($term) {
                        $uq->where('name', 'like', "%{$term}%")
                            ->orWhere('username', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    });
            });
        }

        return $query->latest('submitted_at')->paginate($perPage);
    }

    /**
     * Get single verification request details.
     */
    public function getVerificationDetails(int $id): ProfileVerification
    {
        return ProfileVerification::with(['user.profile', 'admin'])->findOrFail($id);
    }

    /**
     * Approve verification request by admin.
     */
    public function approve(
        ProfileVerification $verification,
        User $admin,
        ?string $notes = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileVerification {
        return DB::transaction(function () use ($verification, $admin, $notes, $ip, $userAgent) {
            $targetUser = $verification->user;
            $oldStatus = $verification->status;

            $verification->update([
                'status' => ProfileVerification::STATUS_VERIFIED,
                'admin_id' => $admin->id,
                'admin_notes' => $notes,
                'rejection_reason' => null,
                'reviewed_at' => now(),
            ]);

            // Assign VERIFIED_USER role via RbacService
            Role::firstOrCreate(
                ['name' => 'VERIFIED_USER'],
                ['label' => 'Verified User', 'description' => 'Identity verified citizen user with blue badge']
            );

            if (! $targetUser->hasRole('VERIFIED_USER')) {
                $this->rbacService->assignRole($targetUser, 'VERIFIED_USER', $admin);
            }

            // Audit log
            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'profile.verification_approved',
                'entity_type' => ProfileVerification::class,
                'entity_id' => $verification->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => [
                    'status' => ProfileVerification::STATUS_VERIFIED,
                    'target_user_id' => $targetUser->id,
                    'admin_notes' => $notes,
                ],
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            $this->profileService->invalidateProfileCache($targetUser);

            return $verification->fresh(['user', 'admin']);
        });
    }

    /**
     * Reject verification request by admin with mandatory reason.
     */
    public function reject(
        ProfileVerification $verification,
        User $admin,
        string $reason,
        ?string $notes = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileVerification {
        return DB::transaction(function () use ($verification, $admin, $reason, $notes, $ip, $userAgent) {
            $targetUser = $verification->user;
            $oldStatus = $verification->status;

            $verification->update([
                'status' => ProfileVerification::STATUS_REJECTED,
                'admin_id' => $admin->id,
                'rejection_reason' => $reason,
                'admin_notes' => $notes,
                'reviewed_at' => now(),
            ]);

            // If user previously had VERIFIED_USER role, remove it
            if ($targetUser->hasRole('VERIFIED_USER')) {
                $this->rbacService->removeRole($targetUser, 'VERIFIED_USER', $admin);
            }

            // Audit log
            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'profile.verification_rejected',
                'entity_type' => ProfileVerification::class,
                'entity_id' => $verification->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => [
                    'status' => ProfileVerification::STATUS_REJECTED,
                    'target_user_id' => $targetUser->id,
                    'rejection_reason' => $reason,
                    'admin_notes' => $notes,
                ],
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            $this->profileService->invalidateProfileCache($targetUser);

            return $verification->fresh(['user', 'admin']);
        });
    }

    /**
     * Request additional information from user for verification.
     */
    public function requestInfo(
        ProfileVerification $verification,
        User $admin,
        string $instructions,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileVerification {
        return DB::transaction(function () use ($verification, $admin, $instructions, $ip, $userAgent) {
            $targetUser = $verification->user;
            $oldStatus = $verification->status;

            $verification->update([
                'status' => ProfileVerification::STATUS_NEEDS_INFO,
                'admin_id' => $admin->id,
                'admin_notes' => $instructions,
                'reviewed_at' => now(),
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'profile.verification_info_requested',
                'entity_type' => ProfileVerification::class,
                'entity_id' => $verification->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => [
                    'status' => ProfileVerification::STATUS_NEEDS_INFO,
                    'target_user_id' => $targetUser->id,
                    'instructions' => $instructions,
                ],
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            $this->profileService->invalidateProfileCache($targetUser);

            return $verification->fresh(['user', 'admin']);
        });
    }

    /**
     * Revoke an active verified status.
     */
    public function revoke(
        ProfileVerification $verification,
        User $admin,
        string $reason,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileVerification {
        return DB::transaction(function () use ($verification, $admin, $reason, $ip, $userAgent) {
            $targetUser = $verification->user;
            $oldStatus = $verification->status;

            $verification->update([
                'status' => ProfileVerification::STATUS_UNVERIFIED,
                'admin_id' => $admin->id,
                'rejection_reason' => $reason,
                'reviewed_at' => now(),
            ]);

            if ($targetUser->hasRole('VERIFIED_USER')) {
                $this->rbacService->removeRole($targetUser, 'VERIFIED_USER', $admin);
            }

            // Audit log
            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'profile.verification_revoked',
                'entity_type' => ProfileVerification::class,
                'entity_id' => $verification->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => [
                    'status' => ProfileVerification::STATUS_UNVERIFIED,
                    'target_user_id' => $targetUser->id,
                    'revocation_reason' => $reason,
                ],
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            $this->profileService->invalidateProfileCache($targetUser);

            return $verification->fresh(['user', 'admin']);
        });
    }
}
