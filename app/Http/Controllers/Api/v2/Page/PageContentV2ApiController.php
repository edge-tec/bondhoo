<?php

namespace App\Http\Controllers\Api\v2\Page;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Post;
use App\Services\Page\EnterprisePageService;
use App\Services\PageService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * এন্টারপ্রাইজ পেজ কনটেন্ট কন্ট্রোলার:
 * পোস্ট তৈরি, ড্রাফট, শিডিউলিং, ক্যালেন্ডার ফিড এবং লাইফসাইকেল ম্যানেজমেন্ট।
 */
class PageContentV2ApiController extends Controller
{
    public function __construct(
        protected EnterprisePageService $enterprisePageService,
        protected PageService $pageService
    ) {}

    /**
     * পেজের প্রকাশিত পোস্ট তালিকা (পাবলিক বা অনুমোদিত ভিউয়ার)
     */
    public function index(Request $request, Page $page): JsonResponse
    {
        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));

        $posts = Post::where('page_id', $page->id)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'published');
            })
            ->with(['author.profile', 'media'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * পেজের ড্রাফট পোস্ট তালিকা
     */
    public function drafts(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('createPost', $page)) {
            abort(403, 'You do not have permission to view drafts for this page.');
        }

        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $drafts = Post::where('page_id', $page->id)
            ->where('status', 'draft')
            ->with(['author.profile', 'media'])
            ->latest('updated_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $drafts->items(),
            'meta' => [
                'current_page' => $drafts->currentPage(),
                'last_page' => $drafts->lastPage(),
                'total' => $drafts->total(),
            ],
        ]);
    }

    /**
     * পেজের শিডিউলড পোস্ট তালিকা
     */
    public function scheduled(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('schedulePost', $page)) {
            abort(403, 'You do not have permission to view scheduled posts for this page.');
        }

        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $scheduled = Post::where('page_id', $page->id)
            ->where('status', 'scheduled')
            ->with(['author.profile', 'media'])
            ->orderBy('scheduled_at', 'asc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $scheduled->items(),
            'meta' => [
                'current_page' => $scheduled->currentPage(),
                'last_page' => $scheduled->lastPage(),
                'total' => $scheduled->total(),
            ],
        ]);
    }

    /**
     * কনটেন্ট ক্যালেন্ডার ফিড (মাসিক বা তারিখ সীমা অনুযায়ী সকল ড্রাফট, শিডিউলড ও প্রকাশিত পোস্ট)
     */
    public function calendar(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('createPost', $page)) {
            abort(403, 'You do not have permission to view the content calendar.');
        }

        $start = $request->query('start_date') ? Carbon::parse($request->query('start_date')) : now()->startOfMonth();
        $end = $request->query('end_date') ? Carbon::parse($request->query('end_date')) : now()->endOfMonth();

        $posts = Post::where('page_id', $page->id)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end])
                    ->orWhereBetween('scheduled_at', [$start, $end]);
            })
            ->with(['author:id,name,username', 'media'])
            ->get(['id', 'page_id', 'user_id', 'content', 'status', 'scheduled_at', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }

    /**
     * নতুন পোস্ট তৈরি করা (সরাসরি পাবলিশ, ড্রাফট বা শিডিউলিং)
     */
    public function store(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:10000'],
            'status' => ['nullable', 'string', 'in:published,draft,scheduled'],
            'scheduled_at' => ['nullable', 'required_if:status,scheduled', 'date', 'after:now'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
            'privacy' => ['nullable', 'string', 'in:public,followers,private'],
        ]);

        $status = $validated['status'] ?? 'published';

        if ($status === 'scheduled') {
            if (! Gate::forUser($user)->allows('schedulePost', $page)) {
                abort(403, 'You do not have permission to schedule posts for this page.');
            }
        } else {
            if (! Gate::forUser($user)->allows('createPost', $page)) {
                abort(403, 'You do not have permission to create posts on this page.');
            }
        }

        try {
            $post = $this->pageService->createPagePost(
                $page,
                $user,
                [
                    'content' => $validated['content'],
                    'media_ids' => $validated['media_ids'] ?? [],
                    'privacy' => $validated['privacy'] ?? 'public',
                    'status' => $status,
                    'scheduled_at' => ! empty($validated['scheduled_at']) ? Carbon::parse($validated['scheduled_at']) : null,
                ]
            );

            $this->enterprisePageService->logAudit($page, $user, 'post.create', 'Post', $post->id, null, [
                'status' => $status,
                'scheduled_at' => $validated['scheduled_at'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => $status === 'scheduled' ? 'Post scheduled successfully.' : ($status === 'draft' ? 'Draft saved successfully.' : 'Post published successfully.'),
                'data' => $post->load(['author.profile', 'media']),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * পোস্ট এডিট করা
     */
    public function update(Request $request, Page $page, Post $post): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('editPost', $page)) {
            abort(403, 'You do not have permission to edit posts on this page.');
        }

        if ((int) $post->page_id !== (int) $page->id) {
            abort(404, 'Post not found for this page.');
        }

        $validated = $request->validate([
            'content' => ['sometimes', 'string', 'max:10000'],
            'status' => ['nullable', 'string', 'in:published,draft,scheduled,archived'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $prev = $post->only(['content', 'status', 'scheduled_at']);
        $post->update($validated);

        $this->enterprisePageService->logAudit($page, $user, 'post.update', 'Post', $post->id, $prev, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully.',
            'data' => $post->fresh(['author.profile', 'media']),
        ]);
    }

    /**
     * পোস্ট ডিলিট করা
     */
    public function destroy(Request $request, Page $page, Post $post): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('editPost', $page)) {
            abort(403, 'You do not have permission to delete posts on this page.');
        }

        if ((int) $post->page_id !== (int) $page->id) {
            abort(404, 'Post not found for this page.');
        }

        $post->delete();
        $page->decrement('posts_count');

        $this->enterprisePageService->logAudit($page, $user, 'post.delete', 'Post', $post->id);

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);
    }

    /**
     * শিডিউলড পোস্টের সময় পরিবর্তন (Reschedule)
     */
    public function reschedule(Request $request, Page $page, Post $post): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('schedulePost', $page)) {
            abort(403, 'You do not have permission to reschedule posts on this page.');
        }

        if ((int) $post->page_id !== (int) $page->id) {
            abort(404, 'Post not found for this page.');
        }

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $prevTime = $post->scheduled_at;
        $post->update([
            'scheduled_at' => Carbon::parse($validated['scheduled_at']),
            'status' => 'scheduled',
        ]);

        $this->enterprisePageService->logAudit($page, $user, 'post.reschedule', 'Post', $post->id, [
            'scheduled_at' => $prevTime,
        ], [
            'scheduled_at' => $validated['scheduled_at'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post rescheduled successfully.',
            'data' => $post,
        ]);
    }
}
