# PHASE 13: MIGRATION PLAN
**Platform:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform  
**System Module:** Professional Learning & Development Platform (LMS)  
**Date:** 2026-11-04  
**Author:** Lead Database Architect  

---

## 1. Migration Strategy & Ordering

To ensure zero downtime, strict referential integrity, and smooth forward and rollback execution, migrations are grouped logically by dependencies:

```
Step 1: 2026_11_04_160000_create_learning_categories_and_instructors_tables.php
        -> Creates learning_categories, learning_instructors

Step 2: 2026_11_04_161000_create_learning_courses_and_taxonomy_tables.php
        -> Creates learning_courses, learning_course_categories, learning_course_skills

Step 3: 2026_11_04_162000_create_learning_curriculum_and_lessons_tables.php
        -> Creates learning_course_sections, learning_course_lessons, learning_lesson_attachments

Step 4: 2026_11_04_163000_create_learning_enrollments_and_progress_tables.php
        -> Creates learning_enrollments, learning_lesson_progress, learning_course_progress

Step 5: 2026_11_04_164000_create_learning_quizzes_and_attempts_tables.php
        -> Creates learning_quizzes, learning_quiz_questions, learning_quiz_options, learning_quiz_attempts, learning_quiz_answers

Step 6: 2026_11_04_165000_create_learning_assignments_and_submissions_tables.php
        -> Creates learning_assignments, learning_assignment_submissions

Step 7: 2026_11_04_166000_create_learning_certificates_and_reviews_tables.php
        -> Creates learning_certificates, learning_course_reviews, learning_wishlists

Step 8: 2026_11_04_167000_create_learning_community_coupons_and_transactions_tables.php
        -> Creates learning_discussions, learning_discussion_replies, learning_course_announcements, learning_course_coupons, learning_transactions
```

---

## 2. Reversibility & Rollback Guarantees
- Every migration implements an inverse `down()` method that safely drops created foreign keys and tables in reverse dependency order.
- In production, migrations can be executed with:
  ```bash
  php artisan migrate --force
  ```
- And rolled back safely if required with:
  ```bash
  php artisan migrate:rollback --step=8
  ```

