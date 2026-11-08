<?php

namespace App\Services\Moderation;

use App\Models\ModerationAuditLog;
use App\Models\ModerationPolicySetting;
use App\Models\User;
use App\Models\UserRestriction;
use Illuminate\Support\Facades\Log;

class ModerationPolicyEngine
{
    public const POLICY_APPROVE = 'approve';

    public const POLICY_REJECT = 'reject';

    public const POLICY_MANUAL_REVIEW = 'manual_review';

    public const POLICY_QUARANTINE = 'quarantine';

    /**
     * Get the active adult content policy (approve, reject, manual_review).
     */
    public function getAdultContentPolicy(): string
    {
        return strtolower((string) ModerationPolicySetting::get('adult_content_policy', config('moderation.adult_content_policy', 'manual_review')));
    }

    /**
     * Check if fail-closed architecture is active.
     */
    public function isFailClosed(): bool
    {
        return (bool) ModerationPolicySetting::get('fail_closed', config('moderation.fail_closed', true));
    }

    /**
     * Retrieve threshold for a specific category.
     */
    public function getThreshold(string $category): float
    {
        $sanitized = str_replace(['/', '-'], '_', $category);
        $settingKey = "threshold_{$sanitized}";
        $defaultConfig = config("moderation.thresholds.{$sanitized}", 0.70);

        return (float) ModerationPolicySetting::get($settingKey, $defaultConfig);
    }

