# PHASE 20 — SEARCH API & BACKEND API CERTIFICATION

**Project:** Bondhoo Social Platform
**Endpoint Investigated:** `GET /api/v1/search?q=bondhoo`
**Execution Timestamp:** 2026-11-20T20:42:56+06:00
**Local API Contract & Automated Tests:** **PASS**
**Live VPS Deployment of Fix:** **BLOCKED (Live VPS Still Serving Pre-`4295874` Commit)**

---

## 1. Root-Cause Investigation of Live `HTTP 401` on `/api/v1/search?q=bondhoo`

- **Intended API Contract (`routes/api.php`):** `GET /api/v1/search` is intentionally a **public/optional-auth** search endpoint (`SearchController@search`), allowing both guests (`HTTP 200`) and authenticated Sanctum users (`HTTP 200`) to search public content, while protected endpoints (`/api/v1/user`, `/api/v1/conversations/*`, `/api/v1/posts` POST) enforce `auth:sanctum` (`HTTP 401` for unauthenticated callers).
- **Why Live `https://bondhoo.com/api/v1/search?q=bondhoo` Currently Returns `HTTP 401` (`0.513s`):** Prior to commit `4295874`, `routes/api.php` had a duplicate `Route::get('/search', ...)` inside the `auth:sanctum` middleware group that shadowed the public `/search` route. That bug was fixed in commit `4295874` and verified in `PlatformParityAndSecurityTest` & `Phase14CrossPlatform30ScenariosTest` (Scenario 16: returns `HTTP 200` for guests and authenticated users).
- **Resolution:** Running `git pull --ff-only origin main && ./deploy/production/cutover-hostvra.sh` on HostVra VPS will immediately deploy the fixed `routes/api.php` and rebuild `route:cache`.
