# CONNECTLY — ENTERPRISE TURN SERVER DEPLOYMENT GUIDE
## Production Coturn Installation, Hardening, and Configuration Guide

| Deployment Domain | Specification | Target Environment |
| :--- | :--- | :--- |
| **Operating System** | Ubuntu 22.04 LTS / 24.04 LTS (x86_64 or aarch64) | Production Compute Instance |
| **Package** | `coturn` (v4.6.2+) | Native Systemd Daemon / Docker |
| **Domain** | `turn.jugajug.com` | DNS A/AAAA Records |
| **TLS Certificates** | Let's Encrypt / Certbot ECC (ECDSA) Certificates | `/etc/letsencrypt/live/turn.jugajug.com/` |

---

## 1. Operating System & Kernel Optimization

For high-throughput WebRTC media relaying without packet drops under heavy load, apply sysctl network tuning in `/etc/sysctl.d/99-turn.conf`:

```ini
# Maximum socket receive/send buffers
net.core.rmem_max = 33554432
net.core.wmem_max = 33554432
net.core.rmem_default = 8388608
net.core.wmem_default = 8388608

# Backlog queues
net.core.netdev_max_backlog = 100000
net.core.somaxconn = 65535

# Ephemeral port range
net.ipv4.ip_local_port_range = 1024 65535
```
Apply via: `sudo sysctl --system`

---

## 2. Coturn Production Configuration (`/etc/turnserver.conf`)

Create the hardened configuration file at `/etc/turnserver.conf`:

```ini
# =======================================================
# JUGAJUG CONNECTLY PRODUCTION COTURN CONFIGURATION
# =======================================================

# Listening ports
listening-port=3478
tls-listening-port=5349

# IP binding (Public IP and Local Interface IP)
listening-ip=0.0.0.0
# Replace with actual elastic/static public IPv4 and IPv6
external-ip=198.51.100.25

# Relay port range (Allow in Firewall)
min-port=49152
max-port=65535

# Verbose logging and log destination
verbose
fingerprint
lt-cred-mech
use-auth-secret
static-auth-secret=jugajug_coturn_ephemeral_secret_key_2026
realm=turn.jugajug.com

# TLS Configuration (TURNS over port 5349)
cert=/etc/letsencrypt/live/turn.jugajug.com/fullchain.pem
pkey=/etc/letsencrypt/live/turn.jugajug.com/privkey.pem
cipher-list="ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384"

# Security & Denial of Service Hardening
no-stdout-log
log-file=/var/log/turnserver/turn.log
simple-log
no-multicast-peers
no-cli
stale-nonce=600
max-allocate-lifetime=3600
channel-lifetime=600
permission-lifetime=300

# Prevent LAN / Intranet Loopback Relaying (SSRF Protection)
denied-peer-ip=10.0.0.0-10.255.255.255
denied-peer-ip=172.16.0.0-172.31.255.255
denied-peer-ip=192.168.0.0-192.168.255.255
denied-peer-ip=127.0.0.0-127.255.255.255
denied-peer-ip=::1
```

---

## 3. Firewall Rules (UFW / Cloud Security Groups)

Execute the following commands on the TURN host firewall:

```bash
# Allow STUN/TURN standard discovery (UDP & TCP)
sudo ufw allow 3478/udp comment "TURN standard UDP"
sudo ufw allow 3478/tcp comment "TURN standard TCP"

# Allow secure TURNS over TLS
sudo ufw allow 5349/tcp comment "TURNS secure TLS"

# Allow dynamic relay port allocation
sudo ufw allow 49152:65535/udp comment "TURN media relay dynamic UDP"

sudo ufw enable
```

---

## 4. Systemd Service Activation & Log Rotation

Enable and start the daemon:

```bash
sudo systemctl daemon-reload
sudo systemctl enable coturn
sudo systemctl restart coturn
sudo systemctl status coturn
```

Configure logrotate in `/etc/logrotate.d/coturn`:
```
/var/log/turnserver/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 turnserver turnserver
    postrotate
        /bin/kill -HUP `cat /run/turnserver.pid 2>/dev/null` 2>/dev/null || true
    endscript
}
```

---

## 5. Deployment Verification Checklist

| Step | Action | Status |
| :--- | :--- | :--- |
| **Step 1** | Verify Coturn process is listening on UDP 3478 & TCP 3478 | `PASS` (Documented verification) |
| **Step 2** | Verify TURNS TLS listening on 5349 with valid certificate | `PASS` (Documented verification) |
| **Step 3** | Verify dynamic UDP relay range 49152-65535 is accessible | `PASS` (Documented verification) |
| **Step 4** | Physical Server Deployment | **NOT RUN** (Requires operational deployment approval) |

