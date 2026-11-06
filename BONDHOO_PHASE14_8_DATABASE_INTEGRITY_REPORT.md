# BONDHOO (বন্ধু) — PHASE 14.8: DATABASE INTEGRITY REPORT
**Target Database:** MySQL / SQLite (Testing)  
**Schema Scope:** Jobs, Recruitment, ATS, Interviews, Saved Jobs & Skills  
**Auditor:** Database Reliability Engineer & Principal Architect  
**Date:** November 2026  
**Status:** **SCHEMA VERIFIED, CONCURRENCY-PROTECTED & RELIABLE**

---

## 1. Database Entity-Relationship Diagram

```
+------------------+         1:N         +-------------------------+
|    companies     | <------------------ |       job_postings      |
|------------------|                     |-------------------------|
| id (PK)          |                     | id (PK)                 |
| owner_user_id(FK)|                     | user_id (FK)            |
| name, slug       |                     | company_id (FK)         |
| status           |                     | title, workplace_type   |
+--------+---------+                     | salary_min, salary_max  |
         |                               | status, vacancies       |
         | 1:N                           +------------+------------+
         v                                            |
+------------------+                                  | 1:N
| company_members  |                                  v
|------------------|                     +-------------------------+
| id (PK)          |                     |     job_applications    |
| company_id (FK)  |                     |-------------------------|
| user_id (FK)     |                     | id (PK)                 |
| role             |                     | job_posting_id (FK)     |
+------------------+                     | user_id (FK)            |
                                         | company_id (FK)         |
                                         | status, full_name, email|
                                         +------------+------------+
                                                      |
                                                      | 1:N
                                                      v
                                         +-------------------------+
                                         |        interviews       |
                                         |-------------------------|
                                         | id (PK)                 |
                                         | application_id (FK)     |
                                         | scheduled_by (FK)       |
                                         | status, scheduled_at    |
                                         | meeting_url, feedback   |
                                         +-------------------------+
```

---

## 2. Table Inspection & Schema Integrity

### 2.1 `job_postings`
- **Primary Key:** `id` (BigIncrements)
- **Foreign Keys:**
  - `user_id` $\to$ `users(id)` (onDelete cascade)
  - `company_id` $\to$ `companies(id)` (nullable, onDelete set null)
  - `category_id` $\to$ `job_categories(id)` (nullable, onDelete set null)
- **Indexes:** `status`, `workplace_type`, `employment_type`, `location`, `salary_min`, `salary_max`, `published_at`, `deadline`.
- **Soft Deletes:** `deleted_at` timestamp column active with `SoftDeletes` trait on Eloquent model.

### 2.2 `job_applications`
- **Primary Key:** `id` (BigIncrements)
- **Foreign Keys:**
  - `job_posting_id` $\to$ `job_postings(id)` (onDelete cascade)
  - `user_id` $\to$ `users(id)` (onDelete cascade)
- **Unique Constraint / Business Rule:** A candidate may only have one active application per job posting. Tested and enforced at API level via `ValidationException` (422) when querying existing `job_posting_id` + `user_id`.
- **Status Lifecycle:** `applied`, `under_review`, `shortlisted`, `screening`, `interview`, `interviewed`, `assessment`, `offer`, `hired`, `rejected`, `withdrawn`.

### 2.3 `interviews`
- **Primary Key:** `id` (BigIncrements)
- **Foreign Keys:**
  - `application_id` $\to$ `job_applications(id)` (onDelete cascade)
  - `company_id` $\to$ `companies(id)` (nullable, onDelete set null)
  - `scheduled_by` $\to$ `users(id)` (onDelete cascade)
- **Valid Status Values:** `scheduled`, `confirmed`, `rescheduled`, `completed`, `cancelled`, `declined`, `no_show`.
- **Data Casting:** `scheduled_at` $\to$ `datetime`, `duration_minutes` $\to$ `integer`.

### 2.4 `saved_jobs`
- **Primary Key:** `id` (BigIncrements)
- **Foreign Keys:**
  - `user_id` $\to$ `users(id)` (onDelete cascade)
  - `job_id` $\to$ `job_postings(id)` (onDelete cascade)
- **Uniqueness:** User can only save a specific job once; subsequent saves toggle off (delete) or prevent duplicates.

### 2.5 `job_skills` & `job_posting_skills` (Pivot)
- Normalized many-to-many relationship linking skills (`PHP`, `Kotlin`, `Laravel`, `AWS`, etc.) to `job_postings`.
- Automatic slug generation and deduplication on job publish.

---

## 3. Concurrency Locking & Transaction Integrity

To avoid race conditions and database corruption during simultaneous ATS actions:
1. **Row-Level Locking on Applications:**
   ```php
   $lockedApp = JobApplication::where('id', $application->id)
       ->lockForUpdate()
       ->firstOrFail();
   ```
2. **Duplicate Active Interview Guard:**
   ```php
   $hasActiveInterview = Interview::where('application_id', $application->id)
       ->whereIn('status', [
           Interview::STATUS_SCHEDULED,
           'pending',
           Interview::STATUS_CONFIRMED,
           Interview::STATUS_RESCHEDULED
       ])
       ->exists();
   if ($hasActiveInterview) {
       throw ValidationException::withMessages(['interview' => ['An active interview is already scheduled.']]);
   }
   ```
3. **Atomic Transactions:**
   All multi-step database updates (e.g. creating interview, updating application stage, recording stage history log) are wrapped in `DB::transaction()`. If any step fails, the entire transaction rolls back cleanly without leaving orphan records.

---

## 4. Verification Summary

Database constraints, soft deletes, foreign key cascades, and transactional rollbacks were verified across both SQLite in-memory test suites and live MySQL database execution. Zero data corruption or deadlocks occurred.

