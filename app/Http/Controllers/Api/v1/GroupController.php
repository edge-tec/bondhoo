<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use App\Services\GroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * গ্রুপ এক্সপ্লোর ও সার্চ।
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) $request->query('per_page', 15), 50);

        $filters = [
            'q' => $request->query('q'),
            'category' => $request->query('category'),
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
            'group_type' => ['nullable', 'string', 'max:40'],
            'language' => ['nullable', 'string', 'max:10'],
            'location' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:60'],
            'privacy' => ['nullable', 'string', 'in:public,private,hidden,invite_only'],
            'membership_approval_mode' => ['nullable', 'string', 'in:anyone,approval_required,admin_only'],
            'post_approval_mode' => ['nullable', 'string', 'in:auto,admin_approval,new_members_approval'],
            'cover_image_url' => ['nullable', 'string'],
            'avatar_url' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
            'rules' => ['nullable', 'array'],
            'questions' => ['nullable', 'array'],
        ]);

        $group = $this->groupService->createGroup($user, $validated);

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

        $this->groupService->leaveGroup($group, $user);

        return $this->successResponse(
            message: 'Left the group successfully.'
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
        $search = $request->query('q');

        $query = GroupMember::where('group_id', $group->id)->where('status', $status);

        if ($role && $role !== 'all') {
            $query->where('role', $role);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $members = $query->with('user.profile')->paginate((int) $request->query('per_page', 20));
        $members->getCollection()->transform(fn (GroupMember $m) => $m->toResponseArray());

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
     * পেন্ডিং মেম্বারশিপ অনুমোদন করা।
     */
    public function approve(Request $request, int $id, int $userId): JsonResponse
    {
        $admin = $request->user();
        $group = Group::findOrFail($id);

        $approved = $this->groupService->approveMember($group, $admin, $userId);

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

        $this->groupService->rejectMember($group, $admin, $userId);

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
            'role' => ['required', 'string', 'in:admin,moderator,editor,member'],
            'permissions' => ['nullable', 'array'],
        ]);

        $member = $this->groupService->updateMemberRole($group, $actor, $userId, $validated['role'], $validated['permissions'] ?? null);

        return $this->successResponse(
            data: $member->toResponseArray(),
            message: 'Member role updated successfully.'
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

        $posts = $this->groupService->getGroupFeed($group, $user, $perPage);

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
     * গ্রুপে পোস্ট করা।
     */
    public function storePost(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'in:text,media,poll,announcement'],
            'poll_data' => ['nullable', 'array'],
            'link_preview' => ['nullable', 'array'],
        ]);

        $post = $this->groupService->createGroupPost($group, $user, $validated);

        return $this->successResponse(
            data: $post->toResponseArray($user),
            message: $post->status === 'published' ? 'Post published successfully.' : 'Post submitted for admin approval.',
            statusCode: 201
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
        $group = Group::findOrFail($id);

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
     * অ্যানালিটিক্স মেট্রিকস।
     */
    public function analytics(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);
        $days = (int) $request->query('days', 30);

        $analytics = $this->groupService->getAnalytics($group, $user, $days);

        return $this->successResponse(
            data: $analytics,
            message: 'Analytics retrieved successfully.'
        );
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
}
