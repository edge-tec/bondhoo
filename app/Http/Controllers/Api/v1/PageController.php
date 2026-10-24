<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * পেজ কন্ট্রোলার:
 * পাবলিক পেজ তৈরি, ফলো/আনফলো এবং পেজ পোস্ট পরিচালনা করে।
 */
class PageController extends Controller
{
    public function __construct(
        protected PageService $pageService
    ) {}

    /**
     * পেজগুলোর তালিকা পেজিনেশনসহ রিটার্ন করে।
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->query('per_page', 15);

        $query = Page::with('owner');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('bio', 'like', "%{$search}%");
            });
        }

        if ($category = $request->query('category')) {
            if ($category !== 'all') {
                $query->where('category', 'like', "%{$category}%");
            }
        }

        $pages = $query->latest('followers_count')->paginate($perPage);

        $pages->getCollection()->transform(fn (Page $p) => $p->toResponseArray($user));

        return $this->successResponse(
            data: $pages->items(),
            message: 'Pages retrieved successfully.',
            meta: [
                'current_page' => $pages->currentPage(),
                'last_page' => $pages->lastPage(),
                'total' => $pages->total(),
            ]
        );
    }

    /**
     * নতুন পেজ তৈরি করা।
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:50'],
            'bio' => ['nullable', 'string', 'max:500'],
            'short_description' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:3000'],
            'username' => ['nullable', 'string', 'max:60'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:60'],
            'visibility' => ['nullable', 'string', 'in:public,friends,private,unlisted,restricted'],
            'avatar_url' => ['nullable', 'string', 'max:1000'],
            'cover_image_url' => ['nullable', 'string', 'max:1000'],
        ]);

        $page = $this->pageService->createPage($user, $validated);

        return $this->successResponse(
            data: $page->toResponseArray($user),
            message: 'Page created successfully.',
            statusCode: 201
        );
    }

    /**
     * নির্দিষ্ট পেজের বিস্তারিত তথ্য নিয়ে আসা।
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();
        $page = Page::where('slug', $slug)->orWhere('id', $slug)->firstOrFail();

        return $this->successResponse(
            data: $page->toResponseArray($user),
            message: 'Page retrieved successfully.'
        );
    }

    /**
     * পেজ ফলো বা আনফলো করা।
     */
    public function follow(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $page = Page::findOrFail($id);

        $result = $this->pageService->toggleFollow($page, $user);

        return $this->successResponse(
            data: [
                'page_id' => $page->id,
                'is_following' => $result['is_following'],
                'followers_count' => $result['followers_count'],
            ],
            message: $result['message']
        );
    }

    /**
     * পেজের পোস্ট ফিড পেজিনেশনসহ প্রদর্শন করা।
     */
    public function feed(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $page = Page::findOrFail($id);
        $perPage = (int) $request->query('per_page', 15);

        $posts = $this->pageService->getPageFeed($page, $user, $perPage);

        return $this->successResponse(
            data: $posts->items(),
            message: 'Page feed retrieved successfully.',
            meta: [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ]
        );
    }

    /**
     * পেজের পক্ষ থেকে পোস্ট প্রকাশ করা (শুধুমাত্র ওনার বা অনুমোদিত মেম্বার)।
     */
    public function storePost(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $page = Page::findOrFail($id);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'in:text,media'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
        ]);

        $post = $this->pageService->createPagePost($page, $user, $validated);

        return $this->successResponse(
            data: $post->toResponseArray($user),
            message: 'Post published to page successfully.',
            statusCode: 201
        );
    }
}
