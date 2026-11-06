# BONDHOO (বন্ধু) — PHASE 14.9: DATABASE CONCURRENCY REPORT
**Focus:** Concurrency Locking, Race Conditions, Transaction Isolation & Deadlock Prevention  
**Auditor:** Database Reliability Engineer & Senior Backend Engineer  
**Date:** November 2026  
**Concurrency Assessment:** **VERIFIED, LOCK-PROTECTED & ACID-COMPLIANT**

---

## 1. Concurrency Architecture

In high-throughput recruitment environments, concurrent actions (e.g. multiple recruiters acting on the same candidate, or candidate withdrawing while an interview is being booked) can lead to inconsistent state without proper locking.

Phase 14.9 enforced strict row-level pessimistic locking (`lockForUpdate()`) and atomic transaction wrapping across all critical recruitment pathways.

```
       Request A (Recruiter Schedule)          Request B (Candidate Withdraw)
                     |                                       |
                     +-------------------+-------------------+
                                         |
                                         v
                         +-------------------------------+
                         |      DB::transaction()        |
                         |               |               |
                         |  SELECT * FROM applications   |
                         |    WHERE id = ? FOR UPDATE    |  <-- Row Lock Acquired by A
                         +---------------+---------------+
                                         |
                         +---------------+---------------+
                         | Request B must wait or gets   |
                         | rolled back cleanly           |
                         +-------------------------------+
```

---

## 2. Lock Implementation Inspection

### 2.1 Schedule Interview Concurrency Guard
In `App\Services\Recruitment\RecruitmentAtsService::scheduleInterview`:
```php
return DB::transaction(function () use ($application, $scheduler, $data) {
    // Row-level exclusive lock on application record
    $lockedApp = JobApplication::where('id', $application->id)->lockForUpdate()->firstOrFail();

    if ($lockedApp->status === JobApplication::STATUS_WITHDRAWN) {
        throw ValidationException::withMessages([
            'application' => ['Cannot schedule an interview for a withdrawn application.'],
        ]);
    }

    // Active interview check within the locked transaction
    $hasActiveInterview = Interview::where('application_id', $application->id)
        ->whereIn('status', [Interview::STATUS_SCHEDULED, 'pending', Interview::STATUS_CONFIRMED, Interview::STATUS_RESCHEDULED])
        ->exists();

    if ($hasActiveInterview) {
        throw ValidationException::withMessages([
            'interview' => ['An active interview is already scheduled for this application.'],
        ]);
    }

    $interview = Interview::create([...]);
    return $interview;
});
```

### 2.2 Interview State Transition Concurrency Guard
In `respondToInterview`, `rescheduleInterview`, `cancelInterview`, and `recordInterviewFeedback`:
```php
return DB::transaction(function () use ($interview, ...) {
    $locked = Interview::where('id', $interview->id)->lockForUpdate()->firstOrFail();

    // State transition guard verified against locked database row
    if (! in_array($locked->status, [allowed_states], true)) {
        throw ValidationException::withMessages([
            'interview' => ['Cannot transition from current status: '.$locked->status],
        ]);
    }

    $locked->update([...]);
    return $locked->fresh();
});
```

---

## 3. Concurrency Stress Test Results

| Test Scenario | Concurrency Mechanism | Expected Outcome | Actual Result |
| :--- | :--- | :--- | :---: |
| **Simultaneous Interview Booking** | `lockForUpdate()` on application + active check | First succeeds, second rejected with 422 | **PASS (422)** |
| **Schedule on Withdrawn Candidate** | `lockedApp->status === WITHDRAWN` check | Rejected immediately with 422 | **PASS (422)** |
| **Stage Advance on Withdrawn Candidate**| `lockForUpdate()` on application | Blocked inside transaction with 422 | **PASS (422)** |
| **Concurrent Candidate Response** | `lockForUpdate()` on interview | First updates state; second rejected with 422 | **PASS (422)** |
| **Reschedule Cancelled Interview** | `lockForUpdate()` + valid status array | Blocked with 422 | **PASS (422)** |
| **Complete Declined Interview** | `lockForUpdate()` + confirmed status check | Blocked with 422 | **PASS (422)** |

---

## 4. Database Foreign Keys & Cascading Rules

1. `job_applications.job_posting_id` $\to$ `cascade on delete`.
2. `interviews.application_id` $\to$ `cascade on delete`.
3. `saved_jobs.job_id` $\to$ `cascade on delete`.
4. `job_posting_skills` (pivot) $\to$ `cascade on delete`.

Soft deletion on `job_postings` ensures no inadvertent data loss occurs when employers archive or soft-delete job advertisements.

