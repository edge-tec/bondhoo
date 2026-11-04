# PHASE 12: API SPECIFICATION & CONTRACT DESIGN
**Platform:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform
**Base URL:** `/api/v1`
**Authentication:** `Bearer <sanctum_token>`
**Content-Type:** `application/json`

---

## 1. Company Management Endpoints

| Method | Endpoint | Description | Auth & Permission |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/companies` | List user's companies or browse directory | Authenticated / Public filter |
| `POST` | `/api/v1/companies` | Create a new company profile | Authenticated User |
| `GET` | `/api/v1/companies/{company}` | Get company profile details | Authenticated / Public |
| `PUT` | `/api/v1/companies/{company}` | Update company details | Owner / Admin |
| `GET` | `/api/v1/companies/{company}/career-page` | Public career page with open jobs | Public |
| `POST` | `/api/v1/companies/{company}/members/invite` | Invite user as company member | Owner / Admin |
| `GET` | `/api/v1/companies/{company}/members` | List company members | Company Member |
| `PATCH` | `/api/v1/companies/{company}/members/{member}` | Update member role/permissions | Owner / Admin |
| `DELETE` | `/api/v1/companies/{company}/members/{member}` | Remove member from company | Owner / Admin |
| `POST` | `/api/v1/companies/{company}/verification` | Submit company verification request | Owner / Admin |

---

## 2. Job Creation & Management Endpoints

| Method | Endpoint | Description | Auth & Permission |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/jobs` | Search & list jobs with multi-filter | Public / Authenticated |
| `POST` | `/api/v1/jobs` | Create a job posting (wizard) | Authenticated (Company Recruiter+) |
| `GET` | `/api/v1/jobs/{job}` | Get job details and increment view | Public / Authenticated |
| `PUT` | `/api/v1/jobs/{job}` | Update job posting | Creator / Recruiter+ |
| `DELETE` | `/api/v1/jobs/{job}` | Delete / archive job | Creator / Admin |
| `POST` | `/api/v1/jobs/{job}/publish` | Publish draft job | Recruiter+ |
| `POST` | `/api/v1/jobs/{job}/pause` | Pause active job | Recruiter+ |
| `POST` | `/api/v1/jobs/{job}/resume` | Resume paused job | Recruiter+ |
| `POST` | `/api/v1/jobs/{job}/close` | Close job posting | Recruiter+ |
| `POST` | `/api/v1/jobs/{job}/duplicate` | Duplicate job posting | Recruiter+ |
| `GET` | `/api/v1/jobs/{job}/analytics` | Job view & application funnel stats | Recruiter+ / Hiring Manager |
| `GET` | `/api/v1/jobs/categories` | List hierarchical job categories | Public |
| `GET` | `/api/v1/jobs/skills` | Autocomplete / list job skills | Public |

---

## 3. Candidate Profile & Resumes Endpoints

| Method | Endpoint | Description | Auth & Permission |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/candidate/profile` | Get current candidate profile | Authenticated |
| `PUT` | `/api/v1/candidate/profile` | Update candidate career preferences | Authenticated |
| `GET` | `/api/v1/candidate/experiences` | List experiences | Authenticated |
| `POST` | `/api/v1/candidate/experiences` | Add experience entry | Authenticated |
| `PUT` | `/api/v1/candidate/experiences/{experience}` | Update experience entry | Authenticated (Owner) |
| `DELETE` | `/api/v1/candidate/experiences/{experience}` | Delete experience entry | Authenticated (Owner) |
| `GET` | `/api/v1/candidate/education` | List education entries | Authenticated |
| `POST` | `/api/v1/candidate/education` | Add education entry | Authenticated |
| `PUT` | `/api/v1/candidate/education/{education}` | Update education entry | Authenticated (Owner) |
| `DELETE` | `/api/v1/candidate/education/{education}` | Delete education entry | Authenticated (Owner) |
| `GET` | `/api/v1/candidate/resumes` | List uploaded resumes | Authenticated |
| `POST` | `/api/v1/candidate/resumes` | Upload resume (PDF/DOCX max 10MB) | Authenticated |
| `POST` | `/api/v1/candidate/resumes/{resume}/default` | Set resume as default | Authenticated (Owner) |
| `DELETE` | `/api/v1/candidate/resumes/{resume}` | Delete resume safely | Authenticated (Owner) |
| `GET` | `/api/v1/candidate/resumes/{resume}/download` | Authorized secure resume download | Owner / Authorized Recruiter |

---

## 4. Job Applications & ATS Endpoints

| Method | Endpoint | Description | Auth & Permission |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/jobs/{job}/applications` | Apply for job (with resume & answers)| Candidate User |
| `GET` | `/api/v1/candidate/applications` | Candidate's application history | Candidate User |
| `GET` | `/api/v1/applications/{application}` | View application details & timeline | Candidate (Owner) / Recruiter |
| `POST` | `/api/v1/applications/{application}/withdraw` | Withdraw candidate application | Candidate (Owner) |
| `GET` | `/api/v1/companies/{company}/applications` | Recruiter applicant list (with filters)| Recruiter+ / Hiring Manager |
| `GET` | `/api/v1/companies/{company}/recruitment/pipeline` | Kanban ATS pipeline board | Recruiter+ / Hiring Manager |
| `PATCH` | `/api/v1/applications/{application}/stage` | Move candidate to another stage | Recruiter+ |
| `POST` | `/api/v1/applications/{application}/notes` | Add internal recruiter note | Recruiter+ / Hiring Manager |
| `GET` | `/api/v1/applications/{application}/notes` | List internal recruiter notes | Recruiter+ (Company Members Only)|
| `POST` | `/api/v1/applications/{application}/assign` | Assign candidate to recruiter | Admin / Recruiter |
| `POST` | `/api/v1/applications/{application}/reject` | Reject candidate with reason | Recruiter+ |

