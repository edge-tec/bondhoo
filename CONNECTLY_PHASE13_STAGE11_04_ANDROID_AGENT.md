# CONNECTLY — PHASE 13 STAGE 11.04
## ANDROID CHILD DEVICE AGENT ARCHITECTURE

**Platform:** Connectly Parental Control & Family Digital Safety Platform  
**Target OS:** Android 8.0+ (API 26 to 35)  
**Module:** `:feature:parental`  
**Package:** `com.connectly.android.feature.parental`  
**Status:** IMPLEMENTED & COMPLIANT  

---

### 1. Component Architecture & Responsibilities

| Component | Responsibility | Technologies Used |
|---|---|---|
| `ChildAgentService.kt` | Foreground Android Service maintaining supervision lifecycle | Android Foreground Service, Persistent Notification |
| `UsageStatsCollector.kt` | Safe query of foreground app runtime | Android `UsageStatsManager` |
| `UsageSyncWorker.kt` | Periodic background batch synchronization | AndroidX `WorkManager`, Retrofit |
| `PolicySyncWorker.kt` | Periodic policy retrieval and local caching | AndroidX `WorkManager`, Retrofit |
| `RemoteCommandWorker.kt` | Polling and immediate execution of remote commands | AndroidX `WorkManager`, Retrofit |
| `DeviceRegistrationManager.kt` | Device UUID persistence & pairing workflow | Encrypted `SharedPreferences`, Retrofit |
| `ChildDashboardScreen.kt` | Transparent child-facing status UI | Jetpack Compose, Material 3 |

---

### 2. Privacy & Compliance Guarantees (No Covert Surveillance)

1. **Persistent Visual Notification:**
   - Whenever `ChildAgentService` runs, it must display an ongoing non-dismissible notification (`NotificationCompat.PRIORITY_LOW`) on channel `connectly_parental_supervision_channel`.
2. **Explicit Forbidden Operations:**
   - Connectly Child Agent strictly DOES NOT capture screen contents, record microphone audio, access camera video, log keystrokes, or intercept private messages.
3. **Transparent Child UI:**
   - The child can open `ChildDashboardScreen` at any time to see exactly what time is counted, what rules are active, when bedtime begins, and request extra screen time.

