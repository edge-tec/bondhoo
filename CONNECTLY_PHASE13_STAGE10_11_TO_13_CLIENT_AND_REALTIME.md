# CONNECTLY — PHASE 13 STAGE 10.11 TO 10.13
## ANDROID MODULE CONTRACTS & REAL-TIME BROADCAST SPECIFICATION

**Platform:** Connectly Parental Control & Family Digital Safety  
**Audit Date:** November 05, 2026  
**Status:** IMPLEMENTED & AUDITED  

---

### 1. Android Feature Module Structure (Stage 10.11 & 10.12)
- **Module Name:** `:feature:parental`
- **Gradle Config:** `android/feature/parental/build.gradle.kts` registered in `android/settings.gradle.kts`
- **Package:** `com.connectly.android.feature.parental`

#### Key Kotlin Contracts:
1. `model/ParentalModels.kt`:
   - Data classes for `Family`, `FamilyMember`, `ChildDevice`, `PairingCodeResponse`, `ScreenTimeSummary`, `DevicePolicy`, `RemoteCommand`.
2. `data/ParentalApiService.kt`:
   - Retrofit HTTP client defining Parent endpoints (`/api/v1/parental/families/...`) and Child Agent endpoints (`/api/v1/parental/device/...`).
3. `agent/UsageStatsCollector.kt`:
   - Android SDK `UsageStatsManager` extraction logic querying daily foreground durations safely.
4. `agent/ChildAgentService.kt`:
   - Transparent, non-covert Android Foreground Service.
   - Enforces persistent notification (`NotificationCompat.PRIORITY_LOW`) on channel `connectly_parental_supervision_channel` to satisfy child safety transparency standards.

---

### 2. Real-Time Broadcast & Push Events (Stage 10.13)
- **Event Classes:**
  1. `App\Events\Parental\ChildSecurityAlertRaised`:
     - Channel: `private-family.{familyId}.guardians`
     - Event Name: `child.security.alert`
  2. `App\Events\Parental\ConsentRequestCreated`:
     - Channel: `private-family.{familyId}.guardians`
     - Event Name: `consent.request.created`
  3. `App\Events\Parental\RemoteCommandQueued`:
     - Channel: `private-device.{deviceId}.commands`
     - Event Name: `remote.command.queued`

