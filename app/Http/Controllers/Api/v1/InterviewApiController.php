<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\ScheduleInterviewRequest;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\User;
use App\Services\Recruitment\RecruitmentAtsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterviewApiController extends Controller
{
    public function __construct(
        protected RecruitmentAtsService $atsService
    ) {}

    /**
     * GET /api/v1/interviews
     *
     * List user interviews (candidate or company recruiter).
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $interviews = Interview::with(['application.jobPosting', 'company', 'scheduler'])
            ->where(function ($q) use ($user) {
                // As candidate
                $q->whereHas('application', fn ($aq) => $aq->where('user_id', $user->id))
                    // Or as company member
                    ->orWhereHas('company.members', function ($cq) use ($user) {
                        $cq->where('user_id', $user->id)
                            ->where('invitation_status', 'accepted');
                    });
            })
            ->orderBy('scheduled_at')
            ->paginate(20);

        $items = collect($interviews->items())
            ->map(fn (Interview $i) => $i->toResponseArray($user))
            ->values()->all();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'interviews' => $items,
            ],
            'meta' => [
                'total' => $interviews->total(),
                'current_page' => $interviews->currentPage(),
                'last_page' => $interviews->lastPage(),
            ],
            'message' => 'ইন্টারভিউ তালিকা লোড হয়েছে।',
        ]);
    }

    /**
     * GET /api/v1/interviews/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $interview = Interview::with(['application.jobPosting', 'company', 'scheduler'])->findOrFail($id);

        $isCandidate = $interview->application && $interview->application->user_id === $user->id;
        $isCompanyMember = $interview->company && $interview->company->hasMember($user->id);
        $isAdmin = $user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN');

        if (! $isCandidate && ! $isCompanyMember && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'ইন্টারভিউ তথ্য দেখার অনুমতি নেই।',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'interview' => $interview->toResponseArray($user),
            ],
            'message' => 'ইন্টারভিউ বিস্তারিত তথ্য লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/applications/{application}/interviews
     */
    public function schedule(ScheduleInterviewRequest $request, int $applicationId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $application = JobApplication::with('jobPosting.company')->findOrFail($applicationId);

        $isCompanyRecruiter = $application->company && in_array(
            $application->company->getMemberRole($user->id),
            ['owner', 'admin', 'recruiter'],
            true
        );
        $isJobCreator = $application->jobPosting && $application->jobPosting->user_id === $user->id;
        $isAdmin = $user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN');

        if (! $isCompanyRecruiter && ! $isJobCreator && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'ইন্টারভিউ নির্ধারণ করার অনুমতি নেই।',
            ], 403);
        }

        $interview = $this->atsService->scheduleInterview($application, $user, $request->validated());

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'interview' => $interview->toResponseArray($user),
            ],
            'message' => 'ইন্টারভিউ সফলভাবে নির্ধারণ করা হয়েছে! 📅',
        ], 201);
    }

    /**
     * PUT /api/v1/interviews/{id}
     */
    public function reschedule(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $interview = Interview::with('company')->findOrFail($id);

        $isCompanyRecruiter = $interview->company && in_array(
            $interview->company->getMemberRole($user->id),
            ['owner', 'admin', 'recruiter'],
            true
        );
        $isAdmin = $user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN');

        if (! $isCompanyRecruiter && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'ইন্টারভিউ পরিবর্তন করার অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            'timezone' => 'nullable|string|max:50',
            'meeting_url' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'instructions' => 'nullable|string|max:2000',
        ]);

        $updated = $this->atsService->rescheduleInterview($interview, $validated, $user);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'interview' => $updated->toResponseArray($user),
            ],
            'message' => 'ইন্টারভিউ সফলভাবে পুনঃনির্ধারণ করা হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/interviews/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $interview = Interview::with(['company', 'application'])->findOrFail($id);

        $isCandidate = $interview->application && $interview->application->user_id === $user->id;
        $isCompanyMember = $interview->company && $interview->company->hasMember($user->id);
        $isAdmin = $user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN');

        if (! $isCandidate && ! $isCompanyMember && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'ইন্টারভিউ বাতিল করার অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $cancelled = $this->atsService->cancelInterview($interview, $validated['reason'] ?? null);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'interview' => $cancelled->toResponseArray($user),
            ],
            'message' => 'ইন্টারভিউ বাতিল করা হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/interviews/{id}/response
     */
    public function response(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $interview = Interview::with('application')->findOrFail($id);

        if (! $interview->application || $interview->application->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'শুধুমাত্র আমন্ত্রিত প্রার্থীই রেসপন্স করতে পারবেন।',
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:accept,confirmed,decline,declined',
        ]);

        $responded = $this->atsService->respondToInterview($interview, $validated['status']);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'interview' => $responded->toResponseArray($user),
            ],
            'message' => 'ইন্টারভিউ রেসপন্স সফলভাবে গৃহীত হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/interviews/{id}/feedback
     */
    public function feedback(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $interview = Interview::with('company')->findOrFail($id);

        $isCompanyRecruiter = $interview->company && in_array(
            $interview->company->getMemberRole($user->id),
            ['owner', 'admin', 'recruiter', 'hiring_manager'],
            true
        );
        $isAdmin = $user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN');

        if (! $isCompanyRecruiter && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'ইন্টারভিউ ফিডব্যাক জমা দেওয়ার অনুমতি নেই।',
            ], 403);
        }

        $validated = $request->validate([
            'feedback' => 'required|string|max:5000',
        ]);

        $completed = $this->atsService->recordInterviewFeedback($interview, $validated['feedback']);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'interview' => $completed->toResponseArray($user),
            ],
            'message' => 'ইন্টারভিউ মূল্যায়ন ও ফিডব্যাক সফলভাবে সংরক্ষিত হয়েছে।',
        ]);
    }
}
