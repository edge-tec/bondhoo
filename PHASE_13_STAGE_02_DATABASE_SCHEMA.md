# Phase 13 — Stage 02: LMS Database Schema Specification
**Project:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform  
**Stage:** 02 — LMS Database, Backend Architecture & API Foundation  
**Date:** November 2026  
**Author:** Principal Software Architect & Lead Database Architect  

---

## 1. Schema Overview

The LMS database schema consists of **26 normalized relational tables** integrated cleanly into the Connectly MySQL database.

All tables enforce:
1. `unsignedBigInteger('id')->autoIncrement()` as Primary Key.
2. Foreign key constraints with appropriate `ON DELETE CASCADE` or `ON DELETE SET NULL`.
3. Unique compound constraints preventing duplicate enrollments, reviews, and progress tracking.
4. Deterministic sorting columns (`sort_order`, `order`) for curriculums and questions.
5. Soft deletes (`deleted_at`) on critical user-generated data.

---

## 2. Table Specifications by Subsystem

### 2.1 Course Discovery & Taxonomy

#### 1. `learning_categories`
* **Purpose:** Course subject categorization with hierarchical parent-child support.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `parent_id`: BIGINT UNSIGNED NULLABLE (FK -> `learning_categories.id` ON DELETE SET NULL)
  - `name`: VARCHAR(100) NOT NULL
  - `slug`: VARCHAR(120) NOT NULL UNIQUE
  - `icon`: VARCHAR(255) NULLABLE
  - `description`: TEXT NULLABLE
  - `sort_order`: INT DEFAULT 0
  - `is_active`: BOOLEAN DEFAULT TRUE
  - `courses_count`: INT UNSIGNED DEFAULT 0
  - `created_at`, `updated_at`: TIMESTAMP

#### 2. `learning_courses`
* **Purpose:** Primary course catalog records.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `instructor_id`: BIGINT UNSIGNED NOT NULL (FK -> `users.id` ON DELETE CASCADE)
  - `category_id`: BIGINT UNSIGNED NULLABLE (FK -> `learning_categories.id` ON DELETE SET NULL)
  - `title`: VARCHAR(200) NOT NULL
  - `slug`: VARCHAR(220) NOT NULL UNIQUE
  - `subtitle`: VARCHAR(300) NULLABLE
  - `description`: LONGTEXT NOT NULL
  - `level`: ENUM('all_levels', 'beginner', 'intermediate', 'expert') DEFAULT 'all_levels'
  - `language`: VARCHAR(30) DEFAULT 'en'
  - `thumbnail_url`: VARCHAR(500) NULLABLE
  - `promotional_video_url`: VARCHAR(500) NULLABLE
  - `type`: ENUM('free', 'paid') DEFAULT 'free'
  - `price`: DECIMAL(10, 2) DEFAULT 0.00
  - `discounted_price`: DECIMAL(10, 2) NULLABLE
  - `currency`: VARCHAR(10) DEFAULT 'BDT'
  - `status`: ENUM('draft', 'pending_review', 'published', 'suspended', 'archived') DEFAULT 'draft'
  - `duration_minutes`: INT UNSIGNED DEFAULT 0
  - `total_sections`: INT UNSIGNED DEFAULT 0
  - `total_lessons`: INT UNSIGNED DEFAULT 0
  - `enrollments_count`: INT UNSIGNED DEFAULT 0
  - `rating_avg`: DECIMAL(3, 2) DEFAULT 0.00
  - `rating_count`: INT UNSIGNED DEFAULT 0
  - `is_featured`: BOOLEAN DEFAULT FALSE
  - `published_at`: TIMESTAMP NULLABLE
  - `deleted_at`: TIMESTAMP NULLABLE (SoftDeletes)
  - `created_at`, `updated_at`: TIMESTAMP

#### 3. `learning_course_categories`
* **Purpose:** Multi-category association pivot.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `category_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_categories.id` ON DELETE CASCADE)
  - `is_primary`: BOOLEAN DEFAULT FALSE
  - **Constraints:** `UNIQUE KEY unique_course_category (course_id, category_id)`

#### 4. `learning_course_skills`
* **Purpose:** Links courses to standardized `job_skills` from Job Portal / ATS.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `job_skill_id`: BIGINT UNSIGNED NOT NULL (FK -> `job_skills.id` ON DELETE CASCADE)
  - **Constraints:** `UNIQUE KEY unique_course_skill (course_id, job_skill_id)`

---

### 2.2 Curriculum Structure

