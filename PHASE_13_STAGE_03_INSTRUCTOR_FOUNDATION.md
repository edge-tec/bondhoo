# PHASE 13 — STAGE 03: INSTRUCTOR FOUNDATION & APPLICATION WORKFLOW AUDIT REPORT

**Project:** Connectly / JUGAJUG  
**Stage:** 03  
**Module:** Instructor Applications, Verification, and Profile Hub  

---

## 1. Application & Approval Lifecycle

1. **Instructor Application Submission (`POST /api/v1/learning/instructors/apply`):**
   * Authenticated professional user applies by providing headline, biography, expertise array, and portfolio links.
   * Creates record in `learning_instructor_applications` with `status = 'pending'`.
   * **Duplicate Guard:** If user already has an active pending application, the request is rejected with `422 Unprocessable Entity` ("You already have a pending instructor application under review.").
   * Initializes or updates `LearningInstructor` profile in unverified state (`is_verified = false`).

2. **Admin Verification & Approval (`POST /api/v1/learning/admin/instructors/{id}/verify`):**
   * Admin reviews application and triggers verification.
   * Updates `LearningInstructor` record: `is_verified = true`, `verified_at = now()`.
   * Automatically updates corresponding pending application to `status = 'approved'`, `reviewed_by = admin.id`, `reviewed_at = now()`.

3. **Instructor Self Profile (`GET /api/v1/learning/instructors/me`):**
   * Returns authenticated user's instructor stats: total courses, total students, average rating, verification status, and latest application status.

4. **Public Instructor Profile (`GET /api/v1/learning/instructors/{id}`):**
   * Returns public profile, bio, expertise, and published courses list for prospective students.

---

## 2. Test Verification Evidence

* Feature Test: `Phase13Stage03MarketplaceAndFoundationTest::test_instructor_application_flow_and_profile`
* Feature Test: `Phase13CompleteLmsSystemTest::test_instructor_application_and_admin_verification`
* Assertions: 16
* Status: **PASS (100%)**

