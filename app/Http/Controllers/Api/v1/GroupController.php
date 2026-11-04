<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\ConversationParticipant;
use App\Models\Group;
use App\Models\GroupCategory;
use App\Models\GroupInvitation;
use App\Models\GroupMember;
use App\Models\GroupNotificationPreference;
use App\Models\GroupReport;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\GroupService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * এন্টারপ্রাইজ গ্রুপ কন্ট্রোলার:
 * ওয়েব, অ্যান্ড্রয়েড, আইওএস, ম্যাক এবং উইন্ডোজ ক্লায়েন্টের জন্য সিঙ্গেল ইউনিফাইড সোশ্যাল কমিউনিটি এপিআই।
 */
class GroupController extends Controller
{
    public function __construct(
        protected GroupService $groupService
    ) {}

    /**
     * প্রফেশনাল গ্রুপস হোম হাব।
     */
    public function home(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $this->groupService->getGroupsHome($user);

        return $this->successResponse(
            data: $data,
            message: 'Groups home feed retrieved successfully.'
        );
    }

    /**
     * গ্রুপ এক্সপ্লোর ও সার্চ (মাল্টি-ক্রাইটেরিয়া ফিল্টারসহ)।
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) $request->query('per_page', 15), 50);

        $filters = [
            'q' => $request->query('q'),
            'name' => $request->query('name'),
            'username' => $request->query('username'),
            'keywords' => $request->query('keywords'),
            'location' => $request->query('location'),
            'industry' => $request->query('industry'),
            'description' => $request->query('description'),
            'category' => $request->query('category'),
            'privacy' => $request->query('privacy'),
            'min_members' => $request->query('min_members'),
            'max_members' => $request->query('max_members'),
            'activity' => $request->query('activity'),
            'filter' => $request->query('filter'),
            'language' => $request->query('language'),
            'sort' => $request->query('sort', 'trending'),
        ];

        $groups = $this->groupService->getDiscoveryGroups($user, $filters, $perPage);
        $groups->getCollection()->transform(fn (Group $g) => $g->toResponseArray($user));

        return $this->successResponse(
            data: $groups->items(),
            message: 'Groups retrieved successfully.',
            meta: [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'total' => $groups->total(),
            ]
        );
    }

    /**
     * ইউজারের জয়েন করা গ্রুপগুলোর তালিকা।
     */
    public function myGroups(Request $request): JsonResponse
    {
        $user = $request->user();
        $groups = $this->groupService->getUserGroups($user);

        return $this->successResponse(
            data: $groups->map(fn (Group $g) => $g->toResponseArray($user))->values(),
            message: 'My groups retrieved successfully.'
        );
    }

    /**
     * নতুন গ্রুপ তৈরি করা (মাল্টি-স্টেপ উইজার্ড ডেটাসহ)।
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:60', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:60'],
            'subcategory' => ['nullable', 'string', 'max:60'],
            'industry' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'group_type' => ['nullable', 'string', 'max:40'],
            'language' => ['nullable', 'string', 'max:10'],
            'location' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:60'],
            'privacy' => ['nullable', 'string', 'in:public,private,hidden,invite_only'],
            'membership_approval_mode' => ['nullable', 'string', 'in:anyone,approval_required,admin_only,invite_only'],
            'allow_member_invitations' => ['nullable', 'boolean'],
            'allow_member_recommendations' => ['nullable', 'boolean'],
            'enable_membership_screening' => ['nullable', 'boolean'],
            'max_members_limit' => ['nullable', 'integer', 'min:1'],
            'auto_approve_trusted' => ['nullable', 'boolean'],
            'who_can_post' => ['nullable', 'string', 'in:anyone_in_group,admins_moderators_only'],
            'who_can_comment' => ['nullable', 'string', 'in:anyone_in_group,admins_moderators_only'],
            'who_can_upload_media' => ['nullable', 'string', 'in:anyone_in_group,admins_only'],
            'who_can_create_polls' => ['nullable', 'string', 'in:anyone_in_group,admins_only'],
            'who_can_create_events' => ['nullable', 'string', 'in:anyone_in_group,admins_only'],
            'who_can_create_announcements' => ['nullable', 'string', 'in:admins_moderators_only'],
            'post_approval_mode' => ['nullable', 'string', 'in:auto,admin_approval,new_members_approval'],
            'enable_comment_moderation' => ['nullable', 'boolean'],
            'allow_anonymous_posts' => ['nullable', 'boolean'],
            'allow_scheduled_posts' => ['nullable', 'boolean'],
            'cover_image_url' => ['nullable', 'string'],
            'avatar_url' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
            'rules' => ['nullable', 'array'],
            'questions' => ['nullable', 'array'],
            'invited_user_ids' => ['nullable', 'array'],
            'invited_user_ids.*' => ['integer', 'exists:users,id'],
            'invite_user_ids' => ['nullable', 'array'],
            'invite_user_ids.*' => ['integer', 'exists:users,id'],
            'allow_member_invites' => ['nullable', 'boolean'],
            'allow_member_posts' => ['nullable', 'boolean'],
            'require_admin_approval' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['require_admin_approval'])) {
            $validated['membership_approval_mode'] = Group::APPROVAL_REQUIRED;
        }

        if (isset($validated['allow_member_posts']) && ! $validated['allow_member_posts']) {
            $validated['post_approval_mode'] = Group::POST_APPROVAL_ADMIN;
        }

        $features = $validated['features'] ?? [];
        if (isset($validated['allow_member_invites'])) {
            $features['member_invites'] = (bool) $validated['allow_member_invites'];
        }
        $validated['features'] = $features;

        $group = $this->groupService->createGroup($user, $validated);

        // Process invited members if specified
        $invitedIds = $validated['invited_user_ids'] ?? ($validated['invite_user_ids'] ?? []);
        if (! empty($invitedIds)) {
            foreach (array_unique($invitedIds) as $inviteeId) {
                if ((int) $inviteeId === (int) $user->id) {
                    continue;
                }
                $inv = GroupInvitation::where('group_id', $group->id)
                    ->where('invitee_id', (int) $inviteeId)
                    ->first();

                if (! $inv) {
                    $inv = GroupInvitation::create([
                        'group_id' => $group->id,
                        'invitee_id' => (int) $inviteeId,
                        'inviter_id' => $user->id,
                        'token' => Str::random(40),
                        'role' => GroupMember::ROLE_MEMBER,
                        'status' => GroupInvitation::STATUS_PENDING,
                        'expires_at' => now()->addDays(14),
                    ]);
                } elseif ($inv->status !== GroupInvitation::STATUS_PENDING) {
                    $inv->update([
                        'inviter_id' => $user->id,
                        'token' => Str::random(40),
                        'status' => GroupInvitation::STATUS_PENDING,
                        'expires_at' => now()->addDays(14),
                    ]);
                }

                try {
                    if (app()->bound(NotificationServiceInterface::class)) {
                        app(NotificationServiceInterface::class)->send(
                            recipientId: (int) $inviteeId,
                            type: 'notification.group_invitation',
                            data: [
                                'group_id' => $group->id,
                                'group_name' => $group->name,
                                'inviter_id' => $user->id,
                                'inviter_name' => $user->name,
                                'title' => 'Group Invitation',
                                'message' => "{$user->name} invited you to join {$group->name}.",
                            ],
                            channels: ['database']
                        );
                    }
                } catch (\Throwable) {
                }
            }
        }

        return $this->successResponse(
            data: $group->toResponseArray($user),
            message: 'Group created successfully.',
            statusCode: 201
        );
    }

    /**
     * নির্দিষ্ট গ্রুপের বিবরণ নিয়ে আসা।
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();
        $group = Group::where('slug', $slug)
            ->orWhere('id', is_numeric($slug) ? (int) $slug : 0)
            ->orWhere('username', $slug)
            ->firstOrFail();

        if (! $group->canView($user)) {
            return $this->errorResponse('This group is hidden and accessible only to members.', 403);
        }

        return $this->successResponse(
            data: $group->toResponseArray($user),
            message: 'Group retrieved successfully.'
        );
    }

    /**
     * প্রফেশনাল গ্রুপ প্রোফাইল ও সমস্ত ৯টি নেভিগেশন ট্যাবের ডাটা।
     */
    public function profile(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();
        $group = Group::where('slug', $slug)
            ->orWhere('id', is_numeric($slug) ? (int) $slug : 0)
            ->orWhere('username', $slug)
            ->with(['creator', 'rules'])
            ->firstOrFail();

        if (! $group->canView($user)) {
            return $this->errorResponse('This group is hidden and accessible only to members.', 403);
        }

        $data = $this->groupService->getGroupProfileData($group, $user);

        return $this->successResponse(
            data: $data,
            message: 'Group profile retrieved successfully.'
        );
    }

