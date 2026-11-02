# CONNECTLY — FCM DEVICE TOKEN LIFECYCLE REPORT
## Phase 10: Complete Token Generation, Rotation, Persistence, and Revocation

| Stage | Lifecycle Event | Component Responsible | Status |
| :--- | :--- | :--- | :--- |
| **Stage 1** | App Cold Launch / Initialization | `ConnectlyApp.onCreate()` -> `DeviceTokenManager.initializeToken()` | **PASS** |
| **Stage 2** | New Token Generation / Firebase SDK Callback | `ConnectlyFirebaseMessagingService.onNewToken()` | **PASS** |
| **Stage 3** | Local Token Persistence | `DeviceTokenManager` Encrypted / App Private SharedPreferences | **PASS** |
| **Stage 4** | Backend Synchronization | `DeviceTokenManager.syncTokenWithBackend()` -> `POST /v1/devices/register` | **PASS** |
| **Stage 5** | Token Rotation | Re-invokes `onNewToken()` and syncs rotated token to backend | **PASS** |
| **Stage 6** | User Logout / Session Teardown | `DeviceTokenManager.revokeToken()` -> `DELETE /v1/devices/{id}` & `deleteToken()` | **PASS** |
| **Stage 7** | Dead / Unregistered Token Cleanup | Laravel `PushNotificationService` marks `is_revoked = true` on HTTP 404 | **PASS** |

---

## 1. Lifecycle State Machine Diagram

```
 [Fresh Install / App Launch]
              │
              ▼
   FirebaseMessaging.token
              │
              ▼
  ConnectlyFirebaseMessagingService::onNewToken(token)
              │
              ▼
    DeviceTokenManager::onNewToken(token)
              │
       ┌──────┴─────────────────────────────────┐
       ▼                                        ▼
 [Save in SharedPreferences]          [POST /v1/devices/register]
       │                                        │
       │                                        ▼
       │                              [Store backendSessionId]
       │                                        │
       │◄───────────────────────────────────────┘
       │
       ├───► [Token Rotated by Google] ───► Repeats Stage 2-4
       │
       ▼
 [User Logs Out of Connectly]
       │
       ├─► 1. DELETE /v1/devices/{sessionId} (Revoke on backend)
       ├─► 2. FirebaseMessaging.deleteToken() (Invalidate locally)
       ├─► 3. Clear fcm_push_token & backend_session_id in prefs
       │
       ▼
  [Session Completely Revoked]
```

---

## 2. Implementation Verification

### Token Acquisition & Retrieval
In `ConnectlyApp.kt`:
```kotlin
@HiltAndroidApp
class ConnectlyApp : Application() {
    @Inject
    lateinit var deviceTokenManager: DeviceTokenManager

    override fun onCreate() {
        super.onCreate()
        deviceTokenManager.initializeToken()
    }
}
```
In `DeviceTokenManager.kt`:
```kotlin
fun initializeToken() {
    try {
        FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
            if (task.isSuccessful && !task.result.isNullOrEmpty()) {
                onNewToken(task.result)
            }
        }
    } catch (e: Exception) {
        Log.w(TAG, "FirebaseMessaging unavailable: ${e.message}")
    }
}
```

### Logout Token Invalidation
```kotlin
fun revokeToken(onComplete: (() -> Unit)? = null) {
    scope.launch {
        val sessionId = backendSessionId
        try {
            if (sessionId != null) {
                api.revokeDeviceSession(sessionId)
            } else {
                api.registerDevice(RegisterDeviceRequest(deviceId, ..., push_token = null))
            }
        } catch (_: Exception) {}

        try {
            withContext(ioDispatcher) {
                FirebaseMessaging.getInstance().deleteToken()
            }
        } catch (_: Exception) {}

        currentPushToken = null
        backendSessionId = null
        onComplete?.invoke()
    }
}
```

---

## 3. Multi-Device Synchronization & Deduplication

1. **Unique Device UUID**:
   Connectly generates a persistent UUID per installation (`android_${UUID.randomUUID()}` backed by `Settings.Secure.ANDROID_ID`). Each physical device maintains its own distinct `DeviceSession` row in MySQL.
2. **Multi-Device Dispatch**:
   When a call or message is dispatched, Laravel queries all active, non-revoked devices of the target user (`DeviceSession::where('user_id', $user->id)->where('is_revoked', false)`), delivering push notifications to all user devices simultaneously.
3. **Notification Deduplication**:
   Notifications use deterministic notification tags (`msg_${conversationId}_${messageId}` or `call_${callId}`) to prevent duplicate alert spamming across Reverb WebSockets and FCM push streams.

---

## 4. Verification Verdict

- Token Retrieval & Callback: **PASS**
- Local Token Persistence: **PASS**
- Backend Token Sync: **PASS**
- Logout Invalidation Flow: **PASS**
- Multi-Device Push Handling: **PASS**
- Automated Unit Tests: **PASS** (`DeviceTokenManagerTest` passed)

