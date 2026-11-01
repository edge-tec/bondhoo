# ENGINE 2 — REAL-TIME AUDIO & VIDEO CALLING INTEGRATION GUIDE

> **Engine:** WebRTC Audio & Video Calling Engine  
> **Signaling:** Laravel Reverb (WebSocket) + REST Fallback (`POST /api/v1/calls/{id}/signal`)  
> **Media Engine:** WebRTC PeerConnection, Opus Audio (48kHz), VP8 Video, STUN/TURN (RFC 5389 / RFC 5766)  
> **Status:** Fully Integrated & Verified

---

## 1. Overview & Architectural Capabilities

Engine 2 provides real-time, low-latency, two-way audio and video calling between Connectly Android and the JUGAJUG Web platform.

### Core Features:
- **Audio & Video Modes:** 1-to-1 audio calls, 1-to-1 video calls, and group calls.
- **Full Call State Machine:** `IDLE` -> `OUTGOING_RINGING` / `INCOMING_RINGING` -> `CONNECTING` -> `CONNECTED` -> `ENDED` / `REJECTED`.
- **In-Call Controls:** Mute/unmute microphone, toggle camera on/off, switch between front/rear camera, toggle loudspeaker vs. earpiece.
- **Audio Focus Management:** Utilizes Android `AudioManager` and `AudioFocusRequest` to pause media and route communication streams appropriately.
- **Call Notifications:**
  - High-priority Heads-up Notification for incoming calls with Accept / Decline action buttons.
  - Ongoing call persistent foreground notification with duration timer and Hang Up button.
- **ICE Candidate Exchange & ICE Restart:** Seamless candidate buffering prior to remote SDP completion, with automatic ICE restart upon network handover (WiFi to cellular).
- **Background & Terminated App Support:** High-priority VoIP data push wakes up backgrounded or terminated applications to trigger the incoming call ringing screen.

---

## 2. WebRTC Signaling Protocol & REST Fallback

Signaling occurs primarily through Laravel Reverb over the channel:  
`private-conversation.{conversationId}` or `private-user.{calleeId}` with event name `call.signal`.

```
Caller (Android)                Laravel Backend / Reverb               Callee (Web/Android)
     |                                    |                                    |
     |--- 1. POST /api/v1/calls --------> |                                    |
     |    (call_type, receiver_id)        |--- 2. call.incoming -------------> |
     |                                    |    + VoIP Push (FCM)               | (Ringing UI)
     |                                    |                                    |
     |                                    |<-- 3. POST /calls/{id}/respond --- |
     |<-- 4. call.accepted -------------- |    (action: "accept")              |
     |                                    |                                    |
     |--- 5. Signal: "offer" -----------> |--- 6. Signal: "offer" -----------> |
     |    (SDP offer)                     |                                    |
     |                                    |<-- 7. Signal: "answer" ----------- |
     |<-- 8. Signal: "answer" ----------- |    (SDP answer)                    |
     |                                    |                                    |
     |<-- 9. Signal: "candidate" -------- | <-- 10. Signal: "candidate" -------|
     |--- 11. Signal: "candidate" ------> | --- 12. Signal: "candidate" -----> |
     |                                    |                                    |
     +============ PEER-TO-PEER ENCRYPTED MEDIA (DTLS-SRTP) ===================+
```

### 2.1 Signal Event Payload Schema
```json
{
  "call_id": 9102,
  "signal_type": "offer",
  "sender_id": 42,
  "sender_name": "Tanzil Rahman",
  "target_user_id": 43,
  "payload": {
    "type": "offer",
    "sdp": "v=0\r\no=- 1730460000 2 IN IP4 127.0.0.1\r\ns=-\r\nt=0 0\r\na=group:BUNDLE 0 1\r\nm=audio 9 UDP/TLS/RTP/SAVPF 111 103\r\n..."
  }
}
```

### 2.2 Signal Types Supported
- `offer`: Initial Session Description Protocol offer containing audio/video codecs.
- `answer`: Callee Session Description Protocol answer acknowledging codecs.
- `candidate`: Interactive Connectivity Establishment (ICE) candidate containing IP, port, protocol, and candidate type (host, srflx, relay).
- `renegotiate`: Triggers ICE restart upon network handover or codec renegotiation.

---

## 3. WebRTC Engine Architecture (Android)

### 3.1 Signaling Coordinator (`WebRtcSignalingCoordinator.kt`)
- Subscribes to `ReverbWebSocketManager.callSignalFlow`.
- Buffers incoming ICE candidates in `pendingIceCandidates` if received prior to `remoteDescription`.
- Periodically executes REST fallback polling (`GET /api/v1/calls/{id}/signals`) every 1,500ms to recover any signals dropped due to intermittent network glitches.

### 3.2 Audio Routing (`CallAudioManager.kt`)
- Sets Android `AudioManager.mode = MODE_IN_COMMUNICATION`.
- Requests audio focus with `AUDIOFOCUS_GAIN_TRANSIENT_EXCLUSIVE`.
- Manages output routing: speakerphone vs. earpiece vs. Bluetooth SCO headset.
- Restores original audio focus and normal audio mode on call termination.

### 3.3 Permissions & Full-Screen Intent
- Manifest declares:
  - `android.permission.RECORD_AUDIO`
  - `android.permission.CAMERA`
  - `android.permission.MODIFY_AUDIO_SETTINGS`
  - `android.permission.USE_FULL_SCREEN_INTENT`
  - `android.permission.POST_NOTIFICATIONS`
- Full-Screen Intent launches `CallActivity` when an incoming call arrives while the device is locked.

---

## 4. Verification & Two-Way Media Testing

- **Android <-> Android:** Verified two-way audio and two-way video rendering. Audio mute, camera toggle, and speakerphone toggles synchronize instantly without audio feedback loops.
- **Android <-> Web:** Call initiated on Android rings on JUGAJUG Web desktop client with browser WebRTC popup. Answering on Web connects media stream; ending on either platform terminates both sessions immediately.
- **Missed & Rejected Calls:** Handled with proper reason recording in `call_participants` table and push alert delivery.

