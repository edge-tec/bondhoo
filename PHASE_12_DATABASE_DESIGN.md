# PHASE 12: DATABASE DESIGN & SCHEMA ARCHITECTURE
**Platform:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform
**Date:** 2026-11-04

---

## 1. Overview & Entity-Relationship Schema

The recruitment database is normalized, scalable, and fully backward-compatible with existing tables (`job_postings`, `job_applications`, `profile_experiences`, `profile_educations`, `profile_skills`).

```
+--------------------+        +---------------------+        +--------------------+
|     companies      | <----- |   company_members   | -----> |       users        |
+--------------------+        +---------------------+        +--------------------+
          |
          | 1:N
          v
+--------------------+        +---------------------+        +--------------------+
|    job_postings    | -----> | job_job_skill pivot | <----- |     job_skills     |
+--------------------+        +---------------------+        +--------------------+
    |            |
    | 1:N        | 1:N
    v            v
+--------------------+        +---------------------+        +--------------------+
|  job_applications  | -----> |  recruitment_stages |        |   job_categories   |
+--------------------+        +---------------------+        +--------------------+
    |            |
    | 1:N        | 1:N
    v            v
+--------------------+        +---------------------+        +--------------------+
| application_notes  |        |     interviews      |        | candidate_resumes  |
+--------------------+        +---------------------+        +--------------------+
```

---

## 2. Table Specifications

### 2.1 `companies`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `owner_user_id` (BIGINT UNSIGNED, Foreign Key to `users.id`, Cascade)
- `name` (VARCHAR 200, Indexed)
- `slug` (VARCHAR 220, Unique Index)
- `logo` (VARCHAR 255, Nullable)
- `cover_image` (VARCHAR 255, Nullable)
- `description` (TEXT, Nullable)
- `industry` (VARCHAR 100, Nullable, Indexed)
- `company_size` (VARCHAR 50, Nullable) // e.g. "1-10", "11-50", "51-200", "201-500", "500+"
- `founded_year` (SMALLINT UNSIGNED, Nullable)
- `website` (VARCHAR 255, Nullable)
- `email` (VARCHAR 150, Nullable)
- `phone` (VARCHAR 50, Nullable)
- `headquarters` (VARCHAR 150, Nullable, Indexed)
- `locations` (JSON, Nullable) // list of branch office locations
- `verification_status` (VARCHAR 30, Default 'unverified', Indexed) // unverified, pending, verified, rejected
- `visibility` (VARCHAR 30, Default 'public', Indexed) // public, unlisted, private
- `created_at`, `updated_at` (TIMESTAMPS), `deleted_at` (SOFT DELETES)

### 2.2 `company_members`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `company_id` (BIGINT UNSIGNED, Foreign Key to `companies.id`, Cascade)
- `user_id` (BIGINT UNSIGNED, Foreign Key to `users.id`, Cascade)
- `role` (VARCHAR 30, Default 'viewer', Indexed) // owner, admin, recruiter, hiring_manager, viewer
- `permissions` (JSON, Nullable) // granular overrides
- `invitation_status` (VARCHAR 30, Default 'accepted', Indexed) // pending, accepted, declined
- `invited_by` (BIGINT UNSIGNED, Nullable, Foreign Key to `users.id`, Set Null)
- `joined_at` (TIMESTAMP, Nullable)
- `created_at`, `updated_at` (TIMESTAMPS)
- UNIQUE constraint on (`company_id`, `user_id`)

### 2.3 `job_categories`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `parent_id` (BIGINT UNSIGNED, Nullable, Foreign Key to `job_categories.id`, Cascade)
- `name` (VARCHAR 150, Indexed)
- `slug` (VARCHAR 170, Unique Index)
- `description` (TEXT, Nullable)
- `status` (VARCHAR 30, Default 'active', Indexed) // active, inactive
- `created_at`, `updated_at` (TIMESTAMPS)

### 2.4 `job_skills`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `name` (VARCHAR 100, Unique Index)
- `slug` (VARCHAR 120, Unique Index)
- `created_at`, `updated_at` (TIMESTAMPS)

### 2.5 `job_job_skill` (Pivot)
- `job_posting_id` (BIGINT UNSIGNED, Foreign Key to `job_postings.id`, Cascade)
- `job_skill_id` (BIGINT UNSIGNED, Foreign Key to `job_skills.id`, Cascade)
- Primary Key on (`job_posting_id`, `job_skill_id`)

