# JUGAJUG — PHASE 11 API V2 AUDIT & CONTRACT SPECIFICATION

## Enterprise API v2 Architecture, Contracts, Authorization & Resilience

**Date:** 2026-09-17  
**Gateway Prefix:** `/api/v2`  
**API Version Header:** `X-API-Version: 2.0.0-enterprise`  
**Correlation Header:** `X-Correlation-ID: {UUID}`  
**Middleware Pipeline:** `ApiV2Middleware`, `throttle:api` (60 req/min)

---

## 1. Complete API v2 Endpoint Inventory

| # | Endpoint | Method | Auth | Authorization / Scope | Rate Limit | Idempotency | Status |
|:---:|:---|:---:|:---:|:---|:---:|:---:|:---:|
| 1 | `/api/v2` | GET | Public | None | 60/min | No | **PASS** |
| 2 | `/api/v2/ai/chat` | POST | Public/Auth | Optional User Context | 60/min | Yes | **PASS** |
| 3 | `/api/v2/ai/suggest-comment` | POST | Public/Auth | Post validation | 60/min | Yes | **PASS** |
| 4 | `/api/v2/ai/caption` | POST | Public/Auth | Max 500 chars | 60/min | Yes | **PASS** |
| 5 | `/api/v2/ai/translate` | POST | Public/Auth | Max 5000 chars | 60/min | Yes | **PASS** |
| 6 | `/api/v2/ai/moderate` | POST | Public/Auth | Max 5000 chars | 60/min | Yes | **PASS** |
| 7 | `/api/v2/ai/citizen-services` | POST | Public/Auth | Bangladesh e-Gov query | 60/min | Yes | **PASS** |
| 8 | `/api/v2/ai/search/semantic` | GET | Public | Max 20 items | 60/min | No | **PASS** |
| 9 | `/api/v2/ai/usage` | GET | **Auth** | User scoped / Admin system | 60/min | No | **PASS** |
| 10 | `/api/v2/feed/ranked` | GET | Public/Auth | Public/Friend rank filter | 60/min | No | **PASS** |
| 11 | `/api/v2/search/hybrid` | GET | Public | Keyword + Vector search | 60/min | No | **PASS** |
| 12 | `/api/v2/live/streams` | GET | Public | Live/Ready status only | 60/min | No | **PASS** |
| 13 | `/api/v2/live/channels` | POST | **Auth** | Creator account | 60/min | Yes | **PASS** |
| 14 | `/api/v2/live/streams/{id}/start` | POST | **Auth** | **Owner Only (IDOR Guard)** | 60/min | Yes | **PASS** |
| 15 | `/api/v2/live/streams/{id}/end` | POST | **Auth** | **Owner Only (IDOR Guard)** | 60/min | Yes | **PASS** |
| 16 | `/api/v2/live/streams/{id}/comments`| POST | **Auth** | Stream active check | 60/min | Yes | **PASS** |
| 17 | `/api/v2/live/streams/{id}/gifts` | POST | **Auth** | Coin balance check | 60/min | Yes | **PASS** |
| 18 | `/api/v2/marketplace/categories` | GET | Public | Active categories | 60/min | No | **PASS** |
| 19 | `/api/v2/marketplace/products` | GET | Public | Active products, filter | 60/min | No | **PASS** |
| 20 | `/api/v2/marketplace/products` | POST | **Auth** | Seller account | 60/min | Yes | **PASS** |
| 21 | `/api/v2/marketplace/products/{id}/order` | POST | **Auth** | Active product check | 60/min | Yes | **PASS** |
| 22 | `/api/v2/marketplace/orders/{id}/release-escrow` | POST | **Auth** | **Buyer Only (IDOR Guard)** | 60/min | Yes | **PASS** |
| 23 | `/api/v2/federation/actors/{username}` | GET | Public | User lookup | 60/min | No | **PASS** |
| 24 | `/api/v2/federation/inbox` | POST | Public | Schema validation (422) | 60/min | Yes | **PASS** |
| 25 | `/api/v2/federation/users/{username}/outbox` | GET | Public | OrderedCollection | 60/min | No | **PASS** |
| 26 | `/.well-known/webfinger` | GET | Public | RFC 7033 JRD lookup | 60/min | No | **PASS** |
| 27 | `/api/v2/offline/sync` | POST | **Auth** | User batch queue | 60/min | Yes | **PASS** |
| 28 | `/api/v2/graphql` | POST | Public/Auth | GraphQL schema resolver | 60/min | No | **PASS** |

---

## 2. Authorization & IDOR/BOLA Protection Verification

### 2.1 Live Stream Owner Controls
- **Endpoint:** `POST /api/v2/live/streams/{id}/start` and `/end`
- **Guarding Logic:**
  ```php
  $stream = LiveStream::where('user_id', $request->user()->id)->findOrFail($id);
  ```
- **Validation:** Attempting to start or terminate another creator's stream key yields `404 Not Found`, completely blocking horizontal privilege escalation.

### 2.2 Marketplace Escrow Release
- **Endpoint:** `POST /api/v2/marketplace/orders/{id}/release-escrow`
- **Guarding Logic:**
  ```php
  $order = MarketplaceOrder::where('buyer_id', $request->user()->id)->findOrFail($orderId);
  ```
- **Validation:** Only the designated buyer who funded the order can release funds to the seller.

### 2.3 AI Token Usage Auditing
- **Endpoint:** `GET /api/v2/ai/usage`
- **Guarding Logic:** Unauthenticated calls return `401 Unauthorized`. Authenticated non-admin users only view their personal daily tokens; admin role is required to access system-wide financial summaries.

---

## 3. Idempotency Key Handling Verification

- **Mechanism:** Implemented in `ApiV2Middleware`.
- **Header:** `Idempotency-Key: <unique-client-key>`
- **Behavior:** Responses to state-mutating requests (`POST`, `PUT`, `PATCH`) are cached in Redis/Cache for 24 hours. Repeating identical requests returns the cached payload with header `X-Cache-Lookup: HIT (Idempotent)` without re-executing database operations.
- **Verification Evidence:** Verified by `ApiV2GatewayTest::test_idempotency_key_middleware_caches_and_replays` (Pass).

---

## 4. Contract Conformance Summary

- **Consistent JSON Format:** All responses conform to standard envelope `{ "status": "success" | "error", "data": ... }`.
- **HTTP Status Codes:** `200` (OK), `201` (Created), `202` (Accepted/Async), `401` (Unauthorized), `404` (Not Found), `422` (Unprocessable/Validation Error), `429` (Rate Limited).
- **Correlation Tracking:** `X-Correlation-ID` header is attached to every single HTTP request/response cycle for end-to-end tracing.

**API Audit Status:** **`VERIFIED & HARDENED`**
