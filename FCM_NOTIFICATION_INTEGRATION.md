# ENGINE 3 — FIREBASE CLOUD MESSAGING (FCM) INTEGRATION GUIDE

> **Engine:** Push Notification & Remote Device Messaging Engine  
> **Backend Integration:** Laravel 13 `PushNotificationService` (FCM HTTP v1 & Legacy Fallback)  
> **Android Client:** Firebase Cloud Messaging SDK + Android 13+ Notification Runtime Permissions  
> **Status:** Fully Integrated & Verified

---

## 1. Overview & Capabilities

Engine 3 bridges server-side events to user devices when the Connectly Android app is in the background, minimized, or terminated.

### Supported Notification Types:
1. **New Messages:** Chat alerts with sender name, message snippet, and conversation deep link.
2. **Incoming Calls (VoIP High-Priority):** Wakes up device with full-screen intent and custom ringtone.
3. **Missed Calls:** Alerts user of missed audio/video calls with quick callback action.
4. **Friend Requests & Acceptances:** Social connection notifications.
5. **Reactions & Comments:** Real-time engagement alerts for user posts and reels.
6. **Live Streams:** Alerts followers when a creator or friend starts broadcasting.
7. **Security Alerts:** Immediate notification on new device login or suspicious account session.

---

## 2. Device Registration & Token Lifecycle

### 2.1 Registration Contract
Upon app launch or FCM token refresh, Android client sends hardware and session metadata to:
`POST /api/v1/devices/register`

#### Request Payload:
```json
{
  "device_id": "9a38f72c-81bc-4e2a-921a-e27c10b74109",
  "device_name": "Google Pixel 8 Pro",
  "platform": "android",
  "app_version": "1.0.0",
  "push_token": "fcm_token_abcdef1234567890..."
}
```

#### Response:
```json
{
  "success": true,
  "data": {
    "id": 18,
    "user_id": 42,
    "device_id": "9a38f72c-81bc-4e2a-921a-e27c10b74109",
    "device_name": "Google Pixel 8 Pro",
    "platform": "android",
    "is_revoked": false
  },
  "message": "Device session registered successfully."
}
```

### 2.2 Token Revocation & Logout
When the user logs out, the client calls `DELETE /api/v1/devices/{id}` to revoke the session, preventing stale notifications from being dispatched to the device.

---

## 3. Server-Side Push Dispatching (`PushNotificationService.php`)

### 3.1 Security & Credentials
- Server communicates with Firebase using server-side service account credentials (`services.fcm.server_key` / `services.fcm.project_id`).
- Credentials are never bundled inside the Android APK.
- Supports both **FCM HTTP v1 API** (`https://fcm.googleapis.com/v1/projects/{projectId}/messages:send`) and **Legacy HTTP API** fallback.

### 3.2 High-Priority VoIP Push Configuration
```php
$dataPayload = [
    'type' => 'call.incoming',
    'action' => 'call.incoming',
    'call_id' => (string) $callData['call_id'],
    'conversation_id' => (string) $callData['conversation_id'],
    'call_type' => (string) $callData['call_type'],
    'caller_id' => (string) $callData['caller']['id'],
    'caller_name' => (string) $callData['caller']['name'],
    'priority' => 'high',
    'ttl' => '45s',
    'click_action' => 'CONNECTLY_CALL_NOTIFICATION_CLICK',
];
```

---

## 4. Android Notification Channels & Privacy

Android 8.0+ notification channels configured in `NotificationChannelManager.kt`:

| Channel ID | Name | Importance | Sound & Behavior |
| :--- | :--- | :--- | :--- |
| `channel_connectly_messages` | Messages & Chats | HIGH | Notification chime, heads-up banner, vibration. |
| `channel_connectly_calls` | Voice & Video Calls | MAX | Looping ringtone, full-screen intent, ongoing service. |
| `channel_connectly_social` | Likes, Reactions & Comments | DEFAULT | Silent or standard chime, grouped notifications. |
| `channel_connectly_community`| Groups & Events | DEFAULT | Community alerts and event reminders. |
| `channel_connectly_live` | Live Streaming | HIGH | Urgent broadcast notification. |
| `channel_connectly_security` | Security & Sessions | HIGH | Security alert on new device logins. |

### 4.1 Lock Screen Privacy
Notifications default to `VISIBILITY_PRIVATE`. When the device is locked, sensitive chat message bodies are replaced with generic text:  
`"Connectly — You have a new alert"`

### 4.2 Deep Linking
All notification intents use verified URI scheme:
- Messages: `connectly://messenger/chat/{conversationId}`
- Social: `connectly://posts/{postId}` or `connectly://notifications`
- Calls: Launches `CallActivity` directly.