    /**
     * ওভারভিউ ট্যাব তথ্য।
     */
    public function overview(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if (! $group->canView($user)) {
            return $this->errorResponse('This group is hidden and accessible only to members.', 403);
        }

        $data = $this->groupService->getGroupProfileData($group, $user);

        return $this->successResponse(
            data: $data['tabs']['overview'],
            message: 'Overview data retrieved successfully.'
        );
    }

    /**
     * অ্যানাউন্সমেন্ট তালিকা (Tab 3)।
     */
    public function announcements(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if (! $group->canView($user)) {
            return $this->errorResponse('This group is private or hidden.', 403);
        }

        $announcements = $group->announcements()->with('author')->latest()->paginate((int) $request->query('per_page', 15));
        $announcements->getCollection()->transform(fn ($a) => $a->toResponseArray());

        return $this->successResponse(
            data: $announcements->items(),
            message: 'Announcements retrieved successfully.',
            meta: [
                'current_page' => $announcements->currentPage(),
                'last_page' => $announcements->lastPage(),
                'total' => $announcements->total(),
            ]
        );
    }

    /**
     * গ্রুপ মিডিয়া গ্যালারি (Tab 6)।
     */
    public function media(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if (! $group->canView($user)) {
            return $this->errorResponse('This group is private or hidden.', 403);
        }

        $media = $this->groupService->getGroupMediaGallery($group, (int) $request->query('limit', 30));

        return $this->successResponse(
            data: $media,
            message: 'Group media retrieved successfully.'
        );
    }

    /**
     * গ্রুপ ফলো/আনফলো টগল।
     */
    public function toggleFollow(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $followed = $this->groupService->toggleFollowGroup($group, $user);

        return $this->successResponse(
            data: [
                'group_id' => $group->id,
                'is_following' => $followed,
            ],
            message: $followed ? 'You are now following this group updates.' : 'Unfollowed group updates.'
        );
    }

    /**
     * গ্রুপের সেটিংস আপডেট করা (অ্যাডমিন)।
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if (! $group->hasPermission($user->id, 'manage_settings')) {
            return $this->errorResponse('Permission denied.', 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:60'],
            'privacy' => ['sometimes', 'string', 'in:public,private,hidden,invite_only'],
            'membership_approval_mode' => ['sometimes', 'string', 'in:anyone,approval_required,admin_only'],
            'post_approval_mode' => ['sometimes', 'string', 'in:auto,admin_approval,new_members_approval'],
            'cover_image_url' => ['nullable', 'string'],
            'avatar_url' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
        ]);

        $group->update($validated);

        return $this->successResponse(
            data: $group->toResponseArray($user),
            message: 'Group settings updated successfully.'
        );
    }

    /**
     * গ্রুপ ডিলিট করা (শুধুমাত্র ওনার)।
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if (! $group->isOwner($user->id)) {
            return $this->errorResponse('Only group owner can delete the group.', 403);
        }

        $group->delete();

        return $this->successResponse(
            message: 'Group deleted successfully.'
        );
    }

    /**
     * গ্রুপে জয়েন করা বা রিকোয়েস্ট পাঠানো।
     */
    public function join(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $answers = $request->input('answers', []);

        $result = $this->groupService->joinGroup($group, $user, (array) $answers);

        return $this->successResponse(
            data: [
                'group_id' => $group->id,
                'status' => $result['status'],
                'members_count' => $result['members_count'] ?? $group->members_count,
            ],
            message: $result['message']
        );
    }

    /**
     * গ্রুপ ত্যাগ করা।
     */
    public function leave(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        try {
            $this->groupService->leaveGroup($group, $user);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }

        return $this->successResponse(
            message: 'Left the group successfully.'
        );
    }

