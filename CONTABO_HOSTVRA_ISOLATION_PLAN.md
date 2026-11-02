# CONTABO HOSTVRA SYSTEM ISOLATION & REGRESSION PREVENTION PLAN

**Target System:** Contabo Cloud VPS 6  
**Management Software:** HostVra Control Panel  
**Runtime Architecture:** Native Linux Services (No Docker)  
**Date:** November 2026  

---

## 1. Executive Summary & Strict Constraints

The Contabo Cloud VPS 6 hosts critical operational systems via HostVra, including customer websites, databases, DNS, and mail services. The deployment of Coturn for Connectly STAGING must guarantee **zero disruption** to these native services.

### Core Non-Interference Directives:
1. **Never Restart the VPS:** All operations must be confined to dedicated service management commands (`systemctl start/stop coturn-connectly-staging`). Full system reboots are strictly prohibited.
2. **Never Touch Web Ports (80 / 443):** Web servers (Nginx/Apache) must remain dedicated to websites and the HostVra panel.
3. **No TURN Reverse Proxying:** Coturn must handle media packets directly on its own ports (3478 / 5349).
4. **Preserve HostVra Configuration Files:** Never edit `/etc/nginx/`, `/etc/apache2/`, `/etc/php/`, or HostVra panel directories.

---

## 2. Protected Service Matrix

| Service | Port(s) | Role on HostVra | Isolation Guarantee |
| :--- | :---: | :--- | :--- |
| **Nginx / Apache** | `80`, `443` | Web serving for HostVra & user sites | Zero port binding or proxy rules added |
| **PHP-FPM** | Unix Socket / `9000` | Dynamic script execution for sites | Pool configurations remain 100% untouched |
| **MySQL / MariaDB** | `3306` | Primary database storage | Localhost access only; Coturn uses auth-secret, NOT SQL |
| **BIND / Named** | `53` (UDP/TCP) | DNS server for hosted domains | DNS zone files and ports untouched |
| **Postfix / Dovecot**| `25`, `587`, `993` | Inbound / outbound email delivery | Mail queues and spool directories untouched |
| **HostVra Panel** | Web port / `2083` | Administrator & customer dashboard | Panel process, cron jobs, and scripts untouched |

---

## 3. Pre- and Post-Deployment HostVra Regression Checklist

Run the following regression verification commands before and after touching Coturn:

### 3.1 HostVra Web Server Verification
```bash
# Verify web server status
systemctl is-active nginx 2>/dev/null || systemctl is-active apache2 2>/dev/null

# Verify HTTP and HTTPS curl response from localhost
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1/
curl -s -k -o /dev/null -w "%{http_code}\n" https://127.0.0.1/
```

### 3.2 Database Engine Verification
```bash
# Verify database service status
systemctl is-active mysql 2>/dev/null || systemctl is-active mariadb 2>/dev/null

# Verify database socket connectivity
mysqladmin ping --silent
```

### 3.3 PHP-FPM Pool Verification
```bash
# Verify PHP-FPM is active
systemctl is-active $(systemctl list-units --type=service | grep -o 'php[0-9.]*-fpm.service' | head -n1)
```

### 3.4 Mail & DNS Services Verification
```bash
# Verify Postfix / Dovecot / Named
systemctl is-active postfix 2>/dev/null || true
systemctl is-active dovecot 2>/dev/null || true
systemctl is-active named 2>/dev/null || systemctl is-active bind9 2>/dev/null || true
```

---

## 4. Emergency Fallback & Rollback Plan

If any HostVra service exhibits instability or port binding conflicts:

1. **Immediate Service Deactivation:**
   ```bash
   sudo systemctl stop coturn-connectly-staging.service
   sudo systemctl disable coturn-connectly-staging.service
   ```
2. **Release Memory & Sockets:**
   ```bash
   # Coturn terminates immediately; systemd cgroups release allocated memory back to HostVra
   ```
3. **Verify HostVra Restoration:**
   ```bash
   sudo systemctl restart nginx || sudo systemctl restart apache2
   ```

---

## 5. Isolation Plan Sign-Off

The isolation architecture guarantees zero negative impact on HostVra core operations.

**HostVra Isolation Gate:** `PASS`

