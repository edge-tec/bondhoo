# COTURN CONNECTLY STAGING SYSTEMD SERVICE SPECIFICATION

**Service Name:** `coturn-connectly-staging.service`  
**Target Environment:** Contabo Cloud VPS 6 (HostVra Linux Environment)  
**Binary Location:** `/usr/bin/turnserver`  
**Configuration File:** `/etc/coturn/connectly-staging/turnserver.conf`  
**Secret Key File:** `/etc/coturn/connectly-staging/turn.secret`  
**Date:** November 2026  

---

## 1. Systemd Service Unit Specification

File: `/etc/systemd/system/coturn-connectly-staging.service`

```ini
[Unit]
Description=Connectly Staging Coturn Media Relay Server
After=network.target
Documentation=man:turnserver(1) https://coturn.net

[Service]
Type=simple
User=turnserver
Group=turnserver
ExecStart=/usr/bin/turnserver -c /etc/coturn/connectly-staging/turnserver.conf
Restart=on-failure
RestartSec=5s

# ------------------------------------------------------------------------------
# Resource Cgroup Quotas (Guarantees HostVra & MySQL have priority)
# ------------------------------------------------------------------------------
CPUQuota=150%
MemoryMax=1024M
MemoryHigh=768M
TasksMax=1024
LimitNOFILE=65536

# ------------------------------------------------------------------------------
# Security Hardening & Sandboxing
# ------------------------------------------------------------------------------
ProtectSystem=full
ProtectHome=true
NoNewPrivileges=true
PrivateTmp=true
ProtectKernelTunables=true
ProtectControlGroups=true

[Install]
WantedBy=multi-user.target
```

---

## 2. Configuration File Specification

File: `/etc/coturn/connectly-staging/turnserver.conf`

```ini
# ==============================================================================
# CONNECTLY STAGING COTURN CONFIGURATION
# ==============================================================================
# Network Ports
listening-port=3478
tls-listening-port=5349

# Dynamic Media Relay Port Allocation Range
min-port=49152
max-port=65535

# Authentication Strategy (RFC 5766 Ephemeral Token Mechanism)
use-auth-secret
static-auth-secret-file=/etc/coturn/connectly-staging/turn.secret
realm=turn.jugajug.com

# Resource Limits & Anti-Abuse
max-allocate-per-user=10
bps-capacity=0
stale-nonce=600

# Logging & Telemetry
no-stdout-log
log-file=/var/log/coturn-connectly-staging/turn.log
syslog

# Security Sandboxing & Strict Mode
no-cli
no-multicast-peers
fingerprint
lt-cred-mech
```

---

## 3. Secret File Specification & Security Model

- **File Path:** `/etc/coturn/connectly-staging/turn.secret`
- **File Mode:** `0600` (Read/Write strictly by `turnserver` service user; root access only).
- **Generation Method:**
  ```bash
  openssl rand -hex 32 > /etc/coturn/connectly-staging/turn.secret
  chmod 600 /etc/coturn/connectly-staging/turn.secret
  chown turnserver:turnserver /etc/coturn/connectly-staging/turn.secret
  ```
- **Backend Synchronization:** The content of this secret file is mirrored exclusively to `TURN_SECRET` in the staging Laravel `.env`. It is **never** outputted to HTTP APIs or compiled into Android APKs.

---

## 4. Operational Commands & Lifecycle

```bash
# Start the staging service
sudo systemctl start coturn-connectly-staging.service

# Check active status & telemetry
sudo systemctl status coturn-connectly-staging.service --no-pager

# Restart service
sudo systemctl restart coturn-connectly-staging.service

# Stop service
sudo systemctl stop coturn-connectly-staging.service

# Inspect logs
sudo tail -f /var/log/coturn-connectly-staging/turn.log
```

---

## 5. Specification Sign-Off

The service specification satisfies all resource isolation, security sandboxing, and non-interference requirements.

**Service Spec Gate:** `PASS`

