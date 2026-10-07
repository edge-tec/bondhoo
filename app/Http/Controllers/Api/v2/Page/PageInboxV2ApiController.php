<?php

namespace App\Http\Controllers\Api\v2\Page;

use App\Events\PageConversationAssignedEvent;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageConversation;
use App\Models\PageMember;
use App\Models\User;
use App\Services\Page\EnterprisePageService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * এন্টারপ্রাইজ পেজ মেসেঞ্জার ও মাল্টি-এজেন্ট ইনবক্স কন্ট্রোলার:
 * কনভারসেশন আইসোলেশন, এজেন্ট অ্যাসাইনমেন্ট, ইন্টারনাল নোটস ও রেসপন্স আর্কিটেকচার।
 */
class PageInboxV2ApiController extends Controller
{
    public function __construct(
        protected EnterprisePageService $enterprisePageService
    ) {}

    /**
     * পেজের কনভারসেশন তালিকা প্রদর্শন
     */
    public function index(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageMessages', $page)) {
            abort(403, 'You do not have permission to access messages for this page.');
        }

        $filters = [
            'status' => $request->query('status'),
            'assigned_to' => $request->query('assigned_to'),
        ];
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $conversations = $this->enterprisePageService->getConversations($page, $filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $conversations->items(),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'total' => $conversations->total(),
            ],
        ]);
    }

    /**
     * নির্দিষ্ট কনভারসেশনের বিস্তারিত তথ্য ও মেসেজসমূহ
     */
    public function show(Request $request, Page $page, int $id): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageMessages', $page)) {
            abort(403, 'You do not have permission to view this conversation.');
        }

        $conversation = PageConversation::where('page_id', $page->id)
            ->with([
                'user.profile',
                'assignedAgent:id,name,username',
                'messages' => fn ($q) => $q->oldest(),
                'notes.user:id,name,username',
            ])
            ->findOrFail($id);

        // পেজ মেসেজগুলো পঠিত (read) হিসেবে মার্ক করা
        $conversation->update(['unread_page_count' => 0]);
        $conversation->messages()->where('sender_type', 'user')->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $conversation,
        ]);
    }

    /**
     * পেজের পক্ষ থেকে ব্যবহারকারীকে মেসেজ পাঠানো
     */
    public function reply(Request $request, Page $page, int $id): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageMessages', $page)) {
            abort(403, 'You do not have permission to send messages from this page.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $message = $this->enterprisePageService->sendMessage(
                $page,
                $user,
                $id,
                $validated['body'],
                'page'
            );

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully.',
                'data' => $message,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * সাধারণ ভিজিটর/ইউজার কর্তৃক পেজে মেসেজ পাঠানো
     */
    public function visitorSendMessage(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();

        // ইউজার কি পেজে ব্লক আছে কিনা যাচাই
        if ($page->isBlocked($user->id)) {
            abort(403, 'You are restricted from messaging this page.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        // কনভারসেশন খুঁজে বের করা অথবা তৈরি করা
        $conv = PageConversation::firstOrCreate(
            ['page_id' => $page->id, 'user_id' => $user->id],
            ['status' => 'open', 'last_message_at' => now()]
        );

        $message = $this->enterprisePageService->sendMessage(
            $page,
            $user,
            $conv->id,
            $validated['body'],
            'user'
        );

        // অটো-রিপ্লাই কনফিগারেশন চেক
        $settings = $page->settings ?? [];
        if (! empty($settings['messaging']['auto_reply_enabled']) && ! empty($settings['messaging']['auto_reply_message'])) {
            $this->enterprisePageService->sendMessage(
                $page,
                $user,
                $conv->id,
                $settings['messaging']['auto_reply_message'],
                'page'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Message sent to page.',
            'data' => $message,
        ], 201);
    }

    /**
     * কনভারসেশন নির্দিষ্ট এজেন্টের কাছে অ্যাসাইন করা
     */
    public function assignAgent(Request $request, Page $page, int $id): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageMessages', $page)) {
            abort(403, 'You do not have permission to assign conversations.');
        }

        $validated = $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $agentId = (int) $validated['assigned_to'];

        // এজেন্ট কি পেজের সদস্য বা ওনার কিনা যাচাই
        $isMember = (int) $page->owner_id === $agentId ||
            PageMember::where('page_id', $page->id)->where('user_id', $agentId)->where('status', 'active')->exists();

        if (! $isMember) {
            return response()->json([
                'success' => false,
                'message' => 'Target user is not an active team member of this page.',
            ], 422);
        }

        $conv = PageConversation::where('page_id', $page->id)->findOrFail($id);
        $conv->update(['assigned_to' => $agentId]);

        $this->enterprisePageService->logAudit($page, $user, 'inbox.assign_agent', 'PageConversation', $conv->id, null, [
            'assigned_to' => $agentId,
        ]);

        $agent = User::find($agentId);
        try {
            broadcast(new PageConversationAssignedEvent(
                pageId: (int) $page->id,
                conversationId: (int) $conv->id,
                assignedAgentId: $agentId,
                assignedAgentName: $agent?->name ?? 'Agent'
            ));
        } catch (\Throwable $e) {
            Log::warning('Could not broadcast PageConversationAssignedEvent: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Conversation assigned successfully.',
            'data' => $conv->fresh('assignedAgent:id,name,username'),
        ]);
    }

    /**
     * কনভারসেশনের স্ট্যাটাস পরিবর্তন (open, resolved, archived)
     */
    public function updateStatus(Request $request, Page $page, int $id): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageMessages', $page)) {
            abort(403, 'You do not have permission to update conversation status.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,resolved,archived'],
        ]);

        $conv = PageConversation::where('page_id', $page->id)->findOrFail($id);
        $conv->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => "Conversation marked as {$validated['status']}.",
            'data' => $conv,
        ]);
    }

    /**
     * কনভারসেশনে ইন্টারনাল স্টাফ নোট যুক্ত করা
     */
    public function addNote(Request $request, Page $page, int $id): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageMessages', $page)) {
            abort(403, 'You do not have permission to add staff notes.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $note = $this->enterprisePageService->addConversationNote(
            $page,
            $user,
            $id,
            $validated['body']
        );

        return response()->json([
            'success' => true,
            'message' => 'Staff note added.',
            'data' => $note->load('user:id,name,username'),
        ], 201);
    }

    /**
     * সাধারণ ভিজিটরের নিজস্ব কথোপকথন দেখা (স্টাফ নোটস সম্পূর্ণ গোপন থাকবে)
     */
    public function visitorConversation(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();

        if ($page->isBlocked($user->id)) {
            abort(403, 'You are restricted from messaging this page.');
        }

        $conversation = PageConversation::where('page_id', $page->id)
            ->where('user_id', $user->id)
            ->with(['messages' => fn ($q) => $q->oldest()])
            ->first();

        if (! $conversation) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        // ইউজার পেজ থেকে প্রাপ্ত মেসেজগুলো পঠিত হিসেবে চিহ্নিত করা
        $conversation->update(['unread_user_count' => 0]);
        $conversation->messages()->where('sender_type', 'page')->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $conversation->id,
                'page_id' => $conversation->page_id,
                'user_id' => $conversation->user_id,
                'status' => $conversation->status,
                'messages' => $conversation->messages,
                'created_at' => $conversation->created_at,
                'updated_at' => $conversation->updated_at,
            ],
        ]);
    }
}
