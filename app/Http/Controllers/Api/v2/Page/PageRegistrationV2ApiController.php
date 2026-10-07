<?php

namespace App\Http\Controllers\Api\v2\Page;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageLocation;
use App\Models\PageVerificationRequest;
use App\Services\Page\PageRegistrationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * এন্টারপ্রাইজ পেজ রেজিস্ট্রেশন ও ট্যাক্সোনমি কন্ট্রোলার (API v2)
 */
class PageRegistrationV2ApiController extends Controller
{
    public function __construct(
        protected PageRegistrationService $registrationService
    ) {}

    /**
     * সক্রিয় পেজ টাইপসমূহের তালিকা
     */
    public function types(): JsonResponse
    {
        $types = $this->registrationService->getPageTypes();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * ক্যাটাগরি তালিকা (ঐচ্ছিক পেজ টাইপ ফিল্টার সহ)
     */
    public function categories(Request $request): JsonResponse
    {
        $pageTypeId = $request->query('page_type_id') ? (int) $request->query('page_type_id') : null;
        $categories = $this->registrationService->getCategories($pageTypeId);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * ক্যাটাগরি সার্চ
     */
    public function searchCategories(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');
        $pageTypeId = $request->query('page_type_id') ? (int) $request->query('page_type_id') : null;

        if (strlen($query) < 1) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $results = $this->registrationService->searchCategories($query, $pageTypeId);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * নির্দিষ্ট ক্যাটাগরির সাবক্যাটাগরি তালিকা
     */
    public function subcategories(int $id): JsonResponse
    {
        $subcategories = $this->registrationService->getSubcategories($id);

        return response()->json([
            'success' => true,
            'data' => $subcategories,
        ]);
    }

    /**
     * নির্দিষ্ট পেজ টাইপ ও ক্যাটাগরির ডায়নামিক ফিল্ডসমূহ
     */
    public function fields(Request $request, int $id): JsonResponse
    {
        $pageTypeId = (int) $request->query('page_type_id', 1);
        $subcategoryId = $request->query('subcategory_id') ? (int) $request->query('subcategory_id') : null;

        $fields = $this->registrationService->getCategoryFields($pageTypeId, $id, $subcategoryId);

        return response()->json([
            'success' => true,
            'data' => $fields,
        ]);
    }

    /**
     * রিয়েল-টাইম পেজ ইউজারনেম অ্যাভেইলেবিলিটি যাচাই
     */
    public function checkUsername(Request $request): JsonResponse
    {
        $username = (string) $request->query('username', '');
        $excludeId = $request->query('exclude_id') ? (int) $request->query('exclude_id') : null;

        if (empty($username)) {
            return response()->json([
                'success' => false,
                'available' => false,
                'message' => 'Please provide a username to check.',
                'suggestions' => [],
            ], 422);
        }

        $result = $this->registrationService->checkUsernameAvailability($username, $excludeId);

        return response()->json([
            'success' => true,
            'available' => $result['available'],
            'normalized' => $result['normalized'],
            'message' => $result['message'],
            'suggestions' => $result['suggestions'],
        ]);
    }

    /**
     * ইউজারের বর্তমান পেজ কোটা ও লিমিট স্ট্যাটাস
     */
    public function quota(Request $request): JsonResponse
    {
        $user = $request->user();
        $ownedCount = Page::where('owner_id', $user->id)->count();
        $maxAllowed = 10;
        $canCreate = $ownedCount < $maxAllowed;

        return response()->json([
            'success' => true,
            'data' => [
                'owned_pages' => $ownedCount,
                'max_allowed_pages' => $maxAllowed,
                'can_create' => $canCreate,
                'remaining' => max(0, $maxAllowed - $ownedCount),
            ],
        ]);
    }

    /**
     * ইউজারের ড্রাফট তালিকা
     */
    public function drafts(Request $request): JsonResponse
    {
        $user = $request->user();
        $drafts = $this->registrationService->getDrafts($user);

        return response()->json([
            'success' => true,
            'data' => $drafts,
        ]);
    }

    /**
     * ড্রাফট সংরক্ষণ / অটোসেভ
     */
    public function saveDraft(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'draft_id' => ['nullable', 'integer'],
            'current_step' => ['nullable', 'integer', 'min:1', 'max:12'],
            'form_data' => ['required', 'array'],
        ]);

        $draft = $this->registrationService->saveDraft(
            $user,
            $validated['form_data'],
            $validated['draft_id'] ?? null,
            $validated['current_step'] ?? 1
        );

        return response()->json([
            'success' => true,
            'message' => 'Draft saved successfully.',
            'data' => $draft,
        ]);
    }

    /**
     * নির্দিষ্ট ড্রাফট দেখা
     */
    public function getDraft(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $draft = $this->registrationService->getDraft($user, $id);

        return response()->json([
            'success' => true,
            'data' => $draft,
        ]);
    }

    /**
     * ড্রাফট ডিলিট করা
     */
    public function deleteDraft(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $deleted = $this->registrationService->deleteDraft($user, $id);

        return response()->json([
            'success' => $deleted,
            'message' => $deleted ? 'Draft deleted.' : 'Draft not found.',
        ]);
    }

    /**
     * সম্পূর্ণ পেজ তৈরি / রেজিস্ট্রেশন (Atomic Creation)
     */
    public function register(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'username' => ['nullable', 'string', 'max:60'],
            'page_type_id' => ['nullable', 'integer', 'exists:page_types,id'],
            'page_category_id' => ['nullable', 'integer', 'exists:page_categories,id'],
            'page_subcategory_id' => ['nullable', 'integer', 'exists:page_subcategories,id'],
            'category' => ['nullable', 'string', 'max:50'],
            'sub_category' => ['nullable', 'string', 'max:50'],
            'short_description' => ['nullable', 'string', 'max:300'],
            'bio' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:3000'],
            'avatar_url' => ['nullable', 'string', 'url'],
            'logo_url' => ['nullable', 'string', 'url'],
            'cover_image_url' => ['nullable', 'string', 'url'],
            'website' => ['nullable', 'string', 'url'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:60'],
            'state' => ['nullable', 'string', 'max:60'],
            'district' => ['nullable', 'string', 'max:60'],
            'country' => ['nullable', 'string', 'max:60'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'business_hours' => ['nullable', 'array'],
            'business_details' => ['nullable', 'array'],
            'custom_fields_data' => ['nullable', 'array'],
            'social_links' => ['nullable', 'array'],
            'gallery_urls' => ['nullable', 'array'],
            'cta_type' => ['nullable', 'string', 'max:40'],
            'cta_url' => ['nullable', 'string', 'url'],
            'visibility' => ['nullable', 'string', 'in:public,unlisted,restricted'],
            'privacy_settings' => ['nullable', 'array'],
            'branches' => ['nullable', 'array'],
            'request_verification' => ['nullable', 'boolean'],
            'verification_type' => ['nullable', 'string'],
            'verification_document_url' => ['nullable', 'string', 'url'],
            'verification_notes' => ['nullable', 'string', 'max:1000'],
            'draft_id' => ['nullable', 'integer'],
        ]);

        try {
            $page = $this->registrationService->registerPage($user, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Enterprise page registered successfully.',
                'data' => $page->toResponseArray($user),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * পেজের ব্রাঞ্চ / লোকেশন যুক্ত করা
     */
    public function addLocation(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageSettings', $page)) {
            abort(403, 'You do not have permission to add locations for this page.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'business_hours' => ['nullable', 'array'],
            'is_headquarters' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $location = PageLocation::create(array_merge($validated, [
            'page_id' => $page->id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Location added successfully.',
            'data' => $location,
        ], 201);
    }

    /**
     * পেজের সকল ব্রাঞ্চ ও লোকেশন তালিকা
     */
    public function locations(Page $page): JsonResponse
    {
        $locations = PageLocation::where('page_id', $page->id)
            ->where('is_public', true)
            ->orderByDesc('is_headquarters')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $locations,
        ]);
    }

    /**
     * অফিসিয়াল পেজ ভেরিফিকেশন আবেদন করা
     */
    public function requestVerification(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageSettings', $page)) {
            abort(403, 'You do not have permission to request verification for this page.');
        }

        $validated = $request->validate([
            'verification_type' => ['required', 'string', 'in:business,brand,organization,creator,public_figure,media'],
            'legal_name' => ['required', 'string', 'max:150'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'tax_id' => ['nullable', 'string', 'max:80'],
            'document_url' => ['required', 'string', 'url'],
            'additional_info' => ['nullable', 'string', 'max:2000'],
        ]);

        $existing = PageVerificationRequest::where('page_id', $page->id)
            ->whereIn('status', ['pending', 'under_review'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A verification request is already pending review for this page.',
            ], 422);
        }

        $verReq = PageVerificationRequest::create(array_merge($validated, [
            'page_id' => $page->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]));

        $page->update(['verification_status' => 'pending']);

        return response()->json([
            'success' => true,
            'message' => 'Verification request submitted successfully.',
            'data' => $verReq,
        ], 201);
    }
}
