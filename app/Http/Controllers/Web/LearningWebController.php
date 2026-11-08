<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\LearningCategory;
use App\Models\LearningCertificate;
use App\Models\LearningCourse;
use App\Models\LearningEnrollment;
use App\Models\LearningLessonProgress;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Wallet;
use App\Services\Learning\CertificateService;
use App\Services\Learning\CourseService;
use App\Services\Learning\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class LearningWebController extends Controller
{
    public function __construct(
        protected CourseService $courseService,
        protected EnrollmentService $enrollmentService,
        protected CertificateService $certificateService
    ) {}

    /**
     * Resolve optional authenticated viewer from session or Sanctum cookie.
     */
    protected function resolveViewer(Request $request): ?User
    {
        $user = $request->user();
        if (! $user && $request->hasCookie('jugajug_token')) {
            $rawToken = (string) $request->cookie('jugajug_token');
            $pat = PersonalAccessToken::findToken($rawToken);
            if ($pat && $pat->tokenable instanceof User) {
                $user = $pat->tokenable;
                auth('web')->login($user);
            }
        }

        return $user;
    }

    /**
     * Display the Learning & Development catalog, My Learning dashboard, and Certificates hub.
     */
    public function index(Request $request): Response
    {
        $viewer = $this->resolveViewer($request);

        return $this->renderHub($request, $viewer, $request->query('course') ? (string) $request->query('course') : null);
    }

    /**
     * Display the Learning hub with a pre-opened course detail & curriculum view.
     */
    public function show(Request $request, string $slug): Response
    {
        $viewer = $this->resolveViewer($request);

        $course = LearningCourse::with(['instructor.profile', 'category', 'skills'])
            ->where('slug', $slug)
            ->first();

        if (! $course || ! Gate::forUser($viewer)->allows('view', $course)) {
            abort(404, 'Course not found.');
        }

        return $this->renderHub($request, $viewer, $slug, $course);
    }

    /**
     * Public verification page for Learning Certificates (/verify/cert/{code}).
     */
    public function verifyCertificate(Request $request, string $code): Response
    {
        $verification = $this->certificateService->verifyCertificate($code);

        return Inertia::render('Learning/VerifyCertificate', [
            'code' => $code,
            'certificate' => $verification,
        ]);
    }

    /**
     * Build the authoritative payload for the Inertia Learning Hub.
     */
    protected function renderHub(
        Request $request,
        ?User $viewer,
        ?string $selectedSlug = null,
        ?LearningCourse $preloadedCourse = null
    ): Response {
        $filters = [
            'q' => (string) $request->query('q', ''),
            'category_slug' => (string) $request->query('category', ''),
            'level' => (string) $request->query('level', ''),
            'type' => (string) $request->query('type', ''),
            'sort_by' => (string) $request->query('sort', 'popular'),
        ];

        $cleanFilters = array_filter($filters, static fn ($val) => $val !== '' && $val !== 'all');

        $categories = LearningCategory::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LearningCategory $cat) => [
                'id' => (int) $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'icon' => $cat->icon,
                'courses_count' => (int) $cat->courses_count,
                'children' => $cat->children->map(fn (LearningCategory $sub) => [
                    'id' => (int) $sub->id,
                    'parent_id' => (int) $sub->parent_id,
                    'name' => $sub->name,
                    'slug' => $sub->slug,
                    'courses_count' => (int) $sub->courses_count,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $paginator = $this->courseService->getMarketplaceCourses($cleanFilters, 12);

        $enrollmentsByCourse = collect();
        if ($viewer) {
            $enrollmentsByCourse = LearningEnrollment::where('user_id', $viewer->id)
                ->whereIn('status', [LearningEnrollment::STATUS_ACTIVE, LearningEnrollment::STATUS_COMPLETED])
                ->get()
                ->keyBy('course_id');
        }

        $coursesData = collect($paginator->items())
            ->map(fn (LearningCourse $c) => $this->formatCourseSummary($c, $viewer, $enrollmentsByCourse->get($c->id)))
            ->values()
            ->all();

        $myLearning = [];
        $certificates = [];
        $stats = [
            'enrolled_count' => 0,
            'in_progress_count' => 0,
            'completed_count' => 0,
            'certificates_count' => 0,
            'wallet_balance' => 0.0,
        ];

        if ($viewer) {
            $enrollments = LearningEnrollment::with(['course.instructor.profile', 'course.category', 'course.skills'])
                ->where('user_id', $viewer->id)
                ->whereIn('status', [LearningEnrollment::STATUS_ACTIVE, LearningEnrollment::STATUS_COMPLETED])
                ->whereHas('course')
                ->orderByDesc('updated_at')
                ->get();

            $certsByCourse = LearningCertificate::where('user_id', $viewer->id)
                ->where('is_revoked', false)
                ->get()
                ->keyBy('course_id');

            $myLearning = $enrollments->map(function (LearningEnrollment $e) use ($viewer, $certsByCourse) {
                $course = $e->course;
                $cert = $course ? $certsByCourse->get($course->id) : null;

                return [
                    'id' => (int) $e->id,
                    'course_id' => (int) $e->course_id,
                    'status' => $e->status,
                    'progress_percentage' => (float) $e->progress_percentage,
                    'enrolled_at' => $e->enrolled_at?->toIso8601String(),
                    'completed_at' => $e->completed_at?->toIso8601String(),
                    'certificate_code' => $cert?->certificate_code,
                    'course' => $course ? $this->formatCourseSummary($course, $viewer, $e) : null,
                ];
            })->values()->all();

            $certificates = $certsByCourse->values()->map(fn (LearningCertificate $cert) => [
                'id' => (int) $cert->id,
                'course_id' => (int) $cert->course_id,
                'certificate_code' => $cert->certificate_code,
                'student_name' => $cert->student_name,
                'course_title' => $cert->course_title,
                'instructor_name' => $cert->instructor_name,
                'issued_at' => $cert->issued_at?->toIso8601String(),
                'pdf_url' => $cert->pdf_url ?: url("/api/v1/learning/certificates/{$cert->certificate_code}/download"),
                'verify_url' => url("/verify/cert/{$cert->certificate_code}"),
            ])->all();

            $wallet = Wallet::where('user_id', $viewer->id)->first();
            $completedCount = $enrollments->filter(
                fn (LearningEnrollment $e) => $e->status === LearningEnrollment::STATUS_COMPLETED || (float) $e->progress_percentage >= 100.0
            )->count();

            $stats = [
                'enrolled_count' => $enrollments->count(),
                'in_progress_count' => max(0, $enrollments->count() - $completedCount),
                'completed_count' => $completedCount,
                'certificates_count' => count($certificates),
                'wallet_balance' => $wallet ? (float) $wallet->balance : 0.0,
            ];
        }

        $selectedCourse = null;
        if ($selectedSlug) {
            $course = $preloadedCourse ?? LearningCourse::with(['instructor.profile', 'category', 'skills'])
                ->where('slug', $selectedSlug)
                ->first();

            if ($course && Gate::forUser($viewer)->allows('view', $course)) {
                $selectedCourse = $this->formatCourseDetail($course, $viewer);
            }
        }

        $events = Event::where('start_time', '>=', now())->orderBy('start_time', 'asc')->take(3)->get();
        $upcomingEvents = $events->map(fn (Event $e) => [
            'id' => $e->id,
            'title' => $e->title,
            'month' => $e->start_time ? strtoupper($e->start_time->format('M')) : 'NOV',
            'day' => $e->start_time ? $e->start_time->format('d') : '07',
            'time_formatted' => $e->start_time ? $e->start_time->format('D, g:i A') : '10:00 AM',
            'address' => $e->address ?: 'Online',
            'cover_image' => $e->cover_image ? UserProfile::normalizeStorageUrl($e->cover_image) : null,
        ])->values()->all();

        return Inertia::render('Learning/Index', [
            'categories' => $categories,
            'courses' => [
                'data' => $coursesData,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'myLearning' => $myLearning,
            'certificates' => $certificates,
            'stats' => $stats,
            'selectedCourse' => $selectedCourse,
            'activeTab' => (string) $request->query('tab', 'explore'),
            'filters' => $filters,
            'upcomingEvents' => $upcomingEvents,
            'unreadCount' => $viewer ? $viewer->unreadNotifications()->count() : 0,
            'unreadMessagesCount' => 0,
        ]);
    }

    /**
     * Format a course summary card with viewer-specific ownership & enrollment truth.
     */
    protected function formatCourseSummary(
        LearningCourse $course,
        ?User $viewer,
        ?LearningEnrollment $enrollment = null
    ): array {
        $price = (float) $course->price;
        $discounted = $course->discounted_price !== null ? (float) $course->discounted_price : null;
        $effectivePrice = $course->type === LearningCourse::TYPE_FREE
            ? 0.0
            : ($discounted !== null ? $discounted : $price);

        $isInstructor = $viewer !== null && (int) $viewer->id === (int) $course->instructor_id;
        $isEnrolled = $enrollment !== null;

        return [
            'id' => (int) $course->id,
            'instructor_id' => (int) $course->instructor_id,
            'title' => $course->title,
            'slug' => $course->slug,
            'subtitle' => $course->subtitle,
            'description' => $course->description,
            'thumbnail_url' => $course->thumbnail_url,
            'promo_video_url' => $course->promo_video_url,
            'level' => $course->level,
            'language' => $course->language ?? 'English',
            'type' => $course->type,
            'price' => $price,
            'discounted_price' => $discounted,
            'effective_price' => $effectivePrice,
            'currency' => $course->currency ?: 'USD',
            'duration_minutes' => (int) $course->duration_minutes,
            'total_sections' => (int) $course->total_sections,
            'total_lessons' => (int) $course->total_lessons,
            'enrollments_count' => (int) $course->enrollments_count,
            'rating_avg' => (float) $course->rating_avg,
            'rating_count' => (int) $course->rating_count,
            'status' => $course->status,
            'is_featured' => (bool) $course->is_featured,
            'is_instructor' => $isInstructor,
            'is_enrolled' => $isEnrolled,
            'enrollment_status' => $enrollment?->status,
            'progress_percentage' => $enrollment ? (float) $enrollment->progress_percentage : 0.0,
            'category' => $course->category ? [
                'id' => (int) $course->category->id,
                'name' => $course->category->name,
                'slug' => $course->category->slug,
            ] : null,
            'instructor' => $course->instructor ? [
                'id' => (int) $course->instructor->id,
                'name' => $course->instructor->name,
                'username' => $course->instructor->username,
                'avatar_url' => $course->instructor->profile?->avatar_url
                    ? UserProfile::normalizeStorageUrl($course->instructor->profile->avatar_url)
                    : $course->instructor->avatar_url,
            ] : null,
            'skills' => $course->relationLoaded('skills')
                ? $course->skills->pluck('skill_name')->values()->all()
                : [],
        ];
    }

    /**
     * Format full course details with curriculum & lesson completion state.
     */
    protected function formatCourseDetail(LearningCourse $course, ?User $viewer): array
    {
        $enrollment = $viewer
            ? LearningEnrollment::where('course_id', $course->id)
                ->where('user_id', $viewer->id)
                ->whereIn('status', [LearningEnrollment::STATUS_ACTIVE, LearningEnrollment::STATUS_COMPLETED])
                ->first()
            : null;

        $summary = $this->formatCourseSummary($course, $viewer, $enrollment);

        $canAccessFullContent = $viewer !== null && (
            $enrollment !== null
            || (int) $course->instructor_id === (int) $viewer->id
            || $viewer->isAdmin()
        );

        $completedLessonIds = [];
        if ($viewer && $enrollment) {
            $completedLessonIds = LearningLessonProgress::where('course_id', $course->id)
                ->where('user_id', $viewer->id)
                ->where('is_completed', true)
                ->pluck('lesson_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }
        $completedLookup = array_flip($completedLessonIds);

        $sections = $course->sections()
            ->when(! $canAccessFullContent, fn ($q) => $q->where('is_published', true))
            ->with(['lessons' => function ($q) use ($canAccessFullContent) {
                if (! $canAccessFullContent) {
                    $q->where('is_published', true);
                }
                $q->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($section) use ($canAccessFullContent, $completedLookup) {
                return [
                    'id' => (int) $section->id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'sort_order' => (int) $section->sort_order,
                    'lessons' => $section->lessons->map(function ($lesson) use ($canAccessFullContent, $completedLookup) {
                        $unlocked = $canAccessFullContent || (bool) $lesson->is_preview;

                        return [
                            'id' => (int) $lesson->id,
                            'section_id' => (int) $lesson->section_id,
                            'title' => $lesson->title,
                            'type' => $lesson->type,
                            'duration_minutes' => (int) $lesson->duration_minutes,
                            'is_preview' => (bool) $lesson->is_preview,
                            'is_locked' => ! $unlocked,
                            'is_completed' => isset($completedLookup[(int) $lesson->id]),
                            'video_url' => $unlocked ? $lesson->video_url : null,
                            'content' => $unlocked ? $lesson->content : null,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        return array_merge($summary, [
            'learning_objectives' => $course->learning_objectives ?? [],
            'prerequisites' => $course->prerequisites ?? [],
            'target_audience' => $course->target_audience ?? [],
            'sections' => $sections,
            'completed_lesson_ids' => $completedLessonIds,
        ]);
    }
}
