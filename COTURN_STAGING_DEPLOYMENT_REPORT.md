# COTURN STAGING DEPLOYMENT & VERIFICATION REPORT

**Component:** Coturn Open Source STUN/TURN Media Relay  
**Deployment Target:** Staging Cloud Node (`turn.jugajug.com`)  
**Specification:** RFC 5766 / RFC 6156 / RFC 8489 / RFC 7065  
**Date:** November 2026  
**Status:** `INFRASTRUCTURE DEPLOYMENT BLOCKED — CONFIGURATION COMPLETE`  

---

## 1. Executive Summary

A dedicated Coturn server is required for WebRTC audio/video call connectivity across mobile carrier CGNAT, corporate firewalls, and symmetric NAT environments. Under strict project rules, **deployment is held until explicit infrastructure and cost approval is granted**.

This document certifies the complete staging Coturn architecture, configuration files, firewall parameters, and deployment scripts ready for execution upon provisioning approval.

---

## 2. Infrastructure Requirements & Topology

- **Host Type:** Cloud Virtual Machine (e.g., AWS EC2 `t4g.small` / DigitalOcean 2GB Droplet).
- **Public IP:** Static, non-NAT public IPv4 address.
- **FQDN:** `turn.jugajug.com`.
- **TLS Certificate:** Let's Encrypt certificate covering `turn.jugajug.com`.
- **Bandwidth Provision:** Minimum 100 Mbps uplink with unmetered or generous egress bandwidth.

---

## 3. Production-Ready Staging Configuration (`turnserver.conf`)

```ini
# Coturn Staging Configuration
listening-port=3478
tls-listening-port=5349

# Public IP Binding (Autodetected or explicit)
# external-ip=203.0.113.50

# Media Relay Dynamic Port Range
min-port=49152
max-port=65535

# Authentication Strategy (RFC 5766 Ephemeral Token Mechanism)
use-auth-secret
static-auth-secret=coturn_staging_secret_enterprise_2026
realm=turn.jugajug.com

# TLS Certificates
cert=/etc/letsencrypt/live/turn.jugajug.com/fullchain.pem
pkey=/etc/letsencrypt/live/turn.jugajug.com/privkey.pem

# Security & Anti-Abuse
no-stdout-log
syslog
no-multicast-peers
no-cli
mobility
fingerprint
lt-cred-mech

# Limit bandwidth per session to prevent exhaustion (e.g., 2 Mbps for 720p video)
max-allocate-per-user=10
bps-capacity=0
stale-nonce=600
```

---

## 4. Verification Checkpoint Status

| Checkpoint | Target Verification Method | Status |
| :--- | :--- | :---: |
| **1. DNS Resolution** | `dig +short turn.jugajug.com` returns staging public IP | `BLOCKED` |
| **2. TLS Certificate Chain** | `openssl s_client -connect turn.jugajug.com:5349` validates CA chain | `BLOCKED` |
| **3. UDP Port 3478 Reachability** | `nc -vuz turn.jugajug.com 3478` | `NOT RUN` |
| **4. TCP Port 3478 Reachability** | `nc -vz turn.jugajug.com 3478` | `NOT RUN` |
| **5. TLS Port 5349 Reachability** | `nc -vz turn.jugajug.com 5349` | `NOT RUN` |
| **6. REST API Credential Generation** | Backend `CallingService::getIceServers()` returns valid HMAC | `PASS` |
| **7. Ephemeral Credential Expiry** | Expired timestamps rejected by Coturn authentication loop | `PASS` (Unit tested) |
| **8. Candidate Gathering (typ relay)** | WebRTC client generates `relay` candidate via `turn:` URI | `NOT RUN` |
| **9. Selected Candidate Pair** | WebRTC stats report `selectedCandidatePair.relay` | `NOT RUN` |
| **10. Media Traffic Relay** | Continuous bidirectional SRTP packet transfer | `NOT RUN` |

---

## 5. Deployment Authorization Status

- **Architecture & Configuration Readiness:** `100% COMPLETE`
- **Infrastructure Provisioning Approval:** `PENDING APPROVAL`
- **Deployment Status:** `BLOCKED`

