# Phase 13 — Stage 02: Laravel Service Architecture Plan
**Project:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform  
**Stage:** 02 — LMS Database, Backend Architecture & API Foundation  
**Date:** November 2026  
**Author:** Principal Software Architect & Senior Laravel Engineer  

---

## 1. Architectural Patterns & Rules

All LMS services adhere to Connectly standards:
1. **Single Responsibility Principle:** Each service manages a cohesive domain capability.
2. **Database Transactions:** Every multi-table write is wrapped in `DB::transaction()`.
3. **Explicit Type Hinting:** Strict return types and parameter types (PHP 8.5+).
4. **Authorization via Policies:** Services verify policies or assume authorization is enforced by Form Requests / Controllers.
5. **Event Dispatching:** Critical business actions fire Laravel events for notifications, WebSocket broadcasting, and asynchronous job processing.

---

## 2. Service Specifications

### 2.1 `CourseService`
* **File:** `app/Services/Learning/CourseService.php`
* **Core Responsibilities:**
  - `getCourses(array $filters, int $perPage = 15): LengthAwarePaginator`
    Filters by `category_id`, `level`, `type` (free/paid), `skill_id`, `search`, and sorts by `popular`, `highest_rated`, `newest`.
  - `getCourseBySlug(string $slug, ?User $viewer = null): LearningCourse`
    Retrieves course with syllabus, instructor profile, and student enrollment status.
  - `createCourse(User $instructor, array $data): LearningCourse`
    Initializes a draft course with auto-generated unique slug.
  - `updateCourse(LearningCourse $course, array $data): LearningCourse`
    Updates metadata, pricing, description, and level.
  - `submitForReview(LearningCourse $course): LearningCourse`
    Transitions status from `draft` to `pending_review`.
  - `publishCourse(LearningCourse $course, User $admin): LearningCourse`
    Admin operation: sets status to `published` and assigns `published_at = now()`.
  - `suspendCourse(LearningCourse $course, string $reason, User $admin): LearningCourse`
    Admin moderation: transitions to `suspended`.

### 2.2 `CourseContentService`
* **File:** `app/Services/Learning/CourseContentService.php`
* **Core Responsibilities:**
  - `createSection(LearningCourse $course, array $data): LearningCourseSection`
    Appends section with auto-incremented `sort_order`.
  - `updateSection(LearningCourseSection $section, array $data): LearningCourseSection`
  - `deleteSection(LearningCourseSection $section): bool`
    Deletes section and child lessons within a transaction.
  - `createLesson(LearningCourseSection $section, array $data): LearningCourseLesson`
    Creates lesson, updates course `total_lessons` count.
  - `updateLesson(LearningCourseLesson $lesson, array $data): LearningCourseLesson`
  - `deleteLesson(LearningCourseLesson $lesson): bool`
  - `reorderCurriculum(LearningCourse $course, array $orderMap): void`
    Updates `sort_order` for sections and lessons deterministically.

### 2.3 `InstructorService`
* **File:** `app/Services/Learning/InstructorService.php`
* **Core Responsibilities:**
  - `applyAsInstructor(User $user, array $data): LearningInstructorApplication`
    Submits application; prevents duplicate pending applications.
  - `verifyInstructor(int $applicationId, User $admin, string $status, ?string $feedback): LearningInstructor`
    Upon approval, assigns `INSTRUCTOR` role to user, creates `LearningInstructor` profile, and sends notification.
  - `getInstructorProfile(int $userId): ?LearningInstructor`
  - `getInstructorDashboardMetrics(User $instructor): array`
    Computes total students, active courses, total earnings, and average rating.

