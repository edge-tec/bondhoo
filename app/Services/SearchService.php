<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;

/**
 * ইউনিভার্সাল সার্চ সার্ভিস:
 * ইউজার, পোস্ট, গ্রুপ এবং পেজজুড়ে সার্বজনীন সার্চ সম্পাদন করে।
 */
class SearchService
{
    /**
     * সার্চ কোয়েরি অনুযায়ী ফলাফল রিটার্ন করে।
     */
    public function search(string $query, string $type = 'all', ?User $viewer = null, int $limit = 10): array
    {
        $term = trim($query);
        if (empty($term)) {
            return [
                'query' => $term,
                'results' => [
                    'users' => [],
                    'posts' => [],
                    'groups' => [],
                    'pages' => [],
                ],
            ];
        }

        $results = [];

        // ১. ইউজার সার্চ
        if ($type === 'all' || $type === 'users') {
            $results['users'] = User::where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%");
            })
                ->with('profile')
                ->limit($limit)
                ->get()
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                    'avatar_url' => $u->profile?->avatar_url,
                    'bio' => $u->profile?->bio,
                ])
                ->values();
        }

        // ২. পোস্ট সার্চ (শুধুমাত্র পাবলিক পোস্ট)
        if ($type === 'all' || $type === 'posts') {
            $results['posts'] = Post::where('audience', 'public')
                ->where('content', 'like', "%{$term}%")
                ->with(['user.profile', 'media'])
                ->latest('id')
                ->limit($limit)
                ->get()
                ->map(fn (Post $p) => $p->toResponseArray($viewer))
                ->values();
        }

        // ৩. গ্রুপ সার্চ (শুধুমাত্র পাবলিক গ্রুপ)
        if ($type === 'all' || $type === 'groups') {
            $results['groups'] = Group::where('privacy', Group::PRIVACY_PUBLIC)
                ->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn (Group $g) => $g->toResponseArray($viewer))
                ->values();
        }

        // ৪. পেজ সার্চ
        if ($type === 'all' || $type === 'pages') {
            $results['pages'] = Page::where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%")
                    ->orWhere('bio', 'like', "%{$term}%");
            })
                ->limit($limit)
                ->get()
                ->map(fn (Page $p) => $p->toResponseArray($viewer))
                ->values();
        }

        return [
            'query' => $term,
            'type' => $type,
            'results' => $results,
        ];
    }
}
