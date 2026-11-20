# PHASE 13.5 — Real-Time Messenger & WebSocket Audit

## 1. Messenger & WebSocket Verification Matrix

| Capability | Backend Implementation | Website Client | Android Client | Verification Status |
|---|---|---|---|---|
| **1:1 & Group Messaging** | `MessengerService`, `MessageController` | `messages/index.blade.php`, `Messenger.vue` | `ChatScreen.kt`, `MessengerRepositoryImpl.kt` | **PASS** (173 PHPUnit tests + Phase 13 integration test) |
| **Polls, Voting & Close** | `EnterpriseMessengerService` | Blade Poll Card + Create/Vote JS | Compose Poll Card + Dialog + VM | **PASS** |
| **Disappearing Messages** | `messages.expires_at` + `messenger:process-queue` | Sidebar Timer Modal | Timer Dialog + VM | **PASS** |
| **Scheduled Messages** | `scheduled_messages` + `messenger:process-queue` | Composer Schedule Prompt | `scheduleMessage` Repo + VM | **PASS** |
| **Location & Live Location** | `shareLocation`, `stopLiveLocation` | Geolocation Share + Maps Card | Location Bubble + Stop Action | **PASS** |
| **Chat Themes** | `updateTheme`, `resetTheme` | Theme Prompt + CSS Var | Theme Dialog + Bubble Color | **PASS** |
| **Voice Speed (0.5x–2x) & Transcription** | `VoiceMessageController`, `transcribeVoiceMessage` | `.voice-speed-pill` + `.voice-transcribe-btn` | `MediaPlayer.playbackParams` + `Aa` Chip | **PASS** |
| **Channel Auth Guards** | `routes/channels.php` (`['guards' => ['web', 'sanctum']]`) | `/broadcasting/auth` & `/api/v1/broadcasting/auth` | `/api/v1/broadcasting/auth` | **PASS** (401 unauth & 403 IDOR verified) |
| **Web `window.Echo` Client** | Pusher Protocol v7 | `public/js/bondhoo-echo-client.js` | `ReverbWebSocketManager.kt` | **PASS** (Code & Protocol verified) |
| **Live Production Reverb Server** | `wss://bondhoo.com/app/connectly_reverb_key` | Auto-connects when `reverb` active | Connects to `wss://bondhoo.com/app/...` | **BLOCKED** (Returns HTTP 404 on `bondhoo.com` until Reverb daemon & Nginx `/app` proxy are enabled on server) |
