# PHASE 13: LMS SYSTEM ARCHITECTURE SPECIFICATION
**Platform:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform  
**System Module:** Professional Learning & Development Platform (LMS)  
**Date:** 2026-11-04  
**Author:** Senior Software Architect & Lead LMS Engineer  

---

## 1. Architectural Philosophy & Principles

The Connectly LMS platform is designed as an enterprise-grade, domain-driven learning and development ecosystem integrated natively within the Connectly professional social graph.

### Core Principles
1. **Zero Social Network Duplication:** Community interactions, discussions, and student-instructor messaging leverage existing `User`, `NotificationService`, and discussion primitives.
2. **Strict Authorization & Integrity:** Course access, lesson streaming, quiz scoring, and certificate issuance are validated strictly server-side through declarative Laravel Policies.
3. **Optimistic & Offline-Resilient Mobile UX:** Android clients provide instant feedback, resume playback seamlessly from cached timestamps, and handle intermittent connectivity cleanly.
4. **Normalized & Indexed Persistence:** Data models prevent orphaned records, race-condition duplicate enrollments, or fabricated certificates.

---

## 2. Backend Domain Service Layer

The backend architecture is structured into decoupled domain services under `App\Services\Learning\`:

```
app/
├── Http/
│   ├── Controllers/Api/v1/Learning/
│   │   ├── LearningCourseApiController.php
│   │   ├── LearningCurriculumApiController.php
│   │   ├── LearningEnrollmentApiController.php
│   │   ├── LearningLessonApiController.php
│   │   ├── LearningQuizApiController.php
│   │   ├── LearningAssignmentApiController.php
│   │   ├── LearningCertificateApiController.php
│   │   ├── LearningInstructorApiController.php
│   │   ├── LearningReviewApiController.php
│   │   ├── LearningDiscussionApiController.php
│   │   └── LearningAdminApiController.php
│   └── Requests/Learning/
│       ├── StoreCourseRequest.php
│       ├── UpdateCourseRequest.php
│       ├── StoreLessonRequest.php
│       ├── SubmitQuizAttemptRequest.php
│       ├── SubmitAssignmentRequest.php
│       ├── StoreCourseReviewRequest.php
│       └── InstructorApplicationRequest.php
├── Models/
│   ├── LearningCategory.php
│   ├── LearningCourse.php
│   ├── LearningCourseSection.php
│   ├── LearningCourseLesson.php
│   ├── LearningLessonAttachment.php
│   ├── LearningInstructor.php
│   ├── LearningEnrollment.php
│   ├── LearningLessonProgress.php
│   ├── LearningCourseProgress.php
│   ├── LearningQuiz.php
│   ├── LearningQuizQuestion.php
│   ├── LearningQuizOption.php
│   ├── LearningQuizAttempt.php
│   ├── LearningQuizAnswer.php
│   ├── LearningAssignment.php
│   ├── LearningAssignmentSubmission.php
│   ├── LearningCertificate.php
│   ├── LearningCourseReview.php
│   ├── LearningWishlist.php
│   ├── LearningDiscussion.php
│   ├── LearningDiscussionReply.php
│   ├── LearningCourseAnnouncement.php
│   ├── LearningCourseCoupon.php
│   └── LearningTransaction.php
├── Policies/
│   ├── LearningCoursePolicy.php
│   ├── LearningLessonPolicy.php
│   ├── LearningQuizPolicy.php
│   ├── LearningAssignmentPolicy.php
│   └── LearningCertificatePolicy.php
└── Services/Learning/
    ├── CourseService.php
    ├── CourseContentService.php
    ├── InstructorService.php
    ├── EnrollmentService.php
    ├── LearningProgressService.php
    ├── QuizService.php
    ├── AssignmentService.php
    ├── CertificateService.php
    ├── CourseReviewService.php
    └── LearningAnalyticsService.php
