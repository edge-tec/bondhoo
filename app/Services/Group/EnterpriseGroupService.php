<?php

namespace App\Services\Group;

use App\Models\Group;

class EnterpriseGroupService
{
    /**
     * Configure group membership screening questions.
     *
     * @param  string[]  $questions
     */
    public function setMembershipQuestions(Group $group, array $questions): Group
    {
        $settings = $group->settings ?? [];
        $settings['membership_questions'] = $questions;

        $group->update(['settings' => $settings]);

        return $group;
    }

    /**
     * Configure community rules engine.
     *
     * @param  array<int, array{title: string, description: string}>  $rules
     */
    public function setCommunityRules(Group $group, array $rules): Group
    {
        $settings = $group->settings ?? [];
        $settings['rules'] = $rules;

        $group->update(['settings' => $settings]);

        return $group;
    }

    /**
     * Flag post for moderation queue if it contains blacklisted keywords.
     */
    public function checkKeywordAlerts(Group $group, string $content): array
    {
        $settings = $group->settings ?? [];
        $keywords = $settings['keyword_alerts'] ?? ['scam', 'spam', 'fake', 'টাকা', 'জুয়া'];

        $detected = [];
        foreach ($keywords as $kw) {
            if (str_contains(mb_strtolower($content), mb_strtolower($kw))) {
                $detected[] = $kw;
            }
        }

        return [
            'flagged' => ! empty($detected),
            'detected_keywords' => $detected,
            'requires_admin_review' => ! empty($detected),
        ];
    }
}
