# CONNECTLY — FCM ANDROID IMPLEMENTATION REPORT
## Phase 10: Enterprise Firebase Cloud Messaging Android Architecture

| Metric | Specification | Status |
| :--- | :--- | :--- |
| **Component** | Connectly Android (Kotlin + Jetpack Compose) | **PASS** |
| **SDK Integration** | Official Firebase BoM `33.7.0` + `firebase-messaging` | **PASS** |
| **Gradle Plugin** | `com.google.gms.google-services` `4.4.2` | **PASS** |
| **Service Superclass** | `com.google.firebase.messaging.FirebaseMessagingService` | **PASS** |
| **Manifest Registration** | `com.google.firebase.MESSAGING_EVENT` intent filter | **PASS** |
| **Full Build Status** | `:app:assembleDebug` compiled (67MB APK) | **PASS** |
| **Unit Test Status** | 16/16 tests passing | **PASS** |

---

## 1. Resolution of DEF-RT-02

In Phase 8/9 validation, defect **DEF-RT-02** noted:
1. `ConnectlyFirebaseMessagingService` improperly extended `android.app.Service` instead of `FirebaseMessagingService`.
2. Firebase Messaging SDK was not linked in Gradle catalogs.
3. `google-services.json` was missing, blocking the Google Services Gradle task.
4. Background incoming call push wake-up was not connected to `CallSessionManager`.

### Corrective Actions Implemented:
- **Dependency Catalogs Updated**:
  - `android/gradle/libs.versions.toml`: Added `firebaseBom = "33.7.0"`, `googleServices = "4.4.2"`, `firebase-bom`, `firebase-messaging`, and `google-services` plugin.
- **Multi-Module Gradle Configuration**:
  - `android/build.gradle.kts`: Declared `alias(libs.plugins.google.services) apply false`.
  - `android/app/build.gradle.kts`: Applied `alias(libs.plugins.google.services)`.
  - `android/feature/notifications/build.gradle.kts`: Integrated `platform(libs.firebase.bom)`, `libs.firebase.messaging`, and `project(":core:webrtc")`.
- **Inheritance & Lifecycle Fix**:
  - `ConnectlyFirebaseMessagingService.kt` refactored to extend `com.google.firebase.messaging.FirebaseMessagingService`.
  - Handled `onNewToken(token: String)` with delegation to `DeviceTokenManager.onNewToken(token)`.
  - Handled `onMessageReceived(remoteMessage: RemoteMessage)` routing across incoming VoIP calls, chat messages, social notifications, live events, and security alerts.

---

## 2. Service Architecture & Dependency Injection

```
                  ┌───────────────────────────────┐
                  │    Firebase Cloud Messaging   │
                  │        (FCM Network)          │
                  └──────────────┬────────────────┘
                                 │ Background Push
                                 ▼
              ┌──────────────────────────────────────┐
              │ ConnectlyFirebaseMessagingService    │
              │ (extends FirebaseMessagingService)   │
              └───────┬──────────────────────┬───────┘
                      │                      │
       onNewToken(token)           onMessageReceived(msg)
                      │                      │
                      ▼                      ▼
            ┌──────────────────┐    ┌──────────────────┐
            │DeviceTokenManager│    │ Payload Router   │
            └─────────┬────────┘    └────────┬─────────┘
                      │                      │
           POST /v1/devices/register         ├── type: "call.incoming" -> CallSessionManager & CallNotificationManager
                                             ├── type: "chat.message"  -> NotificationChannelManager (CHANNEL_MESSAGES)
                                             ├── type: "live.stream"   -> NotificationChannelManager (CHANNEL_LIVE)
                                             ├── type: "group.invite"  -> NotificationChannelManager (CHANNEL_COMMUNITY)
                                             └── type: "security"      -> NotificationChannelManager (CHANNEL_SECURITY)
```

### Injected Dependencies:
- `DeviceTokenManager`: Manages secure device UUID, token caching in SharedPreferences, and API sync.
- `NotificationChannelManager`: Manages Android O+ notification channels, custom sounds, and lockscreen privacy.
- `CallNotificationManager`: Dispatches high-priority heads-up calling notifications with full-screen intents and action buttons.
- `CallSessionManager`: Orchestrates WebRTC peer connection wake-up, SDP negotiation, and ICE candidate exchange.

---

## 3. Notification Channels & Attributes

All notification channels adhere to Android O+ (API 26+) specifications:

| Channel ID | Channel Name | Importance | Sound / Attributes | Privacy |
| :--- | :--- | :--- | :--- | :--- |
| `channel_connectly_messages` | Messages & Chats | `IMPORTANCE_HIGH` | Communication sound, vibration enabled | `VISIBILITY_PRIVATE` (Redacted on lock screen) |
| `channel_connectly_calls` | Voice & Video Calls | `IMPORTANCE_MAX` | System Ringtone, continuous vibration pattern | `VISIBILITY_PUBLIC` (Ringtone UI on lock screen) |
| `channel_connectly_social` | Likes, Reactions & Comments | `IMPORTANCE_DEFAULT` | Standard system alert sound | `VISIBILITY_PRIVATE` |
| `channel_connectly_community`| Groups & Events | `IMPORTANCE_DEFAULT` | Standard alert sound | `VISIBILITY_PRIVATE` |
| `channel_connectly_live` | Live Streaming | `IMPORTANCE_HIGH` | High-priority notification alert | `VISIBILITY_PRIVATE` |
| `channel_connectly_security`| Security & Sessions | `IMPORTANCE_HIGH` | High-priority security chime | `VISIBILITY_PRIVATE` |

---

## 4. Android Manifest Permissions & Configuration

Verified in `android/app/src/main/AndroidManifest.xml`:
```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
<uses-permission android:name="android.permission.VIBRATE" />
<uses-permission android:name="android.permission.WAKE_LOCK" />
<uses-permission android:name="android.permission.USE_FULL_SCREEN_INTENT" />

<service
    android:name="com.connectly.android.feature.notifications.data.fcm.ConnectlyFirebaseMessagingService"
    android:exported="false">
    <intent-filter>
        <action android:name="com.google.firebase.MESSAGING_EVENT" />
    </intent-filter>
</service>

<receiver
    android:name="com.connectly.android.core.webrtc.notification.CallActionBroadcastReceiver"
    android:exported="false">
    <intent-filter>
        <action android:name="com.connectly.android.ACTION_ACCEPT_CALL" />
        <action android:name="com.connectly.android.ACTION_REJECT_CALL" />
        <action android:name="com.connectly.android.ACTION_END_CALL" />
    </intent-filter>
</receiver>
```

---

## 5. Verification Verdict

- Android SDK Integration: **PASS**
- Service Inheritance & Hilt Setup: **PASS**
- Manifest Permissions: **PASS**
- Debug APK Build: **PASS** (`app-debug.apk` built cleanly)
- Unit Tests: **PASS** (16/16 test assertions passing)

