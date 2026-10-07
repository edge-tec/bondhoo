<?php

namespace App\Http\Controllers\Api\v2\Page;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\User;
use App\Services\Page\EnterprisePageService;
use App\Services\PageService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * এন্টারপ্রাইজ সোশ্যাল পেজ কোর কন্ট্রোলার:
 * পেজ লাইফসাইকেল (Create, Configure, Publish, Manage, Trash, Restore, Transfer Ownership).
 */
class PageV2ApiController extends Controller
{
    public function __construct(
        protected EnterprisePageService $enterprisePageService,
        protected PageService $pageService
    ) {}

    /**
     * পেজের তালিকা পেজিনেশনসহ প্রদর্শন (ফিল্টার: managed, following, discovery)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');
        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));

        $query = Page::query()->where('status', Page::STATUS_ACTIVE);

        if ($filter === 'managed' && $user) {
            $query->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', fn ($mq) => $mq->where('user_id', $user->id)->where('status', 'active'));
            });
        } elseif ($filter === 'following' && $user) {
            $query->whereHas('followers', fn ($fq) => $fq->where('user_id', $user->id));
        }

        if ($search = $request->query('q')) {
            $query->where(function ($sq) use ($search) {
                $sq->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $pages = $query->with('owner')->latest('followers_count')->paginate($perPage);

        $pages->getCollection()->transform(fn (Page $p) => $p->toResponseArray($user));

        return response()->json([
            'success' => true,
            'data' => $pages->items(),
            'meta' => [
                'current_page' => $pages->currentPage(),
                'last_page' => $pages->lastPage(),
                'total' => $pages->total(),
            ],
        ]);
    }

    /**
     * নতুন এন্টারপ্রাইজ পেজ তৈরি করা
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:60', 'alpha_dash', 'unique:pages,username'],
            'category' => ['required', 'string', 'max:50'],
            'sub_category' => ['nullable', 'string', 'max:50'],
            'bio' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'avatar_url' => ['nullable', 'string', 'url'],
            'cover_image_url' => ['nullable', 'string', 'url'],
            'website' => ['nullable', 'string', 'url'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:60'],
            'country' => ['nullable', 'string', 'max:60'],
            'cta_type' => ['nullable', 'string', 'in:contact_us,send_message,call_now,visit_website,learn_more'],
            'cta_url' => ['nullable', 'string', 'url'],
        ]);

        $name = trim($validated['name']);
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;
        while (Page::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        $page = Page::create(array_merge($validated, [
            'owner_id' => $user->id,
            'slug' => $slug,
            'username' => $validated['username'] ?? $slug,
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 0,
            'posts_count' => 0,
        ]));

        $this->enterprisePageService->logAudit($page, $user, 'page.create', 'Page', $page->id, null, [
            'name' => $page->name,
            'slug' => $page->slug,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Enterprise page created successfully.',
            'data' => $page->toResponseArray($user),
        ], 201);
    }

    /**
     * নির্দিষ্ট পেজের বিস্তারিত তথ্য প্রদর্শন
     */
    public function show(Request $request, string $slugOrId): JsonResponse
    {
        $user = $request->user();

        $page = Page::where('slug', $slugOrId)
            ->orWhere('username', $slugOrId)
            ->orWhere('id', $slugOrId)
            ->with(['owner.profile'])
            ->firstOrFail();

        if (! Gate::forUser($user)->allows('view', $page)) {
            abort(403, 'You are not authorized to view this page.');
        }

        return response()->json([
            'success' => true,
            'data' => $page->toResponseArray($user),
        ]);
    }

    /**
     * পেজের বিবরণ আপডেট করা
     */
    public function update(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('update', $page)) {
            abort(403, 'You do not have permission to update this page.');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'category' => ['sometimes', 'string', 'max:50'],
            'sub_category' => ['nullable', 'string', 'max:50'],
            'bio' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'avatar_url' => ['nullable', 'string', 'url'],
            'cover_image_url' => ['nullable', 'string', 'url'],
            'website' => ['nullable', 'string', 'url'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:60'],
            'country' => ['nullable', 'string', 'max:60'],
            'cta_type' => ['nullable', 'string', 'in:contact_us,send_message,call_now,visit_website,learn_more'],
            'cta_url' => ['nullable', 'string', 'url'],
            'visibility' => ['nullable', 'string', 'in:public,private,unlisted'],
        ]);

        $prevState = $page->only(array_keys($validated));
        $page->update($validated);

        $this->enterprisePageService->logAudit($page, $user, 'page.update', 'Page', $page->id, $prevState, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully.',
            'data' => $page->toResponseArray($user),
        ]);
    }

    /**
     * পেজ ট্র্যাশে স্থানান্তর করা (Soft Delete)
     */
    public function destroy(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('delete', $page)) {
            abort(403, 'Only the page owner can delete this page.');
        }

        $page->update(['status' => Page::STATUS_TRASH]);
        $page->delete();

        $this->enterprisePageService->logAudit($page, $user, 'page.trash', 'Page', $page->id);

        return response()->json([
            'success' => true,
            'message' => 'Page moved to trash successfully.',
        ]);
    }

    /**
     * ট্র্যাশ থেকে পেজ রিস্টোর করা
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $page = Page::onlyTrashed()->findOrFail($id);

        if ((int) $page->owner_id !== (int) $user->id) {
            abort(403, 'Only the page owner can restore this page.');
        }

        $page->restore();
        $page->update(['status' => Page::STATUS_ACTIVE]);

        $this->enterprisePageService->logAudit($page, $user, 'page.restore', 'Page', $page->id);

        return response()->json([
            'success' => true,
            'message' => 'Page restored successfully.',
            'data' => $page->toResponseArray($user),
        ]);
    }

    /**
     * পেজ সেটিংস পরিবর্তন
     */
    public function updateSettings(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageSettings', $page)) {
            abort(403, 'You do not have permission to manage settings for this page.');
        }

        $validated = $request->validate([
            'privacy' => ['nullable', 'array'],
            'moderation' => ['nullable', 'array'],
            'moderation.blocked_keywords' => ['nullable', 'array'],
            'messaging' => ['nullable', 'array'],
            'messaging.auto_reply_enabled' => ['nullable', 'boolean'],
            'messaging.auto_reply_message' => ['nullable', 'string', 'max:1000'],
            'notifications' => ['nullable', 'array'],
        ]);

        $updated = $this->enterprisePageService->updateSettings($page, $user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Page settings updated successfully.',
            'data' => $updated->settings,
        ]);
    }

    /**
     * পেজ ওনারশিপ হস্তান্তর
     */
    public function transferOwnership(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('transferOwnership', $page)) {
            abort(403, 'Only the page owner can transfer ownership.');
        }

        $validated = $request->validate([
            'new_owner_id' => ['required', 'exists:users,id'],
        ]);

        $newOwner = User::findOrFail($validated['new_owner_id']);

        try {
            $transferred = $this->enterprisePageService->transferOwnership($page, $user, $newOwner);

            return response()->json([
                'success' => true,
                'message' => "Ownership of '{$page->name}' transferred to {$newOwner->name}.",
                'data' => $transferred->toResponseArray($user),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