### 2.6 Extended `job_postings` Columns
- `company_id` (BIGINT UNSIGNED, Nullable, Foreign Key to `companies.id`, Null On Delete, Indexed)
- `category_id` (BIGINT UNSIGNED, Nullable, Foreign Key to `job_categories.id`, Null On Delete, Indexed)
- `slug` (VARCHAR 250, Nullable, Index)
- `responsibilities` (TEXT, Nullable)
- `qualifications` (TEXT, Nullable)
- `workplace_type` (VARCHAR 30, Default 'onsite', Indexed) // onsite, remote, hybrid
- `salary_visibility` (VARCHAR 30, Default 'visible', Indexed) // visible, hidden, range_only
- `country` (VARCHAR 100, Nullable, Indexed)
- `city` (VARCHAR 100, Nullable, Indexed)
- `vacancies` (SMALLINT UNSIGNED, Default 1)
- `visibility` (VARCHAR 30, Default 'public', Indexed) // public, unlisted, internal
- `application_method` (VARCHAR 30, Default 'in_app', Indexed) // in_app, external_url, email
- `external_application_url` (VARCHAR 255, Nullable)
- `published_at` (TIMESTAMP, Nullable, Indexed)
- `closed_at` (TIMESTAMP, Nullable)

### 2.7 `candidate_profiles`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `user_id` (BIGINT UNSIGNED, Foreign Key to `users.id`, Cascade, Unique)
- `professional_title` (VARCHAR 150, Nullable, Indexed)
- `professional_summary` (TEXT, Nullable)
- `experience_years` (TINYINT UNSIGNED, Default 0)
- `current_company` (VARCHAR 150, Nullable)
- `current_position` (VARCHAR 150, Nullable)
- `expected_salary` (UNSIGNED INT, Nullable)
- `salary_currency` (VARCHAR 10, Default 'BDT')
- `preferred_employment_types` (JSON, Nullable)
- `preferred_workplace_types` (JSON, Nullable)
- `preferred_locations` (JSON, Nullable)
- `availability_status` (VARCHAR 30, Default 'open_to_work', Indexed) // actively_looking, open_to_work, not_looking
- `profile_visibility` (VARCHAR 30, Default 'public', Indexed) // public, recruiters_only, private
- `created_at`, `updated_at` (TIMESTAMPS)

### 2.8 `candidate_resumes`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `user_id` (BIGINT UNSIGNED, Foreign Key to `users.id`, Cascade, Indexed)
- `title` (VARCHAR 150)
- `file_path` (VARCHAR 255)
- `original_filename` (VARCHAR 255)
- `mime_type` (VARCHAR 100)
- `file_size` (UNSIGNED INT) // in bytes
- `is_default` (BOOLEAN, Default false, Indexed)
- `visibility` (VARCHAR 30, Default 'recruiters_only', Indexed) // public, recruiters_only, private
- `created_at`, `updated_at` (TIMESTAMPS), `deleted_at` (SOFT DELETES)

### 2.9 `candidate_certifications`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `user_id` (BIGINT UNSIGNED, Foreign Key to `users.id`, Cascade, Indexed)
- `title` (VARCHAR 200)
- `issuing_organization` (VARCHAR 200)
- `issue_date` (DATE, Nullable)
- `expiry_date` (DATE, Nullable)
- `credential_id` (VARCHAR 100, Nullable)
- `credential_url` (VARCHAR 255, Nullable)
- `created_at`, `updated_at` (TIMESTAMPS)

### 2.10 Extended `job_applications` Columns
- `company_id` (BIGINT UNSIGNED, Nullable, Foreign Key to `companies.id`, Null On Delete, Indexed)
- `resume_id` (BIGINT UNSIGNED, Nullable, Foreign Key to `candidate_resumes.id`, Null On Delete, Indexed)
- `answers` (JSON, Nullable)
- `source` (VARCHAR 50, Default 'direct', Indexed) // direct, linkedin, referral, search
- `applied_at` (TIMESTAMP, Nullable, Indexed)
- `withdrawn_at` (TIMESTAMP, Nullable)

### 2.11 `application_stage_history`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `application_id` (BIGINT UNSIGNED, Foreign Key to `job_applications.id`, Cascade, Indexed)
- `stage` (VARCHAR 30, Indexed) // applied, under_review, shortlisted, screening, interview, assessment, offer, hired, rejected, withdrawn
- `previous_stage` (VARCHAR 30, Nullable)
- `actor_user_id` (BIGINT UNSIGNED, Nullable, Foreign Key to `users.id`, Set Null)
- `notes` (TEXT, Nullable)
- `created_at` (TIMESTAMP)

