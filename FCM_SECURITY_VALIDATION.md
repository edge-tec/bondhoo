# CONNECTLY — FCM SECURITY VALIDATION REPORT
## Phase 10: Security Controls, Credential Isolation, and Privacy Auditing

| Security Domain | Control Objective | Implementation Evidence | Verdict |
| :--- | :--- | :--- | :--- |
| **Credential Storage** | Zero hardcoded production secrets in Git | Placeholder `google-services.json` used without real keys; `.env` configuration documented | **PASS** |
| **Server Key Exposure** | Server keys strictly isolated on backend | Android client contains 0 server keys or Firebase Admin credentials | **PASS** |
| **Service Export Security** | FCM service exported attribute | `ConnectlyFirebaseMessagingService` set to `android:exported="false"` | **PASS** |
| **Broadcast Receiver Security** | Notification receiver export security | `CallActionBroadcastReceiver` set to `android:exported="false"` | **PASS** |
| **Deep Link Security** | Deep link URL scheme whitelist | `NotificationChannelManager` only permits validated `connectly://` URIs | **PASS** |
| **Lock Screen Privacy** | Content redaction on locked screens | Channels configured with `VISIBILITY_PRIVATE` and safe generic public versions | **PASS** |
| **Token Hijacking Prevention** | Device session bound to auth user | `DeviceSession` model binds `user_id` to `device_id` and Sanctum token | **PASS** |
| **Session Invalidation** | Secure logout token destruction | `DELETE /v1/devices/{id}` on backend + `FirebaseMessaging.deleteToken()` on client | **PASS** |

---

## 1. Zero-Credential-Leakage Audit

An exhaustive audit of the Android repository and git staging was performed:
1. `android/app/google-services.json`:
   - Contains ONLY development placeholder project strings: `project_id: "connectly-placeholder-dev"`, package names `com.connectly.android`, `com.connectly.android.debug`, `com.connectly.android.staging`, and non-functional placeholder API key values (`AIzaSyPlaceholderKeyForDevelopmentOnly000`).
   - No private certificates, production OAuth client IDs, or Firebase Admin service account keys exist in version control.
2. `android/app/keystore/`:
   - Release keystore environment variable fallback is configured via `CONNECTLY_KEYSTORE_PASSWORD`.
3. Backend Service Account Isolation:
   - Laravel `PushNotificationService` loads credentials strictly from `env('FCM_SERVER_KEY')` or `env('FIREBASE_CREDENTIALS')`.
   - Never sent to or bundled within the client APK.

---

## 2. Component & Intent Protection

### A. Manifest Service Isolation
```xml
<service
    android:name="com.connectly.android.feature.notifications.data.fcm.ConnectlyFirebaseMessagingService"
    android:exported="false">
    <intent-filter>
        <action android:name="com.google.firebase.MESSAGING_EVENT" />
    </intent-filter>
</service>
```
Setting `android:exported="false"` ensures that 3rd-party malicious applications on the device cannot simulate or send fake intents directly to `ConnectlyFirebaseMessagingService`. Only Google Play Services (which binds to the service with OS-level platform signatures) can deliver `MESSAGING_EVENT` broadcasts.

### B. Broadcast Receiver Isolation
```xml
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
`CallActionBroadcastReceiver` is explicitly marked `android:exported="false"`, preventing unauthorized external applications from spoofing call acceptance or rejection. All pending intents specify the app's package name (`setPackage(context.packageName)`).

---

## 3. Lock Screen Data Privacy & Notification Redaction

In `NotificationChannelManager.kt`:
```kotlin
// Public notification shown on lock screen when device is locked (redacts sensitive message text)
val publicNotification = NotificationCompat.Builder(context, channelId)
    .setSmallIcon(android.R.drawable.ic_dialog_info)
    .setContentTitle("Connectly")
    .setContentText("You have a new alert")
    .build()

val builder = NotificationCompat.Builder(context, channelId)
    ...
    .setVisibility(NotificationCompat.VISIBILITY_PRIVATE)
    .setPublicVersion(publicNotification)
```
- For chat and social notifications, the notification text is marked `VISIBILITY_PRIVATE`. When the device is locked, Android displays "Connectly: You have a new alert", safeguarding private chat messages and names from unauthorized bystanders.
- For incoming calls, `VISIBILITY_PUBLIC` is used to allow caller identification and incoming call pickup without unlocking the device first.

---

## 4. Deep Link Scheme Validation

To prevent malicious open-redirect or URI injection vulnerabilities through push payloads:
```kotlin
val isSafeDeepLink = !deepLinkUri.isNullOrEmpty() && deepLinkUri.startsWith("connectly://")
val intent = if (isSafeDeepLink) {
    Intent(Intent.ACTION_VIEW, Uri.parse(deepLinkUri))
} else {
    context.packageManager.getLaunchIntentForPackage(context.packageName)
}
```
Non-whitelisted schemes (`http://`, `javascript:`, `file://`, or arbitrary intents) are rejected, defaulting to the application root intent.

---

## 5. Security Verdict

- Secret Isolation: **PASS**
- Android IPC Hardening: **PASS**
- Lockscreen Privacy Protection: **PASS**
- Deep Link Sanitization: **PASS**
- Invalidation on Logout: **PASS**

