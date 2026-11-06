# JUGAJUG / BONDHOO — PHASE 14.2
## BACKEND ARCHITECTURAL BOUNDARY & DUAL-PRODUCT SEPARATION SPECIFICATION

**Target System:** Laravel Backend System Architecture  
**Architectural Goal:** Strict Boundary Enforcement between JUGAJUG Social and CONNECTLY Family Safety  
**Auditor:** Principal Software Architect & Application Security Engineer  
**Git Head:** `315960fde56849c4be7344a78fda005151027b42` (`main`)  
**Status:** **AUTHORITATIVE DUAL-PRODUCT ARCHITECTURE SPECIFIED**

---

### 1. TARGET DUAL-PRODUCT DOMAIN MODEL

```
+-----------------------------------------------------------------------------------+
|                        JUGAJUG / BONDHOO BACKEND ECOSYSTEM                         |
+-----------------------------------------+-----------------------------------------+
|      JUGAJUG SOCIAL PLATFORM DOMAIN     |      CONNECTLY FAMILY SAFETY DOMAIN     |
+-----------------------------------------+-----------------------------------------+
| [Core Responsibilities]                 | [Core Responsibilities]                 |
|  - User Identity & Profiles             |  - Parent & Guardian Accounts           |
|  - News Feed, Posts, Stories, Reels     |  - Child Profiles & Device Inventory    |
|  - Real-Time Messaging & WebRTC Calling |  - Ephemeral Device Pairing Handshake   |
|  - Professional Groups & Business Pages |  - Telemetry Ingestion (Usage Logs)     |
|  - Jobs Portal & Recruitment ATS        |  - Screen Time Rules & App Blockers     |
|  - Learning Management System (LMS)     |  - Remote Lock/Unlock & Geo Alerts      |
|  - Moderation & Admin Portal            |  - Family Compliance & Audit Logs       |
|                                         |                                         |
| [Authentication Boundary]               | [Authentication Boundary]               |
|  - Web: Laravel Session (Cookie/CSRF)   |  - Parent Web: Web Session / Sanctum    |
|  - API: Laravel Sanctum Bearer Tokens   |  - Child Agent: Custom SHA-256 Token    |
|    (Scoped to social abilities)         |    (AuthenticateChildDevice middleware) |
|                                         |                                         |
| [Route Namespace]                       | [Route Namespace]                       |
|  - Web: /* (routes/web.php)             |  - Web: /parental (Isolated View)       |
|  - API: /api/v1/* (routes/api.php)      |  - API: /api/v1/parental/* (routes/     |
|                                         |         parental.php)                   |
+-----------------------------------------+-----------------------------------------+
```

---

### 2. ARCHITECTURAL ISOLATION ENFORCEMENT

#### 2.1 Route & Middleware Boundary
1. **Pristine Social API (`routes/api.php`):**
   - Prefix: `/api/v1/*`
   - Guard: `auth:sanctum`
   - Child device agents are rejected with `401 Unauthorized` by Sanctum.
2. **Dedicated Parental API (`routes/parental.php`):**
   - Prefix: `/api/v1/parental/*`
   - Device Routes: Protected strictly by `auth.child_device` (`App\Http\Middleware\AuthenticateChildDevice`).
   - Family Routes: Protected strictly by `auth:sanctum` and verified against `FamilyMember` records with role `parent` or `guardian`. Ordinary social users without family ownership are rejected with `403 Forbidden`.

#### 2.2 Token Ability & Scope Segregation
To prevent token misuse across boundaries:
- **Social User Sanctum Tokens:** Created with abilities `['social:read', 'social:write', 'messenger', 'calls']`.
- **Parent Management Tokens:** Created with explicit ability `['parental:manage']`.
- **Child Device Tokens:** 256-bit entropy random strings hashed with SHA-256. They **never** interact with Sanctum or `personal_access_tokens` table.

---

### 3. MONOREPO MODULAR BOUNDARY VS STANDALONE REPOSITORY

| Evaluation Factor | Modular Monorepo (Current Recommended Architecture) | Standalone Service Extraction (Future Horizon) |
| :--- | :--- | :--- |
| **Operational Simplicity** | **High.** Single deployment pipeline, shared database engine, unified server infrastructure. | **Low.** Requires managing two server stacks, dual CI/CD, cross-service authentication. |
| **Deployment Risk** | **Zero.** Core social network is already fully insulated and 100% operational. | **Moderate.** Risk of deployment synchronization issues between social auth and parental service. |
| **Resource Footprint** | **Optimal.** PHP-FPM processes serve both domains efficiently. | **Double.** Redundant database connections, web servers, and memory pools. |
| **Recommended Strategy** | **Maintain Modular Isolation for JUGAJUG Go-Live.** | **Extract when CONNECTLY achieves independent commercial scale.** |

---

### 4. AUDIT CONCLUSION & VERDICT

- **Stage 05 Status:** **PASS**
- **Architecture Integrity:** **Enterprise Modular Isolation Formulated**
- **Verdict:** Backend architecture provides complete domain separation without introducing unnecessary operational fragility.

