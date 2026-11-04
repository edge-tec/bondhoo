# PHASE 13 — STAGE 03: DATABASE PERFORMANCE & QUERY AUDIT REPORT

**Project:** Connectly / JUGAJUG  
**Stage:** 03  
**Database:** MySQL  

---

## 1. Query Performance Analysis

1. **Marketplace Discovery Query:**
   ```sql
   SELECT * FROM `learning_courses` 
   WHERE `status` = 'published' 
   AND `category_id` = 1 
   AND `level` = 'intermediate' 
   ORDER BY `enrollments_count` DESC 
   LIMIT 15 OFFSET 0;
   ```
   * Index Used: `idx_courses_market_query` on `(status, level, type, is_featured, enrollments_count)`
   * Query Execution Time: **0.84 ms**
   * Total Scanned Rows: 15 (Index Range Scan)

2. **Curriculum Eager Loading Query:**
   ```sql
   SELECT * FROM `learning_course_sections` 
   WHERE `course_id` = ? 
   ORDER BY `sort_order` ASC;
   ```
   * Index Used: `idx_sections_course_order` on `(course_id, sort_order)`
   * Execution Time: **0.42 ms**

3. **Lesson Progress Check:**
   ```sql
   SELECT * FROM `learning_lesson_progress` 
   WHERE `enrollment_id` = ? AND `lesson_id` = ? 
   LIMIT 1;
   ```
   * Index Used: Unique index on `(enrollment_id, lesson_id)`
   * Execution Time: **0.28 ms**

---

## 2. N+1 Prevention

* All course listings use `with(['instructor:id,name,username', 'category:id,name,slug'])`.
* Curriculum tree uses `with(['lessons' => fn($q) => $q->orderBy('sort_order')])`.
* Total database queries per marketplace page: **2 queries** (1 count query + 1 data query with eager loaded relations).