#### 5. `learning_course_sections`
* **Purpose:** Course curriculum modules/chapters.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `title`: VARCHAR(150) NOT NULL
  - `description`: TEXT NULLABLE
  - `sort_order`: INT UNSIGNED DEFAULT 0
  - `is_published`: BOOLEAN DEFAULT TRUE
  - `created_at`, `updated_at`: TIMESTAMP

#### 6. `learning_course_lessons`
* **Purpose:** Individual instructional units.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `section_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_course_sections.id` ON DELETE CASCADE)
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `title`: VARCHAR(150) NOT NULL
  - `slug`: VARCHAR(180) NOT NULL
  - `type`: ENUM('video', 'article', 'quiz', 'assignment', 'resource') DEFAULT 'video'
  - `content`: LONGTEXT NULLABLE
  - `video_url`: VARCHAR(500) NULLABLE
  - `video_duration_seconds`: INT UNSIGNED DEFAULT 0
  - `sort_order`: INT UNSIGNED DEFAULT 0
  - `is_preview`: BOOLEAN DEFAULT FALSE
  - `is_mandatory`: BOOLEAN DEFAULT TRUE
  - `deleted_at`: TIMESTAMP NULLABLE (SoftDeletes)
  - `created_at`, `updated_at`: TIMESTAMP

#### 7. `learning_lesson_attachments`
* **Purpose:** Downloadable code, slides, and PDF resources.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `lesson_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_course_lessons.id` ON DELETE CASCADE)
  - `title`: VARCHAR(150) NOT NULL
  - `file_path`: VARCHAR(500) NOT NULL
  - `file_type`: VARCHAR(50) NOT NULL
  - `file_size`: BIGINT UNSIGNED DEFAULT 0
  - `download_count`: INT UNSIGNED DEFAULT 0
  - `created_at`, `updated_at`: TIMESTAMP

---

### 2.3 Instructor Subsystem

#### 8. `learning_instructors`
* **Purpose:** Instructor profiles, biography, and credentials.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NOT NULL UNIQUE (FK -> `users.id` ON DELETE CASCADE)
  - `headline`: VARCHAR(200) NULLABLE
  - `bio`: TEXT NULLABLE
  - `website_url`: VARCHAR(255) NULLABLE
  - `linkedin_url`: VARCHAR(255) NULLABLE
  - `total_students`: INT UNSIGNED DEFAULT 0
  - `total_courses`: INT UNSIGNED DEFAULT 0
  - `rating_avg`: DECIMAL(3, 2) DEFAULT 0.00
  - `revenue_share_percentage`: DECIMAL(5, 2) DEFAULT 70.00
  - `is_verified`: BOOLEAN DEFAULT FALSE
  - `status`: ENUM('active', 'suspended', 'pending') DEFAULT 'pending'
  - `created_at`, `updated_at`: TIMESTAMP

#### 9. `learning_instructor_applications`
* **Purpose:** Instructor onboarding applications.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK -> `users.id` ON DELETE CASCADE)
  - `expertise`: VARCHAR(255) NOT NULL
  - `experience_years`: INT UNSIGNED DEFAULT 1
  - `portfolio_url`: VARCHAR(255) NULLABLE
  - `sample_video_url`: VARCHAR(255) NULLABLE
  - `proposal`: TEXT NOT NULL
  - `status`: ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'
  - `reviewer_id`: BIGINT UNSIGNED NULLABLE (FK -> `users.id` ON DELETE SET NULL)
  - `reviewer_feedback`: TEXT NULLABLE
  - `reviewed_at`: TIMESTAMP NULLABLE
  - `created_at`, `updated_at`: TIMESTAMP
  - **Constraints:** `INDEX (user_id, status)`

---

### 2.4 Enrollments, Progress & Wishlists

#### 10. `learning_enrollments`
* **Purpose:** Student course registration and access rights.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK -> `users.id` ON DELETE CASCADE)
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `enrolled_at`: TIMESTAMP NOT NULL
  - `expires_at`: TIMESTAMP NULLABLE
  - `status`: ENUM('active', 'completed', 'cancelled', 'refunded') DEFAULT 'active'
  - `progress_percentage`: DECIMAL(5, 2) DEFAULT 0.00
  - `completed_at`: TIMESTAMP NULLABLE
  - `price_paid`: DECIMAL(10, 2) DEFAULT 0.00
  - `currency`: VARCHAR(10) DEFAULT 'BDT'
  - `created_at`, `updated_at`: TIMESTAMP
  - **Constraints:** `UNIQUE KEY unique_user_course (user_id, course_id)`

