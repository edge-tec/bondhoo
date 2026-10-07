<?php

namespace App\Services;

use App\Events\GroupAnnouncementPublishedEvent;
use App\Events\GroupMemberJoinedEvent;
use App\Events\GroupPollVotedEvent;
use App\Events\GroupPostCreatedEvent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Group;
use App\Models\GroupAnnouncement;
use App\Models\GroupBookmark;
use App\Models\GroupDiscussion;
use App\Models\GroupDiscussionReply;
use App\Models\GroupEvent;
use App\Models\GroupEventAttendee;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\GroupMemberBadge;
use App\Models\GroupMemberStrike;
use App\Models\GroupModerationAction;
use App\Models\GroupPoll;
use App\Models\GroupPollOption;
use App\Models\GroupPollVote;
use App\Models\GroupQuestion;
use App\Models\GroupQuestionAnswer;
use App\Models\GroupReport;
use App\Models\GroupRule;
use App\Models\GroupSubscription;
use App\Models\Post;
use App\Models\User;
use App\Services\Group\EnterpriseGroupService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * এন্টারপ্রাইজ গ্রুপ ও কমিউনিটি অপারেটিং সিস্টেম সার্ভিস V2:
 * পূর্ণাঙ্গ সোশ্যাল কমিউনিটি প্ল্যাটফর্ম আর্কিটেকচার।
 * গ্রুপ তৈরি, ডিসকভারি, আরব্যাক, পোস্ট অনুমোদন, ডিসকাশন, পোল, ইভেন্ট, মডারেশন ও রিয়েল-টাইম কার্যক্রম পরিচালনা করে।
 */
class GroupService
{
    public function __construct(
        protected EnterpriseGroupService $enterpriseGroupService
    ) {}

    /**
     * নতুন গ্রুপ তৈরি করা এবং ওনার ও গ্রুপ চ্যাট কনভার্সন ইনিশিয়ালাইজ করা।
     */
    public function createGroup(User $creator, array $data): Group
    {
        $name = trim($data['name']);
        $slug = Str::slug($name);

        // ইউনিক স্লাগ নিশ্চিত করা
        $originalSlug = $slug;
        $counter = 1;
        while (Group::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        $username = isset($data['username']) ? Str::slug($data['username']) : null;
        if ($username && Group::where('username', $username)->exists()) {
            $username = "{$username}-{$counter}";
        }

        return DB::transaction(function () use ($creator, $data, $name, $slug, $username) {
            // ১. গ্রুপ চ্যাট কনভার্সন তৈরি
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_GROUP,
                'title' => $name,
                'creator_id' => $creator->id,
                'description' => $data['description'] ?? null,
                'settings' => [
                    'is_group_chat' => true,
                    'allow_member_invites' => true,
                ],
            ]);

            // ওনারকে কনভার্সনে অ্যাডমিন হিসেবে যুক্ত করা
            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $creator->id,
                'role' => 'admin',
                'last_read_at' => now(),
            ]);

            // ২. গ্রুপ রেকর্ড তৈরি
            $group = Group::create([
                'name' => $name,
                'slug' => $slug,
                'username' => $username,
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? 'General',
                'subcategory' => $data['subcategory'] ?? null,
                'tags' => $data['tags'] ?? null,
                'group_type' => $data['group_type'] ?? 'general',
                'community_type' => $data['community_type'] ?? ($data['privacy'] ?? Group::COMMUNITY_INTEREST),
                'language' => $data['language'] ?? 'en',
                'location' => $data['location'] ?? null,
                'country' => $data['country'] ?? null,
                'privacy' => $data['privacy'] ?? Group::PRIVACY_PUBLIC,
                'official_status' => $data['official_status'] ?? 'standard',
                'health_score' => 100.00,
                'membership_approval_mode' => $data['membership_approval_mode'] ?? Group::APPROVAL_ANYONE,
                'post_approval_mode' => $data['post_approval_mode'] ?? Group::POST_APPROVAL_AUTO,
                'cover_image_url' => $data['cover_image_url'] ?? null,
                'avatar_url' => $data['avatar_url'] ?? null,
                'creator_id' => $creator->id,
                'conversation_id' => $conversation->id,
                'members_count' => 1,
                'active_members_count' => 1,
                'posts_count' => 0,
                'status' => Group::STATUS_ACTIVE,
                'verification_status' => Group::VERIFICATION_UNVERIFIED,
                'features' => $data['features'] ?? [
                    'posts' => true,
                    'polls' => true,
                    'events' => true,
                    'files' => true,
                    'guides' => true,
                    'chat' => true,
                    'announcements' => true,
                ],
                'settings' => $data['settings'] ?? [
                    'keyword_alerts' => ['scam', 'spam', 'fake', 'টাকা', 'জুয়া'],
                ],
            ]);

