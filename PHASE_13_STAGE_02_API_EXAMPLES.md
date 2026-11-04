# Phase 13 — Stage 02: LMS REST API Examples
**Project:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform  
**Stage:** 02 — LMS Database, Backend Architecture & API Foundation  
**Date:** November 2026  
**Author:** Lead API Architect  

---

## 1. Course Discovery Payload Examples

### 1.1 Course List Query & Response
**Request:**
```http
GET /api/v1/learning/courses?level=intermediate&sort=popular&page=1 HTTP/1.1
Host: api.connectly.com
Authorization: Bearer 12|c2FtcGxlVG9rZW4...
Accept: application/json
```

**Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": "success",
  "message": "কোর্স তালিকা সফলভাবে লোড হয়েছে।",
  "data": [
    {
      "id": 101,
      "instructor_id": 42,
      "category_id": 5,
      "title": "Modern Android Architecture with Jetpack Compose & MVI",
      "slug": "modern-android-architecture-jetpack-compose-mvi",
      "subtitle": "Build production-grade, reactive Android apps using Clean Architecture",
      "level": "intermediate",
      "language": "en",
      "type": "paid",
      "price": 3000.00,
      "discounted_price": 2400.00,
      "currency": "BDT",
      "status": "published",
      "duration_minutes": 720,
      "total_sections": 8,
      "total_lessons": 45,
      "enrollments_count": 840,
      "rating_avg": 4.85,
      "rating_count": 128,
      "is_featured": true,
      "thumbnail_url": "https://storage.connectly.com/courses/compose-thumb.jpg",
      "instructor": {
        "id": 42,
        "name": "Tanvir Ahmed",
        "username": "tanvir_dev",
        "avatar_url": "https://storage.connectly.com/avatars/tanvir.png",
        "headline": "Lead Mobile Architect @ Connectly"
      },
      "category": {
        "id": 5,
        "name": "Mobile App Development",
        "slug": "mobile-app-development"
      },
      "skills": [
        { "id": 12, "name": "Kotlin", "slug": "kotlin" },
        { "id": 19, "name": "Jetpack Compose", "slug": "jetpack-compose" },
        { "id": 31, "name": "Coroutines & Flow", "slug": "coroutines-flow" }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

## 2. Enrollment & Learning Progress Payload Examples

### 2.1 Course Enrollment Request
**Request:**
```http
POST /api/v1/learning/courses/101/enroll HTTP/1.1
Host: api.connectly.com
Authorization: Bearer 12|c2FtcGxlVG9rZW4...
Content-Type: application/json

{
  "coupon_code": "CONNECTLY_EARLY"
}
```

**Response (HTTP 201 Created):**
```json
{
  "success": true,
  "status": "success",
  "message": "কোর্সে সফলভাবে ভর্তি সম্পন্ন হয়েছে! শুভকামনা। 🎓",
  "data": {
    "id": 502,
    "user_id": 14,
    "course_id": 101,
    "course_title": "Modern Android Architecture with Jetpack Compose & MVI",
    "enrolled_at": "2026-11-04T23:45:00+06:00",
    "status": "active",
    "progress_percentage": 0.00,
    "completed_lessons_count": 0,
    "total_lessons_count": 45,
    "price_paid": 1800.00,
    "currency": "BDT"
  }
}
```

### 2.2 Lesson Progress Update (Sync Playback)
**Request:**
```http
POST /api/v1/learning/lessons/412/progress HTTP/1.1
Host: api.connectly.com
Authorization: Bearer 12|c2FtcGxlVG9rZW4...
Content-Type: application/json

{
  "position_seconds": 680,
  "mark_completed": true
}
```

**Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": "success",
  "message": "পাঠের অগ্রগতি সফলভাবে সংরক্ষিত হয়েছে।",
  "data": {
    "lesson_id": 412,
    "is_completed": true,
    "last_position_seconds": 680,
    "course_progress": {
      "completed_lessons_count": 12,
      "total_lessons_count": 45,
      "progress_percentage": 26.67
    }
  }
}
```

---

## 3. Quiz & Examination Payload Examples

### 3.1 Quiz Retrieval for Student (Answers Masked)
**Request:**
```http
GET /api/v1/learning/quizzes/31 HTTP/1.1
Host: api.connectly.com
Authorization: Bearer 12|c2FtcGxlVG9rZW4...
```

**Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": "success",
  "message": "কুইজ তথ্য লোড হয়েছে।",
  "data": {
    "id": 31,
    "title": "StateFlow vs SharedFlow Comprehension Check",
    "instructions": "Answer all questions. You have 15 minutes.",
    "time_limit_minutes": 15,
    "passing_score": 75,
    "total_questions": 2,
    "questions": [
      {
        "id": 91,
        "question": "Which Flow retains the latest emitted value for new subscribers?",
        "type": "single_choice",
        "points": 5,
        "options": [
          { "id": 301, "option_text": "SharedFlow without replay" },
          { "id": 302, "option_text": "StateFlow" },
          { "id": 303, "option_text": "Channel" }
        ]
      }
    ]
  }
}
```

### 3.2 Quiz Submission & Instant Evaluation
**Request:**
```http
POST /api/v1/learning/quizzes/attempts/18/submit HTTP/1.1
Host: api.connectly.com
Authorization: Bearer 12|c2FtcGxlVG9rZW4...
Content-Type: application/json

{
  "answers": [
    { "question_id": 91, "selected_option_id": 302 }
  ]
}
```

**Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": "success",
  "message": "অভিনন্দন! আপনি কুইজে সফলভাবে উত্তীর্ণ হয়েছেন।",
  "data": {
    "attempt_id": 18,
    "score": 100.00,
    "earned_points": 5,
    "total_points": 5,
    "passed": true,
    "feedback": "Great understanding of Kotlin StateFlow invariants."
  }
}
```

---

## 4. Certificate Claim & Verification Examples

### 4.1 Claim Certificate Response
**Response (HTTP 201 Created):**
```json
{
  "success": true,
  "status": "success",
  "message": "আপনার সার্টিফিকেট সফলভাবে জারি করা হয়েছে! 📜",
  "data": {
    "id": 88,
    "certificate_code": "CERT-2026-KJ92-M8L1",
    "course_title": "Modern Android Architecture with Jetpack Compose & MVI",
    "student_name": "Rahim Chowdhury",
    "instructor_name": "Tanvir Ahmed",
    "issued_at": "2026-11-04T23:50:00+06:00",
    "pdf_url": "https://storage.connectly.com/certificates/CERT-2026-KJ92-M8L1.pdf",
    "qr_code_url": "https://storage.connectly.com/certificates/qr/CERT-2026-KJ92-M8L1.png",
    "is_valid": true
  }
}
```

### 4.2 Public Verification Response
**Request:** `GET /api/v1/learning/certificates/CERT-2026-KJ92-M8L1`

**Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": "success",
  "message": "বৈধ এবং যাচাইকৃত সার্টিফিকেট।",
  "data": {
    "is_valid": true,
    "certificate_code": "CERT-2026-KJ92-M8L1",
    "student_name": "Rahim Chowdhury",
    "course_title": "Modern Android Architecture with Jetpack Compose & MVI",
    "instructor_name": "Tanvir Ahmed",
    "issued_at": "2026-11-04T23:50:00+06:00",
    "issuer": "Connectly Professional Learning Platform"
  }
}
```