```

---

## 3. Android Modular Architecture (`:feature:learning`)

The Android application implements a dedicated feature module `:feature:learning` aligned with Clean Architecture and MVI:

```
android/feature/learning/
├── build.gradle.kts
└── src/
    ├── main/
    │   └── java/com/connectly/android/feature/learning/
    │       ├── data/
    │       │   ├── dto/
    │       │   │   └── LearningDtos.kt
    │       │   ├── mapper/
    │       │   │   └── LearningMappers.kt
    │       │   └── repository/
    │       │       └── LearningRepositoryImpl.kt
    │       ├── domain/
    │       │   ├── model/
    │       │   │   └── LearningModels.kt
    │       │   ├── repository/
    │       │   │   └── LearningRepository.kt
    │       │   └── usecase/
    │       │       └── LearningUseCases.kt
    │       ├── presentation/
    │       │   ├── home/
    │       │   │   ├── LearningHomeScreen.kt
    │       │   │   └── LearningHomeViewModel.kt
    │       │   ├── discovery/
    │       │   │   ├── CourseDiscoveryScreen.kt
    │       │   │   └── CourseDiscoveryViewModel.kt
    │       │   ├── details/
    │       │   │   ├── CourseDetailsScreen.kt
    │       │   │   └── CourseDetailsViewModel.kt
    │       │   ├── player/
    │       │   │   ├── VideoLearningScreen.kt
    │       │   │   └── VideoLearningViewModel.kt
    │       │   ├── mylearning/
    │       │   │   ├── MyLearningScreen.kt
    │       │   │   └── MyLearningViewModel.kt
    │       │   ├── quiz/
    │       │   │   ├── QuizScreen.kt
    │       │   │   └── QuizViewModel.kt
    │       │   ├── assignment/
    │       │   │   ├── AssignmentScreen.kt
    │       │   │   └── AssignmentViewModel.kt
    │       │   ├── certificate/
    │       │   │   ├── CertificateScreen.kt
    │       │   │   └── CertificateViewModel.kt
    │       │   └── instructor/
    │       │       ├── InstructorDashboardScreen.kt
    │       │       └── InstructorDashboardViewModel.kt
    │       ├── di/
    │       │   └── LearningModule.kt
    │       └── navigation/
    │           └── LearningNavigation.kt
    └── test/
        └── java/com/connectly/android/feature/learning/
            └── LearningViewModelTest.kt
```

---

## 4. Key Cross-Cutting Workflows

### 4.1 Video Learning & Progress Synchronization
1. When a student opens a video lesson, `VideoLearningScreen` requests playback data through `GET /api/v1/learning/lessons/{id}`.
2. The server verifies enrollment status via `LearningLessonPolicy`. Upon approval, a signed/validated streaming URL and current progress timestamp are returned.
3. As the video plays via ExoPlayer, progress ticks are sent every 10 seconds via `POST /api/v1/learning/lessons/{id}/progress`.
4. When watch progress reaches $\ge 80\%$, the lesson is marked `completed = true`.
5. Course completion percentage is recalculated dynamically:
   $$\text{Progress \%} = \left(\frac{\text{Completed Lessons}}{\text{Total Required Lessons}}\right) \times 100$$
6. If progress reaches 100% and all quizzes/assignments are cleared, `CertificateService::issueCertificate()` is automatically triggered.

### 4.2 Assessment & Examination Lifecycle
1. The student initiates a quiz attempt (`POST /api/v1/learning/quizzes/{id}/attempt`).
2. Server validates attempt limits and active enrollment, records start time, and returns questions **without** correct answers.
3. Student submits answers (`POST /api/v1/learning/quizzes/{id}/submit`).
4. Server calculates score:
   - For MCQs, Multi-select, and True/False: automatically scored.
   - Percentage score is compared against `passing_score`.
   - Result (`passed = true/false`, score percentage, earned points) is saved and returned.
   - If passed, the quiz lesson progress is marked complete.

### 4.3 Verified Certificate Generation
1. Student meets 100% completion criteria.
2. Server generates a cryptographically unique certificate code (e.g., `CERT-2026-JUGAJUG-XXXX-XXXX`) and verification token.
3. Verification URL is generated: `https://connectly.app/verify/cert/{code}`.
4. Certificate record is created in `learning_certificates`.
5. The verified credential can be optionally published to `candidate_certifications` and `user_profiles` for one-click LinkedIn/Connectly resume display.

