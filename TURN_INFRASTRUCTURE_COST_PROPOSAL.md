# TURN INFRASTRUCTURE COST & SPECIFICATION PROPOSAL

**Document Type:** Infrastructure Engineering & Cost Proposal  
**Target Service:** Coturn STUN/TURN Media Relay Server  
**Audience:** Technical Management & Infrastructure Budget Approvers  
**Date:** November 2026  
**Status:** `PROPOSAL READY — PENDING EXPLICIT USER APPROVAL TO PROVISION`  

---

## 1. Executive Summary & Business Need

WebRTC calling relies on direct peer-to-peer (P2P) connections. However, approximately **18% to 25% of mobile and enterprise WebRTC calls** encounter Symmetric NAT, carrier-grade NAT (CGNAT on mobile LTE/5G), or restrictive corporate firewalls that completely block direct P2P connections. 

Without a TURN (Traversal Using Relays around NAT) server, calls between these users will fail with a black screen or dropped connection. Deploying a dedicated Coturn server ensures **100% call connectivity worldwide**.

> **MANDATORY RULE:** In compliance with project guidelines, **no paid cloud infrastructure will be provisioned or billed without explicit user authorization**. This document details the specifications and budget options for review.

---

## 2. Recommended Cloud Host Specifications

### Option A: DigitalOcean Droplet (Recommended for Staging & Initial Launch)
- **Specification:** 2 vCPU (Regular Intel or AMD), 2 GB RAM, 50 GB NVMe SSD, 2 TB Transfer / month.
- **Monthly Cost:** **$12.00 / month** (or $0.018 / hour).
- **Static IPv4:** Included at no extra cost.
- **Bandwidth Overage:** $0.01 / GB beyond 2 TB.
- **Pros:** Fast setup, predictable pricing, zero hidden egress network fees.

### Option B: AWS EC2 (`t4g.small` ARM Graviton2)
- **Specification:** 2 vCPU (ARM), 2 GB RAM, 20 GB EBS gp3 storage.
- **Monthly Compute Cost:** **$12.26 / month**.
- **Bandwidth Egress Cost:** AWS charges $0.09 / GB egress beyond the free tier. For 1 TB of relayed video traffic, network cost is ~$90.00 / month.
- **Elastic IP:** Free while instance is running.

### Option C: Google Cloud Compute Engine (`e2-small`)
- **Specification:** 2 vCPU, 2 GB RAM, 20 GB Persistent Disk.
- **Monthly Compute Cost:** **$14.50 / month**.
- **Bandwidth Egress Cost:** $0.08 - $0.12 / GB egress to Internet.

### Recommendation:
**DigitalOcean 2 GB Droplet ($12.00 / month)** is the most cost-effective solution for Controlled Staging and early production due to its bundled 2 TB of monthly network bandwidth.

---

## 3. Network Bandwidth & Capacity Calculations

### 3.1 Per-Stream Bandwidth Consumption
| Call Type | Codec | Target Bitrate | Relay Egress (Both Directions) | Bandwidth per 1 Hour Call |
| :--- | :---: | :---: | :---: | :---: |
| **Audio Only** | Opus (Wideband) | 40 kbps | 80 kbps | ~36 MB / hour |
| **Video (720p 30fps)** | VP8 / H.264 | 1,200 kbps | 2,400 kbps | ~1.08 GB / hour |

### 3.2 Monthly Relay Estimates (1,000 Users)
- Assuming 20% of calls require TURN relay (80% connect directly via STUN P2P):
  - 500 hours of relayed audio = 18 GB / month.
  - 200 hours of relayed video = 216 GB / month.
  - **Total Projected Monthly Relay Bandwidth:** ~234 GB / month (well within the 2,000 GB bundle).

### 3.3 Concurrent Call Capacity
- A 2 vCPU, 2 GB RAM Linux VM running Coturn can comfortably handle **250 to 400 concurrent relayed media streams** with `< 5% CPU utilization` due to kernel-level UDP socket routing.

---

## 4. Network & Domain Requirements

| Parameter | Configuration Requirement |
| :--- | :--- |
| **FQDN / Hostname** | `turn.jugajug.com` (Subdomain on existing JUGAJUG DNS) |
| **Public IPv4** | 1 Dedicated Static Public IP address |
| **Listening Ports** | Port `3478` (UDP & TCP) for standard STUN / TURN |
| **TLS/TURNS Port** | Port `5349` (UDP & TCP) for encrypted TLS/DTLS relay |
| **Relay Port Range** | `49152 - 65535` (UDP) for dynamic media RTP packet routing |
| **SSL/TLS Certificate**| Automated Let's Encrypt certificate via Certbot (Free) |

---

## 5. Security & Maintenance Plan

1. **Authentication:** RFC 5766 REST API dynamic HMAC-SHA1 tokens. Shared secret stored only in server `.env` (`TURN_SECRET`), never distributed to clients.
2. **Access Control:** User quotas enforced (`max-allocate-per-user=10`), stale nonces purged after 600 seconds.
3. **Monitoring:** Prometheus `node_exporter` + Coturn CLI telemetry monitoring active bandwidth and allocations.
4. **Automated Maintenance:** Unattended Ubuntu security updates enabled for kernel and Coturn packages.

---

## 6. Budget Approval Request

| Budget Line Item | Provider | Frequency | Cost (USD) |
| :--- | :--- | :---: | :---: |
| **Staging Coturn Cloud Node (2 vCPU, 2GB, 2TB BW)** | DigitalOcean / Linode | Monthly | **$12.00 / month** |
| **Let's Encrypt TLS Certificate** | EFF / Let's Encrypt | Annual | **$0.00 (Free)** |
| **DNS Subdomain (`turn.jugajug.com`)** | Existing JUGAJUG Registrar | Existing | **$0.00** |
| **Total Estimated Staging Cost** | — | — | **$12.00 / month** |

*Note: Provisioning will only commence once the user approves this proposal.*