---

## 5. Interviews Endpoints

| Method | Endpoint | Description | Auth & Permission |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/applications/{application}/interviews` | Schedule candidate interview | Recruiter+ |
| `GET` | `/api/v1/interviews` | List upcoming interviews | Authenticated (Candidate or Recruiter)|
| `GET` | `/api/v1/interviews/{interview}` | Get interview details & link | Candidate / Company Member |
| `PUT` | `/api/v1/interviews/{interview}` | Reschedule interview | Recruiter+ |
| `POST` | `/api/v1/interviews/{interview}/cancel` | Cancel interview | Recruiter+ |
| `POST` | `/api/v1/interviews/{interview}/response` | Candidate accept / decline | Candidate (Owner) |
| `POST` | `/api/v1/interviews/{interview}/feedback` | Submit interviewer feedback | Recruiter+ / Interviewer |

---

## 6. Saved Jobs & Job Alerts Endpoints

| Method | Endpoint | Description | Auth & Permission |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/candidate/saved-jobs` | List bookmarked jobs | Authenticated |
| `POST` | `/api/v1/jobs/{job}/save` | Save / bookmark job | Authenticated |
| `DELETE` | `/api/v1/jobs/{job}/save` | Unsave job | Authenticated |
| `GET` | `/api/v1/candidate/job-alerts` | List user's job alerts | Authenticated |
| `POST` | `/api/v1/candidate/job-alerts` | Create job alert | Authenticated |
| `PUT` | `/api/v1/candidate/job-alerts/{alert}` | Update job alert criteria | Authenticated (Owner) |
| `DELETE` | `/api/v1/candidate/job-alerts/{alert}` | Delete job alert | Authenticated (Owner) |

---

## 7. Employer Dashboard & Analytics Endpoints

| Method | Endpoint | Description | Auth & Permission |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/companies/{company}/dashboard` | Real hiring stats & pipeline overview| Company Member |
| `GET` | `/api/v1/companies/{company}/analytics` | Time-to-hire, sources, conversion | Recruiter+ / Admin |
| `GET` | `/api/v1/companies/{company}/analytics/export` | CSV export of applicant metrics | Admin / Recruiter |

---

## 8. Admin Job Portal Management Endpoints

| Method | Endpoint | Description | Auth & Permission |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/admin/jobs/pending` | Moderation queue for jobs | Admin / Super Admin |
| `POST` | `/api/v1/admin/jobs/{job}/approve` | Approve pending job | Admin / Super Admin |
| `POST` | `/api/v1/admin/jobs/{job}/reject` | Reject job with violations | Admin / Super Admin |
| `GET` | `/api/v1/admin/companies/verifications` | Verification requests queue | Admin / Super Admin |
| `POST` | `/api/v1/admin/companies/verifications/{verification}/approve` | Approve company verification | Admin / Super Admin |
| `POST` | `/api/v1/admin/companies/verifications/{verification}/reject` | Reject company verification | Admin / Super Admin |
| `POST` | `/api/v1/admin/jobs/categories` | Manage job categories | Admin / Super Admin |
| `POST` | `/api/v1/admin/jobs/skills` | Manage skills catalog | Admin / Super Admin |

