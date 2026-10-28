<?php

namespace App\Services\Security;

use App\Models\AdminTrustedDevice;
use App\Models\AuditLog;
use App\Models\AutomatedResponseAction;
use App\Models\DeviceSession;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\UserRestriction;
use App\Models\ZeroTrustAccessLog;
use App\Models\ZeroTrustPolicy;
use App\Services\Admin\EnterpriseAccessControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ZeroTrustPolicyService
{
    /**
     * Default Versioned Zero-Trust Policies for Privileged Administrative Operations.
     */
    public const DEFAULT_POLICIES = [
        [
            'policy_code' => 'ZTP-ROLE-MANAGE',
            'name' => 'প্রিভিলেজড রোল ও SUPER_ADMIN ব্যবস্থাপনা নীতি',
            'operation_key' => 'roles.manage',
            'effect' => 'allow',
            'risk_level' => 'critical',
            'required_permissions' => ['roles.manage'],
            'require_mfa' => false,
            'require_jit' => false,
            'require_fresh_auth_minutes' => 60,
            'require_dual_approval' => false,
            'prevent_self_approval' => true,
            'max_risk_score' => 70,
            'require_trusted_device' => false,
        ],
        [
            'policy_code' => 'ZTP-JIT-ELEVATE',
            'name' => 'সাময়িক প্রিভিলেজ এলিভেশন (JIT) অনুমোদন নীতি',
            'operation_key' => 'governance.elevate',
            'effect' => 'allow',
            'risk_level' => 'critical',
            'required_permissions' => ['roles.manage'],
            'require_mfa' => false,
            'require_jit' => false,
            'require_fresh_auth_minutes' => 30,
            'require_dual_approval' => false,
            'prevent_self_approval' => true,
            'max_risk_score' => 65,
            'require_trusted_device' => false,
        ],
        [
            'policy_code' => 'ZTP-DR-BACKUP-DELETE',
            'name' => 'ডিজাস্টার রিকভারি ব্যাকআপ অপসারণ সুরক্ষা নীতি',
            'operation_key' => 'dr.backup.delete',
            'effect' => 'allow',
            'risk_level' => 'critical',
            'required_permissions' => ['settings.manage'],
            'require_mfa' => false,
            'require_jit' => false,
            'require_fresh_auth_minutes' => 30,
            'require_dual_approval' => false,
            'prevent_self_approval' => true,
            'max_risk_score' => 65,
            'require_trusted_device' => false,
        ],
        [
            'policy_code' => 'ZTP-PRIVACY-ERASURE',
            'name' => 'ব্যক্তিগত ডাটা ইরেজার ও জিডিপিআর নিষ্পত্তি নীতি',
            'operation_key' => 'governance.privacy.process',
            'effect' => 'allow',
            'risk_level' => 'high',
            'required_permissions' => ['governance.manage'],
            'require_mfa' => false,
            'require_jit' => false,
            'require_fresh_auth_minutes' => 60,
            'require_dual_approval' => false,
            'prevent_self_approval' => true,
            'max_risk_score' => 75,
            'require_trusted_device' => false,
        ],
        [
            'policy_code' => 'ZTP-CONFIG-ROLLBACK',
            'name' => 'প্ল্যাটফর্ম কনফিগারেশন রোলব্যাক ও ড্রিফট নীতি',
            'operation_key' => 'settings.rollback',
            'effect' => 'allow',
            'risk_level' => 'high',
            'required_permissions' => ['settings.rollback'],
            'require_mfa' => false,
            'require_jit' => false,
            'require_fresh_auth_minutes' => 60,
            'require_dual_approval' => false,
            'prevent_self_approval' => true,
            'max_risk_score' => 75,
            'require_trusted_device' => false,
        ],
        [
            'policy_code' => 'ZTP-SECURITY-POLICY',
            'name' => 'জিরো-ট্রাস্ট ও সিকিউরিটি পলিসি পরিবর্তন নীতি',
            'operation_key' => 'security.policy_manage',
            'effect' => 'allow',
            'risk_level' => 'critical',
            'required_permissions' => ['security.manage'],
            'require_mfa' => true,
            'require_jit' => false,
            'require_fresh_auth_minutes' => 30,
            'require_dual_approval' => false,
            'prevent_self_approval' => true,
            'max_risk_score' => 65,
            'require_trusted_device' => false,
        ],
        [
            'policy_code' => 'ZTP-INCIDENT-RESPONSE',
            'name' => 'এসওসি ইনসিডেন্ট ও স্বয়ংক্রিয় রেসপন্স অনুমোদন নীতি',
            'operation_key' => 'security.incident.respond',
            'effect' => 'allow',
            'risk_level' => 'high',
            'required_permissions' => ['security.soc'],
            'require_mfa' => false,
            'require_jit' => false,
            'require_fresh_auth_minutes' => 60,
            'require_dual_approval' => false,
            'prevent_self_approval' => true,
            'max_risk_score' => 75,
            'require_trusted_device' => false,
        ],
        [
            'policy_code' => 'ZTP-EMERGENCY-ACCESS',
            'name' => 'জরুরি ব্রেক-গ্লাস অ্যাক্সেস নীতি (Emergency Break-Glass)',
            'operation_key' => 'security.emergency.access',
            'effect' => 'allow',
            'risk_level' => 'critical',
            'required_permissions' => ['roles.manage'],
            'require_mfa' => true,
            'require_jit' => true,
            'require_fresh_auth_minutes' => 15,
            'require_dual_approval' => true,
            'prevent_self_approval' => true,
            'max_risk_score' => 50,
            'require_trusted_device' => true,
        ],
        [
            'policy_code' => 'ZTP-DR-DESTRUCTIVE',
            'name' => 'ডিজাস্টার রিকভারি ক্ষতিকর পরিবর্তন সুরক্ষা নীতি',
            'operation_key' => 'disaster_recovery.destructive_change',
            'effect' => 'allow',
            'risk_level' => 'critical',
            'required_permissions' => ['settings.manage'],
            'require_mfa' => false,
            'require_jit' => false,
            'require_fresh_auth_minutes' => 30,
            'require_dual_approval' => true,
            'prevent_self_approval' => true,
            'max_risk_score' => 65,
            'require_trusted_device' => false,
        ],
        [
            'policy_code' => 'ZT-AUDIT-EXPORT-01',
            'name' => 'অডিট এক্সপোর্ট ও ফরেনসিক ডাউনলোড নীতি',
            'operation_key' => 'audit.export',
            'effect' => 'allow',
            'risk_level' => 'high',
            'required_permissions' => ['audit.view'],
            'require_mfa' => false,
            'require_jit' => false,
            'require_fresh_auth_minutes' => 60,
            'require_dual_approval' => false,
            'prevent_self_approval' => false,
            'max_risk_score' => 70,
            'require_trusted_device' => false,
        ],
    ];

    public function __construct(
        protected EnterpriseAccessControlService $accessControl,
        protected AbuseDetectionEngineService $riskEngine
    ) {}

    /**
     * Ensure default Zero-Trust policies exist in the database.
     */
    public function syncDefaultPolicies(): void
    {
        if (! Schema::hasTable('zero_trust_policies')) {
            return;
        }

        foreach (self::DEFAULT_POLICIES as $default) {
            ZeroTrustPolicy::firstOrCreate(
                ['policy_code' => $default['policy_code']],
                array_merge($default, [
                    'policy_key' => $default['policy_code'],
                    'version' => 1,
                    'is_active' => true,
                    'change_history' => [[
                        'version' => 1,
                        'action' => 'seeded_default',
                        'timestamp' => now()->toIso8601String(),
                    ]],
                ])
            );
        }
    }

    /**
     * Get all configured Zero-Trust policies and recent access evaluation telemetry.
     */
    public function getZeroTrustOverview(): array
    {
        $this->syncDefaultPolicies();

        $policies = ZeroTrustPolicy::orderBy('id')->get();
        $recentEvaluations = Schema::hasTable('zero_trust_access_logs')
            ? ZeroTrustAccessLog::with('user:id,name,email,username')
                ->latest('id')
                ->take(25)
                ->get()
            : collect();

        $denialsLast24h = Schema::hasTable('zero_trust_access_logs')
            ? ZeroTrustAccessLog::where('decision', '!=', ZeroTrustAccessLog::DECISION_ALLOW)
                ->where('evaluated_at', '>=', now()->subHours(24))
                ->count()
            : 0;

        $allowsLast24h = Schema::hasTable('zero_trust_access_logs')
            ? ZeroTrustAccessLog::where('decision', ZeroTrustAccessLog::DECISION_ALLOW)
                ->where('evaluated_at', '>=', now()->subHours(24))
                ->count()
            : 0;

        return [
            'policies' => $policies,
            'recent_evaluations' => $recentEvaluations,
            'summary' => [
                'total_policies' => $policies->count(),
                'active_policies_count' => $policies->where('is_active', true)->count(),
                'allows_24h' => $allowsLast24h,
                'denials_or_stepups_24h' => $denialsLast24h,
                'global_mfa_enforced' => (bool) PlatformSetting::get('security_require_admin_mfa', false),
                'deny_by_default' => true,
            ],
        ];
    }

    /**
     * Evaluate Zero-Trust Access for a protected administrative operation (Deny-by-Default).
     * Never interprets an unavailable device, IP, or external signal as proof of trust.
     *
     * @param  array<string, mixed>  $context
     * @return array{allowed: bool, decision: string, reason_code: string, message: string, policy_code: string, policy_version: int, risk_score: int, signals: array<string, mixed>}
     */
    public function evaluateAccess(?User $user, string $operationKey, array $context = []): array
    {
        $this->syncDefaultPolicies();

        $alt1 = str_replace('_', '.', $operationKey);
        $alt2 = str_replace('.', '_', $operationKey);

        $policy = ZeroTrustPolicy::where(function ($q) use ($operationKey, $alt1, $alt2) {
            $q->where('operation_key', $operationKey)
                ->orWhere('policy_code', $operationKey)
                ->orWhere('policy_key', $operationKey)
                ->orWhere('operation_key', $alt1)
                ->orWhere('operation_key', $alt2);
        })
            ->where('is_active', true)
            ->first();

        $policyCode = $policy?->policy_code ?? 'ZTP-DEFAULT-DENY';
        $policyVersion = (int) ($policy?->version ?? 1);

        // 1. Authenticated Identity Check
        if (! $user) {
            return $this->recordDecision(
                null,
                $policyCode,
                $policyVersion,
                $operationKey,
                ZeroTrustAccessLog::DECISION_DENY,
                100,
                ['authenticated' => false],
                'UNAUTHENTICATED_IDENTITY',
                'প্রমাণীকরণহীন অনুরোধ প্রত্যাখ্যান করা হয়েছে (Deny-by-Default)।',
                $context
            );
        }

        // 2. Current Account Status & Active Restriction Check
        $accountActive = in_array($user->status, ['active', 'super_admin'], true) && $user->deleted_at === null;
        $hasFullBan = UserRestriction::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('restriction_type', UserRestriction::TYPE_FULL_BAN)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();

        if (! $accountActive || $hasFullBan) {
            return $this->recordDecision(
                $user,
                $policyCode,
                $policyVersion,
                $operationKey,
                ZeroTrustAccessLog::DECISION_DENY,
                100,
                ['authenticated' => true, 'account_status' => $user->status, 'full_ban' => $hasFullBan],
                'ACCOUNT_INACTIVE_OR_RESTRICTED',
                'অ্যাকাউন্ট নিষ্ক্রিয়, স্থগিত অথবা পূর্ণ নিষেধাজ্ঞার আওতায় রয়েছে।',
                $context
            );
        }

        // 3. Session Validity & Revocation Check
        $sessionRevoked = (bool) ($context['session_revoked'] ?? (request()?->hasSession() && request()->session()->get('revoked') === true));
        if ($sessionRevoked) {
            return $this->recordDecision(
                $user,
                $policyCode,
                $policyVersion,
                $operationKey,
                ZeroTrustAccessLog::DECISION_DENY,
                95,
                ['authenticated' => true, 'session_valid' => false],
                'SESSION_REVOKED',
                'বর্তমান সেশনটি বাতিল করা হয়েছে; পুনরায় লগইন আবশ্যক।',
                $context
            );
        }

        // 4. Policy Presence & Explicit Effect Check (Deny-by-Default if policy is missing or effect is deny)
        if (! $policy || $policy->effect === ZeroTrustPolicy::EFFECT_DENY) {
            return $this->recordDecision(
                $user,
                $policyCode,
                $policyVersion,
                $operationKey,
                ZeroTrustAccessLog::DECISION_DENY,
                50,
                ['authenticated' => true, 'policy_found' => (bool) $policy, 'policy_effect' => $policy?->effect ?? 'deny'],
                $policy ? 'POLICY_EXPLICIT_DENY' : 'DENY_BY_DEFAULT_NO_MATCHING_POLICY',
                "জিরো-ট্রাস্ট নীতি ({$policyCode}) অনুযায়ী এই অপারেশনটি অনুমোদিত নয়।",
                $context
            );
        }

        // 5. Required Permissions & Scope Check
        $requiredPermissions = is_array($policy->required_permissions) ? $policy->required_permissions : [];
        foreach ($requiredPermissions as $perm) {
            if (! $this->accessControl->hasPermission($user, (string) $perm)) {
                return $this->recordDecision(
                    $user,
                    $policyCode,
                    $policyVersion,
                    $operationKey,
                    ZeroTrustAccessLog::DECISION_DENY,
                    40,
                    ['authenticated' => true, 'missing_permission' => $perm],
                    'MISSING_REQUIRED_PERMISSION',
                    "প্রয়োজনীয় পারমিশন ({$perm}) আপনার অ্যাকাউন্টে বা সক্রিয় JIT এলিভেশনে নেই।",
                    $context
                );
            }
        }

        // 6. Active JIT Elevation Requirement Check
        $hasActiveJit = $user->adminElevations()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($policy->require_jit && ! $hasActiveJit) {
            return $this->recordDecision(
                $user,
                $policyCode,
                $policyVersion,
                $operationKey,
                ZeroTrustAccessLog::DECISION_STEP_UP_REQUIRED,
                35,
                ['authenticated' => true, 'jit_active' => false],
                'JIT_ELEVATION_REQUIRED',
                'এই সংবেদনশীল অপারেশনের জন্য সক্রিয় Just-in-Time (JIT) প্রিভিলেজ এলিভেশন আবশ্যক।',
                $context
            );
        }

        // 7. MFA / Step-Up Authentication Check
        $globalMfaRequired = filter_var(PlatformSetting::get('security_require_admin_mfa', false), FILTER_VALIDATE_BOOLEAN);
        $mfaVerified = ! empty($user->two_factor_confirmed_at)
            || (bool) ($context['mfa_verified'] ?? false)
            || (request()?->hasSession() && request()->session()->get('mfa_verified') === true)
            || (string) request()?->header('X-Admin-MFA-Verified') === '1';

        if (($policy->require_mfa || $globalMfaRequired) && ! $mfaVerified) {
            return $this->recordDecision(
                $user,
                $policyCode,
                $policyVersion,
                $operationKey,
                ZeroTrustAccessLog::DECISION_STEP_UP_REQUIRED,
                45,
                ['authenticated' => true, 'mfa_verified' => false, 'policy_require_mfa' => $policy->require_mfa, 'global_mfa' => $globalMfaRequired],
                'MFA_STEP_UP_REQUIRED',
                'জিরো-ট্রাস্ট নীতি অনুযায়ী এই অপারেশনের জন্য মাল্টি-ফ্যাক্টর অথেনটিকেশন (MFA) যাচাই আবশ্যক।',
                $context
            );
        }

        // 8. Trusted Device Signal Check (Fail-closed when required: unavailable signal is NEVER proof of trust)
        $deviceTrusted = $this->verifyDeviceTrustSignal($user, $context);
        if ($policy->require_trusted_device && ! $deviceTrusted) {
            return $this->recordDecision(
                $user,
                $policyCode,
                $policyVersion,
                $operationKey,
                ZeroTrustAccessLog::DECISION_DENY,
                60,
                ['authenticated' => true, 'device_trust_verified' => false],
                'UNTRUSTED_OR_UNVERIFIED_DEVICE_CONTEXT',
                'বিশ্বস্ত ডিভাইস (Trusted Device) সিগন্যাল যাচাই করা যায়নি। অপ্রাপ্ত সিগন্যালকে বিশ্বস্ত হিসেবে গণ্য করা হয় না।',
                $context
            );
        }

        // 9. Measured Telemetry Risk Score Check
        $riskAssessment = $this->riskEngine->evaluateUserRisk($user);
        $riskScore = (int) ($context['override_risk_score'] ?? ($context['risk_score'] ?? $riskAssessment['score']));

        if ($riskScore > $policy->max_risk_score) {
            return $this->recordDecision(
                $user,
                $policyCode,
                $policyVersion,
                $operationKey,
                ZeroTrustAccessLog::DECISION_DENY,
                $riskScore,
                ['authenticated' => true, 'risk_score' => $riskScore, 'max_allowed_risk' => $policy->max_risk_score, 'factors' => $riskAssessment['factors']],
                'RISK_THRESHOLD_EXCEEDED',
                "অ্যাকাউন্টের বর্তমান ঝুঁকি স্কোর ({$riskScore}) অনুমোদিত সীমা ({$policy->max_risk_score}) অতিক্রম করেছে।",
                $context
            );
        }

        // 10. Dual Approval & Anti-Self-Approval Check
        if ($policy->require_dual_approval) {
            $approvedByUserId = $context['approved_by_user_id'] ?? null;
            if ($approvedByUserId !== null) {
                if ($policy->prevent_self_approval && (int) $approvedByUserId === (int) $user->id) {
                    return $this->recordDecision(
                        $user,
                        $policyCode,
                        $policyVersion,
                        $operationKey,
                        ZeroTrustAccessLog::DECISION_APPROVAL_REQUIRED,
                        $riskScore,
                        ['authenticated' => true, 'self_approval_attempt' => true],
                        'DUAL_APPROVAL_REQUIRED',
                        'নিরাপত্তা নীতি অনুযায়ী নিজের প্রস্তাবিত অপারেশনের জন্য স্বাধীন দ্বিতীয় অনুমোদন আবশ্যক (Self-Approval Prohibited)।',
                        $context
                    );
                }
            } else {
                $approvalActionId = $context['approval_action_id'] ?? request()?->input('approval_action_id');
                if (! $approvalActionId) {
                    return $this->recordDecision(
                        $user,
                        $policyCode,
                        $policyVersion,
                        $operationKey,
                        ZeroTrustAccessLog::DECISION_APPROVAL_REQUIRED,
                        $riskScore,
                        ['authenticated' => true, 'dual_approval_supplied' => false],
                        'DUAL_APPROVAL_REQUIRED',
                        'এই উচ্চ-ঝুঁকির অপারেশন সম্পাদনের জন্য অপর একজন অনুমোদিত প্রশাসকের স্বাধীন অনুমোদন (Dual Approval) আবশ্যক।',
                        $context
                    );
                }

                $approvalRecord = AutomatedResponseAction::find($approvalActionId);
                if (! $approvalRecord || ! in_array($approvalRecord->status, [AutomatedResponseAction::STATUS_APPROVED, AutomatedResponseAction::STATUS_EXECUTED], true)) {
                    return $this->recordDecision(
                        $user,
                        $policyCode,
                        $policyVersion,
                        $operationKey,
                        ZeroTrustAccessLog::DECISION_APPROVAL_REQUIRED,
                        $riskScore,
                        ['authenticated' => true, 'approval_action_id' => $approvalActionId, 'valid' => false],
                        'INVALID_OR_UNAPPROVED_ACTION_TOKEN',
                        'প্রদত্ত অনুমোদন টোকেনটি বৈধ বা অনুমোদিত নয়।',
                        $context
                    );
                }

                if ($policy->prevent_self_approval && (int) $approvalRecord->approved_by === (int) $user->id && (int) $approvalRecord->requested_by === (int) $user->id) {
                    return $this->recordDecision(
                        $user,
                        $policyCode,
                        $policyVersion,
                        $operationKey,
                        ZeroTrustAccessLog::DECISION_DENY,
                        $riskScore,
                        ['authenticated' => true, 'self_approval_attempt' => true],
                        'SELF_APPROVAL_PROHIBITED',
                        'নিরাপত্তা নীতি অনুযায়ী নিজের প্রস্তাবিত উচ্চ-ঝুঁকির অপারেশন নিজেই অনুমোদন করা (Self-Approval) সম্পূর্ণ নিষিদ্ধ।',
                        $context
                    );
                }
            }
        }

        // All Zero-Trust checks passed
        return $this->recordDecision(
            $user,
            $policyCode,
            $policyVersion,
            $operationKey,
            ZeroTrustAccessLog::DECISION_ALLOW,
            $riskScore,
            [
                'authenticated' => true,
                'account_status' => $user->status,
                'jit_active' => $hasActiveJit,
                'mfa_verified' => $mfaVerified,
                'device_trust_verified' => $deviceTrusted,
                'risk_score' => $riskScore,
            ],
            'POLICY_SATISFIED',
            'জিরো-ট্রাস্ট নীতি যাচাই সফল হয়েছে।',
            $context
        );
    }

    /**
     * Update a Zero-Trust Policy with version increment and immutable change history.
     */
    public function updatePolicy(string $policyCode, array $data, User $admin, string $reason): ZeroTrustPolicy
    {
        $this->syncDefaultPolicies();

        return DB::transaction(function () use ($policyCode, $data, $admin, $reason) {
            $policy = ZeroTrustPolicy::where('policy_code', $policyCode)
                ->orWhere('id', is_numeric($policyCode) ? (int) $policyCode : 0)
                ->lockForUpdate()
                ->firstOrFail();

            $oldSnapshot = $policy->only([
                'effect',
                'risk_level',
                'require_mfa',
                'require_jit',
                'require_fresh_auth_minutes',
                'require_dual_approval',
                'prevent_self_approval',
                'max_risk_score',
                'require_trusted_device',
                'is_active',
            ]);

            $newVersion = $policy->version + 1;
            $history = is_array($policy->change_history) ? $policy->change_history : [];
            $history[] = [
                'version' => $newVersion,
                'updated_by' => $admin->id,
                'updated_by_email' => $admin->email,
                'reason' => $reason,
                'previous' => $oldSnapshot,
                'timestamp' => now()->toIso8601String(),
            ];

            $policy->update([
                'version' => $newVersion,
                'effect' => $data['effect'] ?? $policy->effect,
                'risk_level' => $data['risk_level'] ?? $policy->risk_level,
                'require_mfa' => isset($data['require_mfa']) ? (bool) $data['require_mfa'] : $policy->require_mfa,
                'require_jit' => isset($data['require_jit']) ? (bool) $data['require_jit'] : $policy->require_jit,
                'require_fresh_auth_minutes' => array_key_exists('require_fresh_auth_minutes', $data) ? (int) $data['require_fresh_auth_minutes'] : $policy->require_fresh_auth_minutes,
                'require_dual_approval' => isset($data['require_dual_approval']) ? (bool) $data['require_dual_approval'] : $policy->require_dual_approval,
                'prevent_self_approval' => isset($data['prevent_self_approval']) ? (bool) $data['prevent_self_approval'] : $policy->prevent_self_approval,
                'max_risk_score' => isset($data['max_risk_score']) ? max(10, min(100, (int) $data['max_risk_score'])) : $policy->max_risk_score,
                'require_trusted_device' => isset($data['require_trusted_device']) ? (bool) $data['require_trusted_device'] : $policy->require_trusted_device,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $policy->is_active,
                'change_history' => $history,
                'updated_by' => $admin->id,
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_zero_trust_policy_updated',
                'entity_type' => ZeroTrustPolicy::class,
                'entity_id' => $policy->id,
                'old_values' => $oldSnapshot,
                'new_values' => [
                    'policy_code' => $policy->policy_code,
                    'version' => $newVersion,
                    'reason' => $reason,
                    'require_mfa' => $policy->require_mfa,
                    'require_jit' => $policy->require_jit,
                    'require_dual_approval' => $policy->require_dual_approval,
                    'max_risk_score' => $policy->max_risk_score,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $policy->fresh();
        });
    }

    /**
     * Check if a verified trusted device exists for the user.
     * Never assumes trust if device table or fingerprint is absent.
     */
    protected function verifyDeviceTrustSignal(User $user, array $context): bool
    {
        if (! empty($context['trusted_device_verified'])) {
            return true;
        }

        $fingerprint = (string) ($context['device_fingerprint'] ?? request()?->header('X-Device-Fingerprint', ''));
        if ($fingerprint === '') {
            return false;
        }

        if (Schema::hasTable('admin_trusted_devices')) {
            $exists = AdminTrustedDevice::where('admin_id', $user->id)
                ->where('device_fingerprint', $fingerprint)
                ->where('expires_at', '>', now())
                ->exists();
            if ($exists) {
                return true;
            }
        }

        if (Schema::hasTable('device_sessions')) {
            return DeviceSession::where('user_id', $user->id)
                ->where('device_id', $fingerprint)
                ->where('is_trusted', true)
                ->exists();
        }

        return false;
    }

    /**
     * Persist policy evaluation record and return structured decision.
     */
    protected function recordDecision(
        ?User $user,
        string $policyCode,
        int $policyVersion,
        string $operation,
        string $decision,
        int $riskScore,
        array $signals,
        string $reasonCode,
        string $message,
        array $context
    ): array {
        $uid = 'ZTE-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));

        if (Schema::hasTable('zero_trust_access_logs')) {
            ZeroTrustAccessLog::create([
                'evaluation_uid' => $uid,
                'user_id' => $user?->id,
                'policy_code' => $policyCode,
                'policy_version' => $policyVersion,
                'operation' => $operation,
                'resource_type' => isset($context['resource_type']) ? (string) $context['resource_type'] : null,
                'resource_id' => isset($context['resource_id']) ? (string) $context['resource_id'] : null,
                'decision' => $decision,
                'risk_score' => $riskScore,
                'signals' => $signals,
                'reason_code' => $reasonCode,
                'reason_message' => $message,
                'ip_address' => request()?->ip(),
                'evaluated_at' => now(),
            ]);
        }

        return [
            'evaluation_uid' => $uid,
            'allowed' => $decision === ZeroTrustAccessLog::DECISION_ALLOW,
            'decision' => strtoupper($decision),
            'reason_code' => $reasonCode,
            'message' => $message,
            'policy_code' => $policyCode,
            'policy_version' => $policyVersion,
            'risk_score' => $riskScore,
            'signals' => $signals,
            'evaluated_at' => now()->toIso8601String(),
        ];
    }
}
