<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\Post;
use App\Models\Report;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserRestriction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AbuseDetectionEngineService
{
    /**
     * Compute Explainable Multi-Signal Risk Score (0 - 100) for a User.
     *
     * @return array{score: int, level: string, factors: array<string, mixed>}
     */
    public function evaluateUserRisk(User $user): array
    {
        $score = 0;
        $factors = [];

        // 1. Velocity Signal: Posting velocity in last 5 minutes
        $recentPosts = Post::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentPosts >= 5) {
            $score += 30;
            $factors['rapid_posting_velocity'] = [
                'score' => 30,
                'detail' => "গত ৫ মিনিটে {$recentPosts} টি পোস্ট তৈরি হয়েছে (সম্ভাব্য বট/স্প্যাম স্ক্রিপ্ট)।",
            ];
        } elseif ($recentPosts >= 3) {
            $score += 15;
            $factors['rapid_posting_velocity'] = [
                'score' => 15,
                'detail' => "উচ্চ পোস্টিং ফ্রিকোয়েন্সি: ৫ মিনিটে {$recentPosts} টি পোস্ট।",
            ];
        }

        // 2. Authentication Signal: Failed login attempts in last 24 hours
        $failedLogins = DB::table('failed_login_attempts')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('identifier', $user->email);
            })
            ->where('attempted_at', '>=', now()->subHours(24))
            ->count();

        if ($failedLogins >= 10) {
            $score += 35;
            $factors['credential_stuffing_risk'] = [
                'score' => 35,
                'detail' => "গত ২৪ ঘণ্টায় {$failedLogins} বার ব্যর্থ লগইন সনাক্ত (ব্রুট-ফোর্স ঝুঁকি)।",
            ];
        } elseif ($failedLogins >= 3) {
            $score += 15;
            $factors['repeated_failed_logins'] = [
                'score' => 15,
                'detail' => "ব্যর্থ লগইন প্রচেষ্টা: {$failedLogins} বার।",
            ];
        }

        // 3. Moderation History Signal: Reports against this user
        $reportsAgainstCount = Report::where('reportable_type', User::class)
            ->where('reportable_id', $user->id)
            ->count();

        $postReportsCount = Report::where('reportable_type', Post::class)
            ->whereIn('reportable_id', $user->posts()->pluck('id'))
            ->count();

        $totalReports = $reportsAgainstCount + $postReportsCount;

        if ($totalReports >= 5) {
            $score += 30;
            $factors['high_report_frequency'] = [
                'score' => 30,
                'detail' => "ব্যবহারকারী ও তার কন্টেন্টের বিরুদ্ধে মোট {$totalReports} টি অভিযোগ রয়েছে।",
            ];
        } elseif ($totalReports >= 2) {
            $score += 15;
            $factors['moderate_report_frequency'] = [
                'score' => 15,
                'detail' => "মোট অভিযোগ সংখ্যা: {$totalReports} টি।",
            ];
        }

        // 4. Fresh Account High Activity Signal: Account < 24h old with burst activity
        if ($user->created_at && $user->created_at->isAfter(now()->subHours(24))) {
            $postsCount = $user->posts()->count();
            if ($postsCount >= 8) {
                $score += 20;
                $factors['fresh_account_burst'] = [
                    'score' => 20,
                    'detail' => "নতুন তৈরি অ্যাকাউন্টে (২৪ ঘণ্টার মধ্যে) {$postsCount} টি পোস্ট তৈরি।",
                ];
            }
        }

        // 5. Correlated Suspended Accounts on Same IP
        if ($user->last_login_ip) {
            $suspendedPeers = User::where('last_login_ip', $user->last_login_ip)
                ->where('id', '!=', $user->id)
                ->where(function ($q) {
                    $q->where('status', 'suspended')->orWhere('status', 'banned');
                })
                ->count();

            if ($suspendedPeers > 0) {
                $score += 25;
                $factors['linked_suspended_ip'] = [
                    'score' => 25,
                    'detail' => "একই আইপি ({$user->last_login_ip}) থেকে পূর্বে {$suspendedPeers} টি অ্যাকাউন্ট স্থগিত করা হয়েছে।",
                ];
            }
        }

        $finalScore = min(100, $score);
        $level = match (true) {
            $finalScore >= 75 => 'critical',
            $finalScore >= 50 => 'high',
            $finalScore >= 25 => 'medium',
            default => 'low',
        };

        // Cache evaluated risk on user record
        $user->updateQuietly([
            'risk_score' => $finalScore,
            'risk_factors' => $factors,
        ]);

        return [
            'score' => $finalScore,
            'level' => $level,
            'factors' => $factors,
        ];
    }

    /**
     * Get Chronological Investigation Timeline for a User.
     */
    public function getUserInvestigationTimeline(User $user): array
    {
        $events = collect();

        // Account creation
        if ($user->created_at) {
            $events->push([
                'timestamp' => $user->created_at->toIso8601String(),
                'type' => 'account_created',
                'title' => 'অ্যাকাউন্ট নিবন্ধন',
                'description' => "অ্যাকাউন্ট তৈরি হয়েছে ইমেইল: {$user->email}",
                'icon' => 'user-plus',
                'severity' => 'info',
            ]);
        }

        // Verification
        if ($user->email_verified_at) {
            $events->push([
                'timestamp' => $user->email_verified_at->toIso8601String(),
                'type' => 'email_verified',
                'title' => 'ইমেইল ভেরিফিকেশন সম্পন্ন',
                'description' => 'ব্যবহারকারী ইমেইল ঠিকানা নিশ্চিত করেছেন।',
                'icon' => 'check-circle',
                'severity' => 'success',
            ]);
        }

        // Failed login attempts
        $failedAttempts = DB::table('failed_login_attempts')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('identifier', $user->email);
            })
            ->latest('attempted_at')
            ->take(10)
            ->get();

        foreach ($failedAttempts as $attempt) {
            $events->push([
                'timestamp' => Carbon::parse($attempt->attempted_at)->toIso8601String(),
                'type' => 'failed_login',
                'title' => 'ব্যর্থ লগইন প্রচেষ্টা',
                'description' => "আইপি: {$attempt->ip_address} থেকে ভুল ক্রেডেনশিয়াল প্রদান।",
                'icon' => 'alert-triangle',
                'severity' => 'warning',
            ]);
        }

        // Security events
        $securityEvents = SecurityEvent::where('user_id', $user->id)
            ->latest('created_at')
            ->take(10)
            ->get();

        foreach ($securityEvents as $sec) {
            $events->push([
                'timestamp' => $sec->created_at->toIso8601String(),
                'type' => 'security_incident',
                'title' => 'নিরাপত্তা ঘটনা: '.$sec->event_type,
                'description' => "তীব্রতা: {$sec->severity}, আইপি: {$sec->ip_address}",
                'icon' => 'shield-alert',
                'severity' => $sec->severity === 'critical' ? 'danger' : 'warning',
            ]);
        }

        // Moderation reports received
        $reports = Report::where('reportable_type', User::class)
            ->where('reportable_id', $user->id)
            ->latest('created_at')
            ->take(10)
            ->get();

        foreach ($reports as $rep) {
            $events->push([
                'timestamp' => $rep->created_at->toIso8601String(),
                'type' => 'report_filed',
                'title' => 'রিপোর্ট দায়ের: '.$rep->reason,
                'description' => "স্ট্যাটাস: {$rep->status}, প্রায়োরিটি: {$rep->priority}",
                'icon' => 'flag',
                'severity' => 'warning',
            ]);
        }

        // Applied restrictions
        $restrictions = $user->restrictions()->latest('created_at')->take(10)->get();
        foreach ($restrictions as $res) {
            $events->push([
                'timestamp' => $res->created_at->toIso8601String(),
                'type' => 'restriction_applied',
                'title' => 'সীমাবদ্ধতা প্রয়োগ: '.$res->restriction_type,
                'description' => "কারণ: {$res->reason}".($res->expires_at ? " (মেয়াদ: {$res->expires_at->toFormattedDateString()})" : ''),
                'icon' => 'lock',
                'severity' => 'danger',
            ]);
        }

        // Sort descending by timestamp
        return $events->sortByDesc('timestamp')->values()->toArray();
    }

    /**
     * Find Related Accounts sharing Network IP, Phone or Patterns.
     */
    public function getRelatedAccounts(User $user): Collection
    {
        $query = User::where('id', '!=', $user->id);

        $conditions = [];
        if (! empty($user->last_login_ip)) {
            $conditions[] = ['last_login_ip', '=', $user->last_login_ip];
        }
        if (! empty($user->phone)) {
            $conditions[] = ['phone', '=', $user->phone];
        }

        if (empty($conditions)) {
            return collect();
        }

        return $query->where(function ($q) use ($conditions) {
            foreach ($conditions as $cond) {
                $q->orWhere($cond[0], $cond[1], $cond[2]);
            }
        })
            ->select('id', 'name', 'username', 'email', 'phone', 'last_login_ip', 'status', 'created_at', 'risk_score')
            ->take(15)
            ->get();
    }

    /**
     * Apply a Temporary Restriction on User.
     */
    public function applyRestriction(
        User $targetUser,
        string $restrictionType,
        string $reason,
        User $admin,
        ?int $durationHours = null
    ): UserRestriction {
        return DB::transaction(function () use ($targetUser, $restrictionType, $reason, $admin, $durationHours) {
            $lockedUser = User::where('id', $targetUser->id)->lockForUpdate()->firstOrFail();
            app(SecurityOperationsCenterService::class)->assertSuperAdminProtection($lockedUser, 'restrict');

            $expiresAt = $durationHours ? Carbon::now()->addHours($durationHours) : null;

            $restriction = UserRestriction::create([
                'user_id' => $lockedUser->id,
                'restriction_type' => $restrictionType,
                'reason' => $reason,
                'risk_score' => $lockedUser->risk_score ?? 0,
                'expires_at' => $expiresAt,
                'created_by' => $admin->id,
                'is_active' => true,
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_user_restriction_applied',
                'entity_type' => UserRestriction::class,
                'entity_id' => $restriction->id,
                'new_values' => [
                    'target_user_id' => $lockedUser->id,
                    'restriction_type' => $restrictionType,
                    'reason' => $reason,
                    'duration_hours' => $durationHours,
                    'expires_at' => $expiresAt?->toIso8601String(),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $restriction;
        });
    }

    /**
     * Override an active restriction with mandatory justification.
     */
    public function overrideRestriction(UserRestriction $restriction, User $admin, string $justification): void
    {
        if (empty(trim($justification))) {
            throw new InvalidArgumentException('সীমাবদ্ধতা বাতিল করতে বাধ্যতামূলক কারণ/ব্যাখ্যা প্রদান আবশ্যক।');
        }

        DB::transaction(function () use ($restriction, $admin, $justification) {
            $lockedRestriction = UserRestriction::where('id', $restriction->id)->lockForUpdate()->firstOrFail();
            $lockedRestriction->override($admin, $justification);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_user_restriction_overridden',
                'entity_type' => UserRestriction::class,
                'entity_id' => $lockedRestriction->id,
                'new_values' => [
                    'restriction_id' => $lockedRestriction->id,
                    'override_reason' => $justification,
                    'target_user_id' => $lockedRestriction->user_id,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }
}
