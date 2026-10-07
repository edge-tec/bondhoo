# WebRTC Audio/Video Calling & NAT Traversal Specification

## 1. Architecture
Jugajug calling uses standard WebRTC with server-authoritative state transitions, STUN/TURN NAT traversal, and real-time signaling via WebSocket and REST fallback.

### Call States
`initiating` -> `ringing` -> `accepted` (active) -> `ended`
Branching terminations: `rejected`, `busy`, `missed`, `failed`.

## 2. STUN / TURN Configuration
Clients fetch ICE servers dynamically via:
`GET /api/v1/calls/ice-servers`

### Response
```json
{
  "success": true,
  "data": {
    "ice_servers": [
      {
        "urls": "stun:stun.l.google.com:19302"
      },
      {
        "urls": "turn:turn.jugajug.com:3478?transport=udp",
        "username": "1738294800:user_42",
        "credential": "base64-hmac-sha1-signature"
      }
    ]
  }
}
```
TURN credentials are short-lived (ephemeral HMAC-SHA1 tokens) expiring in 24 hours, preventing unauthorized relay abuse.

## 3. Signaling Protocol
Signaling packets are exchanged via `POST /api/v1/calls/{id}/signal`:
- `offer`: Caller SDP offer.
- `answer`: Callee SDP answer.
- `candidate`: RTCIceCandidate JSON.
- `screen_share`: Screen capture state transition and renegotiation.
- `renegotiate`: Trigger ICE restart or track addition/removal.

## 4. Screen Sharing
- **Web**: Uses `navigator.mediaDevices.getDisplayMedia({ video: true, audio: true })`.
- **Android**: Uses `MediaProjectionManager` and WebRTC `ScreenCapturerAndroid`.
- **iOS**: Uses `ReplayKit` broadcast upload extension.
- **Desktop**: Native display capturer.
Track replacement is performed using `sender.replaceTrack(screenTrack)` without terminating the call session.
