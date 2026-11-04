# PHASE 13 — STAGE 03: MODEL RELATIONSHIPS AUDIT REPORT

**Project:** Connectly / JUGAJUG  
**Stage:** 03  
**Architecture:** Eloquent ORM + Strictly Typed PHP 8.5 Models  

---

## 1. Domain Model Hierarchy & Relationships

### 1.1 Course Ecosystem
* `LearningCourse`:
  * `belongsTo(User::class, 'instructor_id')` -> Maps to instructor user.
  * `belongsTo(LearningCategory::class, 'category_id')` -> Primary category.
  * `hasMany(LearningCourseSection::class, 'course_id')` -> Ordered sections.
  * `hasMany(LearningCourseLesson::class, 'course_id')` -> Direct access to course lessons.
  * `hasMany(LearningEnrollment::class, 'course_id')` -> Student enrollments.
  * `hasMany(LearningCourseReview::class, 'course_id')` -> Student ratings & reviews.
  * `belongsToMany(JobSkill::class, 'learning_course_skills', 'course_id', 'skill_id')` -> Skill ontology reuse.

### 1.2 Curriculum Tree
* `LearningCourseSection`:
  * `belongsTo(LearningCourse::class, 'course_id')`
  * `hasMany(LearningCourseLesson::class, 'section_id')->orderBy('sort_order')`
* `LearningCourseLesson`:
  * `belongsTo(LearningCourseSection::class, 'section_id')`
  * `belongsTo(LearningCourse::class, 'course_id')`
  * `hasMany(LearningLessonAttachment::class, 'lesson_id')`
  * `hasOne(LearningQuiz::class, 'lesson_id')`
  * `hasMany(LearningLessonProgress::class, 'lesson_id')`

### 1.3 Instructor & Applications
* `LearningInstructor`:
  * `belongsTo(User::class, 'user_id')`
  * `hasMany(LearningCourse::class, 'instructor_id', 'user_id')`
* `LearningInstructorApplication`:
  * `belongsTo(User::class, 'user_id')`
  * `belongsTo(User::class, 'reviewed_by')`
  * `scopePending()`, `scopeApproved()`, `scopeRejected()`

### 1.4 Student Enrollment & Progress
* `LearningEnrollment`:
  * `belongsTo(LearningCourse::class, 'course_id')`
  * `belongsTo(User::class, 'user_id')`
  * `hasOne(LearningCourseProgress::class, 'enrollment_id')`
  * `hasMany(LearningLessonProgress::class, 'enrollment_id')`
  * `hasOne(LearningCertificate::class, 'enrollment_id')`

---

## 2. Integrity Verification

All models maintain constructor property promotion, strict return types (`BelongsTo`, `HasMany`, `HasOne`, `BelongsToMany`), and `$casts` configurations matching database schema requirements.