#### 11. `learning_lesson_progress`
* **Purpose:** Granular playback timestamp and completion tracking per lesson.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK -> `users.id` ON DELETE CASCADE)
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `lesson_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_course_lessons.id` ON DELETE CASCADE)
  - `is_completed`: BOOLEAN DEFAULT FALSE
  - `last_position_seconds`: INT UNSIGNED DEFAULT 0
  - `completed_at`: TIMESTAMP NULLABLE
  - `created_at`, `updated_at`: TIMESTAMP
  - **Constraints:** `UNIQUE KEY unique_user_lesson (user_id, lesson_id)`

#### 12. `learning_course_progress`
* **Purpose:** High-level summary of student progress across a course.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK -> `users.id` ON DELETE CASCADE)
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `completed_lessons_count`: INT UNSIGNED DEFAULT 0
  - `total_lessons_count`: INT UNSIGNED DEFAULT 0
  - `progress_percentage`: DECIMAL(5, 2) DEFAULT 0.00
  - `last_activity_at`: TIMESTAMP NULLABLE
  - `created_at`, `updated_at`: TIMESTAMP
  - **Constraints:** `UNIQUE KEY unique_user_course_progress (user_id, course_id)`

#### 13. `learning_wishlists`
* **Purpose:** Saved courses for future enrollment.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK -> `users.id` ON DELETE CASCADE)
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `created_at`, `updated_at`: TIMESTAMP
  - **Constraints:** `UNIQUE KEY unique_user_wishlist (user_id, course_id)`

---

### 2.5 Assessment Subsystem (Quizzes & Assignments)

#### 14. `learning_quizzes`
* **Purpose:** Quiz configurations.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `lesson_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_course_lessons.id` ON DELETE CASCADE)
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `title`: VARCHAR(150) NOT NULL
  - `instructions`: TEXT NULLABLE
  - `time_limit_minutes`: INT UNSIGNED DEFAULT 0
  - `passing_score`: INT UNSIGNED DEFAULT 70
  - `max_attempts`: INT UNSIGNED DEFAULT 0
  - `created_at`, `updated_at`: TIMESTAMP

#### 15. `learning_quiz_questions`
* **Purpose:** Individual questions in a quiz.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `quiz_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_quizzes.id` ON DELETE CASCADE)
  - `question`: TEXT NOT NULL
  - `type`: ENUM('single_choice', 'multiple_choice', 'true_false') DEFAULT 'single_choice'
  - `points`: INT UNSIGNED DEFAULT 1
  - `sort_order`: INT UNSIGNED DEFAULT 0
  - `explanation`: TEXT NULLABLE
  - `created_at`, `updated_at`: TIMESTAMP

#### 16. `learning_quiz_options`
* **Purpose:** Answer choices for questions.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `question_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_quiz_questions.id` ON DELETE CASCADE)
  - `option_text`: TEXT NOT NULL
  - `is_correct`: BOOLEAN DEFAULT FALSE
  - `sort_order`: INT UNSIGNED DEFAULT 0
  - `created_at`, `updated_at`: TIMESTAMP

#### 17. `learning_quiz_attempts`
* **Purpose:** Student quiz submission attempts.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `quiz_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_quizzes.id` ON DELETE CASCADE)
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK -> `users.id` ON DELETE CASCADE)
  - `attempt_number`: INT UNSIGNED DEFAULT 1
  - `score`: DECIMAL(5, 2) DEFAULT 0.00
  - `total_points`: INT UNSIGNED DEFAULT 0
  - `earned_points`: INT UNSIGNED DEFAULT 0
  - `passed`: BOOLEAN DEFAULT FALSE
  - `started_at`: TIMESTAMP NOT NULL
  - `submitted_at`: TIMESTAMP NULLABLE
  - `created_at`, `updated_at`: TIMESTAMP

#### 18. `learning_quiz_answers`
* **Purpose:** Student submitted answers per attempt.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `attempt_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_quiz_attempts.id` ON DELETE CASCADE)
  - `question_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_quiz_questions.id` ON DELETE CASCADE)
  - `selected_option_id`: BIGINT UNSIGNED NULLABLE (FK -> `learning_quiz_options.id` ON DELETE SET NULL)
  - `is_correct`: BOOLEAN DEFAULT FALSE
  - `created_at`, `updated_at`: TIMESTAMP

#### 19. `learning_assignments`
* **Purpose:** Practical assignments created by instructors.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `lesson_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_course_lessons.id` ON DELETE CASCADE)
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `title`: VARCHAR(150) NOT NULL
  - `description`: LONGTEXT NOT NULL
  - `total_points`: INT UNSIGNED DEFAULT 100
  - `deadline_days`: INT UNSIGNED DEFAULT 7
  - `created_at`, `updated_at`: TIMESTAMP