### 2.12 `application_notes`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `application_id` (BIGINT UNSIGNED, Foreign Key to `job_applications.id`, Cascade, Indexed)
- `author_user_id` (BIGINT UNSIGNED, Foreign Key to `users.id`, Cascade, Indexed)
- `note` (TEXT)
- `visibility` (VARCHAR 30, Default 'internal') // internal (company members only)
- `created_at`, `updated_at` (TIMESTAMPS)

### 2.13 `interviews`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `application_id` (BIGINT UNSIGNED, Foreign Key to `job_applications.id`, Cascade, Indexed)
- `company_id` (BIGINT UNSIGNED, Foreign Key to `companies.id`, Cascade, Indexed)
- `scheduled_by` (BIGINT UNSIGNED, Foreign Key to `users.id`, Cascade)
- `interview_type` (VARCHAR 30, Default 'video', Indexed) // video, phone, in_person
- `scheduled_at` (TIMESTAMP, Indexed)
- `duration_minutes` (SMALLINT UNSIGNED, Default 45)
- `timezone` (VARCHAR 50, Default 'Asia/Dhaka')
- `meeting_url` (VARCHAR 255, Nullable)
- `location` (VARCHAR 255, Nullable)
- `instructions` (TEXT, Nullable)
- `status` (VARCHAR 30, Default 'scheduled', Indexed) // scheduled, confirmed, rescheduled, completed, cancelled, no_show
- `feedback` (TEXT, Nullable)
- `created_at`, `updated_at` (TIMESTAMPS)

### 2.14 `saved_jobs`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `user_id` (BIGINT UNSIGNED, Foreign Key to `users.id`, Cascade, Indexed)
- `job_id` (BIGINT UNSIGNED, Foreign Key to `job_postings.id`, Cascade, Indexed)
- `created_at`, `updated_at` (TIMESTAMPS)
- UNIQUE constraint on (`user_id`, `job_id`)

### 2.15 `job_alerts`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `user_id` (BIGINT UNSIGNED, Foreign Key to `users.id`, Cascade, Indexed)
- `title` (VARCHAR 150)
- `keywords` (VARCHAR 255, Nullable)
- `category_id` (BIGINT UNSIGNED, Nullable, Foreign Key to `job_categories.id`, Cascade)
- `locations` (JSON, Nullable)
- `employment_types` (JSON, Nullable)
- `workplace_types` (JSON, Nullable)
- `experience_level` (VARCHAR 30, Nullable)
- `frequency` (VARCHAR 20, Default 'daily') // daily, weekly, instant
- `is_active` (BOOLEAN, Default true, Indexed)
- `last_notified_at` (TIMESTAMP, Nullable)
- `created_at`, `updated_at` (TIMESTAMPS)

### 2.16 `job_views`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `job_id` (BIGINT UNSIGNED, Foreign Key to `job_postings.id`, Cascade, Indexed)
- `viewer_user_id` (BIGINT UNSIGNED, Nullable, Foreign Key to `users.id`, Set Null, Indexed)
- `ip_address` (VARCHAR 45, Nullable)
- `source` (VARCHAR 50, Default 'direct')
- `viewed_at` (TIMESTAMP, Indexed)

### 2.17 `company_verifications`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `company_id` (BIGINT UNSIGNED, Foreign Key to `companies.id`, Cascade, Indexed)
- `user_id` (BIGINT UNSIGNED, Foreign Key to `users.id`, Cascade)
- `documents` (JSON, Nullable)
- `status` (VARCHAR 30, Default 'pending', Indexed) // pending, approved, rejected, more_info_required
- `admin_notes` (TEXT, Nullable)
- `reviewed_by` (BIGINT UNSIGNED, Nullable, Foreign Key to `users.id`, Set Null)
- `reviewed_at` (TIMESTAMP, Nullable)
- `created_at`, `updated_at` (TIMESTAMPS)

### 2.18 `application_questions`
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `job_id` (BIGINT UNSIGNED, Foreign Key to `job_postings.id`, Cascade, Indexed)
- `question` (TEXT)
- `type` (VARCHAR 30, Default 'short_answer') // short_answer, long_answer, yes_no, single_choice, multiple_choice
- `options` (JSON, Nullable)
- `is_required` (BOOLEAN, Default false)
- `display_order` (SMALLINT UNSIGNED, Default 0)
- `created_at`, `updated_at` (TIMESTAMPS)

