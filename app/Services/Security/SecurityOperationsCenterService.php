<?php

namespace App\Services\Security;

use App\Models\AdminAlert;
use App\Models\AuditLog;
use App\Models\Backup;
use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\Admin\EnterpriseAccessControlService;
use App\Services\Audit\TamperEvidentAuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

class SecurityOperationsCenterService
{
    /**
     * Standard Incident Response Playbooks.
     */
    public const PLAYBOOKS = [
        'account_takeover' => [
            'name' => 'অ্যাকাউন্ট টেকওভার (ATO) প্রতিক্রিয়া প্লেবুক',
            'severity' => 'critical',
            'checklist' => [
                'সন্দেহভাজন ব্যবহারকারীর সমস্ত সক্রিয় সেশন এবং টোকেন অবিলম্বে বাতিল করুন',
                'ব্যবহারকারীর পাসওয়ার্ড রিসেট ট্রিগার করুন এবং টু-ফ্যাক্টর অথেনটিকেশন বাধ্যতামূলক করুন',
                'সাম্প্রতিক ইমেইল, ফোন এবং পাসওয়ার্ড পরিবর্তনের অডিট লগ পর্যবেক্ষণ করুন',
                'সাম্প্রতিক লেনদেন বা ক্ষতিকারক কার্যকলাপ রিভার্স করুন',
                'ব্যবহারকারীকে বিকল্প ভেরিফাইড যোগাযোগের মাধ্যমে সতর্ক করুন',
            ],
        ],
        'credential_stuffing' => [
            'name' => 'ক্রেডেনশিয়াল স্টাফিং ও ব্রুট-ফোর্স প্রতিরোধ প্লেবুক',
            'severity' => 'high',
            'checklist' => [
                'আক্রমণকারী সোর্স আইপি বা সিআইডিআর রেঞ্জের উপর অ্যাডমিনিস্ট্রেটিভ ব্লকলিস্ট প্রয়োগ করুন',
                'লগইন এন্ডপয়েন্টে ক্যাপচা বা বায়োমেট্রিক থ্রটলিং তীব্র করুন',
                'টার্গেটেড অ্যাকাউন্টগুলির ব্যর্থ চেষ্টার প্যাটার্ন পর্যালোচনা করুন',
                'সংশ্লিষ্ট অ্যাকাউন্টের জন্য সাময়িক লগইন লক নিশ্চিত করুন',
            ],
        ],
        'content_injection' => [
            'name' => 'ম্যালওয়্যার বা ফিশিং লিঙ্ক ইনজেকশন প্লেবুক',
            'severity' => 'high',
            'checklist' => [
                'ক্ষতিকারক পোস্ট বা মেসেজ স্বয়ংক্রিয়ভাবে কোয়ারেন্টাইনে পাঠান',
                'ফিশিং ডোমেনটি গ্লোবাল ইউআরএল ব্লকলিস্টে যুক্ত করুন',
                'প্রভাবিত লিঙ্কটিতে ক্লিক করা ব্যবহারকারীদের তালিকা চিহ্নিত করুন',
                'উৎপত্তিকারী অ্যাকাউন্টের বিরুদ্ধে অপব্যবহার নিষেধাজ্ঞা প্রয়োগ করুন',
            ],
        ],
    ];

    /**
     * Get Overview of Security Incidents, Threats, and Operational Telemetry.
     */
    public function getSecurityOverview(): array
    {
        $openIncidents = SecurityEvent::with(['user:id,name,email,username', 'assignee:id,name,email'])
            ->where('resolved', false)
            ->latest('id')
            ->take(20)
            ->get();

        $criticalCount = SecurityEvent::where('resolved', false)
            ->where('severity', SecurityEvent::SEVERITY_CRITICAL)
            ->count();

        $highCount = SecurityEvent::where('resolved', false)
            ->where('severity', SecurityEvent::SEVERITY_HIGH)
            ->count();

        $recentFailedLogins = DB::table('failed_login_attempts')
            ->latest('attempted_at')
            ->take(15)
            ->get();

        $activeSessionsCount = DB::table('personal_access_tokens')->count();

        // High Risk IPs: >= 5 failed logins past 24 hours (portable ANSI SQL across SQLite and MySQL)
        $highRiskIps = DB::table('failed_login_attempts')
            ->where('attempted_at', '>=', now()->subDay())
            ->select('ip_address', DB::raw('COUNT(*) as attempts'))
            ->groupBy('ip_address')
            ->havingRaw('COUNT(*) >= ?', [5])
            ->orderByDesc('attempts')
            ->take(10)
            ->get();

        $authFailureTrend = $this->getAuthFailureTrends();
        $adminLoginHistory = $this->getAdminLoginHistory(10);

        // Real operational telemetry (Queue backlog, Scheduler heartbeat, Backup health, Audit integrity)
        $pendingJobs = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $lastHeartbeat = Cache::get('scheduler_last_heartbeat_at');
        $heartbeatMissed = true;
        if ($lastHeartbeat) {
            try {
                $heartbeatMissed = Carbon::parse($lastHeartbeat)->diffInMinutes(now()) > 10;
            } catch (Throwable) {
                $heartbeatMissed = true;
            }
        }

        $failedBackupsCount = Schema::hasTable('backups')
            ? Backup::where('status', Backup::STATUS_FAILED)->where('created_at', '>=', now()->subDays(7))->count()
            : 0;

        $auditIntegrity = app(TamperEvidentAuditService::class)->verifyIntegrity(50);

        return [
            'open_incidents' => $openIncidents,
            'critical_count' => $criticalCount,
            'high_count' => $highCount,
            'active_sessions_count' => $activeSessionsCount,
            'recent_failed_logins' => $recentFailedLogins,
            'high_risk_ips' => $highRiskIps,
            'auth_failure_trend' => $authFailureTrend,
            'auth_failure_trends' => $authFailureTrend,
            'admin_login_history' => $adminLoginHistory,
            'operational_telemetry' => [
                'pending_jobs_count' => $pendingJobs,
                'failed_jobs_count' => $failedJobs,
                'scheduler_last_heartbeat_at' => $lastHeartbeat,
                'scheduler_missed_heartbeat' => $heartbeatMissed,
                'failed_backups_7d' => $failedBackupsCount,
                'audit_chain_verified' => $auditIntegrity['verified'],
            ],
            'playbooks' => self::PLAYBOOKS,
        ];
    }

