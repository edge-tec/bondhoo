# CONNECTLY — PHASE 13 STAGE 11.11 & 11.12
## PARENT WEB & CHILD MOBILE DASHBOARD SPECIFICATION

**Platform:** Connectly Parental Control & Family Digital Safety Platform  
**Audit Date:** November 05, 2026  
**Status:** IMPLEMENTED & AUDITED  

---

### 1. Parent Web Dashboard (`resources/js/Pages/Parental/Dashboard.vue`)

- **Tech Stack:** Vue 3, Inertia.js, Tailwind CSS, TypeScript.
- **Route:** `GET /parental` (Controller: `App\Http\Controllers\Web\ParentalWebController`).
- **Responsive Layout:**
  - Integrated into Connectly `AppLayout.vue`.
  - Header with Family Selector and "Pair New Device" primary action.
  - Overview Metrics: Total Children, Active Devices, Offline Devices, Active Rules, Pending Consent Requests, Security Alerts.
  - Tabs:
    1. **Children & Devices:** Child profile cards, device list, quick device lock trigger, pair device modal trigger.
    2. **Policies:** Curfew and daily limit overview, version tag, active status toggles.
    3. **Consent Requests:** Actionable list with one-click "Approve" and "Reject" buttons.
    4. **Security Alerts:** Severity badges (`critical`, `high`, `medium`) and event descriptions.
    5. **Activity Log:** Audit history showing timestamps and actor roles.

---

### 2. Child Transparent Mobile Dashboard (`ChildDashboardScreen.kt`)

- **Tech Stack:** Jetpack Compose, Material 3, Kotlin.
- **Components:**
  1. **Connection Status Card:** Connected family name, device name, green `ONLINE` badge.
  2. **Screen-Time Progress Card:** Current foreground minutes vs configured quota with dynamic progress bar and "Ask Parents for Extra Time" button.
  3. **Bedtime Card:** Visible upcoming curfew time and explanation.
  4. **Privacy & Disclosure Card:** Explicit guarantee explaining zero microphone/camera/screen recording.
  5. **Sync Timestamp Footer:** Confirms when the device last synchronized with parents.

