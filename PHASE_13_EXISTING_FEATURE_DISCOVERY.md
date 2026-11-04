# Phase 13: Existing Feature Discovery & Reusable Subsystems Analysis
**Platform:** Connectly / JUGAJUG (Enterprise Professional Social Networking Platform)  
**System Module:** Professional Learning & Development Platform (LMS)  
**Date:** November 2026  
**Auditor:** Principal Software Architect & QA Automation Engineer  

---

## 1. Executive Summary

A core requirement of Phase 13 is that **the LMS is an extension of the existing Connectly professional social networking platform, NOT a separate application**. Duplicate modules, database tables, API endpoints, or services must not be created if suitable existing implementations already exist.

This discovery document details all existing subsystems across Laravel and Android, cataloging reusable primitives, database tables, policies, and client architecture that will power the LMS.

---

## 2. Inventory of Reusable Platform Subsystems

### 2.1 Identity, Authentication & Role-Based Access Control (RBAC)
* **Existing Infrastructure:**
  - `App\Models\User`: Central user identity with Sanctum authentication (`HasApiTokens`), email verification, phone verification, and two-factor authentication.
  - `App\Models\Role` & `App\Models\Permission`: Database-driven RBAC system with `role_user` pivot table.
  - Middlewares: `RoleMiddleware`, `PermissionMiddleware`, `ActiveUserMiddleware`, `VerifiedUserMiddleware`.
* **LMS Integration Strategy:**
  - Instructors and students are regular `User` instances.
  - Instructor status is granted via role assignment (`INSTRUCTOR`, `VERIFIED_INSTRUCTOR`) alongside an associated `LearningInstructor` record for instructor bio and credentials.
  - Course access and administrative operations reuse `RoleMiddleware` and Laravel Policies (`LearningCoursePolicy`, `LearningLessonPolicy`).

### 2.2 User Profiles, Professional Skills & Badges
* **Existing Infrastructure:**
  - `UserProfile` model: Bio, headline, location, avatar, cover image, verified badges.
  - `ProfileSkill` & `JobSkill`: Standardized skill taxonomy with levels and endorsement counts.
  - `CandidateCertification`: Stores verified professional credentials (`title`, `issuing_organization`, `credential_id`, `credential_url`).
* **LMS Integration Strategy:**
  - Courses tag standardized `JobSkill` instances in `learning_course_skills`.
  - When a student completes a course and earns a `LearningCertificate`, the certificate is automatically linked to the user's `CandidateCertification` and profile achievements, making it immediately visible to recruiters in the Job Portal & ATS.

### 2.3 Media Storage & Secure Video Streaming
* **Existing Infrastructure:**
  - `App\Models\Media`: Polymorphic media attachment supporting images, videos, audio, and documents.
  - Chunked uploads (`MediaUploadSession`, `MediaUploadChunk`) and background transcoding (`MediaProcessingJob`).
  - Flysystem driver (`league/flysystem-aws-s3-v3` / local public disk) with temporary signed URL capabilities.
  - Android `:core:media` module: Media3 ExoPlayer (`androidx-media3-exoplayer`, `androidx-media3-ui`, `androidx-media3-exoplayer-hls`) with caching and background lifecycle management.
* **LMS Integration Strategy:**
  - Course promotional videos, lesson videos, and downloadable attachments use the polymorphic `Media` model.
  - Video streaming URLs for private lessons are access-controlled: students can only access lesson video URLs if they have an active `LearningEnrollment`.
  - Android `:feature:learning` embeds the existing ExoPlayer from `:core:media` for video lessons.

### 2.4 Notifications & Real-Time Presence
* **Existing Infrastructure:**
  - `App\Services\NotificationService` and `App\Models\Notification`.
  - Laravel Notifications system with real-time broadcasting over Laravel Reverb / Pusher WebSockets.
  - Firebase Cloud Messaging (FCM) via device tokens (`fcm_device_tokens` table) and Android `DeviceTokenManager`.
* **LMS Integration Strategy:**
  - Learning events trigger real-time push and in-app notifications:
    * Quiz submission graded.
    * Assignment feedback provided.
    * New announcement posted by course instructor.
    * Course certificate issued.
    * Course enrollment confirmed.

### 2.5 Groups, Communities & Discussions
* **Existing Infrastructure:**
  - `Group`, `GroupMember`, `GroupPost`, `Reaction`, `Comment`: Enterprise group ecosystem with role hierarchy (`owner`, `admin`, `moderator`, `member`), private/public privacy, and comment nesting.
* **LMS Integration Strategy:**
  - Course Q&A and student discussions leverage existing comment, mention, and moderation capabilities.
  - Instructors can pin answers, mark questions resolved, and report policy violations using existing moderation tools.

### 2.6 Monetization, Wallets & Financial Transactions
* **Existing Infrastructure:**
  - `Wallet` & `WalletTransaction`: Balance tracking, coins, ledger records, holds, and payouts.
  - `MarketplacePaymentTransaction`: Multi-gateway checkout with server-side validation and idempotency keys.
  - `CreatorEarning` & `CreatorPayoutRequest`: Revenue-share distribution and creator payouts.
* **LMS Integration Strategy:**
  - Paid course enrollments execute through the established wallet/payment gateway pipeline.
  - Coupon codes (`learning_course_coupons`) apply percentage or fixed discounts before transaction completion.
  - Instructor earnings are credited automatically to creator balances upon verified student enrollment.

### 2.7 Search & Discovery
* **Existing Infrastructure:**
  - `SearchService` & `SearchController`: Unified multi-entity search engine with debouncing, keyword ranking, and filter criteria.
* **LMS Integration Strategy:**
  - Global search includes a `courses` entity type alongside people, posts, groups, pages, and jobs.
  - Direct course search covers title, category, level, skill tags, and instructor names.

---

## 3. Discovered LMS Artifacts Already In Repository

During this audit, the following pre-existing LMS foundations were discovered in the codebase:
1. **Migrations (8 files):**
   - `2026_11_04_160000_create_learning_categories_and_instructors_tables.php`
   - `2026_11_04_161000_create_learning_courses_and_taxonomy_tables.php`
   - `2026_11_04_162000_create_learning_curriculum_and_lessons_tables.php`
   - `2026_11_04_163000_create_learning_enrollments_and_progress_tables.php`
   - `2026_11_04_164000_create_learning_quizzes_and_attempts_tables.php`
   - `2026_11_04_165000_create_learning_assignments_and_submissions_tables.php`
   - `2026_11_04_166000_create_learning_certificates_and_reviews_tables.php`
   - `2026_11_04_167000_create_learning_community_coupons_and_transactions_tables.php`
2. **Models (26 files):**
   - Located in `app/Models/Learning*.php` (Course, Section, Lesson, Quiz, Assignment, Certificate, Review, etc.).
3. **Services (9 files):**
   - Located in `app/Services/Learning/` (CourseService, EnrollmentService, QuizService, CertificateService, etc.).
4. **Controllers (8 files):**
   - Located in `app/Http/Controllers/Api/v1/Learning/`.
5. **Android Module (`:feature:learning`):**
   - Initial `LearningHomeScreen.kt` with tabs ("Discover", "My Learning", "Certificates"), `LearningViewModel.kt`, `LearningRepositoryImpl.kt`.

All existing components are in working order, with unit and feature tests passing. Phase 13 development will build upon and complete this existing architecture rather than duplicating or replacing it.

