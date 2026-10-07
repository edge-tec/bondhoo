<?php

use App\Models\Group;
use App\Models\Page;
use App\Models\PageConversation;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

/**
 * WebSocket চ্যানেল অথরাইজেশন রুলস:
 * প্রতিটি প্রাইভেট বা প্রেজেন্স চ্যানেলে কারা যুক্ত হতে পারবে তা এখানে নির্ধারিত থাকে।
 */

// ১. ইউজারের নিজস্ব প্রাইভেট চ্যানেল (নোটিফিকেশন ও পার্সোনাল মেসেজ)
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// ২. প্রাইভেট চ্যাট/কনভার্সন চ্যানেল (শুধুমাত্র কনভার্সনের সদস্যরা যুক্ত হতে পারবেন)
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    if (! $user) {
        return false;
    }

    return DB::table('conversation_participants')
        ->where('conversation_id', (int) $conversationId)
        ->where('user_id', (int) $user->id)
        ->exists();
});

// ৩. লাইভ প্রেজেন্স চ্যানেল (অনলাইন ব্যবহারকারীদের তালিকা শেয়ারিং)
Broadcast::channel('presence-users', function ($user) {
    return [
        'id' => $user->id,
        'username' => $user->username,
        'name' => $user->name,
        'avatar_url' => $user->profile?->avatar_url,
    ];
});

// ৪. WebRTC কলিং চ্যানেল (শুধুমাত্র কনভার্সনের সদস্যরা যুক্ত হতে পারবেন)
Broadcast::channel('call.{conversationId}', function ($user, $conversationId) {
    if (! $user) {
        return false;
    }

    return DB::table('conversation_participants')
        ->where('conversation_id', (int) $conversationId)
        ->where('user_id', (int) $user->id)
        ->exists();
});

// ৫. লাইভ স্ট্রিমিং চ্যানেল (পাবলিক চ্যানেল — দর্শক ও ক্রিয়েটর সকলেই যুক্ত হতে পারেন)
Broadcast::channel('live.{channelId}', function () {
    return true;
});

Broadcast::channel('stream.{channelId}', function () {
    return true;
});

// ৬. এন্টারপ্রাইজ সোশ্যাল পেজ ইনবক্স চ্যানেল (শুধুমাত্র পেজ ওনার বা অনুমোদিত টিম মেম্বার)
Broadcast::channel('page.{pageId}.inbox', function ($user, $pageId) {
    if (! $user) {
        return false;
    }

    $page = Page::find((int) $pageId);
    if (! $page) {
        return false;
    }

    return $page->hasPermission($user->id, 'messages.manage');
});

// ৭. পেজ কনভার্সন চ্যানেল (গ্রাহক অথবা পেজের অনুমোদিত টিম মেম্বার)
Broadcast::channel('page.{pageId}.conversation.{conversationId}', function ($user, $pageId, $conversationId) {
    if (! $user) {
        return false;
    }

    $conv = PageConversation::where('page_id', (int) $pageId)->find((int) $conversationId);
    if (! $conv) {
        return false;
    }

    if ((int) $conv->user_id === (int) $user->id) {
        return true;
    }

    $page = $conv->page;

    return $page && $page->hasPermission($user->id, 'messages.manage');
});

// ৮. গ্রুপ চ্যানেল (গ্রুপ সদস্য বা পাবলিক গ্রুপের জন্য)
Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    if (! $user) {
        return false;
    }

    $group = Group::find((int) $groupId);
    if (! $group) {
        return false;
    }

    if ($group->isPublic()) {
        return true;
    }

    return $group->hasMember((int) $user->id);
});

// ৯. গ্রুপ অ্যাডমিন ও মডারেশন চ্যানেল (শুধুমাত্র ওনার, অ্যাডমিন ও মডারেটর)
Broadcast::channel('group.{groupId}.admin', function ($user, $groupId) {
    if (! $user) {
        return false;
    }

    $group = Group::find((int) $groupId);
    if (! $group) {
        return false;
    }

    return $group->isModerator((int) $user->id);
});

// ১০. রিলস চ্যানেলসমূহ (লাইভ কমেন্ট, রিঅ্যাকশন ও এক্সপায়ারেশন ইভেন্ট)
Broadcast::channel('reel.{reelId}', function () {
    return true;
});

Broadcast::channel('reels', function () {
    return true;
});

// ১১. স্টোরিজ চ্যানেল (স্টোরি ক্রিয়েশন ও এক্সপায়ারেশন ইভেন্ট)
Broadcast::channel('stories', function () {
    return true;
});
