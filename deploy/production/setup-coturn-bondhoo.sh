#!/usr/bin/env bash
# ==============================================================================
# BONDHOO — Dedicated Production Coturn (STUN/TURN/TURNS) Setup Script (HostVra)
# Enables 99.9% reliable HD WebRTC audio/video calling across mobile CGNAT.
# Usage (as root on HostVra VPS):
#   TURN_DOMAIN=turn.bondhoo.com bash deploy/production/setup-coturn-bondhoo.sh
# ==============================================================================
set -euo pipefail

TURN_DOMAIN="${TURN_DOMAIN:-turn.bondhoo.com}"
TURN_SECRET="${TURN_SECRET:-$(openssl rand -hex 32)}"
EXTERNAL_IP="${EXTERNAL_IP:-$(curl -s4 https://api.ipify.org || true)}"

echo "=== [1/4] Installing coturn ==="
apt-get update -y
apt-get install -y coturn

sed -i 's/^#TURNSERVER_ENABLED=1/TURNSERVER_ENABLED=1/' /etc/default/coturn || true

echo "=== [2/4] Writing /etc/turnserver.conf ==="
cat > /etc/turnserver.conf <<EOF
listening-port=3478
tls-listening-port=5349
fingerprint
lt-cred-mech
use-auth-secret
static-auth-secret=${TURN_SECRET}
realm=${TURN_DOMAIN}
server-name=${TURN_DOMAIN}
total-quota=200
bps-capacity=0
stale-nonce=600
no-multicast-peers
no-loopback-peers
denied-peer-ip=10.0.0.0-10.255.255.255
denied-peer-ip=172.16.0.0-172.31.255.255
denied-peer-ip=192.168.0.0-192.168.255.255
min-port=49152
max-port=65535
log-file=/var/log/turnserver.log
simple-log
EOF

if [ -n "${EXTERNAL_IP}" ]; then
    echo "external-ip=${EXTERNAL_IP}" >> /etc/turnserver.conf
fi

if [ -f "/etc/letsencrypt/live/${TURN_DOMAIN}/fullchain.pem" ]; then
    cat >> /etc/turnserver.conf <<EOF
cert=/etc/letsencrypt/live/${TURN_DOMAIN}/fullchain.pem
pkey=/etc/letsencrypt/live/${TURN_DOMAIN}/privkey.pem
EOF
fi

echo "=== [3/4] Opening UFW ports (if UFW is active) ==="
if command -v ufw >/dev/null 2>&1 && ufw status | grep -q "Status: active"; then
    ufw allow 3478/tcp
    ufw allow 3478/udp
    ufw allow 5349/tcp
    ufw allow 5349/udp
    ufw allow 49152:65535/udp
fi

echo "=== [4/4] Enabling and starting coturn ==="
systemctl enable coturn
systemctl restart coturn
systemctl is-active coturn

echo ""
echo "Coturn is running! Add these lines to /var/www/bondhoo.com/.env and run php artisan config:cache:"
echo "  TURN_SERVER_URL=turn:${TURN_DOMAIN}:3478"
echo "  TURN_SERVER_TLS_URL=turns:${TURN_DOMAIN}:5349"
echo "  TURN_SERVER_SECRET=${TURN_SECRET}"
echo "  TURN_CREDENTIAL_TTL=86400"
