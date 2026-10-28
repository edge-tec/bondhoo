<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Post;
use App\Models\Report;
use App\Models\SecurityEvent;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Security\AbuseDetectionEngineService;
use Illuminate\Support\Str;

class GlobalInvestigationSearchService
{
    public function __construct(
        protected EnterpriseAccessControlService $accessControl,
        protected AbuseDetectionEngineService $abuseEngine
    ) {}

    /**
     * Role-Aware Unified Search across all platform entities.
     */
    public function search(string $query, User $admin, array $categories = ['all'], int $limit = 10): array
    {
        $term = trim($query);
        if (strlen($term) < 2) {
            return [
                'query' => $term,
                'results' => [],
                'total_matches' => 0,
            ];
        }

        // Sanitize search term to prevent wildcard injection
        $sanitizedTerm = str_replace(['%', '_'], ['\%', '\_'], $term);
        $searchWildcard = "%{$sanitizedTerm}%";

        $results = [];
        $totalMatches = 0;

        $searchAll = in_array('all', $categories, true);

        // 1. Users
        if ($searchAll || in_array('users', $categories, true)) {
            $userMatches = User::where('name', 'like', $searchWildcard)
                ->orWhere('email', 'like', $searchWildcard)
                ->orWhere('username', 'like', $searchWildcard)
                ->orWhere('phone', 'like', $searchWildcard)
                ->select('id', 'name', 'email', 'username', 'status', 'created_at', 'risk_score')
                ->take($limit)
                ->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'type' => 'user',
                    'title' => $u->name.' (@'.$u->username.')',
                    'subtitle' => $u->email,
                    'badge' => $u->status,
                    'risk_score' => $u->risk_score,
                    'link' => "/admin/users/{$u->id}",
                ]);

            if ($userMatches->isNotEmpty()) {
                $results['users'] = $userMatches;
                $totalMatches += $userMatches->count();
            }
        }

        // 2. Posts
        if ($searchAll || in_array('posts', $categories, true)) {
            $postMatches = Post::where('content', 'like', $searchWildcard)
                ->with('user:id,name,username')
                ->latest('id')
                ->take($limit)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'type' => 'post',
                    'title' => 'পোস্ট: '.Str::limit($p->content, 60),
                    'subtitle' => 'লেখক: '.($p->user?->name ?? 'Unknown'),
                    'badge' => $p->status,
                    'link' => "/posts/{$p->id}",
                ]);

            if ($postMatches->isNotEmpty()) {
                $results['posts'] = $postMatches;
                $totalMatches += $postMatches->count();
            }
        }

        // 3. Groups / Communities
        if ($searchAll || in_array('groups', $categories, true)) {
            $groupMatches = Group::where('name', 'like', $searchWildcard)
                ->orWhere('description', 'like', $searchWildcard)
                ->take($limit)
                ->get()
                ->map(fn ($g) => [
                    'id' => $g->id,
                    'type' => 'group',
                    'title' => $g->name,
                    'subtitle' => Str::limit($g->description, 80),
                    'badge' => 'community',
                    'link' => "/groups/{$g->slug}",
                ]);

            if ($groupMatches->isNotEmpty()) {
                $results['groups'] = $groupMatches;
                $totalMatches += $groupMatches->count();
            }
        }

        // 4. Moderation Reports (requires content.moderate or admin)
        if (($searchAll || in_array('reports', $categories, true)) && $this->accessControl->hasPermission($admin, 'content.moderate')) {
            $reportMatches = Report::where('reason', 'like', $searchWildcard)
                ->orWhere('details', 'like', $searchWildcard)
                ->latest('id')
                ->take($limit)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'type' => 'report',
                    'title' => "রিপোর্ট #{$r->id}: {$r->reason}",
                    'subtitle' => Str::limit($r->details, 70),
                    'badge' => $r->priority,
                    'link' => "/admin?tab=moderation&report={$r->id}",
                ]);

            if ($reportMatches->isNotEmpty()) {
                $results['reports'] = $reportMatches;
                $totalMatches += $reportMatches->count();
            }
        }

        // 5. Security Incidents (requires security.view)
        if (($searchAll || in_array('security', $categories, true)) && $this->accessControl->hasPermission($admin, 'security.view')) {
            $securityMatches = SecurityEvent::where('event_type', 'like', $searchWildcard)
                ->orWhere('ip_address', 'like', $searchWildcard)
                ->latest('id')
                ->take($limit)
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'type' => 'security',
                    'title' => "সিকিউরিটি: {$s->event_type}",
                    'subtitle' => "আইপি: {$s->ip_address}",
                    'badge' => $s->severity,
                    'link' => '/admin?tab=security',
                ]);

            if ($securityMatches->isNotEmpty()) {
                $results['security'] = $securityMatches;
                $totalMatches += $securityMatches->count();
            }
        }

        // 6. Support Tickets
        if ($searchAll || in_array('support', $categories, true)) {
            $ticketMatches = SupportTicket::where('subject', 'like', $searchWildcard)
                ->orWhere('ticket_number', 'like', $searchWildcard)
                ->orWhere('guest_email', 'like', $searchWildcard)
                ->latest('id')
                ->take($limit)
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'type' => 'support',
                    'title' => "টিকেট [{$t->ticket_number}]: {$t->subject}",
                    'subtitle' => $t->guest_email ?? 'User ID: '.$t->user_id,
                    'badge' => $t->status,
                    'link' => '/admin?tab=support',
                ]);

            if ($ticketMatches->isNotEmpty()) {
                $results['support'] = $ticketMatches;
                $totalMatches += $ticketMatches->count();
            }
        }

        // 7. Audit Logs (requires audit.view)
        if (($searchAll || in_array('audit', $categories, true)) && $this->accessControl->hasPermission($admin, 'audit.view')) {
            $auditMatches = AuditLog::where('action', 'like', $searchWildcard)
                ->orWhere('ip_address', 'like', $searchWildcard)
                ->latest('id')
                ->take($limit)
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'type' => 'audit',
                    'title' => "অডিট: {$a->action}",
                    'subtitle' => "আইপি: {$a->ip_address}",
                    'badge' => 'audit',
                    'link' => '/admin?tab=audit',
                ]);

            if ($auditMatches->isNotEmpty()) {
                $results['audit'] = $auditMatches;
                $totalMatches += $auditMatches->count();
            }
        }

        return [
            'query' => $term,
            'total_matches' => $totalMatches,
            'results' => $results,
        ];
    }

    /**
     * Generate a Complete Investigation Dossier for a Target User.
     */
    public function generateUserDossier(int $userId): array
    {
        $user = User::with(['profile', 'roles', 'activeRestrictions'])->findOrFail($userId);

        $risk = $this->abuseEngine->evaluateUserRisk($user);
        $timeline = $this->abuseEngine->getUserInvestigationTimeline($user);
        $relatedAccounts = $this->abuseEngine->getRelatedAccounts($user);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->status,
                'is_legal_hold' => (bool) $user->is_legal_hold,
                'created_at' => $user->created_at?->toIso8601String(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'last_login_ip' => $user->last_login_ip,
                'roles' => $user->roles->pluck('name'),
            ],
            'risk_assessment' => $risk,
            'active_restrictions' => $user->activeRestrictions,
            'investigation_timeline' => $timeline,
            'related_accounts' => $relatedAccounts,
            'content_summary' => [
                'total_posts' => $user->posts()->count(),
                'total_comments' => Comment::where('user_id', $user->id)->count(),
                'reports_against_count' => Report::where('reportable_type', User::class)->where('reportable_id', $user->id)->count(),
            ],
        ];
    }
}
