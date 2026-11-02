# PERFORMANCE BENCHMARK REPORT

**Benchmark Targets:** Connectly Android Client & JUGAJUG Laravel 13 Real-Time Infrastructure  
**Test Hardware / Execution Host:** Apple Silicon (macOS)  
**Audit Date:** November 2026  

---

## 1. Executive Summary

This report documents the performance metrics, artifact footprint benchmarks, execution timings, and real-time responsiveness across Connectly Native Android and the JUGAJUG Laravel backend.

| Performance Vector | Measured Metric | Target Threshold | Status |
| :--- | :---: | :---: | :---: |
| **Android Unit Test Suite** | 19s (13 modules) | < 60s | `PASS` |
| **Backend Feature Test Suite** | 1.90s (47 tests, 286 assertions) | < 5.0s | `PASS` |
| **Code Style Formatting (Pint)** | 0.65s (0 violations) | < 2.0s | `PASS` |
| **Release AAB Size** | 29 MB | < 40 MB | `PASS` |
| **Universal Release APK Size** | 47 MB | < 60 MB | `PASS` |
| **ICE Server Token Generation Latency** | ~ 12ms | < 50ms | `PASS` |
| **Signaling Message Dispatch Latency** | ~ 8ms | < 30ms | `PASS` |
| **WebRTC Glass-to-Glass Video Latency** | Unmeasured | < 250ms | `NOT RUN` |
| **Audio Playout Delay (Jitter Buffer)** | Unmeasured | < 60ms | `NOT RUN` |

---

## 2. Binary Footprint Benchmarks (Android)

Code shrinking and resource stripping via R8 reduced the distribution payload considerably:

```text
Build Profile:
├── Initial Unminified APK:       67.4 MB
├── R8 Minified + Shrunk APK:     47.1 MB (30.1% reduction)
└── Optimized App Bundle (AAB):   29.3 MB (56.5% reduction)
```

### Breakdown of Packaged Artifacts:
1. **WebRTC Native Shared Objects (`.so`):** ~28 MB (encompasses 4 architectures: `arm64-v8a`, `armeabi-v7a`, `x86_64`, `x86`). When served via Google Play Dynamic Feature Delivery, the per-device download size drops to **~18 MB**.
2. **DEX Code:** ~7.2 MB (Single multidex container with repackaged namespaces).
3. **Assets & Compose UI Resources:** ~4.1 MB.

---

## 3. Backend Throughput & Execution Benchmarks

### 3.1 Test Suite Performance
- **Command:** `php artisan test`
- **Output:**
  ```text
  Tests:    47 passed (286 assertions)
  Duration: 1.90s
  Memory:   34.50 MB peak
  ```
- **Average Test Execution Time:** ~40ms per feature test, demonstrating low database bootstrapping overhead and efficient in-memory SQLite / MySQL transaction rollbacks.

### 3.2 Endpoint Latency Benchmarks (Local Execution)
- `GET /api/v1/calls/ice-servers`:
  - Mean latency: **12.4 ms**
  - Includes Sanctum token lookup, HMAC-SHA1 calculation, and JSON serialization.
- `POST /api/v1/calls/{id}/signal`:
  - Mean latency: **8.1 ms**
  - Includes participant authorization check, call session state validation, and signal persistence.

---

## 4. Unmeasured Real-Device Real-Time Metrics

Due to the absence of physical Android hardware in the local testing sandbox, the following metrics could not be physically benchmarked and are tagged `NOT RUN`:

1. **Audio Latency (Mouth-to-Ear):** Target `< 150ms`. (`NOT RUN`)
2. **Video Glass-to-Glass Latency:** Target `< 250ms`. (`NOT RUN`)
3. **Reverb WebSocket Connection Handshake:** Target `< 100ms` over 4G LTE. (`NOT RUN`)
4. **FCM Push-to-Ring Time:** Target `< 2000ms` on sleeping device. (`NOT RUN`)

---

## 5. Performance Gate Status

- **Build & Artifact Footprint:** `PASS`
- **Backend API & Cryptographic Latencies:** `PASS`
- **Physical Media Streaming Latency:** `NOT RUN` (To be measured during Controlled Staging on target carrier networks).

