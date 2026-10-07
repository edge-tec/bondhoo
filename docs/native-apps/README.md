# Native App Architecture & Platform Strategy

## 1. Unified Client Architecture
All native clients (Android, iOS, macOS, Windows, Linux) strictly share the exact same backend API, authentication mechanism, data schemas, and event protocol.

```text
UI Layer (Jetpack Compose / SwiftUI / Electron / Tauri)
    │
Presentation / ViewModel
    │
Application Services (CallManager, SyncEngine, ChatService)
    │
Repositories (MessageRepository, ConversationRepository)
    │
Local Persistence (Room / CoreData / SQLite)
    │
Networking (Retrofit / URLSession / Fetch) + WebSocket Client (OkHttp / Starscream / WebSockets)
    │
Jugajug Canonical Backend (/api/v1)
```

## 2. Authentication & Session Security
- Clients authenticate via `POST /api/v1/auth/login` and receive a Sanctum Bearer token.
- Secure Token Storage:
  - **Android**: Android Keystore + EncryptedSharedPreferences.
  - **iOS / macOS**: Apple Keychain Services.
  - **Windows**: Windows Data Protection API (DPAPI) / Credential Manager.
  - **Linux**: Secret Service API / libsecret / Keyring.
- Each installation registers a unique `device_id` via `POST /api/v1/devices/register`.
- If a token is revoked remotely or expires, the client transitions to unauthenticated state safely.

## 3. Versioning & Mandatory Updates
Before showing main UI on app launch, clients query:
`GET /api/v1/app/version?platform={platform}&version={version}`
- If `force_update` is true, an update modal is displayed linking directly to the store/download URL.
