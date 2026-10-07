<?php

namespace App\Services\Page;

use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageCategoryField;
use App\Models\PageCreationDraft;
use App\Models\PageLocation;
use App\Models\PageMember;
use App\Models\PageSubcategory;
use App\Models\PageType;
use App\Models\PageVerificationRequest;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * এন্টারপ্রাইজ পেজ রেজিস্ট্রেশন, ট্যাক্সোনমি, ড্রাফট ও লাইফসাইকেল সার্ভিস
 */
class PageRegistrationService
{
    /**
     * সংরক্ষিত ও নিষিদ্ধ ইউজারনেমসমূহ
     *
     * @var array<string>
     */
    protected const RESERVED_USERNAMES = [
        'admin', 'administrator', 'api', 'app', 'auth', 'blog', 'cache', 'chat',
        'create', 'dashboard', 'dev', 'explore', 'feed', 'groups', 'help', 'home',
        'inbox', 'jugajug', 'login', 'logout', 'manage', 'marketplace', 'media',
        'messages', 'null', 'pages', 'privacy', 'profile', 'reels', 'register',
        'search', 'settings', 'signin', 'signup', 'status', 'stories', 'support',
        'system', 'terms', 'test', 'undefined', 'user', 'users', 'v1', 'v2', 'web',
    ];

    public function __construct(
        protected EnterprisePageService $enterprisePageService
    ) {}