            // ৩. ক্রিয়েটরকে অ্যাডমিন মেম্বারশিপ দেওয়া
            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $creator->id,
                'role' => GroupMember::ROLE_ADMIN,
                'permissions' => [
                    'manage_settings',
                    'manage_members',
                    'moderate_members',
                    'approve_posts',
                    'delete_posts',
                    'pin_posts',
                    'manage_events',
                    'manage_polls',
                    'manage_guides',
                    'manage_files',
                    'view_analytics',
                    'manage_chat',
                ],
                'status' => GroupMember::STATUS_ACTIVE,
                'joined_at' => now(),
            ]);

            // ৪. প্রাথমিক রুলস যুক্ত করা যদি দেওয়া থাকে
            if (! empty($data['rules']) && is_array($data['rules'])) {
                foreach ($data['rules'] as $idx => $ruleData) {
                    GroupRule::create([
                        'group_id' => $group->id,
                        'title' => $ruleData['title'] ?? 'Rule '.($idx + 1),
                        'description' => $ruleData['description'] ?? '',
                        'sort_order' => $idx,
                    ]);
                }
            }

            // ৫. প্রাথমিক স্ক্রিনিং প্রশ্নাবলি যদি থাকে
            if (! empty($data['questions']) && is_array($data['questions'])) {
                foreach ($data['questions'] as $idx => $qData) {
                    GroupQuestion::create([
                        'group_id' => $group->id,
                        'question' => is_string($qData) ? $qData : ($qData['question'] ?? ''),
                        'type' => is_array($qData) ? ($qData['type'] ?? 'text') : 'text',
                        'options' => is_array($qData) ? ($qData['options'] ?? null) : null,
                        'is_required' => true,
                        'sort_order' => $idx,
                    ]);
                }
            }

            // অডিট লগ
            $this->logAction($group, $creator, 'create_group', 'group', $group->id, 'Group created successfully.');

            $group->load(['creator', 'members']);

            return $group;
        });
    }

    /**
     * অ্যাডভান্সড গ্রুপ ডিসকভারি হাব।
     */
    public function getDiscoveryGroups(?User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Group::query()->where('status', Group::STATUS_ACTIVE);

        // প্রাইভেসি অনুযায়ী ফিল্টারিং: হিডেন গ্রুপ কেবল সদস্যদের কাছে দৃশ্যমান
        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('privacy', '!=', Group::PRIVACY_HIDDEN)
                    ->orWhereHas('members', fn ($mq) => $mq->where('user_id', $user->id)->where('status', GroupMember::STATUS_ACTIVE));
            });
        } else {
            $query->where('privacy', Group::PRIVACY_PUBLIC);
        }

        // সার্চ কুয়েরি
        if (! empty($filters['q'])) {
            $term = trim($filters['q']);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%");
            });
        }

        // ক্যাটাগরি ফিল্টার
        if (! empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('category', $filters['category']);
        }

        // ভাষা ফিল্টার
        if (! empty($filters['language'])) {
            $query->where('language', $filters['language']);
        }

        // সর্টিং ও র‍্যাংকিং
        $sort = $filters['sort'] ?? 'trending';
        match ($sort) {
            'popular' => $query->orderByDesc('members_count'),
            'newest' => $query->latest('id'),
            'active' => $query->orderByDesc('posts_count')->orderByDesc('members_count'),
            default => $query->orderByDesc('members_count')->latest('id'), // Trending
        };

        return $query->with('creator')->paginate($perPage);
    }

    /**
     * নির্দিষ্ট ইউজারের যুক্ত থাকা গ্রুপসমূহ।
     */
    public function getUserGroups(User $user): Collection
    {
        return Group::whereHas('members', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->where('status', GroupMember::STATUS_ACTIVE);
        })
            ->with(['creator'])
            ->latest('id')
            ->get();
    }

    /**
     * গ্রুপে যোগ দেওয়া বা রিকোয়েস্ট পাঠানো।
     */
    public function joinGroup(Group $group, User $user, array $screeningAnswers = []): array
    {
        $existing = GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            if ($existing->isBanned()) {
                throw new AuthorizationException('You are banned from this group. Reason: '.($existing->ban_reason ?: 'Violation of community rules'));
            }
            if ($existing->isActive()) {
                return ['status' => 'already_member', 'message' => 'You are already an active member of this group.'];
            }
            if ($existing->isPending()) {
                return ['status' => 'pending', 'message' => 'Your join request is pending approval.'];
            }
        }

        // ইনভাইট-অনলি গ্রুপ হলে সরাসরি রিকোয়েস্ট গ্রহণযোগ্য নয়
        if ($group->isInviteOnly() && ! $existing) {
            throw new AuthorizationException('This group is invite-only. You need an invitation link to join.');
        }

        $requiresApproval = $group->isPrivate() || $group->membership_approval_mode !== Group::APPROVAL_ANYONE;
        $initialStatus = $requiresApproval ? GroupMember::STATUS_PENDING : GroupMember::STATUS_ACTIVE;

        return DB::transaction(function () use ($group, $user, $initialStatus, $screeningAnswers, $existing) {
            if ($existing) {
                $existing->update([
                    'status' => $initialStatus,
                    'answers' => $screeningAnswers,
                    'joined_at' => now(),
                ]);
                $member = $existing;
            } else {
                $member = GroupMember::create([
                    'group_id' => $group->id,
                    'user_id' => $user->id,
                    'role' => GroupMember::ROLE_MEMBER,
                    'status' => $initialStatus,
                    'answers' => $screeningAnswers,
                    'joined_at' => now(),
                ]);
            }

            // স্ক্রিনিং প্রশ্নের উত্তরগুলো ডাটাবেজে সংরক্ষণ করা
            if (! empty($screeningAnswers)) {
                foreach ($screeningAnswers as $qId => $answer) {
                    GroupQuestionAnswer::updateOrCreate(
                        ['group_id' => $group->id, 'question_id' => (int) $qId, 'user_id' => $user->id],
                        ['answer' => is_array($answer) ? json_encode($answer) : (string) $answer]
                    );
                }
            }

            if ($initialStatus === GroupMember::STATUS_ACTIVE) {
                $group->increment('members_count');
                $group->increment('active_members_count');

                // গ্রুপ চ্যাট কনভার্সনে ইউজারকে পার্টিসিপেন্ট হিসেবে যুক্ত করা
                if ($group->conversation_id) {
                    ConversationParticipant::firstOrCreate([
                        'conversation_id' => $group->conversation_id,
                        'user_id' => $user->id,
                    ], [
                        'role' => 'member',
                        'last_read_at' => now(),
                    ]);
                }

                // রিয়েল-টাইম ব্রডকাস্ট
                broadcast(new GroupMemberJoinedEvent($group->id, $member->toResponseArray(), $group->fresh()->members_count))->toOthers();

                return [
                    'status' => 'joined',
                    'message' => 'Successfully joined the group.',
                    'members_count' => $group->fresh()->members_count,
                ];
            }

            return [
                'status' => 'pending',
                'message' => 'Join request submitted for admin review.',
                'members_count' => $group->members_count,
            ];
        });
    }

    /**
     * মেম্বারশিপ অনুমোদন করা (শুধুমাত্র অ্যাডমিন/মডারেটর)।
     */
    public function approveMember(Group $group, User $admin, int $userId): bool
    {
        if (! $group->hasPermission($admin->id, 'approve_members')) {
            throw new AuthorizationException('You do not have permission to approve members in this group.');
        }

        return DB::transaction(function () use ($group, $admin, $userId) {
            $membership = GroupMember::where('group_id', $group->id)
                ->where('user_id', $userId)
                ->where('status', GroupMember::STATUS_PENDING)
                ->first();

            if (! $membership) {
                return false;
            }

            $membership->update([
                'status' => GroupMember::STATUS_ACTIVE,
                'joined_at' => now(),
            ]);

            $group->increment('members_count');
            $group->increment('active_members_count');

            // গ্রুপ চ্যাটে যুক্ত করা
            if ($group->conversation_id) {
                ConversationParticipant::firstOrCreate([
                    'conversation_id' => $group->conversation_id,
                    'user_id' => $userId,
                ], [
                    'role' => 'member',
                    'last_read_at' => now(),
                ]);
            }

            $this->logAction($group, $admin, 'approve_member', 'user', $userId, 'Approved pending member.');

            broadcast(new GroupMemberJoinedEvent($group->id, $membership->toResponseArray(), $group->fresh()->members_count))->toOthers();

            return true;
        });
    }

    /**
     * পেন্ডিং মেম্বারশিপ প্রত্যাখ্যান করা।
     */
    public function rejectMember(Group $group, User $admin, int $userId): bool
    {
        if (! $group->hasPermission($admin->id, 'approve_members')) {
            throw new AuthorizationException('You do not have permission to reject members in this group.');
        }

        $membership = GroupMember::where('group_id', $group->id)
            ->where('user_id', $userId)
            ->where('status', GroupMember::STATUS_PENDING)
            ->first();

        if (! $membership) {
            return false;
        }

        $membership->delete();

        $this->logAction($group, $admin, 'reject_member', 'user', $userId, 'Rejected pending join request.');

        return true;
    }

    /**
     * গ্রুপ ত্যাগ করা।
     */
    public function leaveGroup(Group $group, User $user): bool
    {
        if ($group->creator_id === $user->id) {
            throw new InvalidArgumentException('Group creator/owner cannot leave the group. You must transfer ownership first.');
        }

        return DB::transaction(function () use ($group, $user) {
            $membership = GroupMember::where('group_id', $group->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $membership) {
                return false;
            }

            $isActive = $membership->isActive();
            $membership->delete();

            if ($isActive) {
                $group->decrement('members_count');
                if ($group->active_members_count > 0) {
                    $group->decrement('active_members_count');
                }

                // চ্যাট থেকে পার্টিসিপেন্ট রিমুভ
                if ($group->conversation_id) {
                    ConversationParticipant::where('conversation_id', $group->conversation_id)
                        ->where('user_id', $user->id)
                        ->delete();
                }
            }

            return true;
        });
    }

    /**
     * গ্রুপ থেকে কোনো সদস্যকে রিমুভ করা (অ্যাডমিন বা ক্ষমতাপ্রাপ্ত মডারেটর)।
     */
    public function removeMember(Group $group, User $actor, int $targetUserId, ?string $reason = null): bool
    {
        if (! $group->hasPermission($actor->id, 'manage_members')) {
            throw new AuthorizationException('You do not have permission to remove members.');
        }

        if ($group->isOwner($targetUserId)) {
            throw new AuthorizationException('Group owner cannot be removed.');
        }

        // অ্যাডমিন অন্য অ্যাডমিনকে রিমুভ করতে পারে না যদি না সে ওনার হয়
        if ($group->isAdmin($targetUserId) && ! $group->isOwner($actor->id)) {
            throw new AuthorizationException('Only group owner can remove an administrator.');
        }

        return DB::transaction(function () use ($group, $actor, $targetUserId, $reason) {
            $member = GroupMember::where('group_id', $group->id)
                ->where('user_id', $targetUserId)
                ->first();

            if (! $member) {
                return false;
            }

            $isActive = $member->isActive();
            $member->delete();

            if ($isActive) {
                $group->decrement('members_count');
                if ($group->active_members_count > 0) {
                    $group->decrement('active_members_count');
                }

                if ($group->conversation_id) {
                    ConversationParticipant::where('conversation_id', $group->conversation_id)
                        ->where('user_id', $targetUserId)
                        ->delete();
                }
            }

            $this->logAction($group, $actor, 'remove_member', 'user', $targetUserId, $reason ?: 'Member removed by staff.');

            return true;
        });
    }

    /**
     * মেম্বার রোল ও পারমিশন আপডেট করা।
     */
    public function updateMemberRole(Group $group, User $actor, int $targetUserId, string $newRole, ?array $permissions = null): GroupMember
    {
        if (! $group->isAdmin($actor->id)) {
            throw new AuthorizationException('Only group administrators can change member roles.');
        }

        if ($group->isOwner($targetUserId) && $actor->id !== $targetUserId) {
            throw new AuthorizationException('Cannot modify group owner role.');
        }

        $member = GroupMember::where('group_id', $group->id)
            ->where('user_id', $targetUserId)
            ->firstOrFail();

        $defaultPerms = match ($newRole) {
            GroupMember::ROLE_ADMIN => [
                'manage_settings', 'manage_members', 'moderate_members', 'approve_posts',
                'delete_posts', 'pin_posts', 'manage_events', 'manage_polls', 'manage_guides',
                'manage_files', 'view_analytics', 'manage_chat',
            ],
            GroupMember::ROLE_MODERATOR => [
                'moderate_members', 'approve_posts', 'delete_posts', 'pin_posts',
                'manage_events', 'manage_polls', 'manage_files',
            ],
            GroupMember::ROLE_EDITOR => [
                'approve_posts', 'pin_posts', 'manage_events', 'manage_polls', 'manage_guides',
            ],
            default => [],
        };

        $member->update([
            'role' => $newRole,
            'permissions' => $permissions ?? $defaultPerms,
        ]);

        $this->logAction($group, $actor, 'update_role', 'user', $targetUserId, "Role changed to {$newRole}");

        return $member;
    }

    /**
     * মেম্বারকে সতর্কবার্তা (Warning) দেওয়া।
     */
    public function warnMember(Group $group, User $moderator, int $targetUserId, string $reason): GroupMemberStrike
    {
        if (! $group->hasPermission($moderator->id, 'moderate_members')) {
            throw new AuthorizationException('Permission denied.');
        }

        $strike = GroupMemberStrike::create([
            'group_id' => $group->id,
            'user_id' => $targetUserId,
            'moderator_id' => $moderator->id,
            'reason' => $reason,
            'action_taken' => 'warning',
            'expires_at' => now()->addDays(30),
        ]);

        $member = GroupMember::where('group_id', $group->id)->where('user_id', $targetUserId)->first();
        if ($member) {
            $member->increment('strikes_count');
        }

        $this->logAction($group, $moderator, 'warn_member', 'user', $targetUserId, $reason);

        return $strike;
    }

    /**
     * মেম্বারকে সাময়িক মিউট করা (নির্দিষ্ট সময় পর্যন্ত পোস্ট/কমেন্ট বন্ধ)।
     */
    public function muteMember(Group $group, User $moderator, int $targetUserId, int $durationMinutes, string $reason): GroupMember
    {
        if (! $group->hasPermission($moderator->id, 'moderate_members')) {
            throw new AuthorizationException('Permission denied.');
        }

        $member = GroupMember::where('group_id', $group->id)
            ->where('user_id', $targetUserId)
            ->firstOrFail();

        $mutedUntil = now()->addMinutes($durationMinutes);

        $member->update([
            'status' => GroupMember::STATUS_MUTED,
            'muted_until' => $mutedUntil,
        ]);

        GroupMemberStrike::create([
            'group_id' => $group->id,
            'user_id' => $targetUserId,
            'moderator_id' => $moderator->id,
            'reason' => $reason,
            'action_taken' => "muted_{$durationMinutes}m",
            'expires_at' => $mutedUntil,
        ]);

        $this->logAction($group, $moderator, 'mute_member', 'user', $targetUserId, $reason);

        return $member;
    }

    /**
     * মেম্বারকে ব্যান করা।
     */
    public function banMember(Group $group, User $moderator, int $targetUserId, string $reason): GroupMember
    {
        if (! $group->hasPermission($moderator->id, 'moderate_members')) {
            throw new AuthorizationException('Permission denied.');
        }

        if ($group->isOwner($targetUserId) || $group->isAdmin($targetUserId)) {
            throw new AuthorizationException('Cannot ban group administrators.');
        }

        return DB::transaction(function () use ($group, $moderator, $targetUserId, $reason) {
            $member = GroupMember::where('group_id', $group->id)
                ->where('user_id', $targetUserId)
                ->firstOrFail();

            $wasActive = $member->isActive();

            $member->update([
                'status' => GroupMember::STATUS_BANNED,
                'banned_at' => now(),
                'ban_reason' => $reason,
            ]);

            if ($wasActive) {
                $group->decrement('members_count');
                if ($group->active_members_count > 0) {
                    $group->decrement('active_members_count');
                }

                if ($group->conversation_id) {
                    ConversationParticipant::where('conversation_id', $group->conversation_id)
                        ->where('user_id', $targetUserId)
                        ->delete();
                }
            }

            GroupMemberStrike::create([
                'group_id' => $group->id,
                'user_id' => $targetUserId,
                'moderator_id' => $moderator->id,
                'reason' => $reason,
                'action_taken' => 'banned',
            ]);

            $this->logAction($group, $moderator, 'ban_member', 'user', $targetUserId, $reason);

            return $member;
        });
    }

    /**
     * ব্যান প্রত্যাহার (Unban) করা।
     */
    public function unbanMember(Group $group, User $moderator, int $targetUserId): GroupMember
    {
        if (! $group->hasPermission($moderator->id, 'moderate_members')) {
            throw new AuthorizationException('Permission denied.');
        }

        $member = GroupMember::where('group_id', $group->id)
            ->where('user_id', $targetUserId)
            ->where('status', GroupMember::STATUS_BANNED)
            ->firstOrFail();

        $member->update([
            'status' => GroupMember::STATUS_ACTIVE,
            'banned_at' => null,
            'ban_reason' => null,
            'joined_at' => now(),
        ]);

        $group->increment('members_count');
        $group->increment('active_members_count');

        $this->logAction($group, $moderator, 'unban_member', 'user', $targetUserId, 'Unbanned member.');

        return $member;
    }

    /**
     * গ্রুপে নতুন পোস্ট তৈরি করা (অটো-মডারেশন ও কিওয়ার্ড অ্যালার্টসহ)।
     */
    public function createGroupPost(Group $group, User $user, array $data): Post
    {
        if (! $group->canPost($user->id)) {
            throw new AuthorizationException('You do not have permission to post in this group.');
        }

        return DB::transaction(function () use ($group, $user, $data) {
            $content = $data['content'];

            // ১. কিওয়ার্ড ফিল্টার এবং স্বয়ংক্রিয় মডারেশন স্ক্যানিং
            $alert = $this->enterpriseGroupService->checkKeywordAlerts($group, $content);

            // ২. পোস্ট স্ট্যাটাস নির্ধারণ (অ্যাপ্রুভাল প্রয়োজন কিনা)
            $needsApproval = false;
            if ($group->post_approval_mode === Group::POST_APPROVAL_ADMIN && ! $group->isModerator($user->id)) {
                $needsApproval = true;
            } elseif ($alert['requires_admin_review'] && ! $group->isModerator($user->id)) {
                $needsApproval = true;
            }

            $postStatus = $needsApproval ? 'pending_approval' : 'published';

            $post = Post::create([
                'user_id' => $user->id,
                'group_id' => $group->id,
                'content' => $content,
                'audience' => 'public',
                'type' => $data['type'] ?? 'text',
                'status' => $postStatus,
                'poll_data' => $data['poll_data'] ?? null,
                'link_preview' => $data['link_preview'] ?? null,
            ]);

            if ($postStatus === 'published') {
                $group->increment('posts_count');
                $post->load(['user.profile', 'media']);

                // রিয়েল-টাইম ফিড ব্রডকাস্ট
                broadcast(new GroupPostCreatedEvent($group->id, $post->toResponseArray($user)))->toOthers();
            } else {
                $this->logAction($group, $user, 'post_queued_for_review', 'post', $post->id, 'Flagged by moderation engine.');
            }

            return $post;
        });
    }

    /**
     * মডারেশন কিউ থেকে পোস্ট অনুমোদন করা।
     */
    public function approvePost(Group $group, User $moderator, int $postId): Post
    {
        if (! $group->hasPermission($moderator->id, 'approve_posts')) {
            throw new AuthorizationException('Permission denied.');
        }

        return DB::transaction(function () use ($group, $moderator, $postId) {
            $post = Post::where('group_id', $group->id)
                ->where('id', $postId)
                ->where('status', 'pending_approval')
                ->firstOrFail();

            $post->update(['status' => 'published']);
            $group->increment('posts_count');

            $this->logAction($group, $moderator, 'approve_post', 'post', $postId, 'Post approved by moderator.');

            broadcast(new GroupPostCreatedEvent($group->id, $post->toResponseArray()))->toOthers();

            return $post;
        });
    }

    /**
     * মডারেশন কিউ থেকে পোস্ট বাতিল করা।
     */
    public function rejectPost(Group $group, User $moderator, int $postId, ?string $reason = null): bool
    {
        if (! $group->hasPermission($moderator->id, 'approve_posts')) {
            throw new AuthorizationException('Permission denied.');
        }

        $post = Post::where('group_id', $group->id)
            ->where('id', $postId)
            ->where('status', 'pending_approval')
            ->firstOrFail();

        $post->update(['status' => 'rejected']);

        $this->logAction($group, $moderator, 'reject_post', 'post', $postId, $reason ?: 'Post rejected.');

        return true;
    }

    /**
     * পোস্ট মুছে ফেলা (লেখক বা মডারেটর)।
     */
    public function deletePost(Group $group, User $actor, int $postId): bool
    {
        $post = Post::where('group_id', $group->id)->where('id', $postId)->firstOrFail();

        $canDelete = $post->user_id === $actor->id || $group->hasPermission($actor->id, 'delete_posts');
        if (! $canDelete) {
            throw new AuthorizationException('Permission denied.');
        }

        return DB::transaction(function () use ($group, $actor, $post) {
            $wasPublished = $post->status === 'published';
            $post->delete();

            if ($wasPublished && $group->posts_count > 0) {
                $group->decrement('posts_count');
            }

            if ($post->user_id !== $actor->id) {
                $this->logAction($group, $actor, 'delete_post', 'post', $post->id, 'Post deleted by staff.');
            }

            return true;
        });
    }

    /**
     * গ্রুপের পোস্ট ফিড পেজিনেশনসহ নিয়ে আসা।
     */
    public function getGroupFeed(Group $group, ?User $user, int $perPage = 15, string $status = 'published'): LengthAwarePaginator
    {
        if ($group->isPrivate() && (! $user || ! $group->hasMember($user->id))) {
            throw new AuthorizationException('This group is private. You must be an active member to view posts.');
        }

        $paginator = Post::where('group_id', $group->id)
            ->where('status', $status)
            ->with(['user.profile', 'media', 'reactions'])
            ->latest('id')
            ->paginate($perPage);

        $paginator->getCollection()->transform(fn (Post $p) => $p->toResponseArray($user));

        return $paginator;
    }

    /**
     * মডারেশন কিউ (অননুমোদিত পোস্টগুলোর তালিকা)।
     */
    public function getModerationQueue(Group $group, User $moderator, int $perPage = 15): LengthAwarePaginator
    {
        if (! $group->hasPermission($moderator->id, 'approve_posts')) {
            throw new AuthorizationException('Permission denied.');
        }

        $paginator = Post::where('group_id', $group->id)
            ->where('status', 'pending_approval')
            ->with(['user.profile', 'media'])
            ->latest('id')
            ->paginate($perPage);

        $paginator->getCollection()->transform(fn (Post $p) => $p->toResponseArray($moderator));

        return $paginator;
    }

    /**
     * মডারেশন অডিট লগ হিস্ট্রি।
     */
    public function getModerationHistory(Group $group, User $moderator, int $perPage = 20): LengthAwarePaginator
    {
        if (! $group->hasPermission($moderator->id, 'moderate_members')) {
            throw new AuthorizationException('Permission denied.');
        }

        return GroupModerationAction::where('group_id', $group->id)
            ->with(['moderator', 'targetUser'])
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * গ্রুপ পোল তৈরি করা।
     */
    public function createPoll(Group $group, User $user, array $data): GroupPoll
    {
        if (! $group->hasMember($user->id)) {
            throw new AuthorizationException('Must be a member to create polls.');
        }

        return DB::transaction(function () use ($group, $user, $data) {
            $poll = GroupPoll::create([
                'group_id' => $group->id,
                'creator_id' => $user->id,
                'question' => $data['question'],
                'is_multiple_choice' => (bool) ($data['is_multiple_choice'] ?? false),
                'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
                'can_change_vote' => (bool) ($data['can_change_vote'] ?? true),
                'ends_at' => ! empty($data['ends_at']) ? Carbon::parse($data['ends_at']) : null,
            ]);

            foreach ($data['options'] as $idx => $optText) {
                if (trim($optText)) {
                    GroupPollOption::create([
                        'poll_id' => $poll->id,
                        'option_text' => trim($optText),
                        'sort_order' => $idx,
                    ]);
                }
            }

            return $poll->load('options');
        });
    }

    /**
     * পোলে ভোট দেওয়া বা ভোট পরিবর্তন করা।
     */
    public function votePoll(Group $group, User $user, int $pollId, int|array $optionIds): GroupPoll
    {
        if (! $group->hasMember($user->id)) {
            throw new AuthorizationException('Must be an active member to vote.');
        }

        $poll = GroupPoll::where('group_id', $group->id)->where('id', $pollId)->firstOrFail();

        if ($poll->isExpired()) {
            throw new InvalidArgumentException('This poll has ended.');
        }

        $optionIdArray = is_array($optionIds) ? $optionIds : [$optionIds];

        if (! $poll->is_multiple_choice && count($optionIdArray) > 1) {
            throw new InvalidArgumentException('This poll only allows a single choice.');
        }

        return DB::transaction(function () use ($group, $user, $poll, $optionIdArray) {
            $existingVotes = GroupPollVote::where('poll_id', $poll->id)->where('user_id', $user->id)->get();

            if ($existingVotes->isNotEmpty() && ! $poll->can_change_vote) {
                throw new InvalidArgumentException('You have already voted and vote change is disabled for this poll.');
            }

            // আগের ভোটের কাউন্টার কমানো
            foreach ($existingVotes as $oldVote) {
                GroupPollOption::where('id', $oldVote->poll_option_id)->decrement('votes_count');
                $oldVote->delete();
            }

            // নতুন ভোট রেজিস্টার করা
            foreach ($optionIdArray as $optId) {
                GroupPollVote::create([
                    'poll_id' => $poll->id,
                    'poll_option_id' => $optId,
                    'user_id' => $user->id,
                ]);
                GroupPollOption::where('id', $optId)->increment('votes_count');
            }

            $totalVotes = GroupPollVote::where('poll_id', $poll->id)->count();
            $poll->update(['total_votes_count' => $totalVotes]);

            $poll->refresh()->load('options');

            broadcast(new GroupPollVotedEvent($group->id, $poll->id, $poll->toResponseArray($user)))->toOthers();

            return $poll;
        });
    }

    /**
     * গ্রুপ ইভেন্ট তৈরি করা।
     */
    public function createEvent(Group $group, User $user, array $data): GroupEvent
    {
        if (! $group->hasPermission($user->id, 'manage_events')) {
            throw new AuthorizationException('Permission denied.');
        }

        return GroupEvent::create([
            'group_id' => $group->id,
            'creator_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'cover_image_url' => $data['cover_image_url'] ?? null,
            'location' => $data['location'] ?? null,
            'is_online' => (bool) ($data['is_online'] ?? false),
            'meeting_url' => $data['meeting_url'] ?? null,
            'start_time' => Carbon::parse($data['start_time']),
            'end_time' => ! empty($data['end_time']) ? Carbon::parse($data['end_time']) : null,
            'timezone' => $data['timezone'] ?? 'UTC',
        ]);
    }

    /**
     * ইভেন্টে RSVP দেওয়া (Going, Interested, Not going)।
     */
    public function rsvpEvent(Group $group, User $user, int $eventId, string $status): GroupEventAttendee
    {
        if (! $group->hasMember($user->id)) {
            throw new AuthorizationException('Must be a member to RSVP.');
        }

        $event = GroupEvent::where('group_id', $group->id)->where('id', $eventId)->firstOrFail();

        return DB::transaction(function () use ($event, $user, $status) {
            $rsvp = GroupEventAttendee::updateOrCreate(
                ['event_id' => $event->id, 'user_id' => $user->id],
                ['status' => $status]
            );

            $attendeesCount = GroupEventAttendee::where('event_id', $event->id)->where('status', 'going')->count();
            $event->update(['attendees_count' => $attendeesCount]);

            return $rsvp;
        });
    }

    /**
     * অফিশিয়াল অ্যানাউন্সমেন্ট প্রকাশ করা।
     */
    public function createAnnouncement(Group $group, User $author, array $data): GroupAnnouncement
    {
        if (! $group->hasPermission($author->id, 'pin_posts')) {
            throw new AuthorizationException('Permission denied.');
        }

        $announcement = GroupAnnouncement::create([
            'group_id' => $group->id,
            'author_id' => $author->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'cta_text' => $data['cta_text'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'is_pinned' => (bool) ($data['is_pinned'] ?? true),
            'expires_at' => ! empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
        ]);

        $this->logAction($group, $author, 'publish_announcement', 'announcement', $announcement->id, $data['title']);

        broadcast(new GroupAnnouncementPublishedEvent($group->id, $announcement->toResponseArray()))->toOthers();

        return $announcement;
    }

    /**
     * গ্রুপ রুল যোগ করা।
     */
    public function addRule(Group $group, User $admin, array $data): GroupRule
    {
        if (! $group->hasPermission($admin->id, 'manage_settings')) {
            throw new AuthorizationException('Permission denied.');
        }

        $sortOrder = $group->rules()->count();

        return GroupRule::create([
            'group_id' => $group->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'enforcement_level' => $data['enforcement_level'] ?? 'warning',
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * মেম্বারশিপ স্ক্রিনিং প্রশ্ন যোগ করা।
     */
    public function addQuestion(Group $group, User $admin, array $data): GroupQuestion
    {
        if (! $group->hasPermission($admin->id, 'manage_settings')) {
            throw new AuthorizationException('Permission denied.');
        }

        $sortOrder = $group->questions()->count();

        return GroupQuestion::create([
            'group_id' => $group->id,
            'question' => $data['question'],
            'type' => $data['type'] ?? 'text',
            'options' => $data['options'] ?? null,
            'is_required' => (bool) ($data['is_required'] ?? true),
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * ফাইল বা ডকুমেন্ট শেয়ার করা।
     */
    public function uploadFile(Group $group, User $user, array $data): GroupFile
    {
        if (! $group->hasMember($user->id)) {
            throw new AuthorizationException('Must be a member to upload files.');
        }

        return GroupFile::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'name' => $data['name'],
            'file_url' => $data['file_url'],
            'file_type' => $data['file_type'] ?? 'document',
            'file_size' => (int) ($data['file_size'] ?? 0),
        ]);
    }

    /**
     * কনটেন্ট বা মেম্বার রিপোর্ট করা।
     */
    public function reportItem(Group $group, User $reporter, array $data): GroupReport
    {
        return GroupReport::create([
            'group_id' => $group->id,
            'reporter_id' => $reporter->id,
            'reportable_type' => $data['reportable_type'],
            'reportable_id' => $data['reportable_id'],
            'reason_category' => $data['reason_category'],
            'description' => $data['description'] ?? null,
            'evidence' => $data['evidence'] ?? null,
            'status' => GroupReport::STATUS_PENDING,
        ]);
    }

    /**
     * রিপোর্ট রিভিউ ও অ্যাকশন নেওয়া।
     */
    public function reviewReport(Group $group, User $moderator, int $reportId, string $status, ?string $note = null): GroupReport
    {
        if (! $group->hasPermission($moderator->id, 'moderate_members')) {
            throw new AuthorizationException('Permission denied.');
        }

        $report = GroupReport::where('group_id', $group->id)->where('id', $reportId)->firstOrFail();

        $report->update([
            'status' => $status,
            'reviewed_by' => $moderator->id,
            'decision_note' => $note,
            'reviewed_at' => now(),
        ]);

        $this->logAction($group, $moderator, 'review_report', 'report', $report->id, "Report {$status}: {$note}");

        return $report;
    }

    /**
     * গ্রুপ অ্যানালিটিক্স ডাটা (আসল ডাটাবেজ তথ্য থেকে গণনাকৃত)।
     */
    public function getAnalytics(Group $group, User $admin, int $days = 30): array
    {
        if (! $group->hasPermission($admin->id, 'view_analytics')) {
            throw new AuthorizationException('Permission denied.');
        }

        $startDate = now()->subDays($days);

        $totalMembers = $group->members_count;
        $activeMembers = $group->active_members_count;
        $newMembers = GroupMember::where('group_id', $group->id)->where('created_at', '>=', $startDate)->count();

        $postsCount = Post::where('group_id', $group->id)->where('status', 'published')->count();
        $recentPostsCount = Post::where('group_id', $group->id)->where('status', 'published')->where('created_at', '>=', $startDate)->count();

        $reportsCount = GroupReport::where('group_id', $group->id)->where('status', 'pending')->count();
        $pendingPostsCount = Post::where('group_id', $group->id)->where('status', 'pending_approval')->count();

        $engagementRate = $totalMembers > 0 ? round(($recentPostsCount / $totalMembers) * 100, 2) : 0;

        return [
            'overview' => [
                'total_members' => $totalMembers,
                'active_members' => $activeMembers,
                'new_members' => $newMembers,
                'total_posts' => $postsCount,
                'recent_posts' => $recentPostsCount,
                'pending_posts' => $pendingPostsCount,
                'pending_reports' => $reportsCount,
                'engagement_rate' => $engagementRate,
            ],
            'period_days' => $days,
        ];
    }

    /**
     * কমিউনিটি ডিসকাশন / প্রশ্নোত্তর তৈরি করা।
     */
    public function createDiscussion(Group $group, User $user, array $data): GroupDiscussion
    {
        if (! $group->hasMember($user->id)) {
            throw new AuthorizationException('Must be a member to start discussions.');
        }

        $discussion = GroupDiscussion::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'title' => $data['title'],
            'content' => $data['content'] ?? $data['body'] ?? '',
            'type' => $data['type'] ?? GroupDiscussion::TYPE_DISCUSSION,
        ]);

        $this->logAction($group, $user, 'create_discussion', 'discussion', $discussion->id, $data['title']);

        return $discussion;
    }

    /**
     * ডিসকাশনে উত্তর / রিপ্লাই দেওয়া।
     */
    public function replyToDiscussion(Group $group, User $user, int $discussionId, array $data): GroupDiscussionReply
    {
        if (! $group->hasMember($user->id)) {
            throw new AuthorizationException('Must be a member to reply.');
        }

        $discussion = GroupDiscussion::where('group_id', $group->id)->where('id', $discussionId)->firstOrFail();

        if ($discussion->is_locked) {
            throw new InvalidArgumentException('This discussion is locked.');
        }

        return DB::transaction(function () use ($discussion, $user, $data) {
            $reply = GroupDiscussionReply::create([
                'discussion_id' => $discussion->id,
                'user_id' => $user->id,
                'parent_id' => $data['parent_id'] ?? null,
                'content' => $data['content'] ?? $data['body'] ?? '',
            ]);

            $discussion->increment('replies_count');

            return $reply;
        });
    }

    /**
     * প্রশ্নোত্তর ডিসকাশনে সঠিক উত্তর (Accepted Answer) চিহ্নিত করা।
     */
    public function markDiscussionSolved(Group $group, User $user, int $discussionId, int $replyId): GroupDiscussion
    {
        $discussion = GroupDiscussion::where('group_id', $group->id)->where('id', $discussionId)->firstOrFail();

        // শুধুমাত্র ডিসকাশন লেখক বা মডারেটর সমাধান মার্ক করতে পারে
        if ($discussion->user_id !== $user->id && ! $group->hasPermission($user->id, 'moderate_members')) {
            throw new AuthorizationException('Permission denied.');
        }

        $reply = GroupDiscussionReply::where('discussion_id', $discussion->id)->where('id', $replyId)->firstOrFail();

        return DB::transaction(function () use ($discussion, $reply) {
            GroupDiscussionReply::where('discussion_id', $discussion->id)->update(['is_accepted_answer' => false]);

            $reply->update(['is_accepted_answer' => true]);
            $discussion->update([
                'is_solved' => true,
                'accepted_answer_id' => $reply->id,
            ]);

            return $discussion->fresh()->load('acceptedAnswer');
        });
    }

    /**
     * ডিসকাশন তালিকা।
     */
    public function getDiscussions(Group $group, ?User $user, int $perPage = 15): LengthAwarePaginator
    {
        if ($group->isPrivate() && (! $user || ! $group->hasMember($user->id))) {
            throw new AuthorizationException('This group is private.');
        }

        return GroupDiscussion::where('group_id', $group->id)
            ->with(['author.profile', 'acceptedAnswer'])
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * কন্ট্রিবিউটর ব্যাজ প্রদান করা (অ্যাডমিন)।
     */
    public function assignBadge(Group $group, User $admin, int $userId, string $badgeType): GroupMemberBadge
    {
        if (! $group->isAdmin($admin->id)) {
            throw new AuthorizationException('Only administrators can assign contributor badges.');
        }

        return GroupMemberBadge::firstOrCreate([
            'group_id' => $group->id,
            'user_id' => $userId,
            'badge_type' => $badgeType,
        ], [
            'assigned_by' => $admin->id,
            'created_at' => now(),
        ]);
    }

    /**
     * ইউজারের বুকমার্ক টগল করা।
     */
    public function toggleBookmark(Group $group, User $user, string $type, int $id): bool
    {
        $existing = GroupBookmark::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('bookmarkable_type', $type)
            ->where('bookmarkable_id', $id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        GroupBookmark::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'bookmarkable_type' => $type,
            'bookmarkable_id' => $id,
            'created_at' => now(),
        ]);

        return true;
    }

    /**
     * ইউজারের সাবস্ক্রিপশন টগল করা (গ্র্যানুলার নোটিফিকেশন)।
     */
    public function toggleSubscription(Group $group, User $user, string $type, int $id): bool
    {
        $existing = GroupSubscription::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('subscribable_type', $type)
            ->where('subscribable_id', $id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        GroupSubscription::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'subscribable_type' => $type,
            'subscribable_id' => $id,
            'created_at' => now(),
        ]);

        return true;
    }

    /**
     * অডিট লগ রেকর্ড তৈরি।
     */
    protected function logAction(Group $group, User $actor, string $action, ?string $targetType = null, ?int $targetId = null, ?string $reason = null): GroupModerationAction
    {
        return GroupModerationAction::create([
            'group_id' => $group->id,
            'moderator_id' => $actor->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'reason' => $reason,
        ]);
    }
}
