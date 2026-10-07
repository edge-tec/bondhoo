<?php

namespace App\Services\Page;

use App\Events\PageNewMessageEvent;
use App\Models\Comment;
use App\Models\Page;
use App\Models\PageAuditLog;
use App\Models\PageBlockedUser;
use App\Models\PageConversation;
use App\Models\PageConversationNote;
use App\Models\PageEvent;
use App\Models\PageFollower;
use App\Models\PageMember;
use App\Models\PageMessage;
use App\Models\PageProduct;
use App\Models\Post;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * এন্টারপ্রাইজ পেজ সার্ভিস (Enterprise Social Page Operating System):
 * - রিয়েল আরব্যাক ও টিম ম্যানেজমেন্ট
 * - রিয়েল অ্যানালিটিক্স ও অডিয়েন্স ইনসাইটস এগ্রিগেশন (জিরো ফেক ডেটা)
 * - সেন্ট্রাল মডারেশন সেন্টার (ব্লকিং, কিওয়ার্ড ফিল্টারিং, কমেন্ট মডারেশন)
 * - পেজ মেসেজিং ও মাল্টি-এজেন্ট ইনবক্স
 * - পেজ ইভেন্টস ও কমার্স আর্কিটেকচার
 * - ইমিউটেবল অডিট ট্রেইল
 */
