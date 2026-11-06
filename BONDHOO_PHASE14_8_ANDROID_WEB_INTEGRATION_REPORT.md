# BONDHOO (বন্ধু) — PHASE 14.8: ANDROID & WEB INTEGRATION REPORT
**Platforms:** Android (Kotlin, Jetpack Compose, Retrofit) & Web Frontend (Laravel Blade, Alpine.js, Axios)  
**Target Module:** Jobs & Recruitment Portal  
**Date:** November 2026  
**Integration Status:** **100% PARITY & SEAMLESS CLIENT-SERVER SYNCHRONIZATION**

---

## 1. Cross-Platform Architectural Parity

Both the Android native client and the Web dashboard communicate with the identical set of REST API endpoints on the Laravel backend, ensuring consistent behavior, validation error messages, and real-time state synchronization.

```
                             +------------------------+
                             |   Laravel REST APIs    |
                             +-----------+------------+
                                         |
               +-------------------------+-------------------------+
               |                                                   |
               v                                                   v
+-------------------------------+               +-----------------------------------+
|      Android Jetpack Compose  |               |       Web Client (Blade/Alpine)   |
+-------------------------------+               +-----------------------------------+
| - JobsHomeScreen.kt           |               | - resources/views/dashboard.blade |
|   * Jobs Tab                  |               |   * #jobsModal (Job Discovery)    |
|   * Applied Tab               |               |   * loadJobs()                    |
|   * Saved Tab                 |               |   * loadMyJobApplications()       |
|   * Interviews Tab            |               |   * submitJobApplication()        |
|   * Employer Tab              |               |   * submitCreateJob()             |
| - CreateJobDialog             |               |   * Filter pills & search input   |
| - ScheduleInterviewDialog     |               +-----------------------------------+
+-------------------------------+
```

---

## 2. API Endpoint Matrix Consumed by Android & Web

| Feature | HTTP Method & Route | Android Component | Web Component | Status |
| :--- | :--- | :--- | :--- | :---: |
| **Browse Jobs** | `GET /api/v1/jobs` | `JobRepositoryImpl.getJobs()` | `loadJobs()` in `dashboard.blade.php` | **Verified** |
| **Job Details** | `GET /api/v1/jobs/{id}` | `JobRepositoryImpl.getJobDetails()` | Modal details view | **Verified** |
| **Apply for Job** | `POST /api/v1/jobs/{id}/apply` | `JobRepositoryImpl.applyForJob()` | `submitJobApplication()` | **Verified** |
| **My Applications** | `GET /api/v1/candidate/applications` | `JobRepositoryImpl.getMyApplications()` | `loadMyJobApplications()` | **Verified** |
| **Withdraw Application** | `POST /api/v1/applications/{id}/withdraw` | `JobRepositoryImpl.withdrawApplication()` | Candidate action button | **Verified** |
| **Save / Bookmark Job** | `POST /api/v1/jobs/{id}/save` | `JobRepositoryImpl.saveJob()` | Bookmark icon toggle | **Verified** |
| **Unsave Job** | `DELETE /api/v1/jobs/{id}/save` | `JobRepositoryImpl.unsaveJob()` | Bookmark icon toggle | **Verified** |
| **My Saved Jobs** | `GET /api/v1/candidate/saved-jobs` | `JobRepositoryImpl.getSavedJobs()` | Saved Jobs tab in modal | **Verified** |
| **My Interviews** | `GET /api/v1/interviews` | `JobRepositoryImpl.getInterviews()` | Interviews section | **Verified** |
| **Respond to Interview** | `POST /api/v1/interviews/{id}/response` | `JobRepositoryImpl.respondToInterview()` | "Accept" / "Decline" buttons | **Verified** |
| **Schedule Interview** | `POST /api/v1/applications/{id}/interviews`| `JobRepositoryImpl.scheduleInterview()` | Recruiter ATS modal | **Verified** |
| **Update ATS Stage** | `PATCH /api/v1/applications/{id}/stage` | `JobRepositoryImpl.updateApplicationStage()`| Candidate pipeline dropdown | **Verified** |
| **Create Job Posting** | `POST /api/v1/jobs` | `JobRepositoryImpl.createJob()` | `submitCreateJob()` modal form | **Verified** |

---

## 3. Android UI/UX Implementation Details

### 3.1 Five-Tab Experience (`ScrollableTabRow`)
In `JobsHomeScreen.kt`:
1. **Jobs (Discovery):** Search bar with live query debounce, workplace type chips (`All Jobs`, `Remote`, `Hybrid`, `Onsite`), salary and location filters, card elevation, and Apply Now sheet.
2. **Applied (Tracking):** Chronological history of user submissions, status chips (`Applied`, `Reviewing`, `Interview`, `Hired`), and instantaneous withdrawal button.
3. **Saved (Bookmarks):** Quick-access bookmarked opportunities with 1-tap unsave and direct apply actions.
4. **Interviews (Direct Action):** Detailed cards showing scheduled date/time, format (Video/Phone/In-Person), direct Google Meet link launcher (`Intent.ACTION_VIEW`), and responsive "Accept & Confirm" / "Decline" buttons.
5. **Employer (ATS Pipeline):** Real-time candidate stage advancement (`Review`, `Shortlist`, `Interview`, `Offer`, `Hire`, `Reject`) and direct `Schedule Interview` button opening the new modal dialog.

### 3.2 Dialog Components Added
- **`CreateJobDialog`:** Company selection chips (from user's managed companies), title, workplace type, employment type, experience level (`Entry`, `Mid`, `Senior`, `Lead`), salary min/max validation, vacancies, and comma-separated skill tagging.
- **`ScheduleInterviewDialog`:** Candidate context, format chips (`Video Call`, `Phone`, `In-Person`), scheduled datetime picker, meeting URL input, location, and interviewer instructions.

### 3.3 Retrofit Type Safety Fix
- **Problem:** Parameter wildcard `Map<String, ?>` caused runtime crash on Android during `applyForJob`.
- **Solution:** Replaced with explicit typed data class `JobApplicationRequest` in `ConnectlyApi.kt`:
```kotlin
@POST("api/v1/jobs/{id}/apply")
suspend fun applyForJob(
    @Path("id") jobId: Long,
    @Body request: JobApplicationRequest
): Response<ApiResponse<JobApplicationDto>>
```

---

## 4. Web Frontend Integration Details

In `resources/views/dashboard.blade.php`:
- The `#jobsModal` provides an identical responsive interface on desktop and mobile browsers.
- `loadJobs()` dynamically fetches `/api/v1/jobs` with workplace and category query parameters.
- `submitJobApplication()` handles multipart resume uploads and sends expected salary and cover letter to `POST /api/v1/jobs/{id}/apply`.
- Validation errors (422) are cleanly rendered in inline alert dismissible containers without page reloads.

---

## 5. Verification & Testing

Both Android unit tests (`:feature:jobs:testDebugUnitTest`) and full application debug builds (`:app:assembleDebug`) passed with 100% success.
Live verification on Android Emulator (`emulator-5554`) confirmed flawless responsiveness, seamless tab switching, successful job application submissions, interview confirmations, and job postings.

