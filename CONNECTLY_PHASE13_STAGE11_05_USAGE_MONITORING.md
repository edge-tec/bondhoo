# CONNECTLY — PHASE 13 STAGE 11.05 & 11.06
## USAGE MONITORING & SCREEN-TIME POLICY ENFORCEMENT REPORT

**Platform:** Connectly Parental Control & Family Digital Safety Platform  
**Audit Date:** November 05, 2026  
**Status:** IMPLEMENTED & AUDITED  

---

### 1. Usage Telemetry Aggregation Model

- **Granular Log Ingestion:** `POST /api/v1/parental/device/usage/sync` accepts batch logs and records entries in `app_usage_logs`.
- **Daily Aggregation:** Upserted into `screen_time_records` with composite uniqueness on `(child_device_id, application_package, usage_date)`.
- **Analytics Available:**
  - `GET /api/v1/parental/children/{child}/screen-time`: Daily per-app breakdown & total seconds/minutes.
  - `GET /api/v1/parental/children/{child}/screen-time/weekly`: 7-day trend, top 5 apps, and daily minute totals.
  - `GET /api/v1/parental/children/{child}/screen-time/quota`: Remaining daily minutes vs configured limit, with quota exceeded boolean flag.

---

### 2. Policy Restriction Types & Versioning

- **Supported Policy Rules:**
  1. `daily_time_limit`: Maximum allowed minutes per calendar day (e.g. 120 mins).
  2. `bedtime_curfew`: Bedtime start and end times (e.g. 20:30 to 06:30).
  3. `app_blacklist`: Disallowed application packages (e.g. age-restricted apps).
  4. `app_whitelist`: Explicitly permitted educational packages exempt from curfews.
- **Policy Versioning:**
  - Automatic increment (`policy_version = previous + 1`) whenever a restriction is modified.
  - The Android Child Agent polls `/api/v1/parental/device/policies` to cache the latest version offline.

