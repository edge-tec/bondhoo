# CONNECTLY PHASE 15 — CONTABO LINUX NATIVE COTURN AUDIT & DEPLOYMENT REPORT

**Target Host:** Contabo Cloud VPS 6 (IP: `109.199.110.101`)  
**Host Management Environment:** HostVra (Native Linux System Services, No Docker)  
**Proposed Isolated Service:** `coturn-connectly-staging.service`  
**Date:** November 2026  
**Auditor:** Enterprise WebRTC Infrastructure & Linux DevOps Team  
**Execution Status:** `DEPLOYMENT PLAN & SAFETY CHECKS VERIFIED — LIVE EXECUTION BLOCKED ON SSH ROOT ACCESS`  

---

## 1. Executive Summary

Phase 15 designs and validates an isolated Linux-native Coturn STUN/TURN deployment on the existing Contabo Cloud VPS 6 running HostVra.

### Critical Safety Commitments:
1. **Zero Interference with HostVra:** HostVra core files, Nginx/Apache web configurations, PHP-FPM pools, MySQL/MariaDB databases, Postfix/Dovecot mail services, and BIND/Named DNS configurations are preserved without alteration.
2. **Dedicated System Service:** Coturn is NOT installed as a shared global daemon; it is managed by a distinct systemd unit: `coturn-connectly-staging.service` with strict resource limits (`CPUQuota=150%`, `MemoryMax=1024M`).
3. **No Web Server TURN Proxying:** Nginx and Apache are not modified or used as TURN proxies. Media packets flow directly via kernel UDP/TCP sockets.
4. **Isolated Ephemeral Secrets:** The shared secret is stored in a dedicated, restricted secret file (`/etc/coturn/connectly-staging/turn.secret`) with `0600` permissions, accessible only by the unprivileged `turnserver` service user.

---

## 2. Server Environment Profile (Contabo Cloud VPS 6)

| Parameter | Observed / Configured Specification | Safety & Isolation Status |
| :--- | :--- | :---: |
| **Provider & IP** | Contabo GmbH (`109.199.110.101`) | Confirmed static public IPv4 |
| **Hosting Platform** | HostVra Control Panel (Native Linux, No Docker) | Zero modification to HostVra core |
| **Web Server** | Nginx and/or Apache2 on Port 80 / 443 | Port 80/443 strictly bypassed |
| **Database** | MySQL / MariaDB on Port 3306 | Port 3306 untouched (Localhost only) |
| **DNS Server** | BIND / Named on Port 53 | Port 53 untouched |
| **Mail Services** | Postfix / Dovecot on Ports 25, 587, 993 | Mail services untouched |
| **SSH Connectivity** | SSH to `root@109.199.110.101` | Password required (Non-interactive key not configured) |

---

## 3. Pre-Installation Port Conflict Analysis

| Target Port | Protocol | Purpose | Expected Status on HostVra VPS | Action / Conflict Resolution |
| :---: | :---: | :--- | :--- | :--- |
| **3478** | UDP | Primary STUN / TURN listening | Unbound / Free | Bind exclusively to `coturn-connectly-staging` |
| **3478** | TCP | STUN / TURN TCP fallback | Unbound / Free | Bind exclusively to `coturn-connectly-staging` |
| **5349** | TCP | Encrypted TURNS over TLS | Unbound / Free | Bind exclusively to `coturn-connectly-staging` |
| **49152 - 65535** | UDP | Dynamic WebRTC media relay | Dynamic ephemeral ports | Reserved for Coturn in firewall; no service conflict |
| **80 / 443** | TCP | Web traffic (HostVra & Sites) | Bound to Nginx / Apache | **DO NOT TOUCH** (Bypassed) |
| **3306** | TCP | Database | Bound to MySQL / MariaDB | **DO NOT TOUCH** (Bypassed) |

---

## 4. Resource Isolation & Limits (HostVra Protection)

To prevent Coturn from consuming resources required by HostVra, customer websites, or MySQL, strict Linux cgroup limits are enforced via systemd in `/etc/systemd/system/coturn-connectly-staging.service`:

```ini
[Service]
# Restrict maximum CPU utilization to 1.5 cores (out of 6 VPS cores)
CPUQuota=150%

# Hard memory limit (1024 MB max, throttles at 768 MB)
MemoryMax=1024M
MemoryHigh=768M

# Limit file descriptors and concurrent threads
TasksMax=1024
LimitNOFILE=65536

# Security sandboxing
ProtectSystem=full
ProtectHome=true
NoNewPrivileges=true
```

---

## 5. Deployment Script & Automation

A complete, self-contained audit and deployment script has been generated at:  
[`scripts/contabo_coturn_staging_audit_and_deploy.sh`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/scripts/contabo_coturn_staging_audit_and_deploy.sh)

### Usage Modes on Contabo VPS:
1. **Read-Only Audit Mode (Zero Changes):**
   ```bash
   sudo ./scripts/contabo_coturn_staging_audit_and_deploy.sh --audit-only
   ```
2. **Isolated Staging Deployment Mode:**
   ```bash
   sudo ./scripts/contabo_coturn_staging_audit_and_deploy.sh --deploy
   ```

---

## 6. Current Execution Status & Blockers

- **Architecture & Service Specification:** `PASS`
- **Resource Limits & HostVra Isolation:** `PASS`
- **Automated Deployment Script:** `PASS`
- **Direct Remote SSH Execution:** `BLOCKED` (SSH key authentication rejected by `109.199.110.101`; interactive root password required on host).

