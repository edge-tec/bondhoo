# TURN DEPLOYMENT APPROVAL CHECKLIST & RUNBOOK

**Target Host:** `turn.jugajug.com`  
**Software:** Coturn Media Relay Server  
**Deployment State:** Awaiting User Cost & Provisioning Approval  
**Date:** November 2026  

---

## 1. Pre-Deployment Provisioning Checklist

Before any commands are executed on cloud infrastructure, verify all pre-requisites:

- [ ] **Infrastructure Budget Approved:** Explicit user consent obtained for cloud server hosting (~$12/month).
- [ ] **Cloud Provider Account:** Access credentials available for DigitalOcean / AWS / GCP console.
- [ ] **Static IP Allocated:** Static public IPv4 address reserved on the chosen cloud provider.
- [ ] **DNS A-Record Created:** `turn.jugajug.com` mapped to the static public IPv4 address with TTL 300s.
- [ ] **Base OS Provisioned:** Clean Ubuntu 24.04 LTS (or 22.04 LTS) server instance initialized.

---

## 2. Firewall & Ingress Port Verification Checklist

Verify cloud firewall (Security Group / UFW) rules:

| Port Range | Protocol | Ingress Direction | Source | Verified |
| :---: | :---: | :---: | :---: | :---: |
| `22` | TCP | Inbound | Authorized Bastion / Admin IP only | [ ] |
| `80` | TCP | Inbound | 0.0.0.0/0 (For Certbot HTTP-01 challenge) | [ ] |
| `3478` | UDP & TCP | Inbound | 0.0.0.0/0 (STUN / TURN listening) | [ ] |
| `5349` | UDP & TCP | Inbound | 0.0.0.0/0 (Encrypted TURNS) | [ ] |
| `49152 - 65535` | UDP | Inbound | 0.0.0.0/0 (Media packet relay range) | [ ] |

---

## 3. Server Configuration & Setup Steps (Runbook)

```bash
# Step 1: System update & Coturn installation
sudo apt-get update && sudo apt-get install -y coturn certbot

# Step 2: Acquire SSL/TLS Certificate for TURNS
sudo certbot certonly --standalone -d turn.jugajug.com --non-interactive --agree-tos -m devops@jugajug.com

# Step 3: Configure Coturn (/etc/turnserver.conf)
sudo tee /etc/turnserver.conf <<EOF
listening-port=3478
tls-listening-port=5349
min-port=49152
max-port=65535
use-auth-secret
static-auth-secret=${STAGING_TURN_SECRET}
realm=turn.jugajug.com
cert=/etc/letsencrypt/live/turn.jugajug.com/fullchain.pem
pkey=/etc/letsencrypt/live/turn.jugajug.com/privkey.pem
no-stdout-log
syslog
no-cli
no-multicast-peers
fingerprint
lt-cred-mech
EOF

# Step 4: Enable and start Coturn systemd service
sudo sed -i 's/#TURNSERVER_ENABLED=1/TURNSERVER_ENABLED=1/' /etc/default/coturn
sudo systemctl restart coturn
sudo systemctl enable coturn
```

---

## 4. Post-Deployment Verification Protocol

Execute following installation to verify operational readiness:

1. **Service Status:**
   ```bash
   sudo systemctl status coturn
   # Expected: Active (running)
   ```
2. **Port Listening Check:**
   ```bash
   sudo ss -ulnp | grep -E "3478|5349"
   # Expected: Listening on 0.0.0.0:3478 and 0.0.0.0:5349
   ```
3. **TURNS TLS Handshake:**
   ```bash
   openssl s_client -connect turn.jugajug.com:5349 -starttls turn
   # Expected: Verify return code: 0 (ok)
   ```
4. **Backend `.env` Configuration:**
   - Update `TURN_SERVER_HOST=turn.jugajug.com`
   - Update `TURN_SECRET=<ENTERPRISE_SECRET>`
   - Verify `GET /api/v1/calls/ice-servers` returns updated endpoints.

---

## 5. Formal User Approval Authorization Block

> **Pending User Action:**  
> To authorize the provisioning of this TURN node, reply with approval (e.g., *"Approved to provision Coturn on DigitalOcean at $12/month"*). Until approval is given, no cloud resources will be created.

| Approver Role | Approval Status | Date |
| :--- | :---: | :---: |
| **System Owner / Budget Authority** | `PENDING USER CONFIRMATION` | — |
| **WebRTC Infrastructure Lead** | `READY TO DEPLOY` | November 2026 |

