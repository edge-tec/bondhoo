<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\CreateCompanyRequest;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\JobPosting;
use App\Models\User;
use App\Services\Recruitment\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyApiController extends Controller
{
    public function __construct(
        protected CompanyService $companyService
    ) {}

    /**
     * GET /api/v1/companies
     *
     * List user companies or browse public companies.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var ?User $user */
        $user = $request->user();
        $mine = $request->boolean('mine');

        if ($mine && $user) {
            $companies = Company::where('owner_user_id', $user->id)
                ->orWhereHas('members', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->where('invitation_status', CompanyMember::STATUS_ACCEPTED);
                })
                ->latest('id')
                ->paginate(15);
        } else {
            $query = Company::where('visibility', Company::VISIBILITY_PUBLIC);

            if ($search = $request->query('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('industry', 'like', "%{$search}%")
                        ->orWhere('headquarters', 'like', "%{$search}%");
                });
            }

            if ($industry = $request->query('industry')) {
                $query->where('industry', $industry);
            }

            $companies = $query->latest('id')->paginate(15);
        }

        $items = collect($companies->items())
            ->map(fn (Company $c) => $c->toResponseArray($user))
            ->values()->all();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'companies' => $items,
            ],
            'companies' => $items,
            'meta' => [
                'total' => $companies->total(),
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
            ],
            'message' => 'কোম্পানি তালিকা সফলভাবে লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/companies
     *
     * Create a new company profile.
     */
    public function store(CreateCompanyRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $company = $this->companyService->createCompany($user, $request->validated());

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'company' => $company->toResponseArray($user),
            ],
            'message' => 'কোম্পানি প্রোফাইল সফলভাবে তৈরি হয়েছে! 🏢',
        ], 201);
    }

    /**
     * GET /api/v1/companies/{company}
     *
     * View company profile details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var ?User $user */
        $user = $request->user();
        $company = Company::with(['owner.profile', 'members.user.profile'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'company' => $company->toResponseArray($user),
            ],
            'message' => 'কোম্পানি বিস্তারিত তথ্য লোড হয়েছে।',
        ]);
    }

    /**
     * PUT /api/v1/companies/{company}
     *
     * Update company profile information.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = Company::findOrFail($id);

        $role = $company->getMemberRole($user->id);
        if (! in_array($role, [CompanyMember::ROLE_OWNER, CompanyMember::ROLE_ADMIN], true) && ! $user->hasRole('ADMIN') && ! $user->hasRole('SUPER_ADMIN')) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'কোম্পানি প্রোফাইল আপডেট করার অনুমতি আপনার নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:200',
            'logo' => 'nullable|string|max:255',
            'cover_image' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|string|max:50',
            'founded_year' => 'nullable|integer|min:1800|max:'.(date('Y') + 1),
            'website' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'headquarters' => 'nullable|string|max:150',
            'locations' => 'nullable|array',
            'visibility' => 'nullable|string|in:public,unlisted,private',
        ]);

        $updated = $this->companyService->updateCompany($company, $validated);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'company' => $updated->toResponseArray($user),
            ],
            'message' => 'কোম্পানি প্রোফাইল সফলভাবে আপডেট হয়েছে!',
        ]);
    }

    /**
     * GET /api/v1/companies/{company}/career-page
     *
     * Public career page with open jobs and company overview.
     */
    public function careerPage(Request $request, int $id): JsonResponse
    {
        /** @var ?User $user */
        $user = $request->user();
        $company = Company::with(['owner.profile'])->findOrFail($id);

        $activeJobs = $company->jobs()
            ->with(['skills', 'category'])
            ->where('status', JobPosting::STATUS_ACTIVE)
            ->where('visibility', 'public')
            ->latest('id')
            ->get()
            ->map(fn (JobPosting $j) => $j->toResponseArray($user))
            ->values()->all();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'company' => $company->toResponseArray($user),
                'jobs' => $activeJobs,
            ],
            'message' => 'ক্যারিয়ার পেজ লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/companies/{company}/members/invite
     *
     * Invite member to company team.
     */
    public function inviteMember(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = Company::findOrFail($id);

        $role = $company->getMemberRole($user->id);
        if (! in_array($role, [CompanyMember::ROLE_OWNER, CompanyMember::ROLE_ADMIN], true) && ! $user->hasRole('ADMIN') && ! $user->hasRole('SUPER_ADMIN')) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'টিম সদস্য আমন্ত্রণ জানানোর অনুমতি আপনার নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'email' => 'required|string',
            'role' => 'required|string|in:admin,recruiter,hiring_manager,viewer',
        ]);

        $member = $this->companyService->inviteMember($company, $user, $validated['email'], $validated['role']);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'member' => $member->toResponseArray(),
            ],
            'message' => 'কোম্পানি আমন্ত্রণ সফলভাবে পাঠানো হয়েছে!',
        ], 201);
    }

    /**
     * GET /api/v1/companies/{company}/members
     *
     * List company members.
     */
    public function listMembers(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = Company::findOrFail($id);

        if (! $company->hasMember($user->id) && ! $user->hasRole('ADMIN') && ! $user->hasRole('SUPER_ADMIN')) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'কোম্পানি সদস্য তালিকা দেখার অনুমতি আপনার নেই।',
            ], 403);
        }

        $members = $company->members()->with(['user.profile'])->latest('id')->get();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'members' => $members->map(fn (CompanyMember $m) => $m->toResponseArray())->values()->all(),
            ],
            'message' => 'কোম্পানি সদস্য তালিকা লোড হয়েছে।',
        ]);
    }

    /**
     * PATCH /api/v1/companies/{company}/members/{member}
     *
     * Update member role.
     */
    public function updateMemberRole(Request $request, int $companyId, int $memberId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = Company::findOrFail($companyId);
        $member = CompanyMember::where('company_id', $company->id)->findOrFail($memberId);

        $role = $company->getMemberRole($user->id);
        if (! in_array($role, [CompanyMember::ROLE_OWNER, CompanyMember::ROLE_ADMIN], true) && ! $user->hasRole('ADMIN') && ! $user->hasRole('SUPER_ADMIN')) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'সদস্যের ভূমিকা পরিবর্তন করার অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'role' => 'required|string|in:admin,recruiter,hiring_manager,viewer',
            'permissions' => 'nullable|array',
        ]);

        $member->update([
            'role' => $validated['role'],
            'permissions' => $validated['permissions'] ?? $member->permissions,
        ]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'member' => $member->toResponseArray(),
            ],
            'message' => 'সদস্যের ভূমিকা সফলভাবে পরিবর্তন করা হয়েছে।',
        ]);
    }

    /**
     * DELETE /api/v1/companies/{company}/members/{member}
     *
     * Remove member from company.
     */
    public function removeMember(Request $request, int $companyId, int $memberId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = Company::findOrFail($companyId);
        $member = CompanyMember::where('company_id', $company->id)->findOrFail($memberId);

        $role = $company->getMemberRole($user->id);
        if (! in_array($role, [CompanyMember::ROLE_OWNER, CompanyMember::ROLE_ADMIN], true) && ! $user->hasRole('ADMIN') && ! $user->hasRole('SUPER_ADMIN')) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'সদস্য অপসারণ করার অনুমতি নেই।',
            ], 403);
        }

        if ($member->role === CompanyMember::ROLE_OWNER) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'কোম্পানি ওনারকে অপসারণ করা যাবে না।',
            ], 422);
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'সদস্যকে সফলভাবে টিম থেকে অপসারণ করা হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/companies/{company}/verification
     *
     * Request company verification.
     */
    public function requestVerification(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = Company::findOrFail($id);

        $role = $company->getMemberRole($user->id);
        if (! in_array($role, [CompanyMember::ROLE_OWNER, CompanyMember::ROLE_ADMIN], true) && ! $user->hasRole('ADMIN') && ! $user->hasRole('SUPER_ADMIN')) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'ভেরিফিকেশন আবেদন করার অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'documents' => 'required|array|min:1',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $verification = $this->companyService->requestVerification(
            $company,
            $user,
            $validated['documents'],
            $validated['admin_notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'verification' => $verification->toResponseArray(),
            ],
            'message' => 'কোম্পানি ভেরিফিকেশনের আবেদন সফলভাবে জমা নেওয়া হয়েছে। 📋',
        ], 201);
    }
}
