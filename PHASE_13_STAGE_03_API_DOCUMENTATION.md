# PHASE 13 — STAGE 03: REST API DOCUMENTATION

**Project:** Connectly / JUGAJUG  
**Stage:** 03  
**Base URL:** `/api/v1/learning`  
**Authentication:** Bearer Token (`auth:sanctum`)  

---

## 1. Summary of Registered Stage 03 Endpoints (52 Routes)

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `GET` | `/categories` | Active learning categories tree with courses count | Yes |
| `GET` | `/categories/{category}` | Specific category metadata and courses | Yes |
| `POST` | `/admin/categories` | Admin creates category | Yes (Admin) |
| `PUT` | `/admin/categories/{id}` | Admin updates category | Yes (Admin) |
| `DELETE` | `/admin/categories/{id}` | Admin deletes unused category | Yes (Admin) |
| `GET` | `/courses/featured` | Top 6 featured published courses | Yes |
| `GET` | `/courses/popular` | Top 6 popular published courses | Yes |
| `GET` | `/courses` | Filtered catalog discovery (q, level, type, category, sort) | Yes |
| `GET` | `/courses/{slug}` | Detailed course presentation & curriculum preview | Yes |
| `POST` | `/courses` | Create new course (draft) | Yes (Instructor) |
| `PUT` | `/courses/{id}` | Update existing course | Yes (Owner/Admin) |
| `DELETE` | `/courses/{id}` | Delete course | Yes (Owner/Admin) |
| `POST` | `/courses/{id}/publish` | Publish course to marketplace | Yes (Owner/Admin) |
| `GET` | `/instructors/me` | Current user's instructor profile & stats | Yes |
| `GET` | `/instructors/{id}` | Public instructor profile | Yes |
| `POST` | `/instructors/apply` | Apply to become an instructor | Yes |
| `POST` | `/admin/instructors/{id}/verify` | Admin approves instructor application | Yes (Admin) |
| `GET` | `/instructor/courses` | List courses owned by instructor | Yes (Instructor) |
| `GET` | `/instructor/courses/{id}` | Instructor views own course details | Yes (Owner) |
| `PUT` | `/instructor/courses/{id}` | Instructor updates own course | Yes (Owner) |
| `DELETE`| `/instructor/courses/{id}` | Instructor deletes own course | Yes (Owner) |
| `GET` | `/courses/{id}/curriculum` | Complete course sections & lessons tree | Yes |
| `POST` | `/courses/{id}/sections` | Add section to course | Yes (Owner) |
| `PUT` | `/sections/{id}` | Update section title & description | Yes (Owner) |
| `DELETE`| `/sections/{id}` | Delete section | Yes (Owner) |
| `POST` | `/courses/{id}/sections/reorder` | Reorder sections | Yes (Owner) |
| `POST` | `/sections/{id}/lessons` | Add lesson to section | Yes (Owner) |
| `PUT` | `/lessons/{id}` | Update lesson content & media | Yes (Owner) |
| `DELETE`| `/lessons/{id}` | Delete lesson | Yes (Owner) |
| `POST` | `/sections/{id}/lessons/reorder` | Reorder lessons | Yes (Owner) |
| `GET` | `/lessons/{id}` | View lesson (Preview vs Protected Access Guard) | Yes |
| `POST` | `/lessons/{id}/progress` | Record lesson playback progress (auto-complete >=80%) | Yes (Enrolled) |
| `POST` | `/courses/{id}/enroll` | Enroll in course (Free or Paid) | Yes |
| `GET` | `/my-courses` | Student enrolled active courses list | Yes |
| `GET` | `/my-certificates` | Student issued verifiable certificates | Yes |

---

## 2. API Response Standard

All endpoints follow the Connectly standard response format:
```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": { ... },
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "total": 12
  }
}
```

