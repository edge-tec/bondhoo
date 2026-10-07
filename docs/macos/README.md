# macOS Desktop Client Architecture

## 1. Technical Options
- Option A: Native SwiftUI for macOS with AppKit integration.
- Option B: Cross-platform Electron / Tauri bundle packaging `JugajugMessengerClient.js`.

## 2. macOS Platform Features
- **Menu Bar Tray**: Quick unread message indicator and quick-reply window.
- **Audio Routing**: CoreAudio device enumeration for microphone, AirPods, and external DACs.
- **Screen Sharing**: macOS Screen Recording permissions with Window & Screen picker.
- **Notifications**: UserNotifications framework (`UNUserNotificationCenter`) with action buttons (Reply, Mute, Dismiss).
- **Auto-Update**: Sparkle Framework or electron-updater signed with Apple Developer ID and notarized via `xcrun notarytool`.
