<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

/**
 * এন্টারপ্রাইজ পেজ পলিসি:
 * ওনারশিপ, মাল্টি-রোল আরব্যাক এবং কাস্টম পারমিশনের কঠোর সার্ভার-সাইড এনফোর্সমেন্ট।
 * কোনো আনঅথরাইজড ইউজার বা ক্রস-পেজ এক্সেস সম্পূর্ণরূপে প্রতিরোধ করে।
 */
class PagePolicy
{
    /**
     * পেজ দেখতে পারবে কিনা (পাবলিক হলে সবাই, আনলিস্টেড/ড্রাফট হলে শুধুমাত্র অথরাইজড মেম্বার)
     */
    public function view(?User $user, Page $page): bool
    {
        if ($page->status === Page::STATUS_ACTIVE && $page->visibility === 'public') {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $page->hasPermission($user->id, 'page.view');
    }

    /**
     * পেজের তথ্য এডিট করতে পারবে কিনা
     */
    public function update(User $user, Page $page): bool
    {
        return $page->hasPermission($user->id, 'page.edit');
    }

    /**
     * পেজ ডিলিট করতে পারবে কিনা (শুধুমাত্র পেজ ওনার)
     */
    public function delete(User $user, Page $page): bool
    {
        return (int) $page->owner_id === (int) $user->id;
    }

    /**
     * পেজ সেটিংস পরিবর্তন করতে পারবে কিনা
     */
    public function manageSettings(User $user, Page $page): bool
    {
        return $page->hasPermission($user->id, 'page.settings');
    }

    /**
     * টিম মেম্বারদের ইনভাইট, রোল চেঞ্জ বা রিমুভ করতে পারবে কিনা
     */
    public function manageTeam(User $user, Page $page): bool
    {
        return $page->hasPermission($user->id, 'team.manage');
    }

    /**
     * পোস্ট তৈরি ও পাবলিশ করতে পারবে কিনা
     */
    public function createPost(User $user, Page $page): bool
    {
        return $page->hasPermission($user->id, 'posts.create');
    }

    /**
     * পোস্ট এডিট বা ডিলিট করতে পারবে কিনা
     */
    public function editPost(User $user, Page $page): bool
    {
        return $page->hasPermission($user->id, 'posts.edit');
    }

    /**
     * পোস্ট শিডিউল করতে পারবে কিনা
     */
    public function schedulePost(User $user, Page $page): bool
    {
        return $page->hasPermission($user->id, 'posts.schedule');
    }

    /**
     * কমেন্ট ও কনটেন্ট মডারেশন করতে পারবে কিনা
     */
    public function moderate(User $user, Page $page): bool
    {
        return $page->hasPermission($user->id, 'moderation.manage') ||
               $page->hasPermission($user->id, 'comments.moderate');
    }

    /**
     * পেজের ইনবক্স মেসেজ পরিচালনা করতে পারবে কিনা
     */
    public function manageMessages(User $user, Page $page): bool
    {
        return $page->hasPermission($user->id, 'messages.manage');
    }

    /**
     * পেজের অ্যানালিটিক্স ও ইনসাইটস দেখতে পারবে কিনা
     */
    public function viewAnalytics(User $user, Page $page): bool
    {
        return $page->hasPermission($user->id, 'analytics.view');
    }

    /**
     * ইভেন্ট তৈরি ও পরিচালনা করতে পারবে কিনা
     */
    public function manageEvents(User $user, Page $page): bool
    {
        return $page->hasPermission($user->id, 'events.manage');
    }

    /**
     * ওনারশিপ ট্রান্সফার করতে পারবে কিনা (শুধুমাত্র বর্তমান ওনার)
     */
    public function transferOwnership(User $user, Page $page): bool
    {
        return (int) $page->owner_id === (int) $user->id;
    }
}
