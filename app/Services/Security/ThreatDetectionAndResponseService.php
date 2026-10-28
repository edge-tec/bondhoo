<?php

namespace App\Services\Security;

use App\Models\AdminAlert;
use App\Models\AuditIntegrityRun;
use App\Models\AuditLog;
use App\Models\AutomatedResponseAction;
use App\Models\Backup;
use App\Models\ConfigDriftBaseline;
use App\Models\PlatformSetting;
use App\Models\PrivacyRequest;
use App\Models\ThreatDetection;
use App\Models\ThreatDetectionRule;
use App\Models\User;
use App\Models\UserRestriction;
use App\Models\ZeroTrustAccessLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class ThreatDetectionAndResponseService
{
    /**
     * High-impact response actions that ALWAYS require independent human approval and cannot self-approve.
     */
    public const HIGH_IMPACT_ACTIONS = [
        'disable_admin_account',
        'suspend_account',
        'modify_security_policy',
        'super_admin_role_change',
    ];

    /**
     * Prohibited autonomous actions that must NEVER be executed by automated rules.
     */
    public const PROHIBITED_AUTONOMOUS_ACTIONS = [
        'grant_privilege',
        'erase_evidence',
        'permanent_ban_without_policy',
    ];

    /**
     * Default Versioned Threat Detection Rules backed by real database telemetry.
     */
    public const DEFAULT_RULES = [
        [
            'rule_code' => 'TDR-AUTH-BRUTEFORCE',
            'name' => 'ক্রমাগত ব্যর্থ লগইন ও ক্রেডেনশিয়াল স্টাফিং সনাক্তকরণ',
            'data_source' => 'failed_login_attempts',
            'event_pattern' => 'failed_login_burst_by_ip',
            'threshold_count' => 5,
            'window_minutes' => 15,
            'severity' => 'high',
            'suppression_minutes' => 30,
            'recommended_response' => 'create_incident',
            'requires_human_approval' => false,
        ],
        [
            'rule_code' => 'TDR-AUTHZ-DENIAL-BURST',
            'name' => 'পুনঃপুন জিরো-ট্রাস্ট অথরাইজেশন প্রত্যাখ্যান (Privilege Probing)',
            'data_source' => 'zero_trust_access_logs',
            'event_pattern' => 'repeated_policy_denials',
            'threshold_count' => 3,
            'window_minutes' => 15,
            'severity' => 'high',
            'suppression_minutes' => 30,
            'recommended_response' => 'require_step_up',
            'requires_human_approval' => false,
        ],
        [
            'rule_code' => 'TDR-EXPORT-ANOMALY',
            'name' => 'অস্বাভাবিক ডাটা বা অডিট এক্সপোর্ট ফ্রিকোয়েন্সি',
            'data_source' => 'audit_logs',
            'event_pattern' => 'repeated_data_exports',
            'threshold_count' => 3,
            'window_minutes' => 30,
            'severity' => 'high',
            'suppression_minutes' => 45,
            'recommended_response' => 'hold_export',
            'requires_human_approval' => false,
        ],
        [
            'rule_code' => 'TDR-AUDIT-TAMPER',
            'name' => 'ক্রিপ্টোগ্রাফিক অডিট চেইন অখণ্ডতা ব্যর্থতা',
            'data_source' => 'audit_integrity_runs',
            'event_pattern' => 'audit_chain_verification_failed',
            'threshold_count' => 1,
            'window_minutes' => 60,
            'severity' => 'critical',
            'suppression_minutes' => 15,
            'recommended_response' => 'create_incident',
            'requires_human_approval' => false,
        ],
        [
            'rule_code' => 'TDR-BACKUP-FAILURE',
            'name' => 'ডিজাস্টার রিকভারি ব্যাকআপ বা রিস্টোর ড্রিল ব্যর্থতা',
            'data_source' => 'backups',
            'event_pattern' => 'backup_or_verification_failed',
            'threshold_count' => 1,
            'window_minutes' => 120,
            'severity' => 'critical',
            'suppression_minutes' => 30,
            'recommended_response' => 'create_incident',
            'requires_human_approval' => false,
        ],
        [
            'rule_code' => 'TDR-PRIV-ESCALATION',
            'name' => 'সন্দেহজনক প্রিভিলেজড অ্যাকাউন্ট কার্যক্রম ও রোল পরিবর্তন',
            'data_source' => 'audit_logs',
            'event_pattern' => 'rapid_role_or_elevation_mutations',
            'threshold_count' => 3,
            'window_minutes' => 20,
            'severity' => 'critical',
            'suppression_minutes' => 30,
            'recommended_response' => 'disable_admin_account',
            'requires_human_approval' => true,
        ],
        [
            'rule_code' => 'TDR-CONFIG-DRIFT',
            'name' => 'অনুমোদিত নিরাপত্তা বেসলাইন থেকে কনফিগারেশন বিচ্যুতি (Config Drift)',
            'data_source' => 'config_drift_baselines',
            'event_pattern' => 'unapproved_configuration_drift',
            'threshold_count' => 1,
            'window_minutes' => 60,
            'severity' => 'high',
            'suppression_minutes' => 60,
            'recommended_response' => 'modify_security_policy',
            'requires_human_approval' => true,
        ],
    ];

    public function __construct(
        protected SecurityOperationsCenterService $socService
    ) {}

    /**
     * Ensure default Threat Detection Rules exist.
     */
    public function syncDefaultRules(): void
    {
        if (! Schema::hasTable('threat_detection_rules')) {
            return;
        }

        foreach (self::DEFAULT_RULES as $rule) {
            ThreatDetectionRule::firstOrCreate(
                ['rule_code' => $rule['rule_code']],
                array_merge($rule, [
                    'rule_id' => $rule['rule_code'],
                    'version' => 1,
                    'is_enabled' => true,
                    'version_history' => [[
                        'version' => 1,
                        'action' => 'seeded_default',
                        'timestamp' => now()->toIso8601String(),
                    ]],
                ])
            );
        }
    }

    /**
     * Evaluate all enabled threat detection rules against real application telemetry.
     */
    public function evaluateDetectionRules(?User $actor = null): array
    {
        $this->syncDefaultRules();

        $rules = ThreatDetectionRule::where('is_enabled', true)->orderBy('id')->get();
        $newDetections = [];
        $suppressedCount = 0;

        foreach ($rules as $rule) {
            $observation = $this->measureTelemetryForRule($rule);
            if ($observation['observed_count'] < $rule->threshold_count) {
                continue;
            }

            $dedupKey = sha1($rule->rule_code.'|'.($observation['target_user_id'] ?? 'global').'|'.($observation['ip_address'] ?? 'any'));

            // Suppression window check: bounded to max 60 minutes for critical rules so critical events are never hidden indefinitely
            $suppressionWindow = $rule->severity === 'critical'
                ? min(60, max(1, $rule->suppression_minutes))
                : max(1, $rule->suppression_minutes);

            $existingRecent = ThreatDetection::where('dedup_key', $dedupKey)
                ->where('evaluated_at', '>=', now()->subMinutes($suppressionWindow))
                ->first();

            if ($existingRecent) {
                $newCount = max($existingRecent->observed_count + 1, $observation['observed_count']);
                $existingRecent->update([
                    'observed_count' => $newCount,
                    'occurrence_count' => $newCount,
                    'evaluated_at' => now(),
                ]);
                $suppressedCount++;

                continue;
            }

            $detectionUid = 'THD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
            $correlationId = 'COR-'.now()->format('Ymd').'-'.strtoupper(substr($dedupKey, 0, 10));

            $incident = $this->socService->logIncident(
                eventType: 'threat_detection:'.strtolower($rule->rule_code),
                severity: $rule->severity,
                userId: $observation['target_user_id'],
                ip: $observation['ip_address'],
                details: [
                    'rule_code' => $rule->rule_code,
                    'rule_version' => $rule->version,
                    'observed_count' => $observation['observed_count'],
                    'threshold_count' => $rule->threshold_count,
                    'correlation_id' => $correlationId,
                ],
                evidenceRefs: $observation['evidence_refs']
            );

            $detection = ThreatDetection::create([
                'detection_uid' => $detectionUid,
                'correlation_id' => $correlationId,
                'dedup_key' => $dedupKey,
                'rule_code' => $rule->rule_code,
                'rule_version' => $rule->version,
                'data_source' => $rule->data_source,
                'severity' => $rule->severity,
                'target_user_id' => $observation['target_user_id'],
                'ip_address' => $observation['ip_address'],
                'observed_count' => $observation['observed_count'],
                'threshold_count' => $rule->threshold_count,
                'window_minutes' => $rule->window_minutes,
                'confidence_level' => 'measured_exact',
                'evidence_refs' => $observation['evidence_refs'],
                'limitations' => 'Evaluated from internal application database telemetry; external GeoIP/threat-intel feeds are not configured.',
                'status' => ThreatDetection::STATUS_OPEN,
                'security_event_id' => $incident->id,
                'evaluated_at' => now(),
            ]);

            $rule->increment('true_positive_count');

            // Trigger policy-driven automated response or queue for human approval
            $this->triggerAutomatedResponseForDetection($detection, $rule, $actor);

            $newDetections[] = $detection;
        }

        return [
            'evaluated_rules_count' => $rules->count(),
            'new_detections_count' => count($newDetections),
            'suppressed_duplicates_count' => $suppressedCount,
            'new_detections' => $newDetections,
            'evaluated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Measure actual database telemetry for a given detection rule.
     *
     * @return array{observed_count: int, target_user_id: ?int, ip_address: ?string, evidence_refs: array<int, string>}
     */
    protected function measureTelemetryForRule(ThreatDetectionRule $rule): array
    {
        $since = now()->subMinutes(max(1, $rule->window_minutes));

        switch ($rule->rule_code) {
            case 'TDR-AUTH-BRUTEFORCE':
                $topIp = null;
                if (Schema::hasTable('security_events')) {
                    $topIp = DB::table('security_events')
                        ->select('ip_address', DB::raw('COUNT(*) as cnt'), DB::raw('MAX(user_id) as target_uid'))
                        ->where('event_type', 'failed_login')
                        ->where('created_at', '>=', $since)
                        ->groupBy('ip_address')
                        ->orderByDesc('cnt')
                        ->first();
                }
                if (! $topIp && Schema::hasTable('failed_login_attempts')) {
                    $topIp = DB::table('failed_login_attempts')
                        ->select('ip_address', DB::raw('COUNT(*) as cnt'), DB::raw('MAX(user_id) as target_uid'))
                        ->where('attempted_at', '>=', $since)
                        ->groupBy('ip_address')
                        ->orderByDesc('cnt')
                        ->first();
                }

                if (! $topIp) {
                    return ['observed_count' => 0, 'target_user_id' => null, 'ip_address' => null, 'evidence_refs' => []];
                }

                return [
                    'observed_count' => (int) $topIp->cnt,
                    'target_user_id' => $topIp->target_uid ? (int) $topIp->target_uid : null,
                    'ip_address' => (string) $topIp->ip_address,
                    'evidence_refs' => ["failed_login_attempts:ip={$topIp->ip_address}:count={$topIp->cnt}"],
                ];

            case 'TDR-AUTHZ-DENIAL-BURST':
                if (! Schema::hasTable('zero_trust_access_logs')) {
                    return ['observed_count' => 0, 'target_user_id' => null, 'ip_address' => null, 'evidence_refs' => []];
                }
                $topDenial = DB::table('zero_trust_access_logs')
                    ->select('user_id', DB::raw('COUNT(*) as cnt'), DB::raw('MAX(ip_address) as ip'))
                    ->where('decision', '!=', ZeroTrustAccessLog::DECISION_ALLOW)
                    ->where('evaluated_at', '>=', $since)
                    ->whereNotNull('user_id')
                    ->groupBy('user_id')
                    ->orderByDesc('cnt')
                    ->first();

                if (! $topDenial) {
                    return ['observed_count' => 0, 'target_user_id' => null, 'ip_address' => null, 'evidence_refs' => []];
                }

                return [
                    'observed_count' => (int) $topDenial->cnt,
                    'target_user_id' => (int) $topDenial->user_id,
                    'ip_address' => $topDenial->ip ? (string) $topDenial->ip : null,
                    'evidence_refs' => ["zero_trust_access_logs:user_id={$topDenial->user_id}:denials={$topDenial->cnt}"],
                ];

            case 'TDR-EXPORT-ANOMALY':
                $topExport = DB::table('audit_logs')
                    ->select('user_id', DB::raw('COUNT(*) as cnt'), DB::raw('MAX(ip_address) as ip'))
                    ->where('created_at', '>=', $since)
                    ->where(function ($q) {
                        $q->where('action', 'like', '%export%')
                            ->orWhere('action', 'like', '%download%');
                    })
                    ->whereNotNull('user_id')
                    ->groupBy('user_id')
                    ->orderByDesc('cnt')
                    ->first();

                if (! $topExport) {
                    return ['observed_count' => 0, 'target_user_id' => null, 'ip_address' => null, 'evidence_refs' => []];
                }

                return [
                    'observed_count' => (int) $topExport->cnt,
                    'target_user_id' => (int) $topExport->user_id,
                    'ip_address' => $topExport->ip ? (string) $topExport->ip : null,
                    'evidence_refs' => ["audit_logs:export_burst:user_id={$topExport->user_id}:count={$topExport->cnt}"],
                ];

            case 'TDR-AUDIT-TAMPER':
                if (! Schema::hasTable('audit_integrity_runs')) {
                    return ['observed_count' => 0, 'target_user_id' => null, 'ip_address' => null, 'evidence_refs' => []];
                }
                $failedRuns = AuditIntegrityRun::where('verified', false)
                    ->where('executed_at', '>=', $since)
                    ->get();

                return [
                    'observed_count' => $failedRuns->count(),
                    'target_user_id' => null,
                    'ip_address' => null,
                    'evidence_refs' => $failedRuns->map(fn ($r) => "audit_integrity_run:{$r->run_uid}:{$r->error_code}")->values()->all(),
                ];

            case 'TDR-BACKUP-FAILURE':
                if (! Schema::hasTable('backups')) {
                    return ['observed_count' => 0, 'target_user_id' => null, 'ip_address' => null, 'evidence_refs' => []];
                }
                $failedBackups = Backup::where(function ($q) {
                    $q->where('status', Backup::STATUS_FAILED)
                        ->orWhere('verification_status', Backup::VERIFICATION_CORRUPTED);
                })
                    ->where('updated_at', '>=', $since)
                    ->get();

                return [
                    'observed_count' => $failedBackups->count(),
                    'target_user_id' => null,
                    'ip_address' => null,
                    'evidence_refs' => $failedBackups->map(fn ($b) => 'backup:'.($b->backup_uid ?: $b->id))->values()->all(),
                ];

            case 'TDR-PRIV-ESCALATION':
            case 'TDR-PRIV-ABUSE-VELOCITY':
                $topRoleActor = DB::table('audit_logs')
                    ->select('user_id', DB::raw('COUNT(*) as cnt'), DB::raw('MAX(ip_address) as ip'))
                    ->where('created_at', '>=', $since)
                    ->where(function ($q) {
                        $q->where('action', 'like', '%role%')
                            ->orWhere('action', 'like', '%elevation%')
                            ->orWhere('action', 'like', '%suspend%')
                            ->orWhere('action', 'like', '%delete%');
                    })
                    ->whereNotNull('user_id')
                    ->groupBy('user_id')
                    ->orderByDesc('cnt')
                    ->first();

                if (! $topRoleActor) {
                    return ['observed_count' => 0, 'target_user_id' => null, 'ip_address' => null, 'evidence_refs' => []];
                }

                return [
                    'observed_count' => (int) $topRoleActor->cnt,
                    'target_user_id' => (int) $topRoleActor->user_id,
                    'ip_address' => $topRoleActor->ip ? (string) $topRoleActor->ip : null,
                    'evidence_refs' => ["audit_logs:privilege_velocity:user_id={$topRoleActor->user_id}:count={$topRoleActor->cnt}"],
                ];

            case 'TDR-CONFIG-DRIFT':
                if (! Schema::hasTable('config_drift_baselines')) {
                    return ['observed_count' => 0, 'target_user_id' => null, 'ip_address' => null, 'evidence_refs' => []];
                }
                $drifted = ConfigDriftBaseline::where('status', ConfigDriftBaseline::STATUS_DRIFT_DETECTED)
                    ->where('updated_at', '>=', $since)
                    ->get();

                return [
                    'observed_count' => $drifted->count(),
                    'target_user_id' => null,
                    'ip_address' => null,
                    'evidence_refs' => $drifted->map(fn ($b) => "config_drift:{$b->baseline_uid}:drifts={$b->drift_count}")->values()->all(),
                ];

            default:
                return ['observed_count' => 0, 'target_user_id' => null, 'ip_address' => null, 'evidence_refs' => []];
        }
    }

    /**
     * Trigger or queue an idempotent Automated Response Action for a detection.
     */
    public function triggerAutomatedResponseForDetection(
        ThreatDetection $detection,
        ThreatDetectionRule $rule,
        ?User $actor = null
    ): AutomatedResponseAction {
        $actionType = $rule->recommended_response;

        if (in_array($actionType, self::PROHIBITED_AUTONOMOUS_ACTIONS, true)) {
            throw new InvalidArgumentException("Autonomous execution of '{$actionType}' is strictly prohibited by security policy.");
        }

        $requiresApproval = $rule->requires_human_approval
            || in_array($actionType, self::HIGH_IMPACT_ACTIONS, true);

        $idempotencyKey = 'AUTO-'.$rule->rule_code.'-'.$detection->dedup_key.'-'.now()->format('YmdH');

        $existing = AutomatedResponseAction::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $actionUid = 'ARA-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
        $action = AutomatedResponseAction::create([
            'action_uid' => $actionUid,
            'idempotency_key' => $idempotencyKey,
            'detection_id' => $detection->id,
            'security_event_id' => $detection->security_event_id,
            'action_type' => $actionType,
            'risk_level' => $rule->severity,
            'target_type' => $detection->target_user_id ? User::class : 'system',
            'target_id' => $detection->target_user_id,
            'payload' => [
                'rule_code' => $rule->rule_code,
                'detection_uid' => $detection->detection_uid,
                'ip_address' => $detection->ip_address,
                'observed_count' => $detection->observed_count,
            ],
            'requires_approval' => $requiresApproval,
            'status' => $requiresApproval
                ? AutomatedResponseAction::STATUS_PENDING_APPROVAL
                : AutomatedResponseAction::STATUS_APPROVED,
            'requested_by' => $actor?->id,
            'justification' => "Automated detection rule {$rule->rule_code} (v{$rule->version}) triggered ({$detection->observed_count}/{$rule->threshold_count}).",
        ]);

        if (! $requiresApproval) {
            return $this->executeActionInternal($action, null);
        }

        AdminAlert::trigger(
            type: 'security',
            title: "⏳ মানব অনুমোদন প্রয়োজন: {$actionType} ({$actionUid})",
            message: "হাই-ইমপ্যাক্ট সিকিউরিটি রেসপন্স ({$actionType}) কার্যকর করার জন্য অনুমোদিত প্রশাসকের স্বাধীন অনুমোদন প্রয়োজন।",
            severity: AdminAlert::SEVERITY_CRITICAL,
            data: ['action_id' => $action->id, 'action_uid' => $actionUid, 'rule_code' => $rule->rule_code]
        );

        return $action;
    }

    /**
     * Request a manual or policy-gated high-impact response action with idempotency.
     */
    public function requestResponseAction(
        string $actionType,
        string $targetType,
        ?int $targetId,
        array $payload,
        User $requester,
        string $justification,
        ?string $customIdempotencyKey = null
    ): AutomatedResponseAction {
        if (in_array($actionType, self::PROHIBITED_AUTONOMOUS_ACTIONS, true)) {
            throw new InvalidArgumentException("Action '{$actionType}' is prohibited by security policy.");
        }

        $idempotencyKey = $customIdempotencyKey ?: sha1($actionType.'|'.$targetType.'|'.($targetId ?? 0).'|'.json_encode($payload));
        $existing = AutomatedResponseAction::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $requiresApproval = in_array($actionType, self::HIGH_IMPACT_ACTIONS, true)
            || (bool) ($payload['requires_approval'] ?? true);

        $actionUid = 'ARA-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));

        $action = AutomatedResponseAction::create([
            'action_uid' => $actionUid,
            'idempotency_key' => $idempotencyKey,
            'action_type' => $actionType,
            'risk_level' => in_array($actionType, self::HIGH_IMPACT_ACTIONS, true) ? 'critical' : 'high',
            'target_type' => $targetType,
            'target_id' => $targetId,
            'payload' => $payload,
            'requires_approval' => $requiresApproval,
            'status' => $requiresApproval
                ? AutomatedResponseAction::STATUS_PENDING_APPROVAL
                : AutomatedResponseAction::STATUS_APPROVED,
            'requested_by' => $requester->id,
            'justification' => $justification,
        ]);

        AuditLog::create([
            'user_id' => $requester->id,
            'action' => 'admin_response_action_requested',
            'entity_type' => AutomatedResponseAction::class,
            'entity_id' => $action->id,
            'new_values' => [
                'action_uid' => $actionUid,
                'action_type' => $actionType,
                'target_id' => $targetId,
                'requires_approval' => $requiresApproval,
                'justification' => $justification,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        if (! $requiresApproval) {
            return $this->executeActionInternal($action, $requester);
        }

        return $action;
    }

    /**
     * Approve and execute a pending AutomatedResponseAction, strictly preventing self-approval.
     */
    public function approveAndExecuteAction(int $actionId, User $approver, string $approvalNotes): AutomatedResponseAction
    {
        $action = DB::transaction(function () use ($actionId, $approver, $approvalNotes) {
            $locked = AutomatedResponseAction::where('id', $actionId)->lockForUpdate()->firstOrFail();

            if ($locked->status === AutomatedResponseAction::STATUS_EXECUTED) {
                return $locked; // Idempotent return if already executed
            }

            if ($locked->status !== AutomatedResponseAction::STATUS_PENDING_APPROVAL) {
                throw new InvalidArgumentException("Action {$locked->action_uid} cannot be approved from status '{$locked->status}'.");
            }

            if ($locked->requested_by !== null && (int) $locked->requested_by === (int) $approver->id) {
                throw new InvalidArgumentException('নিরাপত্তা নীতি লঙ্ঘন: নিজের প্রস্তাবিত উচ্চ-ঝুঁকির অ্যাকশন নিজেই অনুমোদন করা (Self-Approval) সম্পূর্ণ নিষিদ্ধ।');
            }

            $locked->update([
                'status' => AutomatedResponseAction::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approval_notes' => $approvalNotes,
            ]);

            return $locked;
        });

        if ($action->status === AutomatedResponseAction::STATUS_EXECUTED) {
            return $action;
        }

        return $this->executeActionInternal($action, $approver);
    }

    /**
     * Reject a pending AutomatedResponseAction.
     */
    public function rejectAction(int $actionId, User $rejecter, string $reason): AutomatedResponseAction
    {
        return DB::transaction(function () use ($actionId, $rejecter, $reason) {
            $locked = AutomatedResponseAction::where('id', $actionId)->lockForUpdate()->firstOrFail();

            if ($locked->status !== AutomatedResponseAction::STATUS_PENDING_APPROVAL) {
                throw new InvalidArgumentException("Only pending actions can be rejected (current: {$locked->status}).");
            }

            $locked->update([
                'status' => AutomatedResponseAction::STATUS_REJECTED,
                'rejected_by' => $rejecter->id,
                'approval_notes' => $reason,
            ]);

            AuditLog::create([
                'user_id' => $rejecter->id,
                'action' => 'admin_response_action_rejected',
                'entity_type' => AutomatedResponseAction::class,
                'entity_id' => $locked->id,
                'new_values' => [
                    'action_uid' => $locked->action_uid,
                    'action_type' => $locked->action_type,
                    'reason' => $reason,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Rollback / compensate a previously executed AutomatedResponseAction safely.
     */
    public function rollbackAction(int $actionId, User $admin, string $reason): AutomatedResponseAction
    {
        return DB::transaction(function () use ($actionId, $admin, $reason) {
            $locked = AutomatedResponseAction::where('id', $actionId)->lockForUpdate()->firstOrFail();

            if ($locked->status === AutomatedResponseAction::STATUS_ROLLED_BACK) {
                return $locked;
            }

            if ($locked->status !== AutomatedResponseAction::STATUS_EXECUTED) {
                throw new InvalidArgumentException("Only executed actions can be rolled back (current: {$locked->status}).");
            }

            $rollbackData = is_array($locked->rollback_payload) ? $locked->rollback_payload : [];

            if (in_array($locked->action_type, ['disable_admin_account', 'suspend_account'], true) && $locked->target_id) {
                $targetUser = User::where('id', $locked->target_id)->lockForUpdate()->first();
                if ($targetUser) {
                    $previousStatus = (string) ($rollbackData['previous_status'] ?? 'active');
                    $targetUser->update(['status' => $previousStatus]);
                }
            } elseif ($locked->action_type === 'restrict_session' && ! empty($rollbackData['restriction_id'])) {
                UserRestriction::where('id', (int) $rollbackData['restriction_id'])->update(['is_active' => false]);
            } elseif ($locked->action_type === 'hold_export' && ! empty($rollbackData['privacy_request_id'])) {
                PrivacyRequest::where('id', (int) $rollbackData['privacy_request_id'])
                    ->update(['status' => (string) ($rollbackData['previous_status'] ?? PrivacyRequest::STATUS_PENDING)]);
            } elseif ($locked->action_type === 'modify_security_policy' && ! empty($rollbackData['setting_key'])) {
                PlatformSetting::set(
                    (string) $rollbackData['setting_key'],
                    (string) ($rollbackData['previous_value'] ?? 'false'),
                    $admin->id
                );
            }

            $locked->update([
                'status' => AutomatedResponseAction::STATUS_ROLLED_BACK,
                'rolled_back_at' => now(),
                'approval_notes' => trim(($locked->approval_notes ? $locked->approval_notes.' | ' : '')."Rolled back: {$reason}"),
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_response_action_rolled_back',
                'entity_type' => AutomatedResponseAction::class,
                'entity_id' => $locked->id,
                'new_values' => [
                    'action_uid' => $locked->action_uid,
                    'action_type' => $locked->action_type,
                    'reason' => $reason,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Public wrapper to execute a response action, returning the updated action record even on failure.
     */
    public function executeResponseAction(AutomatedResponseAction $action, ?User $executor): AutomatedResponseAction
    {
        try {
            return $this->executeActionInternal($action, $executor);
        } catch (Throwable $e) {
            $reason = str_contains($e->getMessage(), 'সুপার অ্যাডমিন') || str_contains($e->getMessage(), 'super admin')
                ? 'CANNOT_DISABLE_LAST_SUPER_ADMIN'
                : Str::limit($e->getMessage(), 400);

            $action->update([
                'status' => AutomatedResponseAction::STATUS_FAILED,
                'retry_count' => ($action->retry_count ?? 0) + 1,
                'error_message' => $reason,
            ]);

            return $action->fresh();
        }
    }

    /**
     * Execute an approved AutomatedResponseAction idempotently and record rollback compensation metadata.
     */
    protected function executeActionInternal(AutomatedResponseAction $action, ?User $executor): AutomatedResponseAction
    {
        return DB::transaction(function () use ($action, $executor) {
            $locked = AutomatedResponseAction::where('id', $action->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === AutomatedResponseAction::STATUS_EXECUTED) {
                return $locked;
            }

            $rollbackPayload = [];

            try {
                switch ($locked->action_type) {
                    case 'create_incident':
                        $rollbackPayload = ['incident_id' => $locked->security_event_id];
                        break;

                    case 'require_step_up':
                        if ($locked->target_id) {
                            $u = User::where('id', $locked->target_id)->lockForUpdate()->first();
                            if ($u) {
                                $rollbackPayload = ['user_id' => $u->id, 'previous_risk_score' => $u->risk_score];
                                $u->updateQuietly(['risk_score' => max((int) $u->risk_score, 55)]);
                            }
                        }
                        break;

                    case 'restrict_session':
                        if ($locked->target_id) {
                            $targetUser = User::where('id', $locked->target_id)->lockForUpdate()->firstOrFail();
                            $this->socService->assertSuperAdminProtection($targetUser, 'restrict');
                            $res = UserRestriction::create([
                                'user_id' => $targetUser->id,
                                'restriction_type' => UserRestriction::TYPE_WARNING_ONLY,
                                'reason' => 'Automated temporary session restriction triggered by '.$locked->action_uid,
                                'risk_score' => $targetUser->risk_score ?? 50,
                                'expires_at' => now()->addHours(2),
                                'created_by' => $executor?->id,
                                'is_active' => true,
                            ]);
                            $rollbackPayload = ['restriction_id' => $res->id];
                        }
                        break;

                    case 'hold_export':
                        $pendingExport = PrivacyRequest::where('request_type', PrivacyRequest::TYPE_EXPORT)
                            ->where('status', PrivacyRequest::STATUS_PENDING)
                            ->when($locked->target_id, fn ($q) => $q->where('user_id', $locked->target_id))
                            ->latest('id')
                            ->first();
                        if ($pendingExport) {
                            $rollbackPayload = [
                                'privacy_request_id' => $pendingExport->id,
                                'previous_status' => $pendingExport->status,
                            ];
                            $pendingExport->update([
                                'notes' => 'Placed on security review hold by '.$locked->action_uid,
                            ]);
                        }
                        break;

                    case 'revoke_tokens':
                        if ($locked->target_id) {
                            $targetUser = User::where('id', $locked->target_id)->lockForUpdate()->firstOrFail();
                            $revoked = $targetUser->tokens()->delete();
                            $rollbackPayload = ['tokens_revoked' => $revoked];
                        }
                        break;

                    case 'disable_admin_account':
                    case 'suspend_account':
                        if ($locked->target_id) {
                            $targetUser = User::where('id', $locked->target_id)->lockForUpdate()->firstOrFail();
                            try {
                                $this->socService->assertSuperAdminProtection($targetUser, 'suspend');
                                $rollbackPayload = ['previous_status' => $targetUser->status];
                                $targetUser->update(['status' => 'suspended']);
                                $targetUser->tokens()->delete();
                            } catch (Throwable $e) {
                                $reason = str_contains($e->getMessage(), 'সুপার অ্যাডমিন') || str_contains($e->getMessage(), 'super admin')
                                    ? 'CANNOT_DISABLE_LAST_SUPER_ADMIN'
                                    : Str::limit($e->getMessage(), 400);

                                $locked->update([
                                    'status' => AutomatedResponseAction::STATUS_FAILED,
                                    'retry_count' => $locked->retry_count + 1,
                                    'error_message' => $reason,
                                ]);

                                return $locked->fresh();
                            }
                        }
                        break;

                    case 'modify_security_policy':
                        $settingKey = (string) ($locked->payload['setting_key'] ?? 'security_require_admin_mfa');
                        $newValue = (string) ($locked->payload['new_value'] ?? 'true');
                        $previousVal = (string) PlatformSetting::get($settingKey, 'false');
                        $rollbackPayload = ['setting_key' => $settingKey, 'previous_value' => $previousVal];
                        PlatformSetting::set($settingKey, $newValue, $executor?->id);
                        break;
                }

                $locked->update([
                    'status' => AutomatedResponseAction::STATUS_EXECUTED,
                    'rollback_payload' => $rollbackPayload,
                    'executed_at' => now(),
                ]);

                if ($locked->detection_id && ! in_array($locked->action_type, ['create_incident', 'require_step_up'], true)) {
                    ThreatDetection::where('id', $locked->detection_id)
                        ->update(['status' => ThreatDetection::STATUS_MITIGATED]);
                }

                AuditLog::create([
                    'user_id' => $executor?->id,
                    'action' => 'admin_response_action_executed',
                    'entity_type' => AutomatedResponseAction::class,
                    'entity_id' => $locked->id,
                    'new_values' => [
                        'action_uid' => $locked->action_uid,
                        'action_type' => $locked->action_type,
                        'target_id' => $locked->target_id,
                        'rollback_supported' => ! empty($rollbackPayload),
                    ],
                    'ip_address' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                ]);
            } catch (Throwable $e) {
                $reason = str_contains($e->getMessage(), 'সুপার অ্যাডমিন') || str_contains($e->getMessage(), 'super admin')
                    ? 'CANNOT_DISABLE_LAST_SUPER_ADMIN'
                    : Str::limit($e->getMessage(), 400);

                $locked->update([
                    'status' => AutomatedResponseAction::STATUS_FAILED,
                    'retry_count' => $locked->retry_count + 1,
                    'error_message' => $reason,
                ]);

                throw $e;
            }

            return $locked->fresh();
        });
    }

    /**
     * Mark a detection as a False Positive and increment the rule's false_positive_count.
     */
    public function markDetectionFalsePositive(int $detectionId, User $admin, string $reason): ThreatDetection
    {
        return DB::transaction(function () use ($detectionId, $admin, $reason) {
            $detection = ThreatDetection::where('id', $detectionId)->lockForUpdate()->firstOrFail();
            $detection->update([
                'status' => ThreatDetection::STATUS_FALSE_POSITIVE,
            ]);

            ThreatDetectionRule::where('rule_code', $detection->rule_code)
                ->increment('false_positive_count');

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_threat_detection_false_positive',
                'entity_type' => ThreatDetection::class,
                'entity_id' => $detection->id,
                'new_values' => [
                    'detection_uid' => $detection->detection_uid,
                    'rule_code' => $detection->rule_code,
                    'reason' => $reason,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $detection->fresh();
        });
    }

    /**
     * Tune a Threat Detection Rule with version tracking and audit trail.
     */
    public function updateRule(string $ruleCode, array $data, User $admin, string $reason): ThreatDetectionRule
    {
        $this->syncDefaultRules();

        return DB::transaction(function () use ($ruleCode, $data, $admin, $reason) {
            $rule = ThreatDetectionRule::where('rule_code', $ruleCode)->lockForUpdate()->firstOrFail();

            $old = $rule->only(['threshold_count', 'window_minutes', 'severity', 'suppression_minutes', 'is_enabled', 'requires_human_approval']);
            $newVersion = $rule->version + 1;

            $history = is_array($rule->version_history) ? $rule->version_history : [];
            $history[] = [
                'version' => $newVersion,
                'updated_by' => $admin->id,
                'reason' => $reason,
                'previous' => $old,
                'timestamp' => now()->toIso8601String(),
            ];

            $rule->update([
                'version' => $newVersion,
                'threshold_count' => isset($data['threshold_count']) ? max(1, (int) $data['threshold_count']) : $rule->threshold_count,
                'window_minutes' => isset($data['window_minutes']) ? max(1, min(1440, (int) $data['window_minutes'])) : $rule->window_minutes,
                'severity' => $data['severity'] ?? $rule->severity,
                'suppression_minutes' => isset($data['suppression_minutes']) ? max(1, min(240, (int) $data['suppression_minutes'])) : $rule->suppression_minutes,
                'requires_human_approval' => isset($data['requires_human_approval']) ? (bool) $data['requires_human_approval'] : $rule->requires_human_approval,
                'is_enabled' => isset($data['is_enabled']) ? (bool) $data['is_enabled'] : $rule->is_enabled,
                'version_history' => $history,
                'updated_by' => $admin->id,
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_threat_detection_rule_tuned',
                'entity_type' => ThreatDetectionRule::class,
                'entity_id' => $rule->id,
                'old_values' => $old,
                'new_values' => [
                    'rule_code' => $rule->rule_code,
                    'version' => $newVersion,
                    'threshold_count' => $rule->threshold_count,
                    'window_minutes' => $rule->window_minutes,
                    'reason' => $reason,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $rule->fresh();
        });
    }

    /**
     * Get comprehensive Threat Detection & Autonomous Response workspace state.
     */
    public function getThreatDetectionWorkspace(): array
    {
        $this->syncDefaultRules();

        $rules = ThreatDetectionRule::orderBy('id')->get();
        $detections = ThreatDetection::with('targetUser:id,name,email,username')
            ->latest('id')
            ->take(25)
            ->get();

        $actions = AutomatedResponseAction::with(['requester:id,name,email', 'approver:id,name,email', 'rejecter:id,name,email'])
            ->latest('id')
            ->take(25)
            ->get();

        return [
            'rules' => $rules,
            'recent_detections' => $detections,
            'response_actions' => $actions,
            'summary' => [
                'enabled_rules_count' => $rules->where('is_enabled', true)->count(),
                'open_detections_count' => ThreatDetection::where('status', ThreatDetection::STATUS_OPEN)->count(),
                'pending_approvals_count' => AutomatedResponseAction::where('status', AutomatedResponseAction::STATUS_PENDING_APPROVAL)->count(),
                'executed_actions_count' => AutomatedResponseAction::where('status', AutomatedResponseAction::STATUS_EXECUTED)->count(),
            ],
        ];
    }
}