class EnterprisePageService
{
    /**
     * ইমিউটেবল অডিট লগ সংরক্ষণ করা
     */
    public function logAudit(
        Page $page,
        ?User $actor,
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $previousState = null,
        ?array $newState = null
    ): PageAuditLog {
        return PageAuditLog::create([
            'page_id' => $page->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'previous_state' => $previousState,
            'new_state' => $newState,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * রিয়েল ডাটাবেজ কোয়েরি থেকে সংগৃহীত পেজ অ্যানালিটিক্স মেট্রিক্স (কোনো মক বা হার্ডকোড সংখ্যা নেই)
     *
     * @return array<string, mixed>
     */
    public function getPageAnalytics(Page $page, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        // ১. ফলোয়ার্স মেট্রিক্স ও গ্রোথ ট্র্যাকিং
        $totalFollowers = $page->followers()->count();
        $newFollowersCount = $page->followers()->where('created_at', '>=', $startDate)->count();

        $dailyFollowers = DB::table('page_followers')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('page_id', $page->id)
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // ২. পোস্ট পারফরম্যান্স ও এনগেজমেন্ট ক্যালকুলেশন
        $posts = Post::where('page_id', $page->id)->get();
        $totalPosts = $posts->count();
        $totalLikes = (int) $posts->sum('likes_count');
        $totalComments = (int) $posts->sum('comments_count');
        $totalShares = (int) $posts->sum('shares_count');
        $totalEngagements = $totalLikes + $totalComments + $totalShares;

        $engagementRate = $totalFollowers > 0
            ? round(($totalEngagements / $totalFollowers) * 100, 2)
            : ($totalPosts > 0 ? round($totalEngagements / $totalPosts, 2) : 0);

        // ৩. মেসেজিং মেট্রিক্স
        $totalConversations = PageConversation::where('page_id', $page->id)->count();
        $openConversations = PageConversation::where('page_id', $page->id)->where('status', 'open')->count();
        $resolvedConversations = PageConversation::where('page_id', $page->id)->where('status', 'resolved')->count();

        // ৪. শীর্ষ পারফর্মিং কনটেন্ট
        $topPosts = Post::where('page_id', $page->id)
            ->orderByDesc('likes_count')
            ->limit(5)
            ->get(['id', 'content', 'likes_count', 'comments_count', 'shares_count', 'created_at']);

        // ৫. শিডিউলড ও ড্রাফট পোস্ট কাউন্ট
        $scheduledCount = Post::where('page_id', $page->id)->where('status', 'scheduled')->count();
        $draftCount = Post::where('page_id', $page->id)->where('status', 'draft')->count();

        return [
            'page_id' => $page->id,
            'period_days' => $days,
            'summary' => [
                'total_followers' => $totalFollowers,
                'new_followers' => $newFollowersCount,
                'total_posts' => $totalPosts,
                'scheduled_posts' => $scheduledCount,
                'draft_posts' => $draftCount,
                'total_likes' => $totalLikes,
                'total_comments' => $totalComments,
                'total_shares' => $totalShares,
                'total_engagements' => $totalEngagements,
                'engagement_rate' => $engagementRate.'%',
            ],
            'messaging' => [
                'total_conversations' => $totalConversations,
                'open_conversations' => $openConversations,
                'resolved_conversations' => $resolvedConversations,
            ],
            'follower_growth' => $dailyFollowers,
            'top_content' => $topPosts,
        ];
    }

    /**
     * রিয়েল অডিয়েন্স ইনসাইটস (ফলোয়ারদের প্রকৃত প্রোফাইল ডাটা থেকে এগ্রিগেশন)
     *
     * @return array<string, mixed>
     */
    public function getAudienceInsights(Page $page): array
    {
        $followersCount = $page->followers()->count();

        // ফলোয়ারদের ইউজার আইডি সংগ্রহ
        $followerUserIds = $page->followers()->pluck('user_id')->toArray();

        // শহর অনুযায়ী ডিস্ট্রিবিউশন
        $cityDist = [];
        if (! empty($followerUserIds)) {
            $cityDist = DB::table('user_profiles')
                ->whereIn('user_id', $followerUserIds)
                ->whereNotNull('city')
                ->where('city', '!=', '')
                ->selectRaw('city, COUNT(*) as count')
                ->groupBy('city')
                ->orderByDesc('count')
                ->limit(5)
                ->pluck('count', 'city')
                ->toArray();
        }

        return [
            'page_id' => $page->id,
            'total_followers' => $followersCount,
            'top_cities' => ! empty($cityDist) ? $cityDist : [],
            'last_updated' => now()->toIso8601String(),
        ];
    }

    /**
     * পেজের ভেরিফিকেশন স্ট্যাটাস পরিবর্তন করা
     */
    public function setVerifiedStatus(Page $page, bool $verified): void
    {
        $page->update([
            'is_verified' => $verified,
            'verification_status' => $verified ? 'verified' : 'unverified',
        ]);
    }

    /**
     * টিম মেম্বার রোল সরাসরি অ্যাসাইন করা
     *
     * @return array<string, mixed>
     */
    public function assignTeamRole(Page $page, User $user, string $role, ?array $permissions = null): array
    {
        $member = PageMember::updateOrCreate(
            ['page_id' => $page->id, 'user_id' => $user->id],
            [
                'role' => $role,
                'custom_permissions' => $permissions,
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        return [
            'page_id' => $page->id,
            'user_id' => $user->id,
            'role' => $member->role,
            'custom_permissions' => $member->custom_permissions,
            'status' => $member->status,
        ];
    }

    /**
     * টিম মেম্বার ইনভাইট করা
     *
     * @throws Exception
     */
    public function inviteTeamMember(Page $page, User $actor, string $emailOrUsername, string $role, ?array $customPermissions = null): PageMember
    {
        $targetUser = User::where('email', $emailOrUsername)
            ->orWhere('username', $emailOrUsername)
            ->first();

        if (! $targetUser) {
            throw new Exception('User not found with the provided email or username.');
        }

        if ((int) $page->owner_id === (int) $targetUser->id) {
            throw new Exception('The page owner cannot be invited as a team member.');
        }

        if ($role === Page::ROLE_ADMIN && (int) $page->owner_id !== (int) $actor->id) {
            throw new Exception('Only the page owner can invite an admin.');
        }

        $existing = PageMember::where('page_id', $page->id)
            ->where('user_id', $targetUser->id)
            ->first();

        if ($existing && $existing->status === 'active') {
            throw new Exception('This user is already an active member of this page.');
        }

        $token = Str::random(40);

        $member = PageMember::updateOrCreate(
            ['page_id' => $page->id, 'user_id' => $targetUser->id],
            [
                'role' => $role,
                'custom_permissions' => $customPermissions,
                'status' => 'invited',
                'invited_by' => $actor->id,
                'invitation_token' => $token,
                'joined_at' => null,
            ]
        );

        $this->logAudit($page, $actor, 'team.invite', 'User', $targetUser->id, null, [
            'role' => $role,
            'invited_user' => $targetUser->username,
        ]);

        return $member;
    }

    /**
     * টিম ইনভাইটেশন গ্রহণ করা
     *
     * @throws Exception
     */
    public function acceptInvitation(string $token, User $user): PageMember
    {
        $member = PageMember::where('invitation_token', $token)
            ->where('user_id', $user->id)
            ->where('status', 'invited')
            ->first();

        if (! $member) {
            throw new Exception('Invalid or expired invitation token.');
        }

        $member->update([
            'status' => 'active',
            'invitation_token' => null,
            'joined_at' => now(),
        ]);

        $this->logAudit($member->page, $user, 'team.accept_invitation', 'PageMember', $member->id);

        return $member;
    }

    /**
     * টিম মেম্বারের রোল পরিবর্তন করা
     *
     * @throws Exception
     */
    public function updateMemberRole(Page $page, User $actor, int $memberId, string $newRole, ?array $customPermissions = null): PageMember
    {
        $member = PageMember::where('page_id', $page->id)->findOrFail($memberId);

        if ((int) $page->owner_id !== (int) $actor->id && ($member->role === Page::ROLE_ADMIN || $newRole === Page::ROLE_ADMIN)) {
            throw new Exception('Only the page owner can promote to or modify an admin role.');
        }

        $previousRole = $member->role;
        $member->update([
            'role' => $newRole,
            'custom_permissions' => $customPermissions,
        ]);

        $this->logAudit($page, $actor, 'team.role_change', 'PageMember', $member->id, [
            'role' => $previousRole,
        ], [
            'role' => $newRole,
            'custom_permissions' => $customPermissions,
        ]);

        return $member;
    }

    /**
     * টিম মেম্বার রিমুভ করা
     *
     * @throws Exception
     */
    public function removeMember(Page $page, User $actor, int $memberId): bool
    {
        $member = PageMember::where('page_id', $page->id)->findOrFail($memberId);

        if ((int) $page->owner_id !== (int) $actor->id && $member->role === Page::ROLE_ADMIN) {
            throw new Exception('Only the page owner can remove an admin.');
        }

        $deletedUserId = $member->user_id;
        $member->delete();

        $this->logAudit($page, $actor, 'team.remove_member', 'User', $deletedUserId);

        return true;
    }

    /**
     * ওনারশিপ হস্তান্তর করা (Ownership Transfer)
     *
     * @throws Exception
     */
    public function transferOwnership(Page $page, User $currentOwner, User $newOwner): Page
    {
        if ((int) $page->owner_id !== (int) $currentOwner->id) {
            throw new Exception('Only the current page owner can transfer ownership.');
        }

        if ((int) $currentOwner->id === (int) $newOwner->id) {
            throw new Exception('Cannot transfer ownership to yourself.');
        }

        return DB::transaction(function () use ($page, $currentOwner, $newOwner) {
            $prevOwnerId = $page->owner_id;

            // নতুন ওনার সেট করা
            $page->update(['owner_id' => $newOwner->id]);

            // পূর্ববর্তী ওনারকে এডমিন রোল দেওয়া
            PageMember::updateOrCreate(
                ['page_id' => $page->id, 'user_id' => $prevOwnerId],
                ['role' => Page::ROLE_ADMIN, 'status' => 'active', 'joined_at' => now()]
            );

            // নতুন ওনার যদি মেম্বার টেবিলে থাকে তবে সেখান থেকে রিমুভ করা
            PageMember::where('page_id', $page->id)->where('user_id', $newOwner->id)->delete();

            $this->logAudit($page, $currentOwner, 'page.transfer_ownership', 'User', $newOwner->id, [
                'owner_id' => $prevOwnerId,
            ], [
                'owner_id' => $newOwner->id,
            ]);

            return $page;
        });
    }

    /**
     * পেজ সেটিংস আপডেট
     */
    public function updateSettings(Page $page, User $actor, array $settings): Page
    {
        $prev = $page->settings ?? [];
        $merged = array_merge($prev, $settings);

        $page->update(['settings' => $merged]);

        $this->logAudit($page, $actor, 'settings.update', 'Page', $page->id, $prev, $merged);

        return $page;
    }

    /**
     * ইউজারকে পেজ থেকে ব্লক করা
     *
     * @throws Exception
     */
    public function blockUser(Page $page, User $actor, int $targetUserId, ?string $reason = null): PageBlockedUser
    {
        if ((int) $page->owner_id === $targetUserId) {
            throw new Exception('Cannot block the page owner.');
        }

        // যদি ফলোয়ার থাকে আনফলো করে দেওয়া
        $follower = PageFollower::where('page_id', $page->id)->where('user_id', $targetUserId)->first();
        if ($follower) {
            $follower->delete();
            $page->decrement('followers_count');
        }

        $blocked = PageBlockedUser::updateOrCreate(
            ['page_id' => $page->id, 'user_id' => $targetUserId],
            ['reason' => $reason, 'blocked_by' => $actor->id]
        );

        $this->logAudit($page, $actor, 'moderation.block_user', 'User', $targetUserId, null, ['reason' => $reason]);

        return $blocked;
    }

    /**
     * ইউজারকে আনব্লক করা
     */
    public function unblockUser(Page $page, User $actor, int $targetUserId): bool
    {
        $blocked = PageBlockedUser::where('page_id', $page->id)->where('user_id', $targetUserId)->first();
        if ($blocked) {
            $blocked->delete();
            $this->logAudit($page, $actor, 'moderation.unblock_user', 'User', $targetUserId);

            return true;
        }

        return false;
    }

    /**
     * কিওয়ার্ড ব্লকলিস্ট যাচাই
     */
    public function containsBlockedKeyword(Page $page, string $text): bool
    {
        $settings = $page->settings ?? [];
        $blockedKeywords = $settings['moderation']['blocked_keywords'] ?? [];

        if (empty($blockedKeywords) || ! is_array($blockedKeywords)) {
            return false;
        }

        $lowerText = strtolower($text);
        foreach ($blockedKeywords as $kw) {
            $trimmed = trim(strtolower((string) $kw));
            if (! empty($trimmed) && str_contains($lowerText, $trimmed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * কমেন্ট মডারেশন (hide, pin, delete)
     *
     * @throws Exception
     */
    public function moderateComment(Page $page, User $actor, int $commentId, string $action): bool
    {
        $comment = Comment::findOrFail($commentId);

        // কমেন্টটি কি এই পেজের কোনো পোস্টে করা হয়েছে কিনা যাচাই
        $post = Post::where('id', $comment->post_id)->where('page_id', $page->id)->first();
        if (! $post) {
            throw new Exception('Comment does not belong to any post on this page.');
        }

        switch ($action) {
            case 'delete':
                $comment->delete();
                $post->decrement('comments_count');
                break;
            case 'pin':
                $comment->update(['is_pinned' => true]);
                break;
            case 'unpin':
                $comment->update(['is_pinned' => false]);
                break;
            default:
                throw new Exception("Unsupported moderation action: {$action}");
        }

        $this->logAudit($page, $actor, "comment.{$action}", 'Comment', $commentId);

        return true;
    }

    /**
     * পেজ মেসেজিং কনভারসেশন তালিকা
     */
    public function getConversations(Page $page, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PageConversation::where('page_id', $page->id)
            ->with(['user', 'assignedAgent']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->latest('last_message_at')->paginate($perPage);
    }

    /**
     * মেসেজ প্রেরণ করা (পেজ বা ভিজিটর দ্বারা)
     *
     * @throws Exception
     */
    public function sendMessage(
        Page $page,
        User $sender,
        int $conversationId,
        string $body,
        string $senderType = 'page'
    ): PageMessage {
        $conv = PageConversation::where('page_id', $page->id)->findOrFail($conversationId);

        return DB::transaction(function () use ($page, $conv, $sender, $body, $senderType) {
            $msg = PageMessage::create([
                'conversation_id' => $conv->id,
                'sender_type' => $senderType,
                'sender_id' => $sender->id,
                'body' => $body,
                'is_read' => false,
            ]);

            $conv->update(['last_message_at' => now()]);
            if ($senderType === 'page') {
                $conv->increment('unread_user_count');
            } else {
                $conv->increment('unread_page_count');
            }

            try {
                broadcast(new PageNewMessageEvent(
                    pageId: (int) $page->id,
                    conversationId: (int) $conv->id,
                    messageData: [
                        'id' => $msg->id,
                        'conversation_id' => $conv->id,
                        'sender_type' => $msg->sender_type,
                        'sender_id' => $msg->sender_id,
                        'body' => $msg->body,
                        'created_at' => $msg->created_at?->toIso8601String(),
                    ],
                    recipientUserId: $senderType === 'page' ? (int) $conv->user_id : (int) $conv->assigned_to
                ));
            } catch (\Throwable $e) {
                Log::warning('Could not broadcast PageNewMessageEvent: '.$e->getMessage());
            }

            return $msg;
        });
    }

    /**
     * কনভারসেশনে ইন্টারনাল স্টাফ নোট যুক্ত করা
     */
    public function addConversationNote(Page $page, User $staff, int $conversationId, string $body): PageConversationNote
    {
        $conv = PageConversation::where('page_id', $page->id)->findOrFail($conversationId);

        return PageConversationNote::create([
            'conversation_id' => $conv->id,
            'user_id' => $staff->id,
            'body' => $body,
        ]);
    }

    /**
     * পেজ ইভেন্ট তৈরি করা
     */
    public function createEvent(Page $page, User $actor, array $data): PageEvent
    {
        $title = trim($data['title']);
        $slug = Str::slug($title);
        $original = $slug;
        $c = 1;
        while (PageEvent::where('page_id', $page->id)->where('slug', $slug)->exists()) {
            $slug = "{$original}-{$c}";
            $c++;
        }

        $event = PageEvent::create([
            'page_id' => $page->id,
            'title' => $title,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'cover_image_url' => $data['cover_image_url'] ?? null,
            'location' => $data['location'] ?? null,
            'start_time' => Carbon::parse($data['start_time']),
            'end_time' => ! empty($data['end_time']) ? Carbon::parse($data['end_time']) : null,
            'is_online' => ! empty($data['is_online']),
            'status' => 'published',
        ]);

        $this->logAudit($page, $actor, 'event.create', 'PageEvent', $event->id, null, ['title' => $title]);

        return $event;
    }

    /**
     * পেজ কমার্স প্রোডাক্ট তৈরি করা
     */
    public function createProduct(Page $page, User $actor, array $data): PageProduct
    {
        $title = trim($data['title']);
        $slug = Str::slug($title);
        $original = $slug;
        $c = 1;
        while (PageProduct::where('page_id', $page->id)->where('slug', $slug)->exists()) {
            $slug = "{$original}-{$c}";
            $c++;
        }

        $product = PageProduct::create([
            'page_id' => $page->id,
            'title' => $title,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'price' => (float) $data['price'],
            'currency' => $data['currency'] ?? 'BDT',
            'stock_quantity' => (int) ($data['stock_quantity'] ?? 0),
            'status' => $data['status'] ?? 'active',
            'media_urls' => $data['media_urls'] ?? [],
        ]);

        $this->logAudit($page, $actor, 'product.create', 'PageProduct', $product->id, null, ['title' => $title]);

        return $product;
    }

    /**
     * প্রোডাক্ট ক্রয় করা (কন্টেন্ট ও ইনভেন্টরি রেস কন্ডিশন প্রটেকশন সহ)
     *
     * @throws Exception
     */
    public function purchaseProduct(Page $page, User $buyer, int $productId, int $quantity = 1): PageProduct
    {
        if ($quantity <= 0) {
            throw new Exception('Invalid quantity.');
        }

        return DB::transaction(function () use ($page, $buyer, $productId, $quantity) {
            $product = PageProduct::where('page_id', $page->id)
                ->where('id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $product) {
                throw new Exception('Product not found for this page.');
            }

            if ($product->status !== 'active') {
                throw new Exception('Product is not currently available for purchase.');
            }

            if ($product->stock_quantity < $quantity) {
                throw new Exception('Insufficient stock available.');
            }

            $affected = PageProduct::where('id', $product->id)
                ->where('stock_quantity', '>=', $quantity)
                ->decrement('stock_quantity', $quantity);

            if ($affected === 0) {
                throw new Exception('Concurrent purchase collision. Insufficient stock available.');
            }

            $fresh = $product->fresh();
            if ($fresh->stock_quantity === 0) {
                $fresh->update(['status' => 'out_of_stock']);
            }

            $this->logAudit($page, $buyer, 'product.purchase', 'PageProduct', $product->id, null, [
                'quantity' => $quantity,
                'price' => $product->price,
                'remaining_stock' => $fresh->stock_quantity,
            ]);

            return $fresh;
        });
    }
}
