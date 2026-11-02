#!/usr/bin/env bash
# ==============================================================================
# CONNECTLY PHASE 15: NATIVE LINUX COTURN AUDIT & STAGING DEPLOYMENT ON CONTABO
# Target: Contabo Cloud VPS 6 running HostVra (Native Linux, No Docker)
# Proposed Service: coturn-connectly-staging.service
# ==============================================================================
set -euo pipefail

MODE="${1:---audit-only}"
LOG_FILE="/var/log/connectly-coturn-staging-install.log"

echo "================================================================="
echo " CONNECTLY PHASE 15 — CONTABO HOSTVRA COTURN DEPLOYMENT"
echo " Execution Mode: ${MODE}"
echo " Timestamp: $(date -u +"%Y-%m-%dT%H:%M:%SZ")"
echo "================================================================="

# ------------------------------------------------------------------------------
# STEP 1: PRE-INSTALLATION SYSTEM & HOSTVRA REGRESSION CHECKS
# ------------------------------------------------------------------------------
echo ">>> [1/7] Running Read-Only System & HostVra Pre-checks..."

echo "--- 1.1 Running System Services ---"
systemctl --type=service --state=running | grep -E "(nginx|apache|httpd|php|mysql|mariadb|named|bind|postfix|dovecot|hostvra)" || true

echo "--- 1.2 System Memory & Disk ---"
free -h
df -h /

echo "--- 1.3 System Uptime & Load ---"
uptime

echo "--- 1.4 Network Interfaces & Routing ---"
ip -brief address
ip route

echo "--- 1.5 Active Port Bindings (TCP/UDP) ---"
ss -tulpn | grep -E "(:80 |:443 |:3478 |:5349 |:3306 |:6379 |:53 |:25 )" || true

# ------------------------------------------------------------------------------
# STEP 2: PORT CONFLICT AUDIT FOR WEBRTC TURN
# ------------------------------------------------------------------------------
echo ">>> [2/7] Checking Port Conflicts for Coturn (3478, 5349, Relay Ports)..."

CONFLICT=0
for port in 3478 5349; do
    if ss -tulpn | grep -q ":${port} "; then
        echo " [!] CONFLICT DETECTED on port ${port}:"
        ss -tulpn | grep ":${port} "
        CONFLICT=1
    else
        echo " [OK] Port ${port} (TCP/UDP) is currently FREE."
    fi
done

# Check dynamic relay range (49152 - 65535) sample
RELAY_OCCUPIED=$(ss -u -a | awk '{print $5}' | grep -E ":(4915[2-9]|491[6-9][0-9]|49[2-9][0-9]{2}|5[0-9]{4}|6[0-5][0-5][0-3][0-5])" | wc -l || echo 0)
echo " [i] Dynamic relay ports (49152-65535) active UDP sockets: ${RELAY_OCCUPIED}"

if [ "${CONFLICT}" -eq 1 ]; then
    echo " [ERROR] Port conflict detected! Aborting deployment to preserve existing HostVra services."
    exit 1
fi

if [ "${MODE}" == "--audit-only" ]; then
    echo ">>> Audit completed successfully. Exiting without system modifications."
    exit 0
fi

# ------------------------------------------------------------------------------
# STEP 3: INSTALL COTURN PACKAGE WITHOUT OVERWRITING EXISTING CONFIGS
# ------------------------------------------------------------------------------
echo ">>> [3/7] Installing Coturn from Distribution Repositories..."
if ! command -v turnserver >/dev/null 2>&1; then
    apt-get update -qq
    DEBIAN_FRONTEND=noninteractive apt-get install -y -qq coturn
else
    echo " Coturn binary already installed: $(which turnserver)"
fi

# Ensure default turnserver.service is disabled so it doesn't conflict with our staging service
systemctl stop coturn 2>/dev/null || true
systemctl disable coturn 2>/dev/null || true

# ------------------------------------------------------------------------------
# STEP 4: CONFIGURE ISOLATED STAGING COTURN SERVICE
# ------------------------------------------------------------------------------
echo ">>> [4/7] Creating Isolated Staging Configuration..."

mkdir -p /etc/coturn/connectly-staging
mkdir -p /var/log/coturn-connectly-staging
chown -R turnserver:turnserver /var/log/coturn-connectly-staging 2>/dev/null || chown -R nobody:nogroup /var/log/coturn-connectly-staging

