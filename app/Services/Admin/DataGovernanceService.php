<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\DataRetentionPolicy;
use App\Models\PrivacyRequest;
use App\Models\SecurityEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class DataGovernanceService
{
    /**
     * Data Inventory Catalog with Classifications.
     */
    public const DATA_INVENTORY = [
        [
            'category' => 'user_pii',
            'name' => 'ব্যক্তিগত তথ্য (User PII)',
            'classification' => 'Confidential',
            'tables' => ['users', 'user_profiles', 'phone_verifications'],
            'legal_basis' => 'Contractual necessity / User consent',
            'default_retention_days' => 365,
        ],
        [
            'category' => 'content_media',
            'name' => 'পোস্ট, মিডিয়া ও কমেন্টস',
            'classification' => 'Public / Restricted',
            'tables' => ['posts', 'comments', 'reactions', 'media'],
            'legal_basis' => 'User generated content',
            'default_retention_days' => 730,
        ],
        [
            'category' => 'direct_messages',
            'name' => 'ব্যক্তিগত বার্তা (Encrypted Messenger)',
            'classification' => 'Restricted',
            'tables' => ['messages', 'conversations'],
            'legal_basis' => 'End-to-End messaging policy',
            'default_retention_days' => 365,
        ],
        [
            'category' => 'audit_compliance',
            'name' => 'ক্রিপ্টোগ্রাফিক অডিট ও অনুপালন লগ',
            'classification' => 'Restricted',
            'tables' => ['audit_logs', 'audit_checkpoints'],
            'legal_basis' => 'Regulatory compliance & security accountability',
            'default_retention_days' => 365,
        ],
        [
            'category' => 'security_telemetry',
            'name' => 'নিরাপত্তা ইভেন্ট ও ব্যর্থ লগইন লগ',
            'classification' => 'Internal',
            'tables' => ['security_events', 'failed_login_attempts'],
            'legal_basis' => 'Platform integrity & fraud prevention',
            'default_retention_days' => 90,
        ],
    ];

    /**
     * Get Data Category Inventory with live database record counts.
     */
    public function getDataInventory(): array
    {
        return array_map(function (array $item) {
            $recordsCount = 0;
            foreach ($item['tables'] ?? [] as $table) {
                try {
                    if (Schema::hasTable($table)) {
                        $recordsCount += DB::table($table)->count();
                    }
                } catch (\Throwable) {
                }
            }

            return array_merge($item, [
                'label' => $item['name'],
                'pii_sensitivity' => $item['classification'],
                'records_count' => $recordsCount,
            ]);
        }, self::DATA_INVENTORY);
    }

    /**
     * Get All Data Retention Policies.
     */
    public function getRetentionPolicies(): Collection
    {
        return DataRetentionPolicy::with('updater:id,name')->get();
    }

    /**
     * Update a Retention Policy.
     */
    public function updatePolicy(int $policyId, array $data, User $admin): DataRetentionPolicy
    {
        $policy = DataRetentionPolicy::findOrFail($policyId);
        $old = $policy->only(['retention_days', 'action', 'is_enabled']);

        $policy->update([
            'retention_days' => isset($data['retention_days']) ? (int) $data['retention_days'] : $policy->retention_days,
            'action' => $data['action'] ?? $policy->action,
            'is_enabled' => isset($data['is_enabled']) ? (bool) $data['is_enabled'] : $policy->is_enabled,
            'description' => $data['description'] ?? $policy->description,
            'updated_by' => $admin->id,
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'admin_retention_policy_updated',
            'entity_type' => DataRetentionPolicy::class,
            'entity_id' => $policy->id,
            'old_values' => $old,
            'new_values' => $policy->only(['retention_days', 'action', 'is_enabled']),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $policy;
    }

    /**
     * Execute Retention Policies and Prune Expired Records.
     */
    public function enforceRetentionPolicies(bool $dryRun = false, ?User $admin = null): array
    {
        $policies = DataRetentionPolicy::where('is_enabled', true)->get();
        $results = [];

        foreach ($policies as $policy) {
            $cutoffDate = Carbon::now()->subDays($policy->retention_days);
            $affected = 0;

            switch ($policy->data_category) {
                case 'failed_logins':
                    if (Schema::hasTable('failed_login_attempts')) {
                        $query = DB::table('failed_login_attempts')->where('attempted_at', '<', $cutoffDate);
                        $affected = $query->count();
                        if (! $dryRun && $affected > 0) {
                            $query->delete();
                        }
                    }
                    break;

                case 'security_events':
                    $query = SecurityEvent::where('resolved', true)->where('created_at', '<', $cutoffDate);
                    $affected = $query->count();
                    if (! $dryRun && $affected > 0) {
                        $query->delete();
                    }
                    break;

                case 'notifications':
                    if (Schema::hasTable('notifications')) {
                        $query = DB::table('notifications')
                            ->whereNotNull('read_at')
                            ->where('created_at', '<', $cutoffDate);
                        $affected = $query->count();
                        if (! $dryRun && $affected > 0) {
                            $query->delete();
                        }
                    }
                    break;

                case 'soft_deleted_users':
                    // Respect Legal Hold! Never delete/anonymize users on legal hold!
                    $query = User::onlyTrashed()
                        ->where('is_legal_hold', false)
                        ->where('deleted_at', '<', $cutoffDate);
                    $affected = $query->count();
                    if (! $dryRun && $affected > 0) {
                        $usersToAnonymize = $query->get();
                        $actualAnonymized = 0;
                        foreach ($usersToAnonymize as $u) {
                            DB::transaction(function () use ($u, &$actualAnonymized) {
                                $lockedUser = User::withTrashed()->where('id', $u->id)->lockForUpdate()->first();
                                if ($lockedUser && ! $lockedUser->isUnderLegalHold()) {
                                    $lockedUser->update([
                                        'name' => 'Deleted User #'.$lockedUser->id,
                                        'email' => "anonymized_{$lockedUser->id}@deleted.bondhoo.com",
                                        'phone' => null,
                                        'status' => 'anonymized',
                                    ]);
                                    $actualAnonymized++;
                                }
                            });
                        }
                        $affected = $actualAnonymized;
                    }
                    break;
            }

            if (! $dryRun) {
                $policy->update([
                    'last_run_at' => now(),
                    'last_records_affected' => $affected,
                ]);
            }

            $results[] = [
                'category' => $policy->data_category,
                'name' => $policy->name,
                'retention_days' => $policy->retention_days,
                'affected_count' => $affected,
                'dry_run' => $dryRun,
            ];
        }

        if (! $dryRun && $admin) {
            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_retention_enforced',
                'entity_type' => DataRetentionPolicy::class,
                'entity_id' => 0,
                'new_values' => ['summary' => $results],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return $results;
    }

    /**
     * Get Filtered Privacy Requests.
     */
    public function getPrivacyRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PrivacyRequest::with(['user:id,name,email,username', 'processor:id,name']);

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['request_type']) && $filters['request_type'] !== 'all') {
            $query->where('request_type', $filters['request_type']);
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Submit a New Privacy/GDPR Request.
     */
    public function submitPrivacyRequest(User $user, string $requestType, ?string $reason = null): PrivacyRequest
    {
        return PrivacyRequest::create([
            'request_number' => 'PRV-'.strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'request_type' => $requestType,
            'status' => PrivacyRequest::STATUS_PENDING,
            'reason' => $reason,
        ]);
    }

    /**
     * Process a Privacy Request with Strict Legal Hold Safeguards.
     */
    public function processPrivacyRequest(int $requestId, string $decision, User $admin, ?string $notes = null): PrivacyRequest
    {
        $blockedByLegalHold = false;

        $processedRequest = DB::transaction(function () use ($requestId, $decision, $admin, $notes, &$blockedByLegalHold) {
            $request = PrivacyRequest::with('user')->where('id', $requestId)->lockForUpdate()->firstOrFail();
            $user = $request->user ? User::where('id', $request->user->id)->lockForUpdate()->first() : null;

            if ($decision === 'reject') {
                $request->update([
                    'status' => PrivacyRequest::STATUS_REJECTED,
                    'notes' => $notes,
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                ]);

                return $request->fresh();
            }

            // Check Legal Hold for Erasure/Deletion requests
            if ($request->request_type === PrivacyRequest::TYPE_ERASURE) {
                if ($user && $user->isUnderLegalHold()) {
                    $request->update([
                        'status' => PrivacyRequest::STATUS_BLOCKED_BY_LEGAL_HOLD,
                        'notes' => 'আইনি স্থগিতাদেশ (Legal Hold) বলবৎ থাকায় এই অ্যাকাউন্টটি মোছা সম্ভব নয়।',
                        'processed_by' => $admin->id,
                        'processed_at' => now(),
                    ]);
                    $blockedByLegalHold = true;

                    return $request->fresh();
                }

                if ($user) {
                    try {
                        $user->delete(); // Soft delete (also guarded by User::deleting Legal Hold hook)
                    } catch (InvalidArgumentException) {
                        $request->update([
                            'status' => PrivacyRequest::STATUS_BLOCKED_BY_LEGAL_HOLD,
                            'notes' => 'আইনি স্থগিতাদেশ (Legal Hold) বলবৎ থাকায় এই অ্যাকাউন্টটি মোছা সম্ভব নয়।',
                            'processed_by' => $admin->id,
                            'processed_at' => now(),
                        ]);
                        $blockedByLegalHold = true;

                        return $request->fresh();
                    }
                }

                $request->update([
                    'status' => PrivacyRequest::STATUS_COMPLETED,
                    'notes' => $notes ?? 'ব্যবহারকারীর আবেদনের প্রেক্ষিতে অ্যাকাউন্ট স্থগিত ও ডাটা মোছার প্রক্রিয়া সম্পন্ন।',
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                ]);
            } elseif ($request->request_type === PrivacyRequest::TYPE_EXPORT) {
                // Generate Data Export archive
                $exportPath = "exports/privacy/export_{$user->id}_".now()->format('Ymd_His').'.json';
                $exportData = [
                    'account' => $user->only(['id', 'name', 'username', 'email', 'phone', 'created_at']),
                    'profile' => $user->profile?->toArray() ?? [],
                    'posts' => $user->posts()->select('id', 'content', 'created_at')->get()->toArray(),
                    'generated_at' => now()->toIso8601String(),
                ];

                Storage::disk('local')->put($exportPath, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                $request->update([
                    'status' => PrivacyRequest::STATUS_COMPLETED,
                    'export_file_path' => $exportPath,
                    'export_expires_at' => now()->addDays(7),
                    'notes' => $notes ?? 'ব্যক্তিগত ডাটা এক্সপোর্ট ফাইল প্রস্তুত হয়েছে। ৭ দিনের মধ্যে ডাউনলোড করতে হবে।',
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                ]);
            }

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_privacy_request_processed',
                'entity_type' => PrivacyRequest::class,
                'entity_id' => $request->id,
                'new_values' => ['status' => $request->status, 'decision' => $decision, 'notes' => $notes],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $request->fresh();
        });

        if ($blockedByLegalHold) {
            throw new InvalidArgumentException('আইনি স্থগিতাদেশ (Legal Hold) বলবৎ থাকায় ব্যবহারকারী বা ডাটা মোছা সম্পূর্ণ নিষিদ্ধ।');
        }

        return $processedRequest;
    }

    /**
     * Toggle Legal Hold on a User.
     */
    public function setLegalHold(User $targetUser, bool $isLegalHold, User $admin, string $reason): User
    {
        return DB::transaction(function () use ($targetUser, $isLegalHold, $admin, $reason) {
            $lockedUser = User::where('id', $targetUser->id)->lockForUpdate()->firstOrFail();
            $lockedUser->update(['is_legal_hold' => $isLegalHold]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => $isLegalHold ? 'admin_user_legal_hold_applied' : 'admin_user_legal_hold_released',
                'entity_type' => User::class,
                'entity_id' => $lockedUser->id,
                'new_values' => [
                    'target_user_id' => $lockedUser->id,
                    'is_legal_hold' => $isLegalHold,
                    'reason' => $reason,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $lockedUser->fresh();
        });
    }
}
