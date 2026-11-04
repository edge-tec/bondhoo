# PHASE 11.3: RATE LIMITING & INPUT VALIDATION REPORT
**Platform:** Connectly / JUGAJUG Professional Social Networking Platform
**Date:** 2026-11-04

---

## 1. Rate Limiting Architecture & Policy
The Connectly search subsystem is governed by a dedicated rate limiter configured in `app/Providers/AppServiceProvider.php` and bound in `routes/api.php`:

### Rate Limiter Specification:
- **Limiter Name:** `search`
- **Max Requests:** 60 requests per minute
- **Key Strategy:** `$request->user()?->id ?: $request->ip()`
- **Exceeded Response (HTTP 429):**
  ```json
  {
    "success": false,
    "message": "Too many search requests. Please slow down and try again."
  }
  ```
- **Client Synchronization:**
  - In Android `SearchViewModel.kt`, search-as-you-type input is debounced by **300ms** via Kotlin Coroutines `delay(300)` and previous in-flight jobs are cancelled with `searchJob?.cancel()`.
  - Normal typing produces ~10 to 15 requests per minute, which operates smoothly under the 60 requests/minute ceiling while blocking automated scrapers.

---

## 2. Input Validation Matrix (`GlobalSearchRequest.php`)

| Field | Rule Specification | Protection Purpose | Failed Behavior |
|---|---|---|---|
| `q` / `query` / `search` | `nullable\|string\|max:150` | Prevents excessive memory overhead and buffer exhaustion | HTTP 422 with validation errors |
| `type` | `nullable\|string\|in:all,users,posts,groups,pages,events,jobs,reels,hashtags` | Whitelists supported entity types; blocks unsupported query routing | HTTP 422 with validation errors |
| `limit` | `nullable\|integer\|min:1\|max:50` | Enforces result set boundaries to prevent large memory spikes | HTTP 422 with validation errors |
| `suggest` | `nullable\|boolean` | Ensures valid boolean casting for discovery mode | HTTP 422 with validation errors |
| `location` | `nullable\|string\|max:100` | Sanitizes geographic search scope | HTTP 422 with validation errors |
| `country` | `nullable\|string\|max:100` | Sanitizes country filtering | HTTP 422 with validation errors |
| `industry` | `nullable\|string\|max:100` | Sanitizes professional category scope | HTTP 422 with validation errors |
| `skills` | `nullable\|string\|max:100` | Prevents arbitrary query injection into skill joins | HTTP 422 with validation errors |
| `category` | `nullable\|string\|max:100` | Restricts content category filtering | HTTP 422 with validation errors |
| `date_from` | `nullable\|date` | Validates ISO date formatting | HTTP 422 with validation errors |
| `date_to` | `nullable\|date\|after_or_equal:date_from` | Prevents inverted date ranges | HTTP 422 with validation errors |
| `job_type` | `nullable\|string\|max:50` | Limits employment type parameter | HTTP 422 with validation errors |
| `sort_by` | `nullable\|string\|in:relevance,popularity,newest,date` | Prevents arbitrary SQL ORDER BY clause injection | HTTP 422 with validation errors |

---

## 3. Automated Validation Test Verification
Validated in `tests/Feature/Phase11CompleteProfessionalGlobalSearchTest.php::test_search_validates_input_and_rejects_unsupported_type_or_invalid_dates`:
- `type=malicious_type` -> returns HTTP 422.
- `date_to < date_from` -> returns HTTP 422.
- `limit=999` -> returns HTTP 422.

