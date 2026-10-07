# Windows Desktop Client Architecture

## 1. Technical Stack
- Option A: Native Windows App SDK / WinUI 3 (C# / .NET 8).
- Option B: Tauri / Electron desktop app packaging `JugajugMessengerClient.js`.

## 2. Windows Platform Features
- **System Tray**: Minimized to tray icon with right-click menu and badge counters.
- **Credential Storage**: Windows Credential Manager or Data Protection API (DPAPI).
- **Notifications**: Windows Action Center Toast notifications with inline reply XML templates.
- **Audio Routing**: WASAPI device selection and communications device priority.
- **Packaging**: MSIX installer with automatic background updates.
