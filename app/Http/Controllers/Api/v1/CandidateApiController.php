<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\CandidateResume;
use App\Models\CompanyMember;
use App\Models\JobAlert;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\ProfileEducation;
use App\Models\ProfileExperience;
use App\Models\SavedJob;
use App\Models\User;
use App\Services\Recruitment\CandidateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CandidateApiController extends Controller
{
    public function __construct(
        protected CandidateService $candidateService
    ) {}

    /**
     * GET /api/v1/candidate/profile
     */
    public function profile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $profile = $this->candidateService->getOrCreateProfile($user);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'profile' => $profile->toResponseArray($user),
            ],
            'message' => 'ক্যান্ডিডেট প্রোফাইল লোড হয়েছে।',
        ]);
    }

    /**
     * PUT /api/v1/candidate/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'professional_title' => 'nullable|string|max:150',
            'professional_summary' => 'nullable|string|max:3000',
            'experience_years' => 'nullable|integer|min:0|max:60',
            'current_company' => 'nullable|string|max:150',
            'current_position' => 'nullable|string|max:150',
            'expected_salary' => 'nullable|integer|min:0',
            'salary_currency' => 'nullable|string|max:10',
            'preferred_employment_types' => 'nullable|array',
            'preferred_workplace_types' => 'nullable|array',
            'preferred_locations' => 'nullable|array',
            'availability_status' => 'nullable|string|in:actively_looking,open_to_work,not_looking',
            'profile_visibility' => 'nullable|string|in:public,recruiters_only,private',
        ]);

        $profile = $this->candidateService->updateProfile($user, $validated);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'profile' => $profile->toResponseArray($user),
            ],
            'message' => 'ক্যারিয়ার প্রোফাইল সফলভাবে আপডেট হয়েছে!',
        ]);
    }

    /**
     * GET /api/v1/candidate/resumes
     */
    public function resumes(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $resumes = CandidateResume::where('user_id', $user->id)->latest('id')->get();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'resumes' => $resumes->map(fn (CandidateResume $r) => $r->toResponseArray())->values()->all(),
            ],
            'message' => 'রিজিউমে তালিকা লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/candidate/resumes
     */
    public function uploadResume(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'title' => 'required|string|max:150',
            'is_default' => 'nullable|boolean',
        ]);

        $resume = $this->candidateService->uploadResume(
            $user,
            $request->file('file'),
            $request->input('title'),
            $request->boolean('is_default')
        );

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'resume' => $resume->toResponseArray(),
            ],
            'message' => 'রিজিউমে সফলভাবে আপলোড হয়েছে! 📄',
        ], 201);
    }

    /**
     * POST /api/v1/candidate/resumes/{id}/default
     */
    public function setDefaultResume(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $resume = CandidateResume::where('user_id', $user->id)->findOrFail($id);

        $updated = $this->candidateService->setDefaultResume($user, $resume);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'resume' => $updated->toResponseArray(),
            ],
            'message' => 'ডিফল্ট রিজিউমে নির্ধারণ করা হয়েছে।',
        ]);
    }

    /**
     * DELETE /api/v1/candidate/resumes/{id}
     */
    public function deleteResume(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $resume = CandidateResume::where('user_id', $user->id)->findOrFail($id);

        $this->candidateService->deleteResume($resume);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'রিজিউমে মুছে ফেলা হয়েছে।',
        ]);
    }

    /**
     * GET /api/v1/candidate/resumes/{id}/download
     *
     * Authorized secure download.
     */
    public function downloadResume(Request $request, int $id): BinaryFileResponse|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $resume = CandidateResume::findOrFail($id);

        // Security check: must be owner, admin, or recruiter of a company where candidate applied
        $isOwner = $resume->user_id === $user->id;
        $isAdmin = $user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN');

        if (! $isOwner && ! $isAdmin) {
            $canRecruiterAccess = JobApplication::where('user_id', $resume->user_id)
                ->where(function ($q) use ($resume) {
                    $q->where('resume_id', $resume->id)
                        ->orWhere('resume_url', $resume->file_path);
                })
                ->whereHas('company.members', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->where('invitation_status', CompanyMember::STATUS_ACCEPTED);
                })
                ->exists();

            if (! $canRecruiterAccess && $resume->visibility !== CandidateResume::VISIBILITY_PUBLIC) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'এই রিজিউমে ডাউনলোড করার অনুমতি আপনার নেই।',
                ], 403);
            }
        }

        if (! Storage::disk('local')->exists($resume->file_path)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'রিজিউমে ফাইলটি সার্ভারে পাওয়া যায়নি।',
            ], 404);
        }

        return response()->download(
            Storage::disk('local')->path($resume->file_path),
            $resume->original_filename,
            ['Content-Type' => $resume->mime_type]
        );
    }

    /**
     * GET /api/v1/candidate/saved-jobs
     */
    public function savedJobs(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $saved = $this->candidateService->getSavedJobs($user, 20);

        $items = collect($saved->items())
            ->filter(fn (SavedJob $s) => $s->job !== null)
            ->map(fn (SavedJob $s) => $s->job->toResponseArray($user))
            ->values()->all();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'jobs' => $items,
            ],
            'meta' => [
                'total' => $saved->total(),
                'current_page' => $saved->currentPage(),
                'last_page' => $saved->lastPage(),
            ],
            'message' => 'সংরক্ষিত চাকরির তালিকা লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/jobs/{id}/save
     */
    public function saveJob(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $job = JobPosting::findOrFail($id);

        $this->candidateService->saveJob($user, $job);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'চাকরি সফলভাবে সংরক্ষণ করা হয়েছে! 🔖',
        ]);
    }

    /**
     * DELETE /api/v1/jobs/{id}/save
     */
    public function unsaveJob(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $job = JobPosting::findOrFail($id);

        $this->candidateService->unsaveJob($user, $job);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'সংরক্ষণ তালিকা থেকে অপসারণ করা হয়েছে।',
        ]);
    }

    /**
     * GET /api/v1/candidate/job-alerts
     */
    public function jobAlerts(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $alerts = JobAlert::with('category')->where('user_id', $user->id)->latest('id')->get();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'alerts' => $alerts->map(fn (JobAlert $a) => $a->toResponseArray())->values()->all(),
            ],
            'message' => 'জব অ্যালার্ট তালিকা লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/candidate/job-alerts
     */
    public function createJobAlert(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'keywords' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:job_categories,id',
            'locations' => 'nullable|array',
            'employment_types' => 'nullable|array',
            'workplace_types' => 'nullable|array',
            'experience_level' => 'nullable|string|max:30',
            'frequency' => 'nullable|string|in:instant,daily,weekly',
        ]);

        $alert = $this->candidateService->createJobAlert($user, $validated);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'alert' => $alert->toResponseArray(),
            ],
            'message' => 'জব অ্যালার্ট সফলভাবে চালু করা হয়েছে! 🔔',
        ], 201);
    }

    /**
     * PUT /api/v1/candidate/job-alerts/{id}
     */
    public function updateJobAlert(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $alert = JobAlert::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'nullable|string|max:150',
            'keywords' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:job_categories,id',
            'locations' => 'nullable|array',
            'employment_types' => 'nullable|array',
            'workplace_types' => 'nullable|array',
            'experience_level' => 'nullable|string|max:30',
            'frequency' => 'nullable|string|in:instant,daily,weekly',
            'is_active' => 'nullable|boolean',
        ]);

        $updated = $this->candidateService->updateJobAlert($alert, $validated);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'alert' => $updated->toResponseArray(),
            ],
            'message' => 'জব অ্যালার্ট সফলভাবে আপডেট হয়েছে।',
        ]);
    }

    /**
     * DELETE /api/v1/candidate/job-alerts/{id}
     */
    public function deleteJobAlert(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $alert = JobAlert::where('user_id', $user->id)->findOrFail($id);

        $alert->delete();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'জব অ্যালার্ট মুছে ফেলা হয়েছে।',
        ]);
    }

    /**
     * GET /api/v1/candidate/experiences
     */
    public function experiences(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $experiences = ProfileExperience::where('user_id', $user->id)->orderBy('start_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $experiences,
            'message' => 'অভিজ্ঞতার তালিকা লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/candidate/experiences
     */
    public function addExperience(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'company_name' => 'required|string|max:150',
            'job_title' => 'required|string|max:150',
            'employment_type' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:150',
            'is_remote' => 'nullable|boolean',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_current' => 'nullable|boolean',
            'description' => 'nullable|string|max:3000',
        ]);

        $experience = ProfileExperience::create([
            'user_id' => $user->id,
            'company_name' => $validated['company_name'],
            'job_title' => $validated['job_title'],
            'employment_type' => $validated['employment_type'] ?? 'full_time',
            'location' => $validated['location'] ?? null,
            'is_remote' => (bool) ($validated['is_remote'] ?? false),
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'is_current' => (bool) ($validated['is_current'] ?? false),
            'description' => $validated['description'] ?? null,
            'privacy' => 'public',
        ]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $experience,
            'message' => 'কর্মঅভিজ্ঞতা সফলভাবে যুক্ত হয়েছে।',
        ], 201);
    }

    /**
     * PUT /api/v1/candidate/experiences/{id}
     */
    public function updateExperience(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $experience = ProfileExperience::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'company_name' => 'nullable|string|max:150',
            'job_title' => 'nullable|string|max:150',
            'employment_type' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:150',
            'is_remote' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_current' => 'nullable|boolean',
            'description' => 'nullable|string|max:3000',
        ]);

        $experience->update($validated);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $experience->fresh(),
            'message' => 'কর্মঅভিজ্ঞতা আপডেট করা হয়েছে।',
        ]);
    }

    /**
     * DELETE /api/v1/candidate/experiences/{id}
     */
    public function deleteExperience(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $experience = ProfileExperience::where('user_id', $user->id)->findOrFail($id);
        $experience->delete();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'কর্মঅভিজ্ঞতা মুছে ফেলা হয়েছে।',
        ]);
    }

    /**
     * GET /api/v1/candidate/education
     */
    public function educations(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $educations = ProfileEducation::where('user_id', $user->id)->orderBy('start_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $educations,
            'message' => 'শিক্ষাগত যোগ্যতা তালিকা লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/candidate/education
     */
    public function addEducation(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'institution_name' => 'required|string|max:200',
            'degree' => 'required|string|max:150',
            'field_of_study' => 'nullable|string|max:150',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'grade' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:2000',
        ]);

        $education = ProfileEducation::create([
            'user_id' => $user->id,
            'institution_name' => $validated['institution_name'],
            'degree' => $validated['degree'],
            'field_of_study' => $validated['field_of_study'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'grade' => $validated['grade'] ?? null,
            'description' => $validated['description'] ?? null,
            'privacy' => 'public',
        ]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $education,
            'message' => 'শিক্ষাগত যোগ্যতা সফলভাবে যুক্ত হয়েছে।',
        ], 201);
    }

    /**
     * PUT /api/v1/candidate/education/{id}
     */
    public function updateEducation(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $education = ProfileEducation::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'institution_name' => 'nullable|string|max:200',
            'degree' => 'nullable|string|max:150',
            'field_of_study' => 'nullable|string|max:150',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'grade' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:2000',
        ]);

        $education->update($validated);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $education->fresh(),
            'message' => 'শিক্ষাগত যোগ্যতা আপডেট করা হয়েছে।',
        ]);
    }

    /**
     * DELETE /api/v1/candidate/education/{id}
     */
    public function deleteEducation(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $education = ProfileEducation::where('user_id', $user->id)->findOrFail($id);
        $education->delete();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'শিক্ষাগত যোগ্যতা মুছে ফেলা হয়েছে।',
        ]);
    }
}
