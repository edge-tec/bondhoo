# PHASE 12: IMPLEMENTATION PLAN & ROADMAP
**Platform:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform
**Phase:** 12 – Professional Job Portal & Recruitment Management System
**Date:** 2026-11-04

---

## 1. Objectives & Architectural Principles

1. Build a production-grade Job Portal & Applicant Tracking System (ATS) integrated natively into the Connectly / JUGAJUG platform.
2. Implement robust backend models, migrations, policies, form requests, and services.
3. Build complete Android UI, ViewModels, repository, and use cases adhering to Clean Architecture and Material 3.
4. Enforce strict authorization: Candidates cannot view other candidates' private resumes, applications, or recruiter internal notes; Recruiters cannot access other companies' candidates.
5. Provide 100% test coverage with real SQLite in-memory database assertions and zero mocked business logic.

---

## 2. Step-by-Step Implementation Sequence

### Step 1: Architecture Planning & Documentation (COMPLETED)
- Create baseline audit, implementation plan, database design, and API design documents.

### Step 2: Database Schema & Migrations
- Migration 1: Create `companies`, `company_members`, `company_verifications` tables.
- Migration 2: Create `job_categories`, `job_skills`, `job_job_skill` pivot tables.
- Migration 3: Extend `job_postings` with `company_id`, `category_id`, `slug`, `responsibilities`, `qualifications`, `workplace_type`, `vacancies`, `visibility`, `application_method`, `external_application_url`, `published_at`, `closed_at`.
- Migration 4: Create `candidate_profiles`, `candidate_resumes`, `candidate_certifications` tables.
- Migration 5: Extend `job_applications` with `company_id`, `resume_id`, `answers`, `source`, `applied_at`, `withdrawn_at`.
- Migration 6: Create `recruitment_stages`, `application_stage_history`, `application_notes`, `application_questions` tables.
- Migration 7: Create `interviews`, `saved_jobs`, `job_alerts`, `job_views` tables.

### Step 3: Backend Models, Relationships & Policies
- Models: `Company`, `CompanyMember`, `CompanyVerification`, `JobCategory`, `JobSkill`, `CandidateProfile`, `CandidateResume`, `CandidateCertification`, `RecruitmentStage`, `ApplicationStageHistory`, `ApplicationNote`, `ApplicationQuestion`, `Interview`, `SavedJob`, `JobAlert`, `JobView`.
- Update `JobPosting` and `JobApplication` with new relationships and helper methods.
- Policies: `CompanyPolicy`, `JobPostingPolicy`, `JobApplicationPolicy`, `InterviewPolicy`, `CandidateResumePolicy`.

### Step 4: Backend Services & Business Logic
- `CompanyService`: Company creation, member invites, RBAC (Owner, Admin, Recruiter, Hiring Manager, Viewer).
- `JobService`: Wizard validation, draft, publish, pause, duplicate, view tracking.
- `CandidateService`: Profile setup, secure resume upload (PDF/DOCX validation, private storage), experience/education/certifications sync.
- `ApplicationService`: Apply validation, duplicate prevention, withdrawal, stage transitions, recruiter notes, interview scheduling.
- `JobAlertService`: Job alert creation, matching job search, notifications.
- `JobAnalyticsService`: Employer dashboard statistics, conversion funnel, applicant pipeline aggregation.

### Step 5: API Form Requests & Controllers
- `CompanyApiController`: Company CRUD, members management, verification requests, public career page.
- `JobPortalApiController`: Advanced job search, details, wizard creation, publish/pause/duplicate, applicant list, analytics.
- `CandidateApiController`: Candidate profile, resumes (upload, default, delete, download), application history, saved jobs, alerts.
- `AtsApiController`: Kanban pipeline board, stage updates, private notes, candidate assignment.
- `InterviewApiController`: Schedule, reschedule, cancel, candidate response.
- `AdminJobPortalApiController`: Admin moderation, company verification review, categories & skills management.

### Step 6: Android Data & Domain Layer
- DTOs in `core:model` or `:feature:jobs`: `JobDto`, `CompanyDto`, `JobApplicationDto`, `CandidateProfileDto`, `InterviewDto`, `JobAlertDto`, `AtsPipelineDto`.
- Retrofit endpoints in `JobApiService.kt` / `ConnectlyApi.kt`.
- Domain models and repository interfaces: `JobRepository.kt`, `CompanyRepository.kt`.
- UseCases: `SearchJobsUseCase`, `GetJobDetailsUseCase`, `ApplyForJobUseCase`, `GetSavedJobsUseCase`, `SaveJobUseCase`, `GetCandidateProfileUseCase`, `UploadResumeUseCase`, `GetAtsPipelineUseCase`, `UpdateApplicationStageUseCase`, `ScheduleInterviewUseCase`.

### Step 7: Android Presentation Layer (Compose Screens)
1. `JobsHomeScreen.kt`: Hero search, recommended jobs, top companies, recent searches.
2. `JobSearchScreen.kt`: Filter drawer (category, workplace type, salary, experience, date), search-as-you-type.
3. `JobDetailsScreen.kt`: Rich job specs, company info, apply CTA, save button.
4. `JobApplicationScreen.kt`: Resume picker / upload, cover letter, employer questions.
5. `MyApplicationsScreen.kt`: Status pills (Applied, Shortlisted, Interview, Offer, Hired, Rejected).
6. `SavedJobsScreen.kt`: Bookmarked jobs with unsave & apply actions.
7. `CandidateProfileScreen.kt`: Headline, summary, experience, education, certifications, resumes.
8. `EmployerDashboardScreen.kt`: Metrics (Total Jobs, Applicants, Interviews, Offers), active jobs, pipeline summary.
9. `RecruitmentPipelineScreen.kt`: Drag-and-drop / stage move Kanban board for candidates.
10. `InterviewManagementScreen.kt`: Upcoming interviews, meeting details, reschedule/cancel.
11. `CompanyCareerPageScreen.kt`: Company overview, open positions, follow action.
12. `CreateJobScreen.kt`: Multi-step job creation wizard.

### Step 8: Android Navigation Integration
- Add Jobs to `MenuScreen.kt` as a first-class service card.
- Provide routes in `JobsNavigation.kt` and wire deep links.

### Step 9: Verification, Security Audit & Release Gate
- Unit & Feature tests in Laravel (`Phase12CompleteJobPortalAndRecruitmentTest.php`).
- Unit tests in Android (`JobsViewModelTest.kt`, `JobRepositoryTest.kt`).
- Lint checks with `vendor/bin/pint`.
- Gradle regression test and APK build (`assembleDebug`, `assembleRelease`).
- Produce all Phase 12 completion deliverables.

