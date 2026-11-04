# Phase 13 — Stage 02: LMS Database Indexing & Performance Plan
**Project:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform  
**Stage:** 02 — LMS Database, Backend Architecture & API Foundation  
**Date:** November 2026  
**Author:** Principal Software Architect & Database Performance Engineer  

---

## 1. Indexing Strategy & Principles

To guarantee sub-50ms API responses under production load, indexes are designed for:
1. **High-Frequency Lookups:** Primary keys and unique slugs (`learning_courses.slug`, `learning_categories.slug`).
2. **Filtering & Sorting Combinations:** Composite indexes matching common WHERE and ORDER BY clauses (e.g. `status` + `is_featured` + `created_at`).
3. **Foreign Key Joins:** Explicit indexes on every foreign key to prevent full-table scans during relationship eager loading.
4. **Idempotence & Concurrency Defense:** Unique compound indexes preventing duplicate records (e.g. enrollments, reviews, wishlists).

---

## 2. Table-by-Table Index Plan

| Table Name | Index Name | Columns Indexed | Type | Targeted Query Pattern |
|---|---|---|---|---|
| `learning_categories` | `PRIMARY` | `id` | BTREE (PK) | Direct category lookups |
| `learning_categories` | `categories_slug_unique` | `slug` | UNIQUE | Category retrieval by slug |
| `learning_categories` | `categories_parent_idx` | `parent_id` | INDEX | Category tree / subcategory queries |
| `learning_courses` | `PRIMARY` | `id` | BTREE (PK) | Direct course lookups |
| `learning_courses` | `courses_slug_unique` | `slug` | UNIQUE | Course landing page by slug |
| `learning_courses` | `courses_instructor_idx` | `instructor_id` | INDEX | Instructor dashboard / author course lists |
| `learning_courses` | `courses_category_status_idx`| `category_id`, `status` | INDEX | Category catalog filtering |
| `learning_courses` | `courses_status_featured_idx`| `status`, `is_featured`, `created_at` | INDEX | Homepage featured & popular course lists |
| `learning_courses` | `courses_rating_idx` | `status`, `rating_avg` | INDEX | Highest rated course sort |
| `learning_courses` | `courses_enrollments_idx` | `status`, `enrollments_count` | INDEX | Most popular / bestselling course sort |
| `learning_course_skills`| `unique_course_skill` | `course_id`, `job_skill_id` | UNIQUE | Prevents duplicate skill tags; fast skill joins |
| `learning_course_sections`| `sections_course_sort_idx`| `course_id`, `sort_order` | INDEX | Deterministic syllabus ordering |
| `learning_course_lessons`| `lessons_section_sort_idx`| `section_id`, `sort_order`| INDEX | Ordered lessons within section |
| `learning_course_lessons`| `lessons_course_type_idx` | `course_id`, `type` | INDEX | Counting lessons by type |
| `learning_enrollments` | `unique_user_course` | `user_id`, `course_id` | UNIQUE | Prevents double-enrollment race conditions |
| `learning_enrollments` | `enrollments_user_status_idx`| `user_id`, `status` | INDEX | "My Learning" active & completed courses |
| `learning_enrollments` | `enrollments_course_idx` | `course_id`, `status` | INDEX | Instructor course student rosters |
| `learning_lesson_progress`| `unique_user_lesson` | `user_id`, `lesson_id` | UNIQUE | Safe idempotent progress synchronization |
| `learning_lesson_progress`| `progress_user_course_idx`| `user_id`, `course_id` | INDEX | Calculating course completion percentage |
| `learning_wishlists` | `unique_user_wishlist` | `user_id`, `course_id` | UNIQUE | Prevents duplicate wishlist items |
| `learning_quiz_attempts`| `attempts_quiz_user_idx` | `quiz_id`, `user_id` | INDEX | User quiz attempt history & pass verification |
| `learning_assignment_submissions`| `submissions_assignment_user_idx` | `assignment_id`, `user_id` | INDEX | Student assignment submission retrieval |
| `learning_certificates`| `certificates_code_unique` | `certificate_code` | UNIQUE | Public verification endpoint lookup |
| `learning_certificates`| `unique_user_course_cert` | `user_id`, `course_id` | UNIQUE | Guarantees one certificate per student/course |
| `learning_course_reviews`| `unique_user_course_review`| `user_id`, `course_id` | UNIQUE | Prevents multiple reviews by same student |
| `learning_course_reviews`| `reviews_course_approved_idx`| `course_id`, `is_approved` | INDEX | Approved reviews list for course landing page |
| `learning_discussions` | `discussions_course_idx` | `course_id`, `created_at` | INDEX | Course Q&A forum feed |
| `learning_course_coupons`| `unique_course_coupon` | `course_id`, `code` | UNIQUE | Fast checkout coupon lookup |

---

## 3. Query Optimization Guidelines

1. **Eager Loading Invariants:**
   - Course listings always eager load `category:id,name,slug`, `instructor:id,name,username,avatar_url`, and `skills:id,name,slug`.
   - Syllabus queries eager load `sections.lessons.attachments`.
2. **Denormalized Counters:**
   - `courses_count` on `learning_categories`, `total_sections` and `total_lessons` on `learning_courses`, `enrollments_count` and `rating_avg` on `learning_courses` are updated atomically on mutations to prevent heavy `COUNT(*)` queries on list endpoints.
3. **Partitioning & Archive Policy:**
   - High-volume playback tracking records in `learning_lesson_progress` update in-place rather than inserting continuous append-only logs.

