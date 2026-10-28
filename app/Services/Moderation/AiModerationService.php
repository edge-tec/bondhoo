<?php

namespace App\Services\Moderation;

use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Models\UserRestriction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiModerationService
{
    /**
     * Enhanced prohibited patterns and heuristics (Bangla & English).
     */
    protected array $patterns = [
        'phishing_scam' => [
            'terms' => [
                'free recharge', 'bKash lottery', 'nagad bonus', 'পাসওয়ার্ড পাঠান', 'লটারি জিতেছেন',
                'টাকা দ্বিগুণ', 'ফ্রি রিচার্জ', 'লগইন লিংক', 'giveaway cash', 'send bkash pin', 'crypto 100x return',
            ],
            'severity' => Report::PRIORITY_HIGH,
            'category' => Report::CATEGORY_SCAM,
            'reason' => 'scam_phishing',
            'base_confidence' => 0.92,
        ],
        'hate_speech_harassment' => [
            'terms' => [
                'kill yourself', 'hate speech', 'শালা শুয়োর', 'মার্ডার করব', 'তুলে নিয়ে যাব',
                'দাঙ্গা লাগাও', 'আগুন লাগিয়ে দাও', 'bomb blast', 'terrorist camp', 'দেশদ্রোহী হত্যা',
            ],
            'severity' => Report::PRIORITY_CRITICAL,
            'category' => Report::CATEGORY_VIOLENCE,
            'reason' => 'violence_harassment',
            'base_confidence' => 0.95,
        ],
        'coordinated_spam' => [
            'terms' => [
                'join telegram channel', 'earn 5000 daily', 'ক্লিক করুন লিংকে', 'টেলিগ্রাম গ্রুপে যুক্ত হন',
                'বিনা পুঁজিতে আয়', 'guaranteed earnings', 'work from home 50000', 'direct message for forex',
            ],
            'severity' => Report::PRIORITY_MEDIUM,
            'category' => Report::CATEGORY_SPAM,
            'reason' => 'spam_promotion',
            'base_confidence' => 0.88,
        ],
        'impersonation' => [
            'terms' => [
                'official customer support', 'অফিসিয়াল হেল্পডেস্ক', 'bKash agent call', 'ব্যাংক প্রতিনিধি',
            ],
            'severity' => Report::PRIORITY_HIGH,
            'category' => Report::CATEGORY_IMPERSONATION,
            'reason' => 'impersonation_fraud',
            'base_confidence' => 0.90,
        ],
    ];

    /**
     * Redact sensitive information (passwords, tokens, phone numbers, card numbers) from text.
     */
    public function redactSensitiveInformation(string $text): string
    {
        // 1. Redact credit card numbers (13-19 digits)
        $text = preg_replace('/\b(?:\d[ -]*?){13,19}\b/', '[REDACTED_CARD]', $text);

        // 2. Redact Bangladeshi and International phone numbers
        $text = preg_replace('/(?:\+8801|01)[3-9]\d{8}/', '[REDACTED_PHONE]', $text);

        // 3. Redact emails
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[REDACTED_EMAIL]', $text);

        // 4. Redact suspected passwords/API tokens
        $text = preg_replace('/(password|secret|token|pin)\s*[:=]\s*\S+/i', '$1: [REDACTED_SECRET]', $text);

        return $text;
    }

    /**
     * Defend against Prompt Injection in user content.
     */
    public function sanitizeAgainstPromptInjection(string $content): string
    {
        $injectionPatterns = [
            '/ignore previous instructions/i',
            '/disregard all previous/i',
            '/you are now in developer mode/i',
            '/system prompt override/i',
            '/output your initial instructions/i',
        ];

        foreach ($injectionPatterns as $pattern) {
            $content = preg_replace($pattern, '[INJECTION_BLOCKED]', $content);
        }

        return $content;
    }

    /**
     * Inspect and scan content using AI/Heuristic moderation.
     * Only public content is inspected; private conversations are strictly excluded.
     *
     * @return array{flagged: bool, confidence: float, reason: ?string, severity: string, category: string, engine: string, evidence: array}
     */
    public function scanContent(string $content, string $entityType = 'post', ?int $entityId = null): array
    {
        // Sanitize and redact
        $safeContent = $this->sanitizeAgainstPromptInjection($content);
        $redactedSnippet = $this->redactSensitiveInformation($safeContent);
        $normalized = Str::lower($safeContent);

        $matchedTerms = [];
        $detectedReason = null;
        $highestCategory = Report::CATEGORY_GENERAL;
        $highestSeverity = Report::PRIORITY_LOW;
        $highestConfidence = 0.0;

        foreach ($this->patterns as $category => $rule) {
            foreach ($rule['terms'] as $term) {
                if (str_contains($normalized, Str::lower($term))) {
                    $matchedTerms[] = $term;
                    $detectedReason = $rule['reason'];
                    $highestCategory = $rule['category'] ?? Report::CATEGORY_GENERAL;
                    $highestSeverity = $rule['severity'];
                    $highestConfidence = max($highestConfidence, $rule['base_confidence']);
                }
            }
        }

        // Repetitive character spam detection
        if (preg_match('/(.)\1{9,}/u', $safeContent)) {
            $matchedTerms[] = 'Excessive repeated characters pattern';
            $detectedReason = $detectedReason ?? 'repetitive_spam';
            $highestCategory = Report::CATEGORY_SPAM;
            $highestSeverity = $highestSeverity === Report::PRIORITY_LOW ? Report::PRIORITY_MEDIUM : $highestSeverity;
            $highestConfidence = max($highestConfidence, 0.85);
        }

        // Suspicious URL pattern detection
        if (preg_match('/(bit\.ly|t\.me|wa\.me|tinyurl\.com)\/[a-zA-Z0-9_\-]+/i', $safeContent, $urlMatches)) {
            $matchedTerms[] = 'Suspicious redirect URL: '.$urlMatches[0];
            $detectedReason = $detectedReason ?? 'suspicious_link';
            $highestCategory = Report::CATEGORY_MALICIOUS_LINK;
            $highestSeverity = $highestSeverity === Report::PRIORITY_LOW ? Report::PRIORITY_MEDIUM : $highestSeverity;
            $highestConfidence = max($highestConfidence, 0.80);
        }

        $isFlagged = count($matchedTerms) > 0;

        return [
            'flagged' => $isFlagged,
            'confidence' => $isFlagged ? round($highestConfidence, 2) : 0.0,
            'reason' => $detectedReason,
            'category' => $highestCategory,
            'severity' => $isFlagged ? $highestSeverity : Report::PRIORITY_LOW,
            'engine' => 'rule_based', // Rule-based detection fallback
            'evidence' => [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'matched_terms' => $matchedTerms,
                'snippet' => Str::limit($redactedSnippet, 120),
                'scanned_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Automatically scan and attach AI recommendation to an existing or new report with duplicate grouping.
     */
    public function flagReportable(Post|Comment|User $entity, ?int $reporterId = null, string $userReason = 'other'): Report
    {
        $content = match (true) {
            $entity instanceof Post => $entity->content ?? '',
            $entity instanceof Comment => $entity->content ?? '',
            $entity instanceof User => ($entity->profile?->bio ?? '').' '.($entity->name ?? ''),
            default => '',
        };

        $scan = $this->scanContent($content, strtolower(class_basename($entity)), $entity->id);

        // Check if an existing open report already exists for this exact entity to group as duplicate
        $existingPrimary = Report::where('reportable_type', get_class($entity))
            ->where('reportable_id', $entity->id)
            ->where('status', Report::STATUS_PENDING)
            ->whereNull('duplicate_of_id')
            ->first();

        return Report::create([
            'reporter_id' => $reporterId ?? User::where('status', 'active')->first()?->id ?? 1,
            'reportable_type' => get_class($entity),
            'reportable_id' => $entity->id,
            'reason' => $scan['reason'] ?? $userReason,
            'category' => $scan['category'] ?? Report::CATEGORY_GENERAL,
            'duplicate_of_id' => $existingPrimary?->id,
            'details' => 'AI Automated Content Scan Report',
            'status' => Report::STATUS_PENDING,
            'ai_flagged' => $scan['flagged'],
            'ai_confidence' => $scan['confidence'],
            'ai_reason' => $scan['reason'],
            'ai_evidence' => $scan['evidence'],
            'priority' => $scan['severity'],
        ]);
    }

    /**
     * Scan an existing report and attach AI analysis.
     */
    public function scanReport(Report $report): Report
    {
        $entity = $report->reportable;
        $content = match (true) {
            $entity instanceof Post => $entity->content ?? '',
            $entity instanceof Comment => $entity->content ?? '',
            $entity instanceof User => ($entity->profile?->bio ?? '').' '.($entity->name ?? ''),
            default => $report->details ?? '',
        };

        $scan = $this->scanContent($content, strtolower(class_basename($entity ?? Post::class)), $report->reportable_id);

        $report->ai_flagged = $scan['flagged'];
        $report->ai_confidence = $scan['confidence'];
        $report->ai_reason = $scan['reason'];
        $report->category = $scan['category'] ?? $report->category;
        $report->ai_evidence = $scan['evidence'];
        $report->priority = $scan['severity'];
        $report->save();

        // Automatically link duplicate pending reports for the same target entity
        $primaryReport = Report::where('reportable_type', $report->reportable_type)
            ->where('reportable_id', $report->reportable_id)
            ->where('status', Report::STATUS_PENDING)
            ->whereNull('duplicate_of_id')
            ->oldest('id')
            ->first();

        if ($primaryReport) {
            Report::where('reportable_type', $report->reportable_type)
                ->where('reportable_id', $report->reportable_id)
                ->where('status', Report::STATUS_PENDING)
                ->where('id', '!=', $primaryReport->id)
                ->whereNull('duplicate_of_id')
                ->update(['duplicate_of_id' => $primaryReport->id]);
        }

        return $report->fresh();
    }

    /**
     * Centralized Moderation Queue with flexible filters, pagination and duplicate counts.
     */
    public function getModerationQueue(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Report::with(['reporter:id,name,username', 'assignee:id,name', 'reviewer:id,name', 'reportable'])
            ->withCount('duplicateReports');

        // Status filter
        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Priority filter
        if (! empty($filters['priority']) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        // Category filter
        if (! empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('category', $filters['category']);
        }

        // AI Flagged filter
        if (isset($filters['ai_flagged']) && $filters['ai_flagged'] !== 'all') {
            $query->where('ai_flagged', filter_var($filters['ai_flagged'], FILTER_VALIDATE_BOOLEAN));
        }

        // Search term in reason, details or admin_notes
        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('reason', 'like', "%{$term}%")
                    ->orWhere('details', 'like', "%{$term}%")
                    ->orWhere('admin_notes', 'like', "%{$term}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Resolve a Report with specific moderation action (warn, hide, remove, restrict, dismiss).
     */
    public function resolveReport(Report $report, string $action, User $admin, ?string $notes = null): Report
    {
        $report->status = $action === 'dismiss' ? Report::STATUS_DISMISSED : Report::STATUS_ACTIONED;
        $report->reviewed_by = $admin->id;
        $report->reviewed_at = now();
        if ($notes) {
            $report->admin_notes = ($report->admin_notes ? $report->admin_notes."\n" : '')."[Moderator Action: {$action}]: ".$notes;
        }

        $entity = $report->reportable;

        // Perform entity action based on human review
        if ($entity && $action !== 'dismiss') {
            match ($action) {
                'hide' => (function () use ($entity) {
                    if (method_exists($entity, 'delete')) {
                        $entity->delete(); // Soft-delete
                    }
                })(),
                'remove' => (function () use ($entity) {
                    if (method_exists($entity, 'forceDelete')) {
                        $entity->forceDelete();
                    } elseif (method_exists($entity, 'delete')) {
                        $entity->delete();
                    }
                })(),
                'restrict_user' => (function () use ($entity, $admin, $notes) {
                    $targetUser = $entity instanceof User ? $entity : ($entity->user ?? null);
                    if ($targetUser) {
                        UserRestriction::create([
                            'user_id' => $targetUser->id,
                            'restriction_type' => UserRestriction::TYPE_POST_BLOCK,
                            'reason' => $notes ?? 'Violation of community guidelines',
                            'expires_at' => now()->addDays(3),
                            'created_by' => $admin->id,
                        ]);
                    }
                })(),
                'suspend_user' => (function () use ($entity) {
                    $targetUser = $entity instanceof User ? $entity : ($entity->user ?? null);
                    if ($targetUser) {
                        $targetUser->update(['status' => 'suspended', 'locked_until' => now()->addDays(7)]);
                    }
                })(),
                default => null,
            };
        }

        // If primary report actioned, resolve child duplicate reports
        if ($report->duplicateReports()->exists()) {
            $report->duplicateReports()->update([
                'status' => $report->status,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);
        }

        $report->save();

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => "admin_moderation_{$action}",
            'entity_type' => Report::class,
            'entity_id' => $report->id,
            'new_values' => ['action' => $action, 'status' => $report->status, 'notes' => $notes],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $report;
    }

    /**
     * Assign a report to a specific moderator.
     */
    public function assignReport(Report $report, User $assignee, User $admin): Report
    {
        return DB::transaction(function () use ($report, $assignee, $admin) {
            $lockedReport = Report::where('id', $report->id)->lockForUpdate()->firstOrFail();
            $lockedReport->update(['assigned_to' => $assignee->id]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_moderation_assign',
                'entity_type' => Report::class,
                'entity_id' => $lockedReport->id,
                'new_values' => ['assigned_to' => $assignee->id, 'assignee_name' => $assignee->name],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $lockedReport->fresh();
        });
    }

    /**
     * Mark report as false positive for training & audit accuracy.
     */
    public function markFalsePositive(Report $report, User $admin, string $reason): Report
    {
        return DB::transaction(function () use ($report, $admin, $reason) {
            $lockedReport = Report::where('id', $report->id)->lockForUpdate()->firstOrFail();
            $lockedReport->update([
                'is_false_positive' => true,
                'status' => Report::STATUS_DISMISSED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'admin_notes' => ($lockedReport->admin_notes ? $lockedReport->admin_notes."\n" : '').'[False Positive]: '.$reason,
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_moderation_false_positive',
                'entity_type' => Report::class,
                'entity_id' => $lockedReport->id,
                'new_values' => ['is_false_positive' => true, 'reason' => $reason],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $lockedReport->fresh();
        });
    }

    /**
     * Submit an appeal against a moderation decision.
     */
    public function submitAppeal(Report $report, string $appealReason, User $appealingUser): Report
    {
        $report->appeal_status = Report::APPEAL_REQUESTED;
        $report->appeal_notes = $appealReason;
        $report->save();

        AuditLog::create([
            'user_id' => $appealingUser->id,
            'action' => 'user_moderation_appeal',
            'entity_type' => Report::class,
            'entity_id' => $report->id,
            'new_values' => ['appeal_status' => Report::APPEAL_REQUESTED, 'notes' => $appealReason],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $report;
    }

    /**
     * Resolve an appeal by moderator (Upheld or Overturned).
     */
    public function resolveAppeal(Report $report, string $decision, int $adminId, ?string $notes = null): Report
    {
        $report->appeal_status = $decision === 'overturn' ? Report::APPEAL_OVERTURNED : Report::APPEAL_UPHELD;
        $report->reviewed_by = $adminId;
        $report->reviewed_at = now();
        if ($notes) {
            $report->admin_notes = ($report->admin_notes ? $report->admin_notes."\n" : '').'[Appeal Resolution]: '.$notes;
        }

        // If overturned, restore content or user
        if ($decision === 'overturn') {
            try {
                $target = $report->reportable;
                if ($target) {
                    if (method_exists($target, 'trashed') && $target->trashed()) {
                        $target->restore();
                    }
                    if ($report->reportable_type === Post::class) {
                        $target->status = 'published';
                        $target->save();
                    } elseif ($report->reportable_type === User::class) {
                        $target->status = 'active';
                        $target->locked_until = null;
                        $target->save();
                    }
                }
            } catch (\Throwable) {
            }
        }

        $report->save();

        AuditLog::create([
            'user_id' => $adminId,
            'action' => "admin_appeal_{$decision}",
            'entity_type' => Report::class,
            'entity_id' => $report->id,
            'new_values' => ['appeal_status' => $report->appeal_status, 'notes' => $notes],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $report;
    }

    /**
     * Reviewer quality metrics (total actioned, overturn rate, false positive rate).
     */
    public function getReviewerMetrics(): array
    {
        $totalReports = Report::count();
        $actioned = Report::whereIn('status', [Report::STATUS_ACTIONED, Report::STATUS_REVIEWED])->count();
        $dismissed = Report::where('status', Report::STATUS_DISMISSED)->count();
        $falsePositives = Report::where('is_false_positive', true)->count();
        $totalAppeals = Report::whereIn('appeal_status', [Report::APPEAL_UPHELD, Report::APPEAL_OVERTURNED])->count();
        $overturnedAppeals = Report::where('appeal_status', Report::APPEAL_OVERTURNED)->count();

        $falsePositiveRate = $totalReports > 0 ? round(($falsePositives / $totalReports) * 100, 2) : 0.0;
        $overturnRate = $totalAppeals > 0 ? round(($overturnedAppeals / $totalAppeals) * 100, 2) : 0.0;

        // Portable SQLite & MySQL average resolution minutes calculation
        $resolvedSamples = Report::whereNotNull('reviewed_at')
            ->whereNotNull('created_at')
            ->latest('id')
            ->take(100)
            ->get(['created_at', 'reviewed_at']);

        $avgResolutionMinutes = 0.0;
        if ($resolvedSamples->isNotEmpty()) {
            $totalMins = $resolvedSamples->sum(function (Report $r) {
                return max(0, $r->created_at->diffInMinutes($r->reviewed_at));
            });
            $avgResolutionMinutes = round($totalMins / $resolvedSamples->count(), 1);
        }

        return [
            'total_reports' => $totalReports,
            'total_reviewed' => $actioned + $dismissed,
            'actioned_count' => $actioned,
            'dismissed_count' => $dismissed,
            'false_positives_count' => $falsePositives,
            'false_positive_rate_pct' => $falsePositiveRate,
            'total_appeals' => $totalAppeals,
            'overturned_appeals' => $overturnedAppeals,
            'overturn_rate_pct' => $overturnRate,
            'appeal_overturn_rate_pct' => $overturnRate,
            'avg_resolution_minutes' => $avgResolutionMinutes,
        ];
    }
}
