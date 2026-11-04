# Phase 13 — Stage 02: LMS Security, Authorization & Privacy Design
**Project:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform  
**Stage:** 02 — LMS Database, Backend Architecture & API Foundation  
**Date:** November 2026  
**Author:** Principal Software Architect & Lead Application Security Officer  

---

## 1. Role-Based Access Control (RBAC) Matrix

The LMS enforces authorization across 6 distinct user contexts:

| Endpoint Category | Guest User | Registered Learner | Enrolled Student | Instructor (Non-Owner) | Course Owner (Instructor) | Administrator |
|---|---|---|---|---|---|---|
| Browse Catalog / Categories | Allowed | Allowed | Allowed | Allowed | Allowed | Allowed |
| View Course Landing Page | Allowed | Allowed | Allowed | Allowed | Allowed | Allowed |
| Stream Preview Lessons (`is_preview=true`)| Allowed | Allowed | Allowed | Allowed | Allowed | Allowed |
| Stream Protected Lessons | Denied (401) | Denied (403) | Allowed | Denied (403) | Allowed | Allowed |
| Download Lesson Attachments | Denied (401) | Denied (403) | Allowed | Denied (403) | Allowed | Allowed |
| Take Quizzes / Submit Assignments | Denied (401) | Denied (403) | Allowed | Denied (403) | Allowed (Preview) | Allowed |
| View Other Student Submissions | Denied (401) | Denied (403) | Denied (403) | Denied (403) | Allowed (Own Course) | Allowed |
| Edit Course / Sections / Lessons | Denied (401) | Denied (403) | Denied (403) | Denied (403) | Allowed | Allowed |
| Publish / Suspend Course | Denied (401) | Denied (403) | Denied (403) | Denied (403) | Submit for Review | Full Control |
| Issue / Claim Certificate | Denied (401) | Denied (403) | Verified 100% | Denied (403) | Allowed (Preview) | Allowed / Revoke |

---

## 2. Core Security & Fraud Defense Mechanisms

### 2.1 Insecure Direct Object Reference (IDOR) Protection
* **Lesson Content & Media:**
  - Access to `GET /api/v1/learning/lessons/{id}` strictly validates `LearningLessonPolicy::view()`.
  - Non-enrolled users attempting to access paid video streaming URLs or resource attachments receive HTTP 403 Forbidden.
* **Student Submissions:**
  - `GET /api/v1/learning/assignments/submissions/{id}` is restricted: only the student who submitted the file or the instructor of that specific course (or an admin) can view the file.
* **Course Modification:**
  - `PUT /api/v1/learning/courses/{id}`, section updates, and lesson deletions verify that `course.instructor_id === auth()->id()` via `LearningCoursePolicy::update()`.

### 2.2 Quiz Answer Key Exposure Defense
* **Zero Client-Side Leaks:**
  - The API endpoint `GET /api/v1/learning/quizzes/{id}` queries `learning_quiz_questions` and `learning_quiz_options` using an explicit attribute mask: `select(['id', 'question_id', 'option_text', 'sort_order'])`.
  - `is_correct` and `explanation` columns are strictly excluded from the serialization payload.
* **Server-Authoritative Evaluation:**
  - Answer evaluation happens exclusively inside `QuizService::submitAttempt()`. The client only sends `{"question_id": X, "selected_option_id": Y}`.

### 2.3 Certificate Forgery Prevention
* **Server-Side Eligibility Engine:**
  - Certificates cannot be granted by client parameters. Calling `POST /api/v1/learning/courses/{id}/certificate` invokes `CertificateService::claimCertificate()`, which performs three database verifications:
    1. Active enrollment exists.
    2. Completed lesson count equals total mandatory lessons (progress = 100%).
    3. Passing scores achieved on all required quizzes.
* **Cryptographic Verification:**
  - Each certificate code is generated using `Str::upper('CERT-' . date('Y') . '-' . Str::random(4) . '-' . Str::random(4))`.
  - The public verification endpoint `GET /api/v1/learning/certificates/{code}` validates against database truth, confirming validity or revocation status.

### 2.4 Financial & Enrollment Integrity
* **Server-Side Price Validation:**
  - Course prices are locked in the `learning_courses` table. Clients cannot submit arbitrary payment amounts.
  - For paid enrollments, the system verifies `MarketplacePaymentTransaction` or `Wallet` debit using server-side transactions. A client-side "payment success" callback without server confirmation is never trusted.

### 2.5 Input Sanitization & File Upload Security
* **MIME-Type & Extension Whitelisting:**
  - Video lessons: `mp4`, `webm`, `mkv` (max 2GB via chunked upload).
  - Attachments & Submissions: `pdf`, `zip`, `docx`, `png`, `jpg` (max 50MB). Executable binaries (`.exe`, `.sh`, `.php`) are blocked at the Form Request level.
* **XSS Defense:**
  - All course descriptions, assignment submissions, and discussion posts are sanitized via HTML Purifier before database persistence.

