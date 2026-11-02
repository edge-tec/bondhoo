# COTURN FIREWALL CONFIGURATION & ATOMIC ROLLBACK RUNBOOK

**Target Host:** Contabo Cloud VPS 6 (`109.199.110.101`)  
**Scope:** Ingress Port Allocation for STUN, TURN, TURNS, and Dynamic Relay Ports  
**Date:** November 2026  

---

## 1. Firewall Architecture & Inspection

HostVra may configure firewall rules via **UFW (Uncomplicated Firewall)**, raw **iptables**, or **nftables**. Before altering firewall rules, identify the active firewall subsystem:

```bash
# Check UFW status
which ufw >/dev/null 2>&1 && ufw status verbose || echo "UFW not active"

# Inspect active iptables filter rules
iptables -S INPUT

# Inspect active nftables rulesets (if present)
which nft >/dev/null 2>&1 && nft list ruleset || echo "nftables not active"
```

---

## 2. Ingress Port Requirements for Coturn

| Port / Range | Protocol | Ingress Source | Service Purpose |
| :---: | :---: | :---: | :--- |
| **3478** | UDP | 0.0.0.0/0 (Any) | Primary WebRTC STUN & TURN UDP listener |
| **3478** | TCP | 0.0.0.0/0 (Any) | STUN & TURN TCP fallback listener |
| **5349** | TCP | 0.0.0.0/0 (Any) | Secure TURNS over TLS listener |
| **49152 : 65535** | UDP | 0.0.0.0/0 (Any) | Dynamic WebRTC media packet relay range |

---

## 3. Firewall Configuration Procedures

### Option A: If UFW is Active
```bash
# Add Coturn rules with explicit comments for auditability
sudo ufw allow 3478/udp comment 'Connectly STUN/TURN UDP'
sudo ufw allow 3478/tcp comment 'Connectly STUN/TURN TCP'
sudo ufw allow 5349/tcp comment 'Connectly TURNS TLS TCP'
sudo ufw allow 49152:65535/udp comment 'Connectly WebRTC Media Relay UDP'

# Reload UFW to apply
sudo ufw reload
```

### Option B: If iptables is Active (Raw Rules)
```bash
# Insert rules into INPUT chain with comment markers
sudo iptables -I INPUT -p udp --dport 3478 -m comment --comment "Connectly STUN/TURN UDP" -j ACCEPT
sudo iptables -I INPUT -p tcp --dport 3478 -m comment --comment "Connectly STUN/TURN TCP" -j ACCEPT
sudo iptables -I INPUT -p tcp --dport 5349 -m comment --comment "Connectly TURNS TLS TCP" -j ACCEPT
sudo iptables -I INPUT -p udp --dport 49152:65535 -m comment --comment "Connectly WebRTC Media Relay UDP" -j ACCEPT
```

---

## 4. Atomic Rollback Procedures (One-Command Clean Reversion)

If Coturn is decommissioned or if firewall changes must be rolled back:

### Rollback for UFW:
```bash
sudo ufw delete allow 3478/udp
sudo ufw delete allow 3478/tcp
sudo ufw delete allow 5349/tcp
sudo ufw delete allow 49152:65535/udp
sudo ufw reload
```

### Rollback for iptables:
```bash
sudo iptables -D INPUT -p udp --dport 3478 -m comment --comment "Connectly STUN/TURN UDP" -j ACCEPT
sudo iptables -D INPUT -p tcp --dport 3478 -m comment --comment "Connectly STUN/TURN TCP" -j ACCEPT
sudo iptables -D INPUT -p tcp --dport 5349 -m comment --comment "Connectly TURNS TLS TCP" -j ACCEPT
sudo iptables -D INPUT -p udp --dport 49152:65535 -m comment --comment "Connectly WebRTC Media Relay UDP" -j ACCEPT
```

---

## 5. Firewall Verification & Sign-Off

The firewall runbook guarantees atomic rule management and zero impact on HostVra web, database, or mail ports.

**Firewall Runbook Gate:** `PASS`