    /**
     * Record a new Security Incident with automatic Alert dispatch if high/critical.
     */
    public function logIncident(
        string $eventType,
        string $severity,
        ?int $userId = null,
        ?string $ip = null,
        ?array $details = null,
        ?array $evidenceRefs = null
    ): SecurityEvent {
        $initialTimeline = [[
            'action' => 'created',
            'actor_id' => $userId,
            'note' => "Incident '{$eventType}' detected ({$severity}).",
            'timestamp' => now()->toIso8601String(),
        ]];

        $event = SecurityEvent::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'severity' => $severity,
            'ip_address' => $ip ?? request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'details' => $details,
            'evidence_refs' => $evidenceRefs,
            'timeline' => $initialTimeline,
            'resolved' => false,
            'status' => SecurityEvent::STATUS_OPEN,
        ]);

        if (in_array($severity, [SecurityEvent::SEVERITY_HIGH, SecurityEvent::SEVERITY_CRITICAL], true)) {
            AdminAlert::trigger(
                type: 'security',
                title: '🚨 '.ucfirst($severity).' Security Incident: '.$eventType,
                message: "A {$severity} security event '{$eventType}' was detected from IP: ".($ip ?? request()?->ip()),
                severity: $severity === SecurityEvent::SEVERITY_CRITICAL ? AdminAlert::SEVERITY_CRITICAL : AdminAlert::SEVERITY_DANGER,
                data: ['security_event_id' => $event->id, 'details' => $details],
                link: '/admin'
            );
        }

        return $event;
    }

    /**
     * Transition a Security Incident through its lifecycle (acknowledge, investigating, escalate, resolve, reopen).
     */
    public function transitionIncidentStatus(
        int $incidentId,
        string $status,
        int $adminId,
        ?string $notes = null,
        ?array $evidenceRefs = null
    ): SecurityEvent {
        $validStatuses = [
            SecurityEvent::STATUS_OPEN,
            SecurityEvent::STATUS_ACKNOWLEDGED,
            SecurityEvent::STATUS_INVESTIGATING,
            SecurityEvent::STATUS_ESCALATED,
            SecurityEvent::STATUS_RESOLVED,
            SecurityEvent::STATUS_REOPENED,
        ];

        if (! in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException("Invalid security incident status: {$status}");
        }

        return DB::transaction(function () use ($incidentId, $status, $adminId, $notes, $evidenceRefs) {
            $event = SecurityEvent::where('id', $incidentId)->lockForUpdate()->firstOrFail();
            $oldStatus = $event->status ?? ($event->resolved ? SecurityEvent::STATUS_RESOLVED : SecurityEvent::STATUS_OPEN);

            $event->status = $status;

            if ($status === SecurityEvent::STATUS_ACKNOWLEDGED) {
                $event->acknowledged_at = now();
                $event->acknowledged_by = $adminId;
                $event->resolved = false;
            } elseif ($status === SecurityEvent::STATUS_ESCALATED) {
                $event->escalated_at = now();
                $event->severity = SecurityEvent::SEVERITY_CRITICAL;
                $event->resolved = false;
            } elseif ($status === SecurityEvent::STATUS_RESOLVED) {
                $event->resolved = true;
                $event->resolved_by = $adminId;
                $event->resolved_at = now();
            } elseif ($status === SecurityEvent::STATUS_REOPENED) {
                $event->resolved = false;
                $event->reopened_at = now();
                $event->reopened_by = $adminId;
            } else {
                $event->resolved = false;
            }

            if ($notes !== null && $notes !== '') {
                $event->internal_notes = $notes;
            }

            if ($evidenceRefs !== null) {
                $existingRefs = is_array($event->evidence_refs) ? $event->evidence_refs : [];
                $event->evidence_refs = array_values(array_unique(array_merge($existingRefs, $evidenceRefs)));
            }

            $event->appendTimelineEvent($status, $adminId, $notes, $evidenceRefs ? ['evidence_refs' => $evidenceRefs] : []);
            $event->save();

            AuditLog::create([
                'user_id' => $adminId,
                'action' => 'admin_security_incident_status_transition',
                'entity_type' => SecurityEvent::class,
                'entity_id' => $event->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => [
                    'status' => $status,
                    'resolved' => $event->resolved,
                    'notes' => $notes,
                    'evidence_refs' => $evidenceRefs,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $event->fresh(['user:id,name,email,username', 'assignee:id,name,email', 'resolver:id,name,email']);
        });
    }

    /**
     * Resolve a security incident with internal notes.
     */
    public function resolveIncident(int $incidentId, int $adminId, ?string $notes = null): SecurityEvent
    {
        return DB::transaction(function () use ($incidentId, $adminId, $notes) {
            $event = SecurityEvent::where('id', $incidentId)->lockForUpdate()->firstOrFail();
            $event->markResolved($adminId, $notes);

            AuditLog::create([
                'user_id' => $adminId,
                'action' => 'admin_resolve_security_incident',
                'entity_type' => SecurityEvent::class,
                'entity_id' => $event->id,
                'new_values' => ['resolved' => true, 'status' => SecurityEvent::STATUS_RESOLVED, 'notes' => $notes],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $event->fresh();
        });
    }

    /**
     * Assign a security incident to an investigator.
     */
    public function assignIncident(int $incidentId, int $assignedToUserId, int $adminId): SecurityEvent
    {
        return DB::transaction(function () use ($incidentId, $assignedToUserId, $adminId) {
            $event = SecurityEvent::where('id', $incidentId)->lockForUpdate()->firstOrFail();
            $event->assigned_to = $assignedToUserId;
            if (($event->status ?? 'open') === SecurityEvent::STATUS_OPEN) {
                $event->status = SecurityEvent::STATUS_INVESTIGATING;
            }
            $event->appendTimelineEvent('assigned', $adminId, "Assigned to user #{$assignedToUserId}");
            $event->save();

            AuditLog::create([
                'user_id' => $adminId,
                'action' => 'admin_assign_security_incident',
                'entity_type' => SecurityEvent::class,
                'entity_id' => $event->id,
                'new_values' => ['assigned_to' => $assignedToUserId],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $event->fresh();
        });
    }

    /**
     * Immediate Revocation of Active Sessions for a User.
     */
    public function revokeUserSessions(int $userId, User $admin, string $reason): int
    {
        return DB::transaction(function () use ($userId, $admin, $reason) {
            $targetUser = User::where('id', $userId)->lockForUpdate()->firstOrFail();

            // Delete all Sanctum personal access tokens
            $tokensDeleted = $targetUser->tokens()->delete();

            // Also purge any database sessions if database driver is used
            try {
                DB::table('sessions')->where('user_id', $userId)->delete();
            } catch (Throwable) {
            }

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_revoke_user_sessions',
                'entity_type' => User::class,
                'entity_id' => $userId,
                'new_values' => [
                    'target_email' => $targetUser->email,
                    'tokens_revoked_count' => $tokensDeleted,
                    'reason' => $reason,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $tokensDeleted;
        });
    }

    /**
     * Authentication failure trends over the past 7 days.
     */
    public function getAuthFailureTrends(): array
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $count = DB::table('failed_login_attempts')
                ->whereDate('attempted_at', $date)
                ->count();

            $days[] = [
                'date' => $date->format('M d'),
                'failures' => $count,
            ];
        }

        return $days;
    }

    /**
     * Recent Administrator Login History.
     */
    public function getAdminLoginHistory(int $limit = 15): array
    {
        $adminRole = Role::whereIn('name', ['ADMIN', 'SUPER_ADMIN'])->pluck('id');

        $adminUsers = User::whereHas('roles', fn ($q) => $q->whereIn('roles.id', $adminRole))
            ->orWhere('status', 'super_admin')
            ->pluck('id');

        try {
            return DB::table('login_histories')
                ->whereIn('user_id', $adminUsers)
                ->latest('login_at')
                ->take($limit)
                ->get()
                ->map(fn ($l) => [
                    'id' => $l->id,
                    'user_id' => $l->user_id,
                    'ip_address' => $l->ip_address,
                    'user_agent' => $l->user_agent,
                    'login_at' => $l->login_at,
                    'status' => $l->status ?? 'success',
                ])
                ->toArray();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Protect the last active Super Administrator from accidental demotion or deletion.
     *
     * @throws InvalidArgumentException
     */
    public function assertSuperAdminProtection(User $targetUser, string $operation = 'demote'): void
    {
        $accessControl = app(EnterpriseAccessControlService::class);
        if ($accessControl->isLastSuperAdmin($targetUser)) {
            throw new InvalidArgumentException(
                "নিরাপত্তা সতর্কতা: প্ল্যাটফর্মে অন্তত একজন সক্রিয় সুপার অ্যাডমিনিস্ট্রেটর থাকা আবশ্যক। শেষ সুপার অ্যাডমিনকে {$operation} করা যাবে না।"
            );
        }
    }
}
