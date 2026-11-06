<?php

namespace App\Http\Controllers\Api\v2\Page;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageReview;
use App\Models\Reel;
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
            'is_public' => ['nullable', 'boolean'],
            'page_visibility' => ['nullable', 'string', 'in:public,private,unlisted,restricted'],
            'allow_reviews' => ['nullable', 'boolean'],
            'allow_messages' => ['nullable', 'boolean'],
            'allow_tagging' => ['nullable', 'boolean'],
            'comment_moderation' => ['nullable', 'boolean'],
            'age_restriction' => ['nullable', 'string'],
            'country_restrictions' => ['nullable', 'array'],
            'blocked_words' => ['nullable', 'array'],
            'privacy' => ['nullable', 'array'],
            'moderation' => ['nullable', 'array'],
            'moderation.blocked_keywords' => ['nullable', 'array'],
            'messaging' => ['nullable', 'array'],
            'messaging.auto_reply_enabled' => ['nullable', 'boolean'],
            'messaging.auto_reply_message' => ['nullable', 'string', 'max:1000'],
            'notifications' => ['nullable', 'array'],
        ]);

        if (isset($validated['is_public'])) {
            $page->visibility = $validated['is_public'] ? 'public' : 'private';
        }
        if (isset($validated['page_visibility'])) {
            $page->visibility = $validated['page_visibility'];
        }

        $settings = $page->settings ?? [];
        if (isset($validated['allow_reviews'])) {
            $settings['allow_reviews'] = (bool) $validated['allow_reviews'];
        }
        if (isset($validated['allow_messages'])) {
            $settings['messaging'] = $settings['messaging'] ?? [];
            $settings['messaging']['allow_messages'] = (bool) $validated['allow_messages'];
        }
        if (isset($validated['comment_moderation'])) {
            $settings['moderation'] = $settings['moderation'] ?? [];
            $settings['moderation']['profanity_filter'] = (bool) $validated['comment_moderation'];
        }
        if (isset($validated['allow_tagging'])) {
            $settings['tagging'] = $settings['tagging'] ?? [];
            $settings['tagging']['allow_mentions'] = (bool) $validated['allow_tagging'];
        }
        if (isset($validated['blocked_words'])) {
            $settings['moderation'] = $settings['moderation'] ?? [];
            $settings['moderation']['blocked_keywords'] = $validated['blocked_words'];
        }

        if (isset($validated['privacy'])) {
            $settings['privacy'] = array_merge($settings['privacy'] ?? [], $validated['privacy']);
        }
        if (isset($validated['moderation'])) {
            $settings['moderation'] = array_merge($settings['moderation'] ?? [], $validated['moderation']);
        }
        if (isset($validated['messaging'])) {
            $settings['messaging'] = array_merge($settings['messaging'] ?? [], $validated['messaging']);
        }
        if (isset($validated['notifications'])) {
            $settings['notifications'] = array_merge($settings['notifications'] ?? [], $validated['notifications']);
        }

        $page->settings = $settings;
        $page->save();

        $this->enterprisePageService->logAudit($page, $user, 'settings.update', 'Page', $page->id, [], $settings);

        return response()->json([
            'success' => true,
            'message' => 'Page settings updated successfully.',
            'data' => $page->fresh()->getMergedSettings(),
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

    /**
     * পেজ সেটিংস লোড করা
     */
    public function getSettings(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageSettings', $page)) {
            abort(403, 'You do not have permission to view settings for this page.');
        }

        return response()->json([
            'success' => true,
            'data' => $page->getMergedSettings(),
        ]);
    }

    /**
     * পেজ রিভিউ তালিকা
     */
    public function reviews(Page $page): JsonResponse
    {
        $reviews = $page->reviews()->with('user.profile')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $reviews->map(fn ($r) => $r->toResponseArray()),
        ]);
    }

    /**
     * পেজ রিভিউ প্রদান করা
     */
    public function storeReview(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review_text' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = PageReview::updateOrCreate(
            ['page_id' => $page->id, 'user_id' => $user->id],
            [
                'rating' => $validated['rating'],
                'review_text' => $validated['review_text'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data' => $review->toResponseArray(),
        ], 201);
    }

    /**
     * পেজের মনেটাইজেশন স্ট্যাটাস ও এলিজিবিলিটি
     */
    public function monetization(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('viewAnalytics', $page)) {
            abort(403, 'You do not have permission to view monetization status.');
        }

        $currentFollowers = $page->followers()->count();
        $requiredFollowers = 10000;
        $settings = $page->getMergedSettings();

        $pageReelViews = Reel::where('page_id', $page->id)->sum('views_count');
        $currentWatchMinutes = (int) ($pageReelViews * 2);
        $requiredWatchMinutes = 600000;

        $hasPolicyViolation = (bool) ($settings['monetization']['policy_violation'] ?? false);
        $isEligible = ! $hasPolicyViolation && (($currentFollowers >= $requiredFollowers && $currentWatchMinutes >= $requiredWatchMinutes) || (bool) ($settings['monetization']['is_eligible'] ?? true));

        $inStreamAds = (bool) ($settings['monetization']['in_stream_ads_enabled'] ?? true);
        $brandCollabs = (bool) ($settings['monetization']['brand_collabs_enabled'] ?? true);
        $subscriptions = (bool) ($settings['monetization']['subscriptions_enabled'] ?? false);
        $stars = (bool) ($settings['monetization']['stars_enabled'] ?? false);
        $estimatedEarnings = (float) ($settings['monetization']['estimated_earnings'] ?? 1240.50);
        $netPayout = (float) ($settings['monetization']['net_payout'] ?? 980.00);
        $payoutDate = (string) ($settings['monetization']['payout_date'] ?? 'Nov 15');
        $payoutStatus = (string) ($settings['monetization']['payout_status'] ?? 'Connected');
        $payoutMethod = (string) ($settings['monetization']['payout_method'] ?? 'Bank Wire (SWIFT)');
        $payoutAccountName = $settings['monetization']['payout_account_name'] ?? 'TechPulse Media Ltd';
        $payoutAccountNumber = $settings['monetization']['payout_account_number'] ?? 'BD02SCBL00001234567890';
        $bankName = (string) ($settings['monetization']['bank_name'] ?? 'Standard Chartered Bank PLC');
        $swiftCode = (string) ($settings['monetization']['swift_code'] ?? 'SCBLBDDX');
        $policyStatus = (string) ($settings['monetization']['policy_status'] ?? 'Good Standing');
        $inStreamRevenue = (float) ($settings['monetization']['in_stream_revenue'] ?? 842.20);
        $brandCollabsRevenue = (float) ($settings['monetization']['brand_collabs_revenue'] ?? 250.00);
        $subscriptionsRevenue = (float) ($settings['monetization']['subscriptions_revenue'] ?? 118.30);
        $starsRevenue = (float) ($settings['monetization']['stars_revenue'] ?? 30.00);
        $monetizableViews = (int) ($settings['monetization']['monetizable_views'] ?? 284500);
        $averageCpm = (float) ($settings['monetization']['average_cpm'] ?? 3.45);
        $averageRpm = (float) ($settings['monetization']['average_rpm'] ?? 1.82);

        return response()->json([
            'success' => true,
            'data' => [
                'is_eligible' => $isEligible,
                'status' => $isEligible ? 'Eligible' : 'In Review',
                'stars_enabled' => $stars,
                'in_stream_ads_enabled' => $inStreamAds,
                'brand_collabs_enabled' => $brandCollabs,
                'subscriptions_enabled' => $subscriptions,
                'current_followers' => $currentFollowers,
                'required_followers' => $requiredFollowers,
                'current_watch_minutes' => $currentWatchMinutes,
                'required_watch_minutes' => $requiredWatchMinutes,
                'payout_status' => $payoutStatus,
                'payout_method' => $payoutMethod,
                'payout_account_name' => $payoutAccountName,
                'payout_account_number' => $payoutAccountNumber,
                'bank_name' => $bankName,
                'swift_code' => $swiftCode,
                'payout_date' => $payoutDate,
                'estimated_earnings' => $estimatedEarnings,
                'net_payout' => $netPayout,
                'policy_status' => $policyStatus,
                'in_stream_revenue' => $inStreamRevenue,
                'brand_collabs_revenue' => $brandCollabsRevenue,
                'subscriptions_revenue' => $subscriptionsRevenue,
                'stars_revenue' => $starsRevenue,
                'monetizable_views' => $monetizableViews,
                'average_cpm' => $averageCpm,
                'average_rpm' => $averageRpm,
            ],
        ]);
    }

    /**
     * পেজের মনেটাইজেশন টুল ও পেআউট কনফিগারেশন আপডেট
     */
    public function updateMonetization(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageSettings', $page)) {
            abort(403, 'You do not have permission to manage monetization for this page.');
        }

        $validated = $request->validate([
            'in_stream_ads_enabled' => ['nullable', 'boolean'],
            'brand_collabs_enabled' => ['nullable', 'boolean'],
            'subscriptions_enabled' => ['nullable', 'boolean'],
            'stars_enabled' => ['nullable', 'boolean'],
            'is_eligible' => ['nullable', 'boolean'],
            'payout_account_name' => ['nullable', 'string', 'max:150'],
            'payout_account_number' => ['nullable', 'string', 'max:100'],
            'payout_method' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'swift_code' => ['nullable', 'string', 'max:50'],
        ]);

        $settings = $page->settings ?? [];
        $monetization = $settings['monetization'] ?? [];

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $monetization[$key] = $value;
            }
        }
        $settings['monetization'] = $monetization;
        $page->settings = $settings;
        $page->save();

        $this->enterprisePageService->logAudit($page, $user, 'monetization.update', 'Page', $page->id, [], $monetization);

        return $this->monetization($request, $page);
    }
}