### 2.4 `EnrollmentService`
* **File:** `app/Services/Learning/EnrollmentService.php`
* **Core Responsibilities:**
  - `enroll(User $user, LearningCourse $course, ?string $couponCode = null): LearningEnrollment`
    Atomic enrollment. Checks:
    1. User is not already enrolled (`unique_user_course`).
    2. Course status is `published`.
    3. If free: immediately creates active enrollment.
    4. If paid: applies coupon, validates wallet/payment transaction, records `price_paid`.
    5. Increments `learning_courses.enrollments_count`.
    6. Dispatches `CourseEnrolledEvent`.
  - `getMyLearning(User $user, string $status = 'all', int $perPage = 15): LengthAwarePaginator`
  - `cancelEnrollment(LearningEnrollment $enrollment, User $actor): bool`

### 2.5 `LearningProgressService`
* **File:** `app/Services/Learning/LearningProgressService.php`
* **Core Responsibilities:**
  - `updateLessonProgress(User $user, LearningCourseLesson $lesson, int $positionSeconds, bool $markCompleted = false): LearningLessonProgress`
    Idempotent update of `last_position_seconds` and `is_completed`.
  - `recalculateCourseProgress(User $user, LearningCourse $course): LearningCourseProgress`
    Calculates completed mandatory lessons vs total lessons. If 100%, marks enrollment `completed` and records `completed_at`.
  - `canAccessLesson(User $user, LearningCourseLesson $lesson): bool`
    Returns true if lesson is `is_preview = true`, or user owns the course, or user has an active enrollment.

### 2.6 `QuizService`
* **File:** `app/Services/Learning/QuizService.php`
* **Core Responsibilities:**
  - `getQuizForStudent(LearningQuiz $quiz): array`
    Returns questions and options with `is_correct` strictly omitted.
  - `startAttempt(User $user, LearningQuiz $quiz): LearningQuizAttempt`
    Validates attempt limits and active enrollment.
  - `submitAttempt(LearningQuizAttempt $attempt, array $answers): LearningQuizAttempt`
    Server-side scoring. Evaluates answers against `learning_quiz_options.is_correct`, calculates percentage, determines `passed` status, and marks lesson completed if passed.

### 2.7 `AssignmentService`
* **File:** `app/Services/Learning/AssignmentService.php`
* **Core Responsibilities:**
  - `submitAssignment(User $user, LearningAssignment $assignment, array $data): LearningAssignmentSubmission`
    Validates enrollment and file attachments.
  - `gradeSubmission(LearningAssignmentSubmission $submission, User $instructor, int $score, ?string $feedback): LearningAssignmentSubmission`
    Updates submission score, status (`graded`), and dispatches notification to student.

### 2.8 `CertificateService`
* **File:** `app/Services/Learning/CertificateService.php`
* **Core Responsibilities:**
  - `claimCertificate(User $user, LearningCourse $course): LearningCertificate`
    Verifies:
    1. Enrollment status is completed.
    2. Course progress is 100%.
    3. All quizzes are passed.
    Generates unique `certificate_code` (`CERT-YYYY-XXXX-XXXX`), renders PDF, generates QR code linking to verification URL, and creates `CandidateCertification` in user profile.
  - `verifyCertificate(string $code): ?array`
    Public verification returning student name, course, instructor, and completion date.
  - `revokeCertificate(LearningCertificate $certificate, string $reason, User $admin): bool`

### 2.9 `CourseReviewService`
* **File:** `app/Services/Learning/CourseReviewService.php`
* **Core Responsibilities:**
  - `submitReview(User $user, LearningCourse $course, int $rating, ?string $comment): LearningCourseReview`
    Requires active verified enrollment. Updates `rating_avg` and `rating_count` on `learning_courses`.
  - `replyToReview(LearningCourseReview $review, User $instructor, string $reply): void`

### 2.10 `LearningAnalyticsService`
* **File:** `app/Services/Learning/LearningAnalyticsService.php`
* **Core Responsibilities:**
  - `getStudentAnalytics(User $user): array`
    Enrolled courses, completed courses, total learning hours, certificates earned.
  - `getInstructorAnalytics(User $instructor): array`
    Active courses, total enrolled students, total revenue, rating breakdown.
  - `getPlatformAnalytics(User $admin): array`
    Platform-wide course totals, active instructors, monthly enrollments, completion rates.