    /**
     * Evaluate moderation results and determine policy decision.
     *
     * @param  array{flagged: bool, categories: array<string, bool>, category_scores: array<string, float>, error: ?string}  $moderationData
     * @return array{decision: string, action_taken: string, reason: ?string, is_escalated: bool, violation_category: ?string}
     */
    public function evaluate(array $moderationData, ?User $user = null): array
    {
        // 1. Handle API/System Error with Fail-Closed Strategy
        if (! empty($moderationData['error'])) {
            if ($this->isFailClosed()) {
                Log::warning("Moderation API error occurred. Fail-closed policy routing to manual review: {$moderationData['error']}");

                return [
                    'decision' => self::POLICY_MANUAL_REVIEW,
                    'action_taken' => 'queued_for_review',
                    'reason' => 'Content queued for safety review due to system inspection delay.',
                    'is_escalated' => false,
                    'violation_category' => 'system_error',
                ];
            }

            return [
                'decision' => self::POLICY_APPROVE,
                'action_taken' => 'auto_approved',
                'reason' => null,
                'is_escalated' => false,
                'violation_category' => null,
            ];
        }

        $categories = $moderationData['categories'] ?? [];
        $scores = $moderationData['category_scores'] ?? [];

        // 2. CHILD SAFETY ZERO TOLERANCE ESCALATION (CSAM / sexual/minors)
        $csamScore = $scores['sexual/minors'] ?? 0.0;
        $csamThreshold = $this->getThreshold('sexual_minors');
        $isCsam = ! empty($categories['sexual/minors']) || $csamScore >= $csamThreshold;

        if ($isCsam) {
            $this->escalateChildSafetyViolation($user, $csamScore);

            return [
                'decision' => self::POLICY_QUARANTINE,
                'action_taken' => 'quarantined',
                'reason' => 'Child safety violation detected. Content quarantined and account restricted.',
                'is_escalated' => true,
                'violation_category' => 'sexual/minors',
            ];
        }

        // 3. ADULT CONTENT EVALUATION (sexual)
        $sexualScore = $scores['sexual'] ?? 0.0;
        $sexualThreshold = $this->getThreshold('sexual');
        $isSexual = ! empty($categories['sexual']) || $sexualScore >= $sexualThreshold;

        if ($isSexual) {
            $adultPolicy = $this->getAdultContentPolicy();

            return match ($adultPolicy) {
                'reject' => [
                    'decision' => self::POLICY_REJECT,
                    'action_taken' => 'auto_rejected',
                    'reason' => 'Content contains adult material which is prohibited under current platform policy.',
                    'is_escalated' => false,
                    'violation_category' => 'sexual',
                ],
                'approve' => [
                    'decision' => self::POLICY_APPROVE,
                    'action_taken' => 'auto_approved',
                    'reason' => 'Adult material permitted under age-gate/warning policy.',
                    'is_escalated' => false,
                    'violation_category' => 'sexual',
                ],
                default => [ // 'manual_review'
                    'decision' => self::POLICY_MANUAL_REVIEW,
                    'action_taken' => 'queued_for_review',
                    'reason' => 'Content flagged for adult material and queued for moderator review.',
                    'is_escalated' => false,
                    'violation_category' => 'sexual',
                ],
            };
        }

        // 4. SEVERE HARMFUL CATEGORIES (Hate, Self-Harm, Severe Violence)
        $severeCategories = [
            'hate/threatening' => 'Hate speech containing direct threats',
            'self-harm/intent' => 'Self-harm or suicide encouragement',
            'self-harm/instructions' => 'Self-harm instructions',
            'violence/graphic' => 'Extremely graphic or gratuitous violence',
            'illicit/violent' => 'Violent illicit actions or terrorism',
        ];

        foreach ($severeCategories as $cat => $desc) {
            $score = $scores[$cat] ?? 0.0;
            $threshold = $this->getThreshold($cat);
            if (! empty($categories[$cat]) || $score >= $threshold) {
                return [
                    'decision' => self::POLICY_REJECT,
                    'action_taken' => 'auto_rejected',
                    'reason' => "Prohibited content detected: {$desc}.",
                    'is_escalated' => true,
                    'violation_category' => $cat,
                ];
            }
        }

        // 5. STANDARD MODERATION CATEGORIES (Hate, Harassment, Violence, Self-Harm)
        $standardCategories = [
            'hate' => 'Hate speech or discriminatory content',
            'harassment' => 'Harassment or targeted abuse',
            'self-harm' => 'Self-harm references',
            'violence' => 'Violence or physical harm',
            'illicit' => 'Illicit or illegal activities',
        ];

        foreach ($standardCategories as $cat => $desc) {
            $score = $scores[$cat] ?? 0.0;
            $threshold = $this->getThreshold($cat);
            if (! empty($categories[$cat]) || $score >= $threshold) {
                return [
                    'decision' => self::POLICY_REJECT,
                    'action_taken' => 'auto_rejected',
                    'reason' => "Violates Community Standards: {$desc}.",
                    'is_escalated' => false,
                    'violation_category' => $cat,
                ];
            }
        }

        // 6. ALL CHECKS PASSED -> APPROVED
        return [
            'decision' => self::POLICY_APPROVE,
            'action_taken' => 'auto_approved',
            'reason' => null,
            'is_escalated' => false,
            'violation_category' => null,
        ];
    }

    /**
     * Mandatory immediate child-safety quarantine & restriction protocol.
     */
    protected function escalateChildSafetyViolation(?User $user, float $confidenceScore): void
    {
        Log::critical('CRITICAL TRUST & SAFETY: Child exploitation flag triggered for user ID: '.($user?->id ?? 'unknown'));

        if ($user) {
            // Apply immediate account posting freeze
            try {
                if (class_exists(UserRestriction::class)) {
                    UserRestriction::updateOrCreate(
                        ['user_id' => $user->id, 'type' => 'posting'],
                        [
                            'reason' => 'Emergency Child Safety Escalation',
                            'restricted_until' => now()->addDays(30),
                            'notes' => 'Account automatically restricted pending child safety investigation.',
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::error("Failed to set UserRestriction for child safety: {$e->getMessage()}");
            }
        }

        ModerationAuditLog::log(
            userId: $user?->id,
            action: 'quarantine',
            targetType: User::class,
            targetId: $user?->id,
            reason: 'Mandatory Child Safety Escalation (CSAM / sexual/minors flag)',
            metadata: [
                'confidence_score' => $confidenceScore,
                'urgent_escalation' => true,
            ]
        );
    }
}
