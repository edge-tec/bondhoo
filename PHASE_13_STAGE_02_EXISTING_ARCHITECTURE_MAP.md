# Phase 13 — Stage 02: Existing Architecture Map & Reusable Systems Inventory
**Project:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform  
**Stage:** 02 — LMS Database, Backend Architecture & API Foundation  
**Date:** November 2026  
**Author:** Principal Software Architect & Lead System Designer  

---

## 1. Executive Summary

This architecture map inventories all existing platform components, classes, models, services, migrations, and Android modules that will be reused for **PHASE 13: Professional Learning & Development Platform (LMS)**.

Adhering strictly to non-negotiable Rule #3 and Rule #4, **no existing User, Role, Permission, Job Skill, Job Category, Media, Payment, Notification, Group or Profile systems will be recreated**.

---

## 2. Reusable Backend Components (Laravel 13 / PHP 8.5)

### 2.1 Identity, Authentication & Role Hierarchy
* **Model:** [`app/Models/User.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/User.php)
  - Traits: `HasApiTokens`, `HasFactory`, `Notifiable`.
  - Methods: `hasRole(string $role)`, `assignRole(string $role)`, `removeRole(string $role)`.
* **RBAC Models:**
  - [`app/Models/Role.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/Role.php) (`name`, `slug`, `description`).
  - [`app/Models/Permission.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/Permission.php) (`name`, `slug`).
  - Pivot: `role_user` (`user_id`, `role_id`).
* **Middleware:**
  - `Authenticate` (`auth:sanctum`).
  - `RoleMiddleware` (`role:INSTRUCTOR`, `role:ADMIN`, `role:SUPER_ADMIN`).
  - `PermissionMiddleware` (`permission:manage_courses`, `permission:publish_courses`).
  - `ActiveUserMiddleware` & `VerifiedUserMiddleware`.

### 2.2 Professional Skills & Taxonomy (Job Portal Integration)
* **Models:**
  - [`app/Models/JobSkill.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/JobSkill.php): Standardized skills (`id`, `name`, `slug`, `category_id`, `is_active`).
  - [`app/Models/JobCategory.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/JobCategory.php): Standardized industry taxonomy (`id`, `name`, `slug`, `icon`, `is_active`).
* **LMS Linkage:**
  - `learning_course_skills` links `learning_courses.id` directly to `job_skills.id`.
  - When students filter courses by skill, the query uses existing `job_skills.slug`.

### 2.3 Candidate Profiles & Verified Certifications
* **Models:**
  - [`app/Models/CandidateProfile.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/CandidateProfile.php): Professional summary, current title, experience level.
  - [`app/Models/CandidateCertification.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/CandidateCertification.php):
    - Fields: `candidate_profile_id`, `user_id`, `title`, `issuing_organization`, `credential_id`, `credential_url`, `issue_date`, `is_verified`.
* **LMS Linkage:**
  - When a student earns a `LearningCertificate`, the system automatically inserts/updates a verified `CandidateCertification` record, displaying the verified Connectly LMS credential in the Job Portal and ATS.

### 2.4 Media Storage, Chunked Uploads & Video Transcoding
* **Models & Services:**
  - [`app/Models/Media.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/Media.php): Polymorphic media records (`mediable_type`, `mediable_id`, `disk`, `file_path`, `mime_type`, `file_size`, `duration_seconds`).
  - [`app/Models/MediaUploadSession.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/MediaUploadSession.php) & [`MediaUploadChunk.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/MediaUploadChunk.php): Multi-part chunked video and document uploads.
  - [`app/Models/MediaProcessingJob.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/MediaProcessingJob.php): Asynchronous background transcoding.
* **LMS Linkage:**
  - Course promotional videos, lesson videos, and downloadable attachments use polymorphic `Media`.
  - Access to private lesson media uses temporary signed URLs validated against active enrollments.

### 2.5 Monetization, Wallets & Payment Gateways
* **Models:**
  - [`app/Models/Wallet.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/Wallet.php): User balance, held balance, coin balance.
  - [`app/Models/WalletTransaction.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/WalletTransaction.php): Ledger of credits, debits, holds, and releases.
  - [`app/Models/MarketplacePaymentTransaction.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/MarketplacePaymentTransaction.php): Multi-gateway transactions with idempotency keys and gateway reference verification.
* **LMS Linkage:**
  - Paid course checkout reuses `Wallet` / `MarketplacePaymentTransaction`. No second payment system is introduced.

### 2.6 Notifications & Real-Time Presence
* **Services & Infrastructure:**
  - `NotificationService`: Database notifications with push tokens (`fcm_device_tokens`).
  - Laravel Reverb / Pusher WebSockets broadcasting (`Broadcast::routes`).
* **LMS Linkage:**
  - Automatic push and in-app notifications for enrollment, quiz grading, assignment feedback, and certificate issuance.

---

## 3. Reusable Android Components (Kotlin / Jetpack Compose)

* **Module Layout:**
  - `:core:network`: `ConnectlyApi.kt`, Retrofit client, OkHttp authenticator, `ApiResponse<T>` envelope.
  - `:core:media`: Media3 ExoPlayer (`androidx-media3-exoplayer`, `androidx-media3-ui`, `androidx-media3-exoplayer-hls`) for HLS/MP4 video streaming.
  - `:core:designsystem`: Material 3 components, theme, typography, buttons, cards.
  - `:feature:learning`: Existing module housing `LearningHomeScreen.kt`, `LearningViewModel.kt`, `LearningRepository.kt`, and `LearningNavigation.kt`.
* **Navigation:**
  - Centralized navigation in `MainActivity.kt` line 187: `learningScreen(...)` and `navigateToLearning()` invoked from `MenuScreen.kt`.

---

## 4. Existing Discovered LMS Architecture

The following pre-existing files in the repository provide the foundation for Phase 13:

| Layer | Existing Files | Purpose |
|---|---|---|
| **Database Migrations** | 8 files (`2026_11_04_160000_*` to `167000_*`) | Table schemas for categories, courses, lessons, enrollments, quizzes, assignments, certificates, reviews. |
| **Eloquent Models** | 26 files in `app/Models/Learning*.php` | Domain relationships and `toResponseArray()` mappers. |
| **Domain Services** | 9 files in `app/Services/Learning/*.php` | Business workflows for courses, progress, quizzes, certificates, and reviews. |
| **API Controllers** | 8 files in `app/Http/Controllers/Api/v1/Learning/*.php` | Versioned REST endpoints under `/api/v1/learning/`. |
| **Android Feature** | `android/feature/learning/` | Initial MVI screens, domain models, repository, and use cases. |