# Secure Secret File (Restrictive 0600 permissions)
SECRET_FILE="/etc/coturn/connectly-staging/turn.secret"
if [ ! -f "${SECRET_FILE}" ]; then
    openssl rand -hex 32 > "${SECRET_FILE}"
    chmod 600 "${SECRET_FILE}"
    chown turnserver:turnserver "${SECRET_FILE}" 2>/dev/null || true
fi
STAGING_SECRET=$(cat "${SECRET_FILE}")

CONFIG_FILE="/etc/coturn/connectly-staging/turnserver.conf"
cat > "${CONFIG_FILE}" <<EOF
# ==============================================================================
# CONNECTLY STAGING COTURN CONFIGURATION (ISOLATED HOSTVRA COMPLIANT)
# ==============================================================================
listening-port=3478
tls-listening-port=5349

# Network Bindings
min-port=49152
max-port=65535

# Authentication via Shared Secret (RFC 5766 REST API)
use-auth-secret
static-auth-secret=${STAGING_SECRET}
realm=turn.jugajug.com

# Resource Limits (HostVra Protection)
max-allocate-per-user=10
bps-capacity=0
stale-nonce=600

# Security & Isolation
no-stdout-log
log-file=/var/log/coturn-connectly-staging/turn.log
syslog
no-cli
no-multicast-peers
fingerprint
lt-cred-mech
EOF

chmod 640 "${CONFIG_FILE}"

# ------------------------------------------------------------------------------
# STEP 5: CREATE DEDICATED SYSTEMD UNIT WITH RESOURCE LIMITS
# ------------------------------------------------------------------------------
echo ">>> [5/7] Installing coturn-connectly-staging.service Unit..."

SERVICE_FILE="/etc/systemd/system/coturn-connectly-staging.service"
cat > "${SERVICE_FILE}" <<EOF
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

# Resource Isolation & Protection for HostVra
CPUQuota=150%
MemoryMax=1024M
MemoryHigh=768M
TasksMax=1024
LimitNOFILE=65536

# Security Sandboxing
ProtectSystem=full
ProtectHome=true
NoNewPrivileges=true

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable coturn-connectly-staging.service
systemctl restart coturn-connectly-staging.service

# ------------------------------------------------------------------------------
# STEP 6: CONFIGURE FIREWALL (UFW OR IPTABLES) SAFELY
# ------------------------------------------------------------------------------
echo ">>> [6/7] Applying Safe Firewall Ingress Rules..."

if command -v ufw >/dev/null 2>&1 && ufw status | grep -q "Status: active"; then
    echo " Configuring UFW rules..."
    ufw allow 3478/tcp comment 'Connectly STUN/TURN TCP'
    ufw allow 3478/udp comment 'Connectly STUN/TURN UDP'
    ufw allow 5349/tcp comment 'Connectly TURNS TLS TCP'
    ufw allow 49152:65535/udp comment 'Connectly WebRTC Media Relay UDP'
    ufw reload
elif command -v iptables >/dev/null 2>&1; then
    echo " Configuring iptables rules directly..."
    iptables -I INPUT -p tcp --dport 3478 -j ACCEPT -m comment --comment "Connectly STUN/TURN TCP"
    iptables -I INPUT -p udp --dport 3478 -j ACCEPT -m comment --comment "Connectly STUN/TURN UDP"
    iptables -I INPUT -p tcp --dport 5349 -j ACCEPT -m comment --comment "Connectly TURNS TLS TCP"
    iptables -I INPUT -p udp --dport 49152:65535 -j ACCEPT -m comment --comment "Connectly WebRTC Media Relay UDP"
fi

# ------------------------------------------------------------------------------
# STEP 7: POST-DEPLOYMENT VERIFICATION & HOSTVRA REGRESSION CHECK
# ------------------------------------------------------------------------------
echo ">>> [7/7] Verifying Service & HostVra Health..."

sleep 2
systemctl status coturn-connectly-staging.service --no-pager

echo "--- Verifying Port Listeners ---"
ss -tulpn | grep -E "(:3478|:5349)"

echo "--- Verifying HostVra Core Services are Running ---"
systemctl is-active nginx 2>/dev/null || systemctl is-active apache2 2>/dev/null || echo "Web server check"
systemctl is-active mysql 2>/dev/null || systemctl is-active mariadb 2>/dev/null || echo "DB check"

echo "================================================================="
echo " CONNECTLY STAGING COTURN DEPLOYMENT COMPLETE"
echo " Shared Secret: (Saved in ${SECRET_FILE})"
echo " Service Name: coturn-connectly-staging.service"
echo " Configuration: ${CONFIG_FILE}"
echo "================================================================="
EOF

