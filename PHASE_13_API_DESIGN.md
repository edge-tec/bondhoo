# PHASE 13: REST API DESIGN SPECIFICATION
**Platform:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform  
**System Module:** Professional Learning & Development Platform (LMS)  
**Date:** 2026-11-04  
**Author:** Lead API Architect  

---

## 1. REST Endpoint Summary

All LMS endpoints are versioned under `/api/v1/learning/` and inherit standard Connectly response envelopes (`{ success, message, data, meta }`).

### 1.1 Categories & Course Marketplace
- `GET /api/v1/learning/categories` — Browse course taxonomy with course counts.
- `GET /api/v1/learning/courses` — Search & filter marketplace courses (search query, category, level, price, rating, skill).
- `GET /api/v1/learning/courses/{slug}` — Retrieve course landing page with syllabus preview and instructor profile.
- `POST /api/v1/learning/courses` — Create a new course (authorized instructors only).
- `PUT /api/v1/learning/courses/{id}` — Update course metadata and pricing.
- `POST /api/v1/learning/courses/{id}/publish` — Submit course for review / publish.
- `GET /api/v1/learning/courses/{id}/curriculum` — Retrieve full sections & lessons hierarchy.

### 1.2 Curriculum Management (Instructor)
- `POST /api/v1/learning/courses/{id}/sections` — Add section to course curriculum.
- `PUT /api/v1/learning/sections/{id}` — Edit section details or sort order.
- `DELETE /api/v1/learning/sections/{id}` — Delete section and cascade child lessons.
- `POST /api/v1/learning/sections/{id}/lessons` — Add a lesson (video, article, quiz, assignment, resource).
- `PUT /api/v1/learning/lessons/{id}` — Update lesson content and metadata.
- `DELETE /api/v1/learning/lessons/{id}` — Delete lesson.

### 1.3 Enrollments & Learning Progress
- `POST /api/v1/learning/courses/{id}/enroll` — Enroll in course (free or coupon/wallet).
- `GET /api/v1/learning/my-learning` — Retrieve student's enrolled courses with progress percentages.
- `GET /api/v1/learning/lessons/{id}` — Securely retrieve lesson details, video streaming URL, and resources (enrolled students only).
- `POST /api/v1/learning/lessons/{id}/progress` — Sync playback timestamp and mark lesson completed.

### 1.4 Quizzes & Examinations
- `GET /api/v1/learning/quizzes/{id}` — Retrieve quiz metadata, instructions, time limit, and questions without answers.
- `POST /api/v1/learning/quizzes/{id}/attempt` — Start new quiz attempt.
- `POST /api/v1/learning/quizzes/{id}/submit` — Submit answers for server-side grading and result generation.
- `GET /api/v1/learning/quizzes/{id}/attempts` — History of user attempts with scores and pass/fail status.

### 1.5 Assignments & Grading
- `GET /api/v1/learning/assignments/{id}` — Retrieve assignment instructions and deadline.
- `POST /api/v1/learning/assignments/{id}/submit` — Submit text and/or file attachment.
- `POST /api/v1/learning/assignments/submissions/{id}/grade` — Instructor grades assignment with score and feedback.

### 1.6 Certificates
- `POST /api/v1/learning/courses/{id}/certificate` — Claim verified certificate upon course completion.
- `GET /api/v1/learning/certificates/{code}` — Public verification endpoint for authenticity validation.
- `GET /api/v1/learning/my-certificates` — Retrieve all certificates earned by authenticated student.

### 1.7 Reviews & Community Discussions
- `GET /api/v1/learning/courses/{id}/reviews` — List verified reviews and average rating distribution.
- `POST /api/v1/learning/courses/{id}/reviews` — Submit course review (verified enrolled students only).
- `GET /api/v1/learning/courses/{id}/discussions` — List course Q&A and community discussions.
- `POST /api/v1/learning/courses/{id}/discussions` — Ask a question or start discussion.
- `POST /api/v1/learning/discussions/{id}/replies` — Post a reply to discussion thread.

### 1.8 Instructor Hub & Admin LMS Dashboard
- `POST /api/v1/learning/instructors/apply` — Apply to become a recognized instructor.
- `GET /api/v1/learning/instructors/dashboard` — Instructor dashboard stats (revenue, students, courses, ratings).
- `GET /api/v1/learning/admin/overview` — Administrative overview (total courses, pending approvals, revenue).
- `POST /api/v1/learning/admin/courses/{id}/approve` — Approve pending course for marketplace discovery.
- `POST /api/v1/learning/admin/instructors/{id}/verify` — Admin verification of instructor badge.

