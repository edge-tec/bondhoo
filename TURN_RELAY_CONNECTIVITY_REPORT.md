# TURN RELAY CONNECTIVITY & TELEMETRY REPORT

**Protocol:** RFC 5766 (TURN) & RFC 8489 (STUN)  
**System:** Connectly Android Native WebRTC Engine (`WebRtcEngine.kt`) + JUGAJUG Backend  
**Date:** November 2026  
**Status:** `REST API VERIFIED — LIVE RELAY PENDING PROVISIONING`  

---

## 1. Executive Summary

This report assesses the connectivity pipeline for WebRTC media relaying through STUN and TURN servers. The evaluation distinguishes between **signaling-level ICE configuration** (handled via Laravel REST API) and **physical media packet relaying** (which requires live Coturn server ports).

---

## 2. ICE Server Configuration Pipeline

### 2.1 Backend Contract Verification
- **Route:** `GET /api/v1/calls/ice-servers`
- **Controller:** [CallController.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Http/Controllers/Api/V1/CallController.php)
- **Service:** [CallingService.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/Calling/CallingService.php)
- **Result:**
  ```json
  {
    "ice_servers": [
      {
        "urls": "stun:stun.l.google.com:19302"
      },
      {
        "urls": "stun:turn.jugajug.com:3478"
      },
      {
        "urls": "turn:turn.jugajug.com:3478?transport=udp",
        "username": "1730599200:101",
        "credential": "mE7yY...=="
      },
      {
        "urls": "turn:turn.jugajug.com:3478?transport=tcp",
        "username": "1730599200:101",
        "credential": "mE7yY...=="
      },
      {
        "urls": "turns:turn.jugajug.com:5349?transport=tcp",
        "username": "1730599200:101",
        "credential": "mE7yY...=="
      }
    ]
  }
  ```
- **Test Status:** `PASS` (Verified via `EnterpriseTurnCredentialTest` in 759ms).

---

## 3. WebRTC Candidate Gathering Telemetry

Inside [WebRtcEngine.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/core/webrtc/src/main/java/com/connectly/android/core/webrtc/WebRtcEngine.kt), the client maps the received ICE configuration directly into the native WebRTC `PeerConnectionFactory`:

```kotlin
val iceServers = response.iceServers.map { server ->
    PeerConnection.IceServer.builder(server.urls)
        .setUsername(server.username ?: "")
        .setPassword(server.credential ?: "")
        .createIceServer()
}

val rtcConfig = PeerConnection.RTCConfiguration(iceServers).apply {
    bundlePolicy = PeerConnection.BundlePolicy.MAXBUNDLE
    rtcpMuxPolicy = PeerConnection.RtcpMuxPolicy.REQUIRE
    continualGatheringPolicy = PeerConnection.ContinualGatheringPolicy.GATHER_CONTINUALLY
    iceTransportsType = PeerConnection.IceTransportsType.ALL
}
```

### Candidate Classification:
1. `typ host`: Local device IP addresses (Wi-Fi 192.168.x.x or cellular interface).
2. `typ srflx`: Server Reflexive candidates discovered via Google STUN (`stun.l.google.com:19302`).
3. `typ relay`: Relayed candidates discovered via Coturn TURN server (`turn.jugajug.com`).

---

## 4. Connectivity Test Results

| Test ID | Objective | Expected Condition | Actual Result | Status |
| :--- | :--- | :--- | :--- | :---: |
| **ICE-01** | STUN Binding Request | `srflx` candidate received | Google STUN candidate gathered | `PASS` |
| **ICE-02** | Ephemeral Token Auth | TURN server accepts HMAC | Rejected if Coturn offline | `NOT RUN` |
| **ICE-03** | UDP Media Relay | SRTP over UDP port 3478 | Requires provisioned Coturn | `NOT RUN` |
| **ICE-04** | TCP Media Relay | Fallback if UDP blocked | Requires provisioned Coturn | `NOT RUN` |
| **ICE-05** | TLS/TURNS Relay | Port 5349 encrypted relay | Requires provisioned Coturn | `NOT RUN` |
| **ICE-06** | Symmetric NAT Traversal | Force `iceTransportsType = RELAY` | Blocked until Coturn host live | `BLOCKED` |

---

## 5. Summary & Blockers

- **Signaling Contract & Parser:** `PASS`
- **Live Coturn Relaying:** `BLOCKED` (Awaiting cloud hosting approval).

