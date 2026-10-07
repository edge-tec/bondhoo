# Android Native Client Architecture

## 1. Technical Stack
- **Language**: Kotlin 2.0+
- **UI Framework**: Jetpack Compose with Material 3
- **Networking**: Retrofit 2 + OkHttp 4 (with WebSocket support)
- **Local Persistence**: Room SQLite DB (for offline messages, outbox, and cached conversations)
- **Dependency Injection**: Hilt / Dagger
- **Realtime**: Pusher Java Client / Reverb WebSocket Client
- **Calling**: Official Google WebRTC Android SDK (`org.webrtc:google-webrtc`)
- **Push**: Firebase Cloud Messaging (FCM)
- **Background Work**: Android WorkManager (for outbox retry and periodic sync)

## 2. Audio & Video Hardware Integration
- Earpiece / Speaker routing: `AudioManager.setSpeakerphoneOn()` and `AudioManager.setMode(MODE_IN_COMMUNICATION)`.
- Bluetooth SCO audio connection handling via `AudioManager.startBluetoothSco()`.
- Camera switching: `Camera2Enumerator` seamlessly swapping between front and rear sensors.
- Screen Sharing: WebRTC `ScreenCapturerAndroid` requesting permission via `MediaProjectionManager.createScreenCaptureIntent()`.

## 3. Deep Linking & App Links
- Android App Links (`https://jugajug.com/conversation/{id}`) and custom scheme (`jugajug://conversation/{id}`).
- Intent filter handles cold-start and background navigation with authentication guard.
