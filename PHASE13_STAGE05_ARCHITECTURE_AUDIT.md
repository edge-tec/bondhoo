# PHASE 13 — STAGE 05: REPOSITORY & ARCHITECTURE AUDIT
## CONNECTLY / JUGAJUG — Ecosystem Inventory & Minimal Surface Audit

### 1. Executive Architecture Inventory
- **Application Framework:** Laravel 11.x on PHP 8.5 (Strict typing, constructor property promotion, Pint PSR-12 standard).
- **Mobile Client:** Android Kotlin (Jetpack Compose, Clean Architecture, Hilt DI, Retrofit/OkHttp, Kotlinx.serialization).
- **Real-Time Layer:** Laravel Reverb / Pusher WebSockets (`Broadcast::channel` in `routes/channels.php`).
- **Database Engine:** MySQL 8.0 with InnoDB foreign key constraints, composite unique indexes, and ACID transaction boundaries.

### 2. Laravel Backend Module Map
| Domain | Controllers | Models | Services / Policies |
| :--- | :--- | :--- | :--- |
| **Authentication & RBAC** | `LoginController`, `RegisterController`, `TwoFactorController` | `User`, `Role`, `Permission`, `DeviceSession` | `AuthService`, `RbacService`, `SessionSecurityService` |
| **Groups & Communities** | `GroupController`, `GroupInsightsController`, `CommunityV2ApiController` | `Group`, `GroupMember`, `GroupCategory`, `GroupRule` | `GroupService`, `EnterpriseGroupService`, `GroupPolicy` |
| **Business Pages** | `PageController`, `PageV2ApiController`, `PageAnalyticsV2ApiController` | `Page`, `PageMember`, `PageCategory`, `PageFollower` | `PageService`, `EnterprisePageService`, `PagePolicy` |
| **LMS Marketplace & Core** | `LearningCourseApiController`, `LearningCurriculumApiController` | `LearningCourse`, `LearningCourseSection`, `LearningCourseLesson` | `CourseService`, `CourseContentService`, `LearningCoursePolicy` |
| **LMS Enrollment & Wallet** | `LearningEnrollmentApiController` | `LearningEnrollment`, `LearningTransaction`, `Wallet`, `WalletTransaction` | `EnrollmentService`, `PaymentGatewayManager` |
| **Assessments & Quizzes** | `LearningQuizApiController` | `LearningQuiz`, `LearningQuizQuestion`, `LearningQuizOption`, `LearningQuizAttempt` | `QuizService`, `LearningLessonPolicy` |
| **Assignments & Lab Projects**| `LearningAssignmentApiController` | `LearningAssignment`, `LearningAssignmentSubmission` | `AssignmentService`, `LearningLessonPolicy` |
| **Certification & Resume Sync**| `LearningCertificateApiController` | `LearningCertificate`, `CandidateCertification` | `CertificateService` |

### 3. Discovered Vulnerability Analysis & Minimal Hardening
1. **Quiz Correct Option Leakage Risk (RESOLVED):** Raw serialization of `LearningQuizOption` previously included `is_correct`. Protected via `protected $hidden = ['is_correct'];` and explicit DTO transformation in `QuizService::getQuizForStudent()`.
2. **Post-Completion Lesson Lockout (RESOLVED):** When enrollment status transitioned to `completed`, `isEnrolledByUser()` previously queried only `status = 'active'`, locking out legitimate graduates. Resolved by supporting `['active', 'completed']`.
3. **Android Deserialization Null Pointer (RESOLVED):** `social_links` and `business_hours` being null caused Kotlinx Serialization array expectations to crash the client. Fixed via resilient serializers (`PageSocialLinksSerializer`, `BusinessHoursSerializer`) and backend accessors defaulting to empty arrays.