    /**
     * ওনারশিপ অন্য মেম্বারকে হস্তান্তর করা (শুধুমাত্র ওনার)।
     */
    public function transferOwnership(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'new_owner_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $newOwner = User::findOrFail($validated['new_owner_id']);

        try {
            $transferred = $this->groupService->transferOwnership($group, $user, $newOwner);
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse(
            data: $transferred->toResponseArray($user),
            message: 'Group ownership transferred successfully.'
        );
    }

    /**
     * গ্রুপের মেম্বার তালিকা।
     */
    public function members(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if ($group->isPrivate() && ! $group->hasMember($user->id)) {
            return $this->errorResponse('This group is private.', 403);
        }

        $role = $request->query('role');
        $status = $request->query('status', 'active');
        $search = $request->query('q') ?? $request->query('query');
        $sort = $request->query('sort', 'newest');

        $query = GroupMember::where('group_members.group_id', $group->id);

        if ($status && $status !== 'all') {
            $query->where('group_members.status', $status);
        }

        if ($role && $role !== 'all') {
            $query->where('group_members.role', $role);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        match ($sort) {
            'oldest', 'join_date_asc' => $query->oldest('group_members.joined_at'),
            'alphabetical', 'name_asc' => $query->join('users', 'group_members.user_id', '=', 'users.id')->orderBy('users.name')->select('group_members.*'),
            'most_active', 'activity_desc' => $query->latest('group_members.updated_at'),
            default => $query->latest('group_members.joined_at'),
        };

        $members = $query->with('user.profile')->paginate((int) $request->query('per_page', 20));
        $members->getCollection()->transform(fn (GroupMember $m) => $m->toDetailedResponseArray());

        return $this->successResponse(
            data: $members->items(),
            message: 'Members retrieved successfully.',
            meta: [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'total' => $members->total(),
            ]
        );
    }

    /**
     * মেম্বারকে সাময়িক রেস্ট্রিক্ট করা।
     */
    public function restrictMember(Request $request, int $id, int $userId): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $member = $this->groupService->restrictMember(
            $group,
            $actor,
            $userId,
            (int) ($validated['duration_days'] ?? 7),
            $validated['reason']
        );

        return $this->successResponse(
            data: $member->toDetailedResponseArray(),
            message: 'Member restricted successfully.'
        );
    }

    /**
     * মেম্বারের রেস্ট্রিকশন প্রত্যাহার করা।
     */
    public function unrestrictMember(Request $request, int $id, int $userId): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $member = $this->groupService->unrestrictMember($group, $actor, $userId);

        return $this->successResponse(
            data: $member->toDetailedResponseArray(),
            message: 'Member unrestricted successfully.'
        );
    }

    /**
     * মেম্বারের পূর্ণাঙ্গ অ্যাক্টিভিটি হিস্ট্রি ও ডিটেইলস (Phase 06)।
     */
    public function memberActivity(Request $request, int $id, int $userId): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $activity = $this->groupService->getMemberActivityDetails($group, $actor, $userId);