    /**
     * সক্রিয় পেজ টাইপসমূহের তালিকা
     */
    public function getPageTypes(): Collection
    {
        return PageType::where('is_active', true)
            ->withCount('categories')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * পেজ টাইপ অনুযায়ী প্রাইমারি ক্যাটাগরি তালিকা
     */
    public function getCategories(?int $pageTypeId = null): Collection
    {
        $query = PageCategory::where('is_active', true)->with('pageType');

        if ($pageTypeId) {
            $query->where('page_type_id', $pageTypeId);
        }

        return $query->orderBy('sort_order')->get();
    }

    /**
     * ক্যাটাগরি সার্চ ও ফিল্টারিং
     */
    public function searchCategories(string $query, ?int $pageTypeId = null): Collection
    {
        $q = PageCategory::where('is_active', true)
            ->where(function ($sub) use ($query) {
                $sub->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->with(['pageType', 'subcategories']);

        if ($pageTypeId) {
            $q->where('page_type_id', $pageTypeId);
        }

        return $q->limit(20)->get();
    }

    /**
     * নির্দিষ্ট ক্যাটাগরির সাবক্যাটাগরি তালিকা
     */
    public function getSubcategories(int $categoryId): Collection
    {
        return PageSubcategory::where('page_category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * নির্দিষ্ট ক্যাটাগরি ও টাইপের জন্য প্রযোজ্য ডায়নামিক ফিল্ডসমূহ
     */
    public function getCategoryFields(int $pageTypeId, ?int $categoryId = null, ?int $subcategoryId = null): Collection
    {
        return PageCategoryField::where('is_active', true)
            ->where(function ($q) use ($pageTypeId, $categoryId, $subcategoryId) {
                $q->where('page_type_id', $pageTypeId);

                if ($categoryId) {
                    $q->orWhere('page_category_id', $categoryId);
                }

                if ($subcategoryId) {
                    $q->orWhere('page_subcategory_id', $subcategoryId);
                }
            })
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * ইউজারনেমের বৈধতা ও রিয়েলটাইম অ্যাভেইলেবিলিটি যাচাই
     *
     * @return array{available: bool, normalized: string, message: string, suggestions: array<string>}
     */
    public function checkUsernameAvailability(string $username, ?int $excludePageId = null): array
    {
        $normalized = strtolower(trim($username));
        $normalized = ltrim($normalized, '@');

        // ১. দৈর্ঘ্যের যাচাই (৩ থেকে ৬০ ক্যারেক্টার)
        if (strlen($normalized) < 3) {
            return [
                'available' => false,
                'normalized' => $normalized,
                'message' => 'Username must be at least 3 characters long.',
                'suggestions' => [],
            ];
        }

        if (strlen($normalized) > 60) {
            return [
                'available' => false,
                'normalized' => $normalized,
                'message' => 'Username cannot exceed 60 characters.',
                'suggestions' => [],
            ];
        }

        // ২. ক্যারেক্টার ফরম্যাট যাচাই (শুধু ইংরেজি অক্ষর, সংখ্যা, আন্ডারস্কোর এবং ডট)
        if (! preg_match('/^[a-z0-9_\.]+$/', $normalized)) {
            return [
                'available' => false,
                'normalized' => $normalized,
                'message' => 'Username can only contain letters, numbers, underscores, and dots.',
                'suggestions' => [],
            ];
        }

        // ৩. সংরক্ষিত নামের তালিকা যাচাই
        if (in_array($normalized, self::RESERVED_USERNAMES, true)) {
            return [
                'available' => false,
                'normalized' => $normalized,
                'message' => 'This username is reserved and cannot be registered.',
                'suggestions' => [
                    $normalized.'_official',
                    $normalized.'_hub',
                    $normalized.'_bd',
                ],
            ];
        }

        // ৪. ডাটাবেজে অন্য কোনো পেজে বিদ্যমান কিনা যাচাই
        $query = Page::where('username', $normalized);
        if ($excludePageId) {
            $query->where('id', '!=', $excludePageId);
        }

        $exists = $query->exists();

        if ($exists) {
            return [
                'available' => false,
                'normalized' => $normalized,
                'message' => 'This username is already taken.',
                'suggestions' => [
                    $normalized.'_page',
                    $normalized.'_official',
                    $normalized.'_'.rand(10, 99),
                ],
            ];
        }

        return [
            'available' => true,
            'normalized' => $normalized,
            'message' => 'Username is available!',
            'suggestions' => [],
        ];
    }

    /**
     * ইউজারের পেজ লিমিট বা কোটা যাচাই (ডিফল্ট ১০টি পেজ)
     */
    public function checkUserPageQuota(User $user, int $maxAllowedPages = 10): bool
    {
        $ownedCount = Page::where('owner_id', $user->id)->count();

        return $ownedCount < $maxAllowedPages;
    }

    /**
     * ড্রাফট সংরক্ষণ / অটোসেভ
     */
    public function saveDraft(User $user, array $formData, ?int $draftId = null, int $currentStep = 1): PageCreationDraft
    {
        $title = $formData['name'] ?? ($formData['identity']['name'] ?? 'Untitled Page');

        if ($draftId) {
            $draft = PageCreationDraft::where('user_id', $user->id)->find($draftId);
            if ($draft) {
                $draft->update([
                    'title' => $title,
                    'current_step' => $currentStep,
                    'form_data' => $formData,
                    'autosaved_at' => now(),
                ]);

                return $draft;
            }
        }

        return PageCreationDraft::create([
            'user_id' => $user->id,
            'title' => $title,
            'current_step' => $currentStep,
            'form_data' => $formData,
            'autosaved_at' => now(),
        ]);
    }

    /**
     * ইউজারের সংরক্ষিত ড্রাফট তালিকা
     */
    public function getDrafts(User $user): Collection
    {
        return PageCreationDraft::where('user_id', $user->id)
            ->latest('updated_at')
            ->get();
    }

    /**
     * নির্দিষ্ট ড্রাফটের বিস্তারিত
     */
    public function getDraft(User $user, int $draftId): PageCreationDraft
    {
        return PageCreationDraft::where('user_id', $user->id)->findOrFail($draftId);
    }

    /**
     * ড্রাফট মুছে ফেলা
     */
    public function deleteDraft(User $user, int $draftId): bool
    {
        $draft = PageCreationDraft::where('user_id', $user->id)->find($draftId);
        if ($draft) {
            $draft->delete();

            return true;
        }

        return false;
    }

    /**
     * এন্টারপ্রাইজ পেজ তৈরি করা (Atomic Transactional Registration)
     *
     * @throws Exception
     */
    public function registerPage(User $user, array $data): Page
    {
        // ১. কোটা এনফোর্সমেন্ট যাচাই
        $maxAllowed = (int) ($data['max_allowed_pages'] ?? 10);
        if (! $this->checkUserPageQuota($user, $maxAllowed)) {
            throw new Exception("You have reached your maximum page limit of {$maxAllowed} pages.");
        }

        // ২. ইউজারনেম চেক ও নরমালাইজেশন
        $rawUsername = $data['username'] ?? null;
        if (! $rawUsername) {
            $rawUsername = Str::slug($data['name']);
        }

        $usernameCheck = $this->checkUsernameAvailability($rawUsername);
        if (! $usernameCheck['available']) {
            throw new Exception($usernameCheck['message']);
        }
        $username = $usernameCheck['normalized'];

        // ৩. স্লাগ তৈরি
        $name = trim($data['name']);
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $c = 1;
        while (Page::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$c}";
            $c++;
        }

        // ৪. ট্যাক্সোনমি রেকর্ড যাচাই
        $pageTypeId = ! empty($data['page_type_id']) ? (int) $data['page_type_id'] : null;
        $pageCategoryId = ! empty($data['page_category_id']) ? (int) $data['page_category_id'] : null;
        $pageSubcategoryId = ! empty($data['page_subcategory_id']) ? (int) $data['page_subcategory_id'] : null;

        $categoryName = $data['category'] ?? 'General';
        if ($pageCategoryId) {
            $catRecord = PageCategory::find($pageCategoryId);
            if ($catRecord) {
                $categoryName = $catRecord->name;
            }
        }

        $subcategoryName = $data['sub_category'] ?? null;
        if ($pageSubcategoryId) {
            $subRecord = PageSubcategory::find($pageSubcategoryId);
            if ($subRecord) {
                $subcategoryName = $subRecord->name;
            }
        }

        // ৫. ট্রানজ্যাকশনের মধ্যে পেজ ও সংশ্লিষ্ট রেকর্ডসমূহ তৈরি
        return DB::transaction(function () use (
            $user, $data, $name, $slug, $username, $pageTypeId, $pageCategoryId,
            $pageSubcategoryId, $categoryName, $subcategoryName
        ) {
            // পেজ রেকর্ড ইনসার্ট
            $page = Page::create([
                'name' => $name,
                'slug' => $slug,
                'username' => $username,
                'owner_id' => $user->id,
                'page_type_id' => $pageTypeId,
                'page_category_id' => $pageCategoryId,
                'page_subcategory_id' => $pageSubcategoryId,
                'category' => $categoryName,
                'sub_category' => $subcategoryName,
                'bio' => $data['bio'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'avatar_url' => $data['avatar_url'] ?? null,
                'logo_url' => $data['logo_url'] ?? null,
                'cover_image_url' => $data['cover_image_url'] ?? null,
                'website' => $data['website'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? 'Bangladesh',
                'zip_code' => $data['zip_code'] ?? null,
                'business_hours' => $data['business_hours'] ?? null,
                'business_details' => $data['business_details'] ?? null,
                'custom_fields_data' => $data['custom_fields_data'] ?? null,
                'social_links' => $data['social_links'] ?? null,
                'gallery_urls' => $data['gallery_urls'] ?? null,
                'cta_type' => $data['cta_type'] ?? 'contact_us',
                'cta_url' => $data['cta_url'] ?? null,
                'visibility' => $data['visibility'] ?? 'public',
                'status' => Page::STATUS_ACTIVE,
                'settings' => $data['settings'] ?? [
                    'messaging' => [
                        'allow_messages' => true,
                        'auto_reply_enabled' => false,
                    ],
                ],
                'privacy_settings' => $data['privacy_settings'] ?? [
                    'public_email' => true,
                    'public_phone' => true,
                    'public_address' => true,
                ],
                'followers_count' => 0,
                'posts_count' => 0,
                'is_verified' => false,
                'verification_status' => 'unverified',
            ]);

            // ৬. ওনার টিম মেম্বার হিসেবে যুক্ত করা
            PageMember::create([
                'page_id' => $page->id,
                'user_id' => $user->id,
                'role' => Page::ROLE_OWNER,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            // ৭. হেডকোয়ার্টার বা প্রধান লোকেশন রেকর্ড তৈরি
            if (! empty($data['address']) || ! empty($data['city'])) {
                PageLocation::create([
                    'page_id' => $page->id,
                    'name' => 'Headquarters',
                    'street_address' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'district' => $data['district'] ?? null,
                    'country' => $data['country'] ?? 'Bangladesh',
                    'zip_code' => $data['zip_code'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'business_hours' => $data['business_hours'] ?? null,
                    'is_headquarters' => true,
                    'is_public' => true,
                ]);
            }

            // ৮. অতিরিক্ত ব্রাঞ্চেস থাকলে সেগুলো সংরক্ষণ
            if (! empty($data['branches']) && is_array($data['branches'])) {
                foreach ($data['branches'] as $branch) {
                    if (! empty($branch['name'])) {
                        PageLocation::create([
                            'page_id' => $page->id,
                            'name' => $branch['name'],
                            'street_address' => $branch['street_address'] ?? null,
                            'city' => $branch['city'] ?? null,
                            'phone' => $branch['phone'] ?? null,
                            'email' => $branch['email'] ?? null,
                            'is_headquarters' => false,
                            'is_public' => true,
                        ]);
                    }
                }
            }

            // ৯. ভেরিফিকেশন রিকোয়েস্ট অপশন থাকলে রিকোয়েস্ট সংরক্ষণ
            if (! empty($data['request_verification']) && ! empty($data['verification_type'])) {
                PageVerificationRequest::create([
                    'page_id' => $page->id,
                    'user_id' => $user->id,
                    'verification_type' => $data['verification_type'],
                    'legal_name' => $data['business_details']['legal_business_name'] ?? $name,
                    'registration_number' => $data['business_details']['registration_number'] ?? null,
                    'tax_id' => $data['business_details']['tax_id'] ?? null,
                    'document_url' => $data['verification_document_url'] ?? null,
                    'additional_info' => $data['verification_notes'] ?? null,
                    'status' => 'pending',
                ]);
            }

            // ১০. ড্রাফট থাকলে মুছে ফেলা
            if (! empty($data['draft_id'])) {
                PageCreationDraft::where('user_id', $user->id)->where('id', $data['draft_id'])->delete();
            }

            // ১১. অডিট লগ সংরক্ষণ
            $this->enterprisePageService->logAudit($page, $user, 'page.create', 'Page', $page->id, null, [
                'name' => $page->name,
                'slug' => $page->slug,
                'category' => $page->category,
            ]);

            return $page;
        });
    }
}
