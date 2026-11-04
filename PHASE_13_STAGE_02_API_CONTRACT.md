# Phase 13 — Stage 02: LMS REST API Contract Specification
**Project:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform  
**Stage:** 02 — LMS Database, Backend Architecture & API Foundation  
**Date:** November 2026  
**Author:** Principal Software Architect & Lead API Architect  

---

## 1. Global API Standards

* **Base URL:** `/api/v1/learning`
* **Authentication:** Laravel Sanctum Bearer Token (`Authorization: Bearer <token>`).
* **Standard Success Envelope:**
  ```json
  {
    "success": true,
    "status": "success",
    "message": "Localized message",
    "data": { ... },
    "meta": { "current_page": 1, "last_page": 5, "total": 75 } // if paginated
  }
  ```
* **Standard Error Envelope:**
  ```json
  {
    "success": false,
    "status": "error",
    "message": "Descriptive error message",
    "errors": { "field": ["Validation message"] }
  }
  ```

---

## 2. API Endpoint Specifications

### 2.1 Course Discovery & Marketplace

#### `GET /api/v1/learning/categories`
* **Auth:** Optional (Public).
* **Parameters:** None.
* **Response `data`:** List of `LearningCategoryDto` (`id`, `name`, `slug`, `icon`, `courses_count`).

#### `GET /api/v1/learning/courses`
* **Auth:** Optional (Public).
* **Parameters (Query):**
  - `q` (string, optional): Search keyword.
  - `category_id` (int, optional): Category ID.
  - `level` (string, optional): `all_levels`, `beginner`, `intermediate`, `expert`.
  - `type` (string, optional): `free`, `paid`.
  - `skill_id` (int, optional): Linked `job_skills` ID.
  - `sort` (string, optional): `popular`, `highest_rated`, `newest`.
  - `page` (int, optional): Page number.
* **Rate Limit:** 60 requests/minute.
* **Response `data`:** Array of `LearningCourseDto` items with pagination `meta`.

#### `GET /api/v1/learning/courses/{slug}`
* **Auth:** Optional (Public / Enriched if Authenticated).
* **Response `data`:** Full course details including syllabus preview, instructor profile, and viewer status (`is_enrolled`, `progress_percentage`).

---

### 2.2 Instructor Hub & Course Authoring

#### `POST /api/v1/learning/instructors/apply`
* **Auth:** Required (`auth:sanctum`).
* **Request Body:**
  ```json
  {
    "expertise": "Android Architecture & Kotlin",
    "experience_years": 8,
    "portfolio_url": "https://github.com/...",
    "proposal": "Comprehensive Jetpack Compose mastery course"
  }
  ```
* **Validation:** All fields validated; prevents duplicate pending applications.
* **Response Code:** 201 Created.

#### `POST /api/v1/learning/courses`
* **Auth:** Required (`role:INSTRUCTOR` or `role:ADMIN`).
* **Request Body:**
  ```json
  {
    "title": "Mastering Jetpack Compose",
    "category_id": 3,
    "level": "intermediate",
    "type": "paid",
    "price": 2500,
    "description": "In-depth modern Android UI course",
    "skills": [12, 18, 45]
  }
  ```
* **Response Code:** 201 Created (`LearningCourseDto`).

#### `POST /api/v1/learning/courses/{id}/sections`
* **Auth:** Required (`auth:sanctum` + Course Owner Policy).
* **Request Body:** `{"title": "Section 1: Architecture Basics", "sort_order": 1}`.
* **Response Code:** 201 Created.

#### `POST /api/v1/learning/sections/{id}/lessons`
* **Auth:** Required (Course Owner Policy).
* **Request Body:**
  ```json
  {
    "title": "MVI Architecture with StateFlow",
    "type": "video",
    "video_url": "https://storage.connectly.com/lessons/vid1.mp4",
    "video_duration_seconds": 1240,
    "is_preview": false,
    "is_mandatory": true
  }
  ```
* **Response Code:** 201 Created.

---

### 2.3 Enrollments & Learning Progress

#### `POST /api/v1/learning/courses/{id}/enroll`
* **Auth:** Required (`auth:sanctum`).
* **Request Body:** `{"coupon_code": "LAUNCH50"}` (optional).
* **Business Logic:**
  - If free: instant enrollment.
  - If paid: checks user wallet balance or verified payment transaction reference.
* **Response Code:** 200 OK / 201 Created (`LearningEnrollmentDto`).

#### `GET /api/v1/learning/my-learning`
* **Auth:** Required (`auth:sanctum`).
* **Parameters:** `status` (`active`, `completed`, `all`).
* **Response `data`:** List of enrolled courses with progress percentage, completed lesson count, and last accessed timestamp.

#### `GET /api/v1/learning/lessons/{id}`
* **Auth:** Required (`auth:sanctum`).
* **Access Control:** Verified via `LearningLessonPolicy`. Returns HTTP 403 if lesson is not a preview and viewer is not enrolled.
* **Response `data`:** Lesson content, secure video streaming URL, and attached resources.

#### `POST /api/v1/learning/lessons/{id}/progress`
* **Auth:** Required (`auth:sanctum`).
* **Request Body:** `{"position_seconds": 450, "mark_completed": true}`.
* **Response `data`:** Updated progress and course recalculation summary.

---

### 2.4 Quizzes & Assignments

#### `GET /api/v1/learning/quizzes/{id}`
* **Auth:** Required (`auth:sanctum`).
* **Security Guard:** `is_correct` field is strictly omitted from all question options.
* **Response `data`:** Quiz title, time limit, passing score, questions, and option texts.

#### `POST /api/v1/learning/quizzes/{id}/attempt`
* **Auth:** Required (`auth:sanctum`).
* **Response Code:** 201 Created (`attempt_id`, `started_at`).

#### `POST /api/v1/learning/quizzes/attempts/{attemptId}/submit`
* **Auth:** Required (`auth:sanctum`).
* **Request Body:** `{"answers": [{"question_id": 1, "selected_option_id": 3}]}`.
* **Response `data`:** Evaluated score, earned points, `passed: true/false`.

#### `POST /api/v1/learning/assignments/{id}/submit`
* **Auth:** Required (`auth:sanctum`).
* **Request Body:** `{"content": "GitHub Repository link...", "file_path": "uploads/assignments/sub1.zip"}`.
* **Response Code:** 201 Created.

---

### 2.5 Certificates & Public Verification

#### `POST /api/v1/learning/courses/{id}/certificate`
* **Auth:** Required (`auth:sanctum`).
* **Eligibility Rule:** Course progress must be 100% and quizzes passed. Returns 422 if requirements not met.
* **Response `data`:** `LearningCertificateDto` with `certificate_code`, `issued_at`, `pdf_url`, and `qr_code_url`.

#### `GET /api/v1/learning/certificates/{code}`
* **Auth:** Optional (Public Verification).
* **Response `data`:** Authenticity confirmation, student name, course title, instructor, and completion timestamp.