        return $this->successResponse(
            data: $activity,
            message: 'Member activity history retrieved successfully.'
        );
    }

    /**
     * বাল্ক মেম্বারশিপ ম্যানেজমেন্ট (Role Assign, Remove, Approve, Reject, Restrict, Ban)।
     */
    public function bulkManage(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:assign_role,remove,approve,reject,restrict,ban'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
            'role' => ['nullable', 'string'],
            'duration_days' => ['nullable', 'integer'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->groupService->bulkManageMembers(
            $group,
            $actor,
            $validated['action'],
            $validated['user_ids'],
            $validated
        );

        return $this->successResponse(
            data: $result,
            message: "Bulk action '{$validated['action']}' completed successfully."
        );
    }

    /**
     * পেন্ডিং মেম্বারশিপ রিকোয়েস্ট তালিকা (শুধুমাত্র অ্যাডমিন/মডারেটর)।
     */
    public function requests(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if (! $group->hasPermission($user->id, 'approve_members')) {
            return $this->errorResponse('Permission denied.', 403);
        }

        $requests = GroupMember::where('group_id', $group->id)
            ->where('status', GroupMember::STATUS_PENDING)
            ->with(['user.profile'])
            ->latest('id')
            ->paginate((int) $request->query('per_page', 20));

        $requests->getCollection()->transform(fn (GroupMember $m) => $m->toResponseArray());

        return $this->successResponse(
            data: $requests->items(),
            message: 'Pending membership requests retrieved successfully.',
            meta: [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'total' => $requests->total(),
            ]
        );
    }

    /**
     * নিজের পেন্ডিং রিকোয়েস্ট ক্যানসেল করা।
     */
    public function cancelRequest(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $pending = GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('status', GroupMember::STATUS_PENDING)
            ->first();

        if (! $pending) {
            return $this->errorResponse('No pending join request found.', 404);
        }

        $pending->delete();

        return $this->successResponse(
            message: 'Join request cancelled successfully.'
        );
    }

    /**
     * পেন্ডিং মেম্বারশিপ অনুমোদন করা।
     */
    public function approve(Request $request, int $id, int $userId): JsonResponse
    {
        $admin = $request->user();
        $group = Group::findOrFail($id);

        try {
            $approved = $this->groupService->approveMember($group, $admin, $userId);
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }

        if (! $approved) {
            return $this->errorResponse('Pending membership request not found.', 404);
        }

        return $this->successResponse(
            message: 'Member approved successfully.'
        );
    }

    /**
     * পেন্ডিং মেম্বারশিপ প্রত্যাখ্যান করা।
     */
    public function reject(Request $request, int $id, int $userId): JsonResponse
    {
        $admin = $request->user();
        $group = Group::findOrFail($id);

        try {
            $this->groupService->rejectMember($group, $admin, $userId);
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }

        return $this->successResponse(
            message: 'Member join request rejected.'
        );
    }

    /**
     * মেম্বার রিমুভ করা।
     */
    public function removeMember(Request $request, int $id, int $userId): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $this->groupService->removeMember($group, $actor, $userId, $request->input('reason'));

        return $this->successResponse(
            message: 'Member removed from group successfully.'
        );
    }

    /**
     * মেম্বারের রোল ও পারমিশন পরিবর্তন করা।
     */
    public function updateMemberRole(Request $request, int $id, int $userId): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'role' => ['required', 'string'],
            'permissions' => ['nullable', 'array'],
        ]);

        $results = $this->groupService->assignRole($group, $actor, [$userId], $validated['role'], $validated['permissions'] ?? null);

        return $this->successResponse(
            data: $results[0] ?? null,
            message: 'Member role updated successfully.'
        );
    }

    /**
     * গ্রুপের সকল রোল ও পারমিশন ক্যাটালগ (Phase 05)।
     */
    public function roles(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $catalog = $this->groupService->getGroupRolesCatalog($group, $user);

        return $this->successResponse(
            data: $catalog,
            message: 'Group roles and permissions retrieved successfully.'
        );
    }

    /**
     * কাস্টম রোল তৈরি করা।
     */
    public function storeRole(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
        ]);

        $role = $this->groupService->createCustomRole($group, $actor, $validated);

        return $this->successResponse(
            data: $role->toResponseArray(),
            message: 'Custom role created successfully.',
            statusCode: 201
        );
    }

    /**
     * কাস্টম রোল আপডেট করা।
     */
    public function updateRole(Request $request, int $id, int $roleId): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $role = $this->groupService->updateCustomRole($group, $actor, $roleId, $validated);

        return $this->successResponse(
            data: $role->toResponseArray(),
            message: 'Custom role updated successfully.'
        );
    }

    /**
     * কাস্টম রোল মুছে ফেলা।
     */
    public function destroyRole(Request $request, int $id, int $roleId): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $this->groupService->deleteCustomRole($group, $actor, $roleId);

        return $this->successResponse(
            message: 'Custom role deleted successfully.'
        );
    }

    /**
     * মেম্বারদের একক বা বাল্ক রোল অ্যাসাইন করা।
     */
    public function assignRole(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'role' => ['required', 'string'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
            'user_id' => ['nullable', 'integer'],
            'permissions' => ['nullable', 'array'],
        ]);

        $userIds = $validated['user_ids'] ?? ($validated['user_id'] ? [$validated['user_id']] : []);

        if (empty($userIds)) {
            return $this->errorResponse('At least one user must be specified for role assignment.', 422);
        }

        $results = $this->groupService->assignRole($group, $actor, $userIds, $validated['role'], $validated['permissions'] ?? null);

        return $this->successResponse(
            data: $results,
            message: 'Role assigned successfully.'
        );
    }

    /**
     * মেম্বারদের রোল রিভোক করে সাধারণ মেম্বার করা।
     */
    public function revokeRole(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $userIds = $validated['user_ids'] ?? ($validated['user_id'] ? [$validated['user_id']] : []);

        if (empty($userIds)) {
            return $this->errorResponse('At least one user must be specified.', 422);
        }

        $results = $this->groupService->revokeRole($group, $actor, $userIds);

        return $this->successResponse(
            data: $results,
            message: 'Role revoked successfully.'
        );
    }

    /**
     * রোল পরিবর্তনের হিস্ট্রি ও অডিট ট্রেইল।
     */
    public function rolesHistory(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $history = $this->groupService->getRolesHistory($group, $actor);

        return $this->successResponse(
            data: $history,
            message: 'Role history retrieved successfully.'
        );
    }

    /**
     * মেম্বারকে সতর্কবার্তা দেওয়া।
     */
    public function warnMember(Request $request, int $id, int $userId): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $strike = $this->groupService->warnMember($group, $actor, $userId, $request->input('reason'));

        return $this->successResponse(
            data: $strike,
            message: 'Member warned successfully.'
        );
    }

    /**
     * মেম্বারকে মিউট করা।
     */
    public function muteMember(Request $request, int $id, int $userId): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $request->validate([
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:43200'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $member = $this->groupService->muteMember($group, $actor, $userId, (int) $request->input('duration_minutes'), $request->input('reason'));

        return $this->successResponse(
            data: $member->toResponseArray(),
            message: 'Member muted successfully.'
        );
    }

    /**
     * মেম্বারকে ব্যান করা।
     */
    public function banMember(Request $request, int $id, int $userId): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $member = $this->groupService->banMember($group, $actor, $userId, $request->input('reason'));

        return $this->successResponse(
            data: $member->toResponseArray(),
            message: 'Member banned from group.'
        );
    }

    /**
     * ব্যান প্রত্যাহার করা।
     */
    public function unbanMember(Request $request, int $id, int $userId): JsonResponse
    {
        $actor = $request->user();
        $group = Group::findOrFail($id);

        $member = $this->groupService->unbanMember($group, $actor, $userId);

        return $this->successResponse(
            data: $member->toResponseArray(),
            message: 'Member unbanned successfully.'
        );
    }

    /**
     * গ্রুপের পোস্ট ফিড।
     */
    public function feed(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);
        $perPage = min((int) $request->query('per_page', 15), 50);

        $filters = [
            'filter' => $request->query('filter'),
            'type' => $request->query('type'),
            'sort' => $request->query('sort', 'newest'),
            'status' => $request->query('status', 'published'),
        ];

        $posts = $this->groupService->getGroupFeed($group, $user, $perPage, $filters);

        return $this->successResponse(
            data: $posts->items(),
            message: 'Group feed retrieved successfully.',
            meta: [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ]
        );
    }

    /**
     * নির্দিষ্ট পোস্ট বিস্তারিত দেখা।
     */
    public function showPost(Request $request, int $id, int $postId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if ($group->isPrivate() && (! $user || ! $group->hasMember($user->id))) {
            return $this->errorResponse('This group is private.', 403);
        }

        $post = Post::where('group_id', $group->id)
            ->where('id', $postId)
            ->with(['user.profile', 'media', 'reactions', 'comments.user.profile'])
            ->firstOrFail();

        return $this->successResponse(
            data: $post->toResponseArray($user),
            message: 'Post retrieved successfully.'
        );
    }

    /**
     * গ্রুপে পোস্ট করা।
     */
    public function storePost(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'in:text,media,poll,announcement'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
            'poll_data' => ['nullable', 'array'],
            'link_preview' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:published,draft,scheduled'],
            'scheduled_at' => ['nullable', 'date'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        if (empty(trim($validated['content'] ?? '')) && empty($validated['media_ids']) && empty($validated['poll_data'])) {
            return $this->errorResponse('Post content, media, or poll is required.', 422);
        }

        try {
            $post = $this->groupService->createGroupPost($group, $user, $validated);

            $statusMessage = match ($post->status) {
                'draft' => 'Post saved as draft.',
                'scheduled' => 'Post scheduled successfully.',
                'pending_approval' => 'Post submitted for admin approval.',
                default => 'Post published successfully.',
            };

            return $this->successResponse(
                data: $post->toResponseArray($user),
                message: $statusMessage,
                statusCode: 201
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * পোস্ট এডিট করা।
     */
    public function updatePost(Request $request, int $id, int $postId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:5000'],
            'poll_data' => ['nullable', 'array'],
            'link_preview' => ['nullable', 'array'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $post = $this->groupService->updateGroupPost($group, $user, $postId, $validated);

        return $this->successResponse(
            data: $post->toResponseArray($user),
            message: 'Post updated successfully.'
        );
    }

    /**
     * পোস্ট পিন বা আনপিন করা।
     */
    public function togglePinPost(Request $request, int $id, int $postId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $isPinned = $this->groupService->togglePinPost($group, $user, $postId);

        return $this->successResponse(
            data: ['is_pinned' => $isPinned],
            message: $isPinned ? 'Post pinned successfully.' : 'Post unpinned successfully.'
        );
    }

    /**
     * পোস্টের পোলে ভোট দেওয়া।
     */
    public function votePostPoll(Request $request, int $id, int $postId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'option_id' => ['required', 'integer'],
        ]);

        $updatedPoll = $this->groupService->votePostPoll($group, $user, $postId, (int) $validated['option_id']);

        return $this->successResponse(
            data: $updatedPoll,
            message: 'Vote cast successfully.'
        );
    }

    /**
     * পোস্টে রিয়্যাকশন দেওয়া / টগল করা।
     */
    public function reactPost(Request $request, int $id, int $postId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:like,love,care,haha,wow,sad,angry'],
        ]);

        $res = $this->groupService->reactGroupPost($group, $user, $postId, $validated['type'] ?? 'like');

        return $this->successResponse(
            data: $res,
            message: 'Reaction updated successfully.'
        );
    }

    /**
     * পোস্ট রিপোর্ট করা।
     */
    public function reportPost(Request $request, int $id, int $postId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'reason_category' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $report = $this->groupService->reportItem($group, $user, [
            'reportable_type' => Post::class,
            'reportable_id' => $postId,
            'reason_category' => $validated['reason_category'],
            'description' => $validated['description'] ?? null,
        ]);

        return $this->successResponse(
            data: $report->toResponseArray(),
            message: 'Post reported to moderators.'
        );
    }

    /**
     * মডারেশন রিপোর্ট তালিকা।
     */
    public function moderationReports(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if (! $group->hasPermission($user->id, 'moderate_members')) {
            return $this->errorResponse('Permission denied.', 403);
        }

        $reports = GroupReport::where('group_id', $group->id)
            ->with(['reporter.profile', 'reviewer'])
            ->latest('id')
            ->paginate((int) $request->query('per_page', 15));

        $reports->getCollection()->transform(fn (GroupReport $r) => $r->toResponseArray());

        return $this->successResponse(
            data: $reports->items(),
            message: 'Moderation reports retrieved successfully.',
            meta: [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'total' => $reports->total(),
            ]
        );
    }

    /**
     * রিপোর্টে সিদ্ধান্ত নেওয়া ও অ্যাকশন গ্রহণ।
     */
    public function actionReport(Request $request, int $id, int $reportId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:action_taken,dismissed,closed'],
            'decision_note' => ['nullable', 'string', 'max:500'],
            'remove_content' => ['nullable', 'boolean'],
        ]);

        $report = $this->groupService->reviewReport(
            $group,
            $user,
            $reportId,
            $validated['status'],
            $validated['decision_note'] ?? null
        );

        if (! empty($validated['remove_content']) && $report->reportable_type === Post::class) {
            Post::where('group_id', $group->id)->where('id', $report->reportable_id)->delete();
            if ($group->posts_count > 0) {
                $group->decrement('posts_count');
            }
        }

        return $this->successResponse(
            data: $report->toResponseArray(),
            message: 'Report resolved successfully.'
        );
    }

    /**
     * পোস্ট ডিলিট করা।
     */
    public function destroyPost(Request $request, int $id, int $postId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $this->groupService->deletePost($group, $user, $postId);

        return $this->successResponse(
            message: 'Post deleted successfully.'
        );
    }

    /**
     * মডারেশন কিউ (অননুমোদিত পোস্ট তালিকা)।
     */
    public function moderationPosts(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $posts = $this->groupService->getModerationQueue($group, $user, (int) $request->query('per_page', 15));

        return $this->successResponse(
            data: $posts->items(),
            message: 'Moderation queue retrieved successfully.',
            meta: [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ]
        );
    }

    /**
     * পোস্ট অনুমোদন করা।
     */
    public function approvePost(Request $request, int $id, int $postId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $post = $this->groupService->approvePost($group, $user, $postId);

        return $this->successResponse(
            data: $post->toResponseArray($user),
            message: 'Post approved and published.'
        );
    }

    /**
     * পোস্ট রিজেক্ট করা।
     */
    public function rejectPost(Request $request, int $id, int $postId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $this->groupService->rejectPost($group, $user, $postId, $request->input('reason'));

        return $this->successResponse(
            message: 'Post rejected.'
        );
    }

    /**
     * পোল তালিকা।
     */
    public function polls(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if ($group->isPrivate() && (! $user || ! $group->hasMember($user->id))) {
            return $this->errorResponse('This group is private.', 403);
        }

        $polls = $group->polls()->with(['options', 'creator'])->paginate((int) $request->query('per_page', 10));
        $polls->getCollection()->transform(fn ($p) => $p->toResponseArray($user));

        return $this->successResponse(
            data: $polls->items(),
            message: 'Polls retrieved successfully.'
        );
    }

    /**
     * পোল তৈরি করা।
     */
    public function storePoll(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*' => ['required', 'string', 'max:200'],
            'is_multiple_choice' => ['nullable', 'boolean'],
            'is_anonymous' => ['nullable', 'boolean'],
            'can_change_vote' => ['nullable', 'boolean'],
            'ends_at' => ['nullable', 'date', 'after:now'],
        ]);

        $poll = $this->groupService->createPoll($group, $user, $validated);

        return $this->successResponse(
            data: $poll->toResponseArray($user),
            message: 'Poll created successfully.',
            statusCode: 201
        );
    }

    /**
     * পোলে ভোট দেওয়া।
     */
    public function votePoll(Request $request, int $id, int $pollId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $request->validate([
            'option_ids' => ['required'],
        ]);

        $poll = $this->groupService->votePoll($group, $user, $pollId, $request->input('option_ids'));

        return $this->successResponse(
            data: $poll->toResponseArray($user),
            message: 'Vote submitted successfully.'
        );
    }

    /**
     * ইভেন্ট তালিকা।
     */
    public function events(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if ($group->isPrivate() && (! $user || ! $group->hasMember($user->id))) {
            return $this->errorResponse('This group is private.', 403);
        }

        $events = $group->events()->with('creator')->paginate((int) $request->query('per_page', 10));
        $events->getCollection()->transform(fn ($e) => $e->toResponseArray($user));

        return $this->successResponse(
            data: $events->items(),
            message: 'Events retrieved successfully.'
        );
    }

    /**
     * ইভেন্ট তৈরি করা।
     */
    public function storeEvent(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:150'],
            'is_online' => ['nullable', 'boolean'],
            'meeting_url' => ['nullable', 'string', 'url'],
            'start_time' => ['required', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'timezone' => ['nullable', 'string'],
        ]);

        $event = $this->groupService->createEvent($group, $user, $validated);

        return $this->successResponse(
            data: $event->toResponseArray($user),
            message: 'Event created successfully.',
            statusCode: 201
        );
    }

    /**
     * ইভেন্টে RSVP দেওয়া।
     */
    public function rsvpEvent(Request $request, int $id, int $eventId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:going,interested,not_going'],
        ]);

        $rsvp = $this->groupService->rsvpEvent($group, $user, $eventId, $validated['status']);

        return $this->successResponse(
            data: $rsvp,
            message: 'RSVP updated successfully.'
        );
    }

    /**
     * অ্যানাউন্সমেন্ট প্রকাশ করা।
     */
    public function storeAnnouncement(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:5000'],
            'cta_text' => ['nullable', 'string', 'max:50'],
            'cta_url' => ['nullable', 'string', 'url'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $announcement = $this->groupService->createAnnouncement($group, $user, $validated);

        return $this->successResponse(
            data: $announcement->toResponseArray(),
            message: 'Announcement published successfully.',
            statusCode: 201
        );
    }

    /**
     * রুলস তালিকা ও নতুন রুল যোগ করা।
     */
    public function rules(Request $request, int $id): JsonResponse
    {
        $group = Group::findOrFail($id);

        return $this->successResponse(
            data: $group->rules,
            message: 'Rules retrieved successfully.'
        );
    }

    public function storeRule(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:1000'],
            'enforcement_level' => ['nullable', 'string', 'in:warning,strike,removal'],
        ]);

        $rule = $this->groupService->addRule($group, $user, $validated);

        return $this->successResponse(
            data: $rule,
            message: 'Rule added successfully.',
            statusCode: 201
        );
    }

    public function updateRule(Request $request, int $id, int $ruleId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'enforcement_level' => ['nullable', 'string', 'in:warning,strike,removal'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $rule = $this->groupService->updateRule($group, $user, $ruleId, $validated);
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }

        return $this->successResponse(
            data: $rule,
            message: 'Rule updated successfully.'
        );
    }

    public function destroyRule(Request $request, int $id, int $ruleId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        try {
            $this->groupService->deleteRule($group, $user, $ruleId);
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }

        return $this->successResponse(
            data: null,
            message: 'Rule deleted successfully.'
        );
    }

    public function reorderRules(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'rule_ids' => ['required', 'array'],
            'rule_ids.*' => ['integer', 'exists:group_rules,id'],
        ]);

        try {
            $rules = $this->groupService->reorderRules($group, $user, $validated['rule_ids']);
        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }

        return $this->successResponse(
            data: $rules,
            message: 'Rules reordered successfully.'
        );
    }

    /**
     * স্ক্রিনিং প্রশ্নাবলি।
     */
    public function questions(Request $request, int $id): JsonResponse
    {
        $group = Group::findOrFail($id);

        return $this->successResponse(
            data: $group->questions,
            message: 'Questions retrieved successfully.'
        );
    }

    public function storeQuestion(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'type' => ['nullable', 'string', 'in:text,multiple_choice,checkbox,boolean'],
            'options' => ['nullable', 'array'],
            'is_required' => ['nullable', 'boolean'],
        ]);

        $q = $this->groupService->addQuestion($group, $user, $validated);

        return $this->successResponse(
            data: $q,
            message: 'Screening question added successfully.',
            statusCode: 201
        );
    }

    /**
     * ফাইল আপলোড ও তালিকা।
     */
    public function files(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if ($group->isPrivate() && (! $user || ! $group->hasMember($user->id))) {
            return $this->errorResponse('This group is private.', 403);
        }

        return $this->successResponse(
            data: $group->files()->with('user')->get()->map(fn ($f) => $f->toResponseArray()),
            message: 'Files retrieved successfully.'
        );
    }

    public function storeFile(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'file_url' => ['required', 'string'],
            'file_type' => ['nullable', 'string', 'max:50'],
            'file_size' => ['nullable', 'integer'],
        ]);

        $file = $this->groupService->uploadFile($group, $user, $validated);

        return $this->successResponse(
            data: $file->toResponseArray(),
            message: 'File added to group successfully.',
            statusCode: 201
        );
    }

    /**
     * রিপোর্ট জমা দেওয়া।
     */
    public function storeReport(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'reportable_type' => ['required', 'string'],
            'reportable_id' => ['required', 'integer'],
            'reason_category' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
            'evidence' => ['nullable', 'array'],
        ]);

        $report = $this->groupService->reportItem($group, $user, $validated);

        return $this->successResponse(
            data: $report->toResponseArray(),
            message: 'Report submitted for moderation review.',
            statusCode: 201
        );
    }

    /**
     * রিপোর্ট তালিকা ও পর্যালোচনা।
     */
    public function reports(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if (! $group->hasPermission($user->id, 'moderate_members')) {
            return $this->errorResponse('Permission denied.', 403);
        }

        $reports = $group->reports()->with(['reporter', 'reviewer'])->paginate((int) $request->query('per_page', 15));
        $reports->getCollection()->transform(fn ($r) => $r->toResponseArray());

        return $this->successResponse(
            data: $reports->items(),
            message: 'Reports retrieved successfully.'
        );
    }

    public function reviewReport(Request $request, int $id, int $reportId): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:action_taken,dismissed,closed'],
            'decision_note' => ['nullable', 'string', 'max:500'],
        ]);

        $report = $this->groupService->reviewReport($group, $user, $reportId, $validated['status'], $validated['decision_note'] ?? null);

        return $this->successResponse(
            data: $report->toResponseArray(),
            message: 'Report reviewed.'
        );
    }

    /**
     * গ্রুপ ড্যাশবোর্ড ও অ্যানালিটিক্স মেট্রিকস (Phase 04)।
     */
    public function analytics(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);
        $period = $request->query('period', $request->query('days', '30d'));
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $analytics = $this->groupService->getAnalytics($group, $user, $period, $startDate, $endDate);

        return $this->successResponse(
            data: $analytics,
            message: 'Group dashboard analytics retrieved successfully.'
        );
    }

    /**
     * গ্রুপ ড্যাশবোর্ড মূল ডেটা এন্ডপয়েন্ট।
     */
    public function dashboard(Request $request, int $id): JsonResponse
    {
        return $this->analytics($request, $id);
    }

    /**
     * মডারেশন অডিট হিস্ট্রি।
     */
    public function moderationHistory(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $history = $this->groupService->getModerationHistory($group, $user, (int) $request->query('per_page', 20));
        $history->getCollection()->transform(fn ($h) => $h->toResponseArray());

        return $this->successResponse(
            data: $history->items(),
            message: 'Moderation history retrieved successfully.'
        );
    }

    /**
     * ক্যাটাগরি তালিকা (ডাটাবেজ ও প্রফেশনাল ক্যাটাগরি হাব)।
     */
    public function categories(Request $request): JsonResponse
    {
        $categories = GroupCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse(
            data: $categories->map(fn (GroupCategory $c) => $c->toResponseArray())->values(),
            message: 'Categories retrieved successfully.'
        );
    }

    /**
     * অ্যাডমিনিস্ট্রেটরদের জন্য নতুন ক্যাটাগরি তৈরি।
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            return $this->errorResponse('Unauthorized. Administrator access required.', 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:group_categories,name'],
            'icon' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $category = GroupCategory::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'icon' => $validated['icon'] ?? 'folder-outline',
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
            'is_system' => false,
            'groups_count' => 0,
        ]);

        return $this->successResponse(
            data: $category->toResponseArray(),
            message: 'Category created successfully.',
            statusCode: 201
        );
    }

    /**
     * গ্রুপ ইমেজ আপলোড (কভার ও প্রোফাইল ফটো)।
     */
    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:15360'],
            'file' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:15360'],
            'avatar' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:15360'],
            'cover' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:15360'],
            'type' => ['nullable', 'string', 'in:avatar,cover,general'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
        ]);

        $file = $request->file('image') ?? $request->file('file') ?? $request->file('avatar') ?? $request->file('cover');
        if (! $file) {
            return $this->errorResponse('No image file uploaded.', 422);
        }

        $type = $request->input('type') ?: ($request->hasFile('avatar') ? 'avatar' : ($request->hasFile('cover') ? 'cover' : 'general'));
        $folder = $type === 'cover' ? 'groups/covers' : ($type === 'avatar' ? 'groups/avatars' : 'groups/media');
        $path = $file->store($folder, 'public');
        $url = '/storage/'.$path;

        if ($groupId = $request->input('group_id')) {
            $group = Group::findOrFail($groupId);
            $user = $request->user();
            if (! $group->hasPermission($user->id, 'manage_settings')) {
                return $this->errorResponse('Permission denied to update group media.', 403);
            }
            if ($type === 'avatar') {
                $group->update(['avatar_url' => $url]);
            } elseif ($type === 'cover') {
                $group->update(['cover_image_url' => $url]);
            }
        }

        $mediaId = null;
        if ($user = $request->user()) {
            if ($type === 'general') {
                $disk = config('filesystems.default', 'public');
                $media = Media::create([
                    'user_id' => $user->id,
                    'collection' => 'groups',
                    'disk' => $disk,
                    'original_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'checksum' => hash_file('sha256', $file->getRealPath()),
                    'processing_status' => 'completed',
                    'metadata' => [
                        'original_filename' => $file->getClientOriginalName(),
                        'url' => $url,
                    ],
                ]);
                $mediaId = $media->id;
            }
        }

        return $this->successResponse(
            data: [
                'id' => $mediaId,
                'url' => $url,
                'path' => $path,
                'type' => $type,
            ],
            message: 'Image uploaded successfully.'
        );
    }

    /**
     * নির্দিষ্ট গ্রুপের জন্য মিডিয়া আপলোড।
     */
    public function uploadGroupMedia(Request $request, int $id): JsonResponse
    {
        $request->merge(['group_id' => $id]);

        return $this->uploadMedia($request);
    }

    /**
     * ইউজারের পেন্ডিং ইনভিটেশন তালিকা।
     */
    public function myInvitations(Request $request): JsonResponse
    {
        $user = $request->user();
        $invitations = GroupInvitation::where('invitee_id', $user->id)
            ->where('status', GroupInvitation::STATUS_PENDING)
            ->with(['group', 'inviter'])
            ->latest()
            ->get();

        return $this->successResponse(
            data: $invitations->map(fn ($inv) => $inv->toResponseArray())->values(),
            message: 'Pending group invitations retrieved successfully.'
        );
    }

    /**
     * নির্দিষ্ট গ্রুপের ইনভিটেশন তালিকা।
     */
    public function groupInvitations(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if (! $group->hasPermission($user->id, 'invite_members') && ! $group->isAdmin($user->id)) {
            return $this->errorResponse('Permission denied.', 403);
        }

        $invitations = GroupInvitation::where('group_id', $group->id)
            ->with(['invitee', 'inviter'])
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        $invitations->getCollection()->transform(fn ($i) => $i->toResponseArray());

        return $this->successResponse(
            data: $invitations->items(),
            message: 'Group invitations retrieved successfully.',
            meta: [
                'current_page' => $invitations->currentPage(),
                'last_page' => $invitations->lastPage(),
                'total' => $invitations->total(),
            ]
        );
    }

    /**
     * ফ্রেন্ডদের গ্রুপে ইনভাইট পাঠানো।
     */
    public function inviteMembers(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        if (! $group->hasPermission($user->id, 'invite_members') && ! $group->isAdmin($user->id)) {
            return $this->errorResponse('Permission denied to invite members.', 403);
        }

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $created = [];
        foreach (array_unique($validated['user_ids']) as $inviteeId) {
            if ((int) $inviteeId === (int) $user->id) {
                continue;
            }

            // Don't invite if already a member
            if ($group->isMember($inviteeId)) {
                continue;
            }

            $invitation = GroupInvitation::where('group_id', $group->id)
                ->where('invitee_id', (int) $inviteeId)
                ->first();

            if (! $invitation) {
                $invitation = GroupInvitation::create([
                    'group_id' => $group->id,
                    'invitee_id' => (int) $inviteeId,
                    'inviter_id' => $user->id,
                    'token' => Str::random(40),
                    'role' => GroupMember::ROLE_MEMBER,
                    'status' => GroupInvitation::STATUS_PENDING,
                    'expires_at' => now()->addDays(14),
                ]);
            } elseif ($invitation->status !== GroupInvitation::STATUS_PENDING) {
                $invitation->update([
                    'inviter_id' => $user->id,
                    'token' => Str::random(40),
                    'status' => GroupInvitation::STATUS_PENDING,
                    'expires_at' => now()->addDays(14),
                ]);
            }

            try {
                if (app()->bound(NotificationServiceInterface::class)) {
                    app(NotificationServiceInterface::class)->send(
                        recipientId: (int) $inviteeId,
                        type: 'notification.group_invitation',
                        data: [
                            'group_id' => $group->id,
                            'group_name' => $group->name,
                            'inviter_id' => $user->id,
                            'inviter_name' => $user->name,
                            'title' => 'Group Invitation',
                            'message' => "{$user->name} invited you to join {$group->name}.",
                        ],
                        channels: ['database']
                    );
                }
            } catch (\Throwable) {
            }

            $created[] = $invitation->id;
        }

        return $this->successResponse(
            data: [
                'invited_count' => count($created),
            ],
            message: 'Invitations sent successfully.'
        );
    }

    /**
     * ইনভিটেশন গ্রহণ করা।
     */
    public function acceptInvitation(Request $request, int $invitationId): JsonResponse
    {
        $user = $request->user();
        $invitation = GroupInvitation::where('id', $invitationId)
            ->where('invitee_id', $user->id)
            ->firstOrFail();

        if ($invitation->status !== GroupInvitation::STATUS_PENDING || $invitation->isExpired()) {
            return $this->errorResponse('This invitation is no longer valid.', 422);
        }

        $group = $invitation->group;

        $existing = GroupMember::where('group_id', $group->id)->where('user_id', $user->id)->first();
        $wasActive = $existing && $existing->isActive();

        // Add member
        $member = GroupMember::updateOrCreate([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ], [
            'role' => $invitation->role ?: GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
            'invited_by' => $invitation->inviter_id,
        ]);

        $invitation->update([
            'status' => GroupInvitation::STATUS_ACCEPTED,
            'uses_count' => $invitation->uses_count + 1,
        ]);

        if (! $wasActive) {
            $group->increment('members_count');
            if (isset($group->active_members_count)) {
                $group->increment('active_members_count');
            }
        }

        if ($group->conversation_id) {
            ConversationParticipant::firstOrCreate([
                'conversation_id' => $group->conversation_id,
                'user_id' => $user->id,
            ], [
                'role' => 'member',
                'last_read_at' => now(),
            ]);
        }

        return $this->successResponse(
            data: [
                'group_id' => $group->id,
                'slug' => $group->slug,
                'role' => $member->role,
            ],
            message: 'You have joined '.$group->name.' successfully.'
        );
    }

    /**
     * ইনভিটেশন প্রত্যাখ্যান করা।
     */
    public function declineInvitation(Request $request, int $invitationId): JsonResponse
    {
        $user = $request->user();
        $invitation = GroupInvitation::where('id', $invitationId)
            ->where('invitee_id', $user->id)
            ->firstOrFail();

        $invitation->update([
            'status' => GroupInvitation::STATUS_DECLINED,
        ]);

        return $this->successResponse(
            message: 'Invitation declined.'
        );
    }

    /**
     * গ্রুপের নোটিফিকেশন প্রেফারেন্স পাওয়া।
     */
    public function getNotificationPreferences(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $pref = GroupNotificationPreference::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        $defaultSettings = [
            'new_posts' => true,
            'comments' => true,
            'member_requests' => true,
            'mentions' => true,
            'events' => true,
        ];

        return $this->successResponse(
            data: [
                'preference' => $pref?->preference ?? GroupNotificationPreference::PREF_ALL,
                'settings' => $pref?->settings ?? $defaultSettings,
            ],
            message: 'Notification preferences retrieved successfully.'
        );
    }

    /**
     * গ্রুপের নোটিফিকেশন প্রেফারেন্স আপডেট করা।
     */
    public function updateNotificationPreferences(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'preference' => ['nullable', 'string', 'in:all,highlights,friends,admin_only,off'],
            'settings' => ['nullable', 'array'],
            'settings.new_posts' => ['nullable', 'boolean'],
            'settings.comments' => ['nullable', 'boolean'],
            'settings.member_requests' => ['nullable', 'boolean'],
            'settings.mentions' => ['nullable', 'boolean'],
            'settings.events' => ['nullable', 'boolean'],
        ]);

        $pref = GroupNotificationPreference::updateOrCreate([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ], [
            'preference' => $validated['preference'] ?? GroupNotificationPreference::PREF_ALL,
            'settings' => $validated['settings'] ?? [
                'new_posts' => true,
                'comments' => true,
                'member_requests' => true,
                'mentions' => true,
                'events' => true,
            ],
        ]);

        return $this->successResponse(
            data: [
                'preference' => $pref->preference,
                'settings' => $pref->settings,
            ],
            message: 'Notification preferences updated successfully.'
        );
    }
}
