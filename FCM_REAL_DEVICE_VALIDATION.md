# CONNECTLY — FCM REAL DEVICE VALIDATION REPORT
## Phase 11: Real-World Push Delivery Across App States and System Constraints

| Push Testing Dimension | Implementation Status | Automated Test Status | Physical Delivery Status |
| :--- | :--- | :--- | :--- |
| **Foreground Push Dispatch** | **PASS** | **PASS** (16/16 tests) | **NOT VERIFIED** |
| **Background Push Dispatch** | **PASS** | **PASS** | **NOT VERIFIED** |
| **Killed Process (Cold Awakening)** | **PASS** | **PASS** | **NOT VERIFIED** |
| **Locked Screen Full-Screen Intent** | **PASS** | **PASS** | **NOT VERIFIED** |
| **Android 13+ POST_NOTIFICATIONS** | **PASS** | **PASS** | **NOT VERIFIED** |
| **Account Logout & Token Clearing** | **PASS** | **PASS** | **PASS** (Unit Verified) |

---

## 1. Matrix of Mobile Lifecycle Push Scenarios

| Scenario ID | Application State | Trigger Event | Expected Client Behavior | Hardware Verdict |
| :--- | :--- | :--- | :--- | :--- |
| **FCM-01** | App in Foreground | Incoming Call (`call.incoming`) | Heads-up notification shown, ringing sound plays, in-app banner or call sheet appears | **NOT VERIFIED** |
| **FCM-02** | App in Background | Incoming Call (`call.incoming`) | High-priority notification displayed with full-screen intent and sound | **NOT VERIFIED** |
| **FCM-03** | App Process Terminated (Swiped away) | Incoming Call (`call.incoming`) | Google Play Services wakes up `ConnectlyFirebaseMessagingService`, initializes `CallSessionManager`, rings phone | **NOT VERIFIED** |
| **FCM-04** | Device Locked / Display Off | Incoming Call (`call.incoming`) | Screen turns on via `USE_FULL_SCREEN_INTENT`, displays lockscreen incoming call ringer UI | **NOT VERIFIED** |
| **FCM-05** | App Background | New Chat Message (`chat.message`) | Notification posted to `Messages & Chats` channel; sound plays; tap opens chat | **NOT VERIFIED** |
| **FCM-06** | Notification Permission Denied | Incoming Call / Message | Service still receives payload in background; call state initialized silently without OS notification crash | **NOT VERIFIED** |
| **FCM-07** | Stale / Cancelled Call | Caller cancels before pickup | `call.ended` push arrives; heads-up notification dismissed automatically | **PASS** (Unit Verified) |
| **FCM-08** | Account Logout | User logs out | `revokeToken()` executed; device session deleted on backend; push token deleted from Firebase | **PASS** (Unit Verified) |
| **FCM-09** | Account Switch | Different user logs into same phone | New token generated and registered with new user ID; no cross-account message leakage | **PASS** (Unit Verified) |

---

## 2. Requirements to Transition to Full PASS

To verify physical delivery:
1. Replace placeholder `google-services.json` with the production JSON file downloaded from the Firebase Console for project `jugajug-connectly`.
2. Configure `FCM_SERVER_KEY` or `FIREBASE_CREDENTIALS` in Laravel `.env`.
3. Install `app-debug.apk` onto two physical Android devices with Google Play Services enabled.
4. Execute test cases `FCM-01` through `FCM-09`.

---

## 3. Verification Verdict

- Code Architecture & Handlers: **PASS**
- Unit & Mocked Dispatch Tests: **PASS**
- Physical Device Cloud Delivery: **NOT VERIFIED**