#### 20. `learning_assignment_submissions`
* **Purpose:** Student submissions with instructor grading.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `assignment_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_assignments.id` ON DELETE CASCADE)
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK -> `users.id` ON DELETE CASCADE)
  - `content`: LONGTEXT NULLABLE
  - `file_path`: VARCHAR(500) NULLABLE
  - `status`: ENUM('submitted', 'under_review', 'graded', 'resubmission_requested') DEFAULT 'submitted'
  - `score`: INT UNSIGNED NULLABLE
  - `feedback`: TEXT NULLABLE
  - `graded_by`: BIGINT UNSIGNED NULLABLE (FK -> `users.id` ON DELETE SET NULL)
  - `graded_at`: TIMESTAMP NULLABLE
  - `created_at`, `updated_at`: TIMESTAMP

---

### 2.6 Certification, Reviews & Community

#### 21. `learning_certificates`
* **Purpose:** Verified completion certificates with cryptographic codes.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK -> `users.id` ON DELETE CASCADE)
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `certificate_code`: VARCHAR(60) NOT NULL UNIQUE
  - `issued_at`: TIMESTAMP NOT NULL
  - `pdf_url`: VARCHAR(500) NULLABLE
  - `qr_code_url`: VARCHAR(500) NULLABLE
  - `is_revoked`: BOOLEAN DEFAULT FALSE
  - `revoked_reason`: VARCHAR(255) NULLABLE
  - `created_at`, `updated_at`: TIMESTAMP
  - **Constraints:** `UNIQUE KEY unique_user_course_certificate (user_id, course_id)`

#### 22. `learning_course_reviews`
* **Purpose:** Ratings and written student feedback.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK -> `users.id` ON DELETE CASCADE)
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `rating`: TINYINT UNSIGNED NOT NULL
  - `review`: TEXT NULLABLE
  - `is_verified_purchase`: BOOLEAN DEFAULT TRUE
  - `is_approved`: BOOLEAN DEFAULT TRUE
  - `created_at`, `updated_at`: TIMESTAMP
  - **Constraints:** `UNIQUE KEY unique_user_course_review (user_id, course_id)`

#### 23. `learning_discussions`
* **Purpose:** Course Q&A and community threads.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `lesson_id`: BIGINT UNSIGNED NULLABLE (FK -> `learning_course_lessons.id` ON DELETE SET NULL)
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK -> `users.id` ON DELETE CASCADE)
  - `title`: VARCHAR(200) NOT NULL
  - `body`: TEXT NOT NULL
  - `replies_count`: INT UNSIGNED DEFAULT 0
  - `is_resolved`: BOOLEAN DEFAULT FALSE
  - `created_at`, `updated_at`: TIMESTAMP

#### 24. `learning_discussion_replies`
* **Purpose:** Replies to Q&A discussion threads.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `discussion_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_discussions.id` ON DELETE CASCADE)
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK -> `users.id` ON DELETE CASCADE)
  - `body`: TEXT NOT NULL
  - `is_instructor_reply`: BOOLEAN DEFAULT FALSE
  - `created_at`, `updated_at`: TIMESTAMP

#### 25. `learning_course_announcements`
* **Purpose:** Broadcast notices from instructors to enrolled students.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `instructor_id`: BIGINT UNSIGNED NOT NULL (FK -> `users.id` ON DELETE CASCADE)
  - `title`: VARCHAR(200) NOT NULL
  - `content`: TEXT NOT NULL
  - `created_at`, `updated_at`: TIMESTAMP

#### 26. `learning_course_coupons`
* **Purpose:** Discount codes applied at checkout.
* **Columns:**
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `course_id`: BIGINT UNSIGNED NOT NULL (FK -> `learning_courses.id` ON DELETE CASCADE)
  - `code`: VARCHAR(50) NOT NULL
  - `discount_type`: ENUM('percentage', 'fixed') DEFAULT 'percentage'
  - `discount_value`: DECIMAL(10, 2) NOT NULL
  - `max_uses`: INT UNSIGNED DEFAULT 0
  - `used_count`: INT UNSIGNED DEFAULT 0
  - `expires_at`: TIMESTAMP NULLABLE
  - `is_active`: BOOLEAN DEFAULT TRUE
  - `created_at`, `updated_at`: TIMESTAMP
  - **Constraints:** `UNIQUE KEY unique_course_coupon (course_id, code)`

