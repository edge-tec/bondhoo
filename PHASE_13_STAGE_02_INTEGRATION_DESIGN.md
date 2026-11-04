# Phase 13 — Stage 02: Platform Integration Design (Payment, Media, Notifications)
**Project:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform  
**Stage:** 02 — LMS Database, Backend Architecture & API Foundation  
**Date:** November 2026  
**Author:** Principal Software Architect & Lead Integration Engineer  

---

## 1. Zero-Duplication Architecture Commitment

In strict accordance with Step 08 and Critical Rule #3:
* **NO duplicate payment gateway or transaction ledger** is introduced.
* **NO duplicate media upload or chunking system** is written.
* **NO duplicate notification or push engine** is created.

The LMS integrates directly with established Connectly platform infrastructure.

---

## 2. Payment & Monetization Integration

### 2.1 Workflow Architecture
1. **Free Course Enrollment:**
   - Client calls `POST /api/v1/learning/courses/{id}/enroll`.
   - `EnrollmentService` confirms `course.type === 'free'`.
   - Direct database transaction inserts active `learning_enrollments` record.
2. **Paid Course Checkout:**
   - Option A: **Direct Wallet Payment:**
     * `EnrollmentService` checks `Wallet::where('user_id', $user->id)->first()`.
     * If `balance >= course.price`, creates debit in `wallet_transactions` with reference `LMS_ENROLL_{course_id}`.
     * Credits instructor's creator balance based on `revenue_share_percentage` (default 70%).
     * Issues active enrollment.
   - Option B: **Payment Gateway (bKash, Nagad, Cards via Marketplace Pipeline):**
     * Checkout initiates `MarketplacePaymentTransaction` with `order_type = 'lms_course'` and `idempotency_key`.
     * Upon server-to-server webhook confirmation, `EnrollmentService::enroll()` activates access.
3. **Refunds & Cancellations:**
   - If a student requests a refund within policy (e.g. 7 days with < 10% progress), `cancelEnrollment()` updates enrollment status to `refunded` and reverses wallet ledger entries.

---

## 3. Media & Secure Video Delivery Integration

### 3.1 Course Media Storage
* **Thumbnails & Attachments:** Stored via standard `Media` polymorphic association (`mediable_type = LearningCourse::class`, `mediable_id = $course->id`).
* **Chunked Video Uploads:**
  - Instructors upload HD lesson videos using `POST /api/v1/media/upload/chunk` (`MediaUploadSession`).
  - Upon completion, `MediaProcessingJob` transcodes video to 1080p/720p HLS format and links to `learning_course_lessons.video_url`.

### 3.2 Access-Controlled Video Streaming
* Protected lesson streaming URLs are access-gated.
* Client calls `GET /api/v1/learning/lessons/{id}`.
* Server validates enrollment via `LearningLessonPolicy`.
* If authorized, returns a temporary signed streaming URL valid for 2 hours (`Storage::disk('s3')->temporaryUrl($path, now()->addHours(2))`).
* Directly streamable in Android `:core:media` ExoPlayer.

---

## 4. Notifications & Real-Time Presence Integration

### 4.1 Automated Trigger Matrix

| Event Name | Trigger Condition | Notification Channel | Recipient |
|---|---|---|---|
| `CourseEnrolledEvent` | Successful enrollment (Free / Paid) | Database + In-App Banner | Student & Instructor |
| `QuizGradedEvent` | Student submits quiz attempt | Push (FCM) + In-App | Student |
| `AssignmentSubmittedEvent`| Student submits assignment | Database + Dashboard | Instructor |
| `AssignmentGradedEvent` | Instructor assigns score & feedback | Push (FCM) + In-App | Student |
| `CertificateIssuedEvent` | Student reaches 100% and claims cert | Push (FCM) + In-App + Profile | Student |
| `CourseAnnouncementEvent`| Instructor broadcasts notice | Reverb WebSocket + FCM | All Enrolled Students |

All notifications reuse `NotificationService` and `fcm_device_tokens`.

