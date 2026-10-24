<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

/**
 * এন্টারপ্রাইজ গ্রুপ পলিসি:
 * গ্রুপ ডেটা, রোল-বেসড এক্সেস কন্ট্রোল (RBAC), প্রাইভেসি এবং মাল্টিমিডিয়া নিরাপত্তার সার্ভার-সাইড এনফোর্সমেন্ট।
 */
class GroupPolicy
{
    /**
     * গ্রুপ প্রোফাইল ও বিবরণ দেখতে পারবে কিনা
     */
    public function view(?User $user, Group $group): bool
    {
        return $group->canView($user);
    }

    /**
     * গ্রুপ সেটিংস আপডেট করতে পারবে কিনা
     */
    public function update(User $user, Group $group): bool
    {
        return $group->hasPermission($user->id, 'manage_settings');
    }

    /**
     * গ্রুপ ডিলিট করতে পারবে কিনা (শুধুমাত্র গ্রুপের ওনার)
     */
    public function delete(User $user, Group $group): bool
    {
        return $group->isOwner($user->id);
    }

    /**
     * গ্রুপের ওনারশিপ হস্তান্তর করতে পারবে কিনা
     */
    public function transferOwnership(User $user, Group $group): bool
    {
        return $group->isOwner($user->id);
    }

    /**
     * গ্রুপে নতুন পোস্ট তৈরি করতে পারবে কিনা
     */
    public function post(User $user, Group $group): bool
    {
        return $group->canPost($user->id);
    }

    /**
     * গ্রুপে নতুন সদস্য ইনভাইট করতে পারবে কিনা
     */
    public function invite(User $user, Group $group): bool
    {
        return $group->hasPermission($user->id, 'invite_members') || $group->isAdmin($user->id);
    }

    /**
     * মেম্বার পরিচালনা ও রোল পরিবর্তন করতে পারবে কিনা
     */
    public function manageMembers(User $user, Group $group): bool
    {
        return $group->hasPermission($user->id, 'manage_members');
    }

    /**
     * মেম্বারশিপ রিকোয়েস্ট অনুমোদন বা বাতিল করতে পারবে কিনা
     */
    public function approveMembers(User $user, Group $group): bool
    {
        return $group->hasPermission($user->id, 'approve_members');
    }

    /**
     * পোস্ট ও কন্টেন্ট মডারেশন করতে পারবে কিনা
     */
    public function moderate(User $user, Group $group): bool
    {
        return $group->isModerator($user->id);
    }

    /**
     * গ্রুপের ফাইল দেখতে পারবে কিনা (প্রাইভেট গ্রুপে সক্রিয় মেম্বার আবশ্যক)
     */
    public function viewFiles(?User $user, Group $group): bool
    {
        if ($group->isPublic()) {
            return true;
        }

        return $user !== null && $group->hasMember($user->id);
    }

    /**
     * গ্রুপের পোল দেখতে পারবে কিনা
     */
    public function viewPolls(?User $user, Group $group): bool
    {
        if ($group->isPublic()) {
            return true;
        }

        return $user !== null && $group->hasMember($user->id);
    }

    /**
     * গ্রুপের ইভেন্ট দেখতে পারবে কিনা
     */
    public function viewEvents(?User $user, Group $group): bool
    {
        if ($group->isPublic()) {
            return true;
        }

        return $user !== null && $group->hasMember($user->id);
    }
}
