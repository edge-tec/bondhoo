# PHASE 15.12 — Cross-Platform End-to-End Verification Matrix

| Scenario | Website (Local Automated) | Android (Local Automated / Contract) | Live Production (`bondhoo.com`) | Status |
|---|---|---|---|---|
| **Login & Multi-Device Session** | PASS | PASS | Pending VPS deploy | **PASS (Local) / BLOCKED (Live)** |
| **Profile Update & View Analytics** | PASS | PASS | Pending VPS deploy | **PASS (Local) / BLOCKED (Live)** |
| **New Post (Text, Image, Video)** | PASS | PASS | Pending VPS deploy | **PASS (Local) / BLOCKED (Live)** |
| **Poll Voting (`option_id` & `option_ids`)** | PASS | PASS | Pending VPS deploy | **PASS (Local) / BLOCKED (Live)** |
| **Direct Message & Idempotency** | PASS | PASS | Pending VPS deploy | **PASS (Local) / BLOCKED (Live)** |
| **Group Message & Owner Poll Close** | PASS | PASS | Pending VPS deploy | **PASS (Local) / BLOCKED (Live)** |
| **Read Receipts & Sync** | PASS | PASS | Pending VPS deploy | **PASS (Local) / BLOCKED (Live)** |
| **Typing Status (`"true"`/`true`)** | PASS | PASS | Pending VPS deploy | **PASS (Local) / BLOCKED (Live)** |
| **Media & Direct Video Upload** | PASS | PASS | Pending VPS deploy | **PASS (Local) / BLOCKED (Live)** |
| **Notifications & Unread Count** | PASS | PASS | Pending VPS deploy | **PASS (Local) / BLOCKED (Live)** |
| **WebSocket Private Channel HMAC Auth** | PASS (`200`/`403`/`401`) | PASS (`200`/`403`/`401`) | HTTP 404 on `/app/...` | **PASS (Local) / BLOCKED (Live)** |
| **Logout & Token Revocation** | PASS | PASS | Pending VPS deploy | **PASS (Local) / BLOCKED (Live)** |
