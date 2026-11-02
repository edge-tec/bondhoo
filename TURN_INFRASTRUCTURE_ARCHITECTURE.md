# CONNECTLY — ENTERPRISE TURN INFRASTRUCTURE ARCHITECTURE
## Phase 11: Distributed NAT Traversal, Coturn Topology & Managed Alternatives

| Architecture Attribute | Specification | Verification Status |
| :--- | :--- | :--- |
| **STUN / TURN RFC Compliance** | RFC 5389 (STUN), RFC 5766 (TURN), RFC 6156 (IPv6 TURN) | **PASS** (Code & Protocol Design) |
| **Transport Protocols** | UDP (Standard Low-Latency), TCP (Strict NAT / Firewall Fallback), TLS (TURNS Port 5349) | **PASS** |
| **Authentication Standard** | Ephemeral REST API Short-Lived Tokens (HMAC-SHA1) | **PASS** |
| **Selected Turn Server** | Coturn Enterprise (Self-Hosted) with Managed Fallback (Metered / Cloudflare) | **PASS** (Evaluation & Arch) |
| **Physical Cloud Relay** | Hardware Relay Data Transmission across Carrier-Grade NAT | **NOT VERIFIED** (Pending Live Coturn Node) |

---

## 1. Executive Summary & Objective

In real-world mobile telecommunication networks, between **15% and 25% of peer-to-peer WebRTC calls** encounter Symmetric NAT, Carrier-Grade NAT (CGNAT), cellular firewalls, or strict corporate proxies where direct peer-to-peer UDP hole-punching fails. Without a high-throughput, low-latency **Traversal Using Relays around NAT (TURN)** server, these calls fail during the ICE gathering and checking states.

Phase 11 architects, implements, and secures the enterprise TURN infrastructure for JUGAJUG Laravel 13 and Connectly Android.

---

## 2. Global Topology & Dual-Layer Architecture

```
                    ┌──────────────────────────────────────────────┐
                    │               Client Device A                │
                    │         (Cellular 4G/5G Symmetric NAT)       │
                    └──────────────────────┬───────────────────────┘
                                           │
                        1. STUN Request    │ 2. ICE Candidates (Host / Srflx)
                        (Port 3478 UDP)    ▼
                    ┌──────────────────────────────────────────────┐
                    │       Direct P2P Connectivity Check          │
                    │               (ICE Checking)                 │
                    └──────────────────────┬───────────────────────┘
                                           │
                     Direct Path Blocked   ▼ (Symmetric NAT / CGNAT)
                    ┌──────────────────────────────────────────────┐
                    │    FALLBACK: Relay via TURN Server           │
                    │ (UDP 3478 -> TCP 3478 -> TURNS 5349 TLS)    │
                    └──────┬────────────────────────────────┬──────┘
                           │                                │
            Encrypted Relay Stream            Encrypted Relay Stream
                           │                                │
                           ▼                                ▼
            ┌─────────────────────────────┐  ┌─────────────────────────────┐
            │   Coturn Edge Cluster 1     │  │   Managed Fallback Edge     │
            │   (turn.jugajug.com:3478)   │  │   (openrelay.metered.ca)    │
            └──────────────┬──────────────┘  └──────────────┬──────────────┘
                           │                                │
                           └───────────────┬────────────────┘
                                           │
                                           ▼
                    ┌──────────────────────────────────────────────┐
                    │               Client Device B                │
                    │            (Restricted Home Wi-Fi)           │
                    └──────────────────────────────────────────────┘
```

---

## 3. Infrastructure & Network Specifications

### A. Network & Firewall Configuration

| Port / Range | Protocol | Direction | Service Purpose |
| :--- | :--- | :--- | :--- |
| **3478** | UDP | Inbound | Standard STUN / TURN discovery and media relay (Primary) |
| **3478** | TCP | Inbound | TCP fallback for restrictive UDP-blocking enterprise firewalls |
| **5349** | TCP / TLS | Inbound | Secure TURNS over TLS (bypasses Deep Packet Inspection / DPI) |
| **49152 – 65535** | UDP | Inbound / Outbound | Dynamic ephemeral relay port allocation range (RFC 5766) |

### B. Compute, Bandwidth & Sizing Model

1. **Audio Call Bandwidth**:
   - Opus codec: ~32–40 kbps bidirectional (~80 kbps per active call session).
2. **Video Call Bandwidth**:
   - VP8/H.264 (720p @ 30fps): ~1.2 Mbps to 1.8 Mbps bidirectional.
3. **Capacity Dimensioning**:
   - For **1,000 concurrent relayed video calls**:
     $$\text{Bandwidth} = 1{,}000 \times 1.5\text{ Mbps} = 1.5\text{ Gbps outbound} + 1.5\text{ Gbps inbound} = 3.0\text{ Gbps total}$$
   - **Recommended Node Sizing**: 2x Dedicated Cloud Instances (4 vCPU, 8GB RAM, 10 Gbps network interface) deployed behind GeoDNS or Anycast BGP routing.

---

## 4. Evaluation of TURN Solutions

| Attribute | Coturn (Self-Hosted on Linux) | Cloudflare Calls | Metered / Twilio Network Traversal |
| :--- | :--- | :--- | :--- |
| **Deployment Model** | Docker / Ubuntu 24.04 LTS VM | Global Anycast Edge (Serverless) | Fully Managed SaaS |
| **Data Sovereignty** | 100% On-prem / Private Cloud | Cloudflare Distributed Network | 3rd Party Cloud |
| **Cost at Scale** | Predictable (Fixed VM + Egress) | Usage-based per minute | High per-GB / per-minute pricing |
| **Custom HMAC Auth** | Native (`use-auth-secret`) | Proprietary API | REST Token API |
| **Latency in Bangladesh** | Ultra-low (Deployable in Dhaka BDIX) | Routed via Singapore / Kolkata | Routed via regional PoPs |
| **Recommendation** | **PRIMARY for Production Core** | **Secondary Multi-Region CDN** | **DEVELOPMENT / STAGING FALLBACK** |

---

## 5. Verification Verdict

- Architecture Specification: **PASS**
- Protocol & Port Matrix Definition: **PASS**
- Sizing & Bandwidth Model: **PASS**
- Physical Server Provisioning: **NOT RUN** (Requires infrastructure deployment approval)
- Live Network Relaying: **NOT VERIFIED**

