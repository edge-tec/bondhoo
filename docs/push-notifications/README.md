# Push Notifications Architecture (FCM, APNs, WebPush)

## 1. Overview
The backend `PushNotificationService` routes push notifications to all active `device_sessions` for the destination user.

## 2. Notification Providers
- **Android**: Firebase Cloud Messaging (FCM HTTP v1 API).
- **iOS / macOS**: Apple Push Notification service (APNs HTTP/2 with token-based .p8 authentication).
- **Web**: Web Push API with VAPID keys.

## 3. High-Priority VoIP Calling Pushes
- For incoming calls, high-priority notifications are dispatched immediately with VoIP payloads.
- On iOS, these trigger CallKit system ringing via PushKit.
- On Android, they trigger foreground call services with fullscreen incoming call intents.

## 4. Privacy & Mute Rules
- Push notifications check conversation mute status and user notification preferences before dispatch.
- Device sessions revoked by the user or invalidated by token refresh do not receive push notifications.
