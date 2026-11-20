# PHASE 20 — PRODUCTION VPS DEPLOYMENT REPORT

**Project:** Bondhoo Social Platform
**Production Domain:** `https://bondhoo.com`
**Target VPS:** HostVra (`/var/www/bondhoo.com`, `2a02:c207:2327:8704::1`)
**Execution Timestamp:** 2026-11-20T20:42:56+06:00
**Final Status:** **BLOCKED (Direct Local SSH Unreachable; Awaiting Operator Execution in HostVra Web Terminal)**

---

## 1. Live Production Verification Probes

| Endpoint | HTTP Code | Response Time | Interpretation | Status |
| :--- | :---: | :---: | :--- | :---: |
| `GET https://bondhoo.com` | `200` | `1.248s` | Live homepage healthy | **PASS** |
| `GET https://bondhoo.com/.env` | `403` | `0.754s` | `.env` blocked from public access | **PASS** |
| `GET https://bondhoo.com/robots.txt` | `200` | `1.271s` | Static root asset served | **PASS** |
| `GET https://bondhoo.com/build/manifest.json` | `200` | `1.173s` | Vite build manifest served | **PASS** |
| `GET https://bondhoo.com/api/v1/search?q=bondhoo` | `401` | `0.513s` | Live VPS still on pre-`4295874` commit | **BLOCKED** |
| `GET https://bondhoo.com/app/connectly_reverb_key` | `404` | `0.693s` | Reverb & Nginx `/app` proxy not yet activated on VPS | **BLOCKED** |
| `HEAD https://bondhoo.com/bondhoo.apk` | `404` | `1.509s` | Signed APK not yet uploaded to VPS | **BLOCKED** |

---

## 2. Exact HostVra Web Terminal Cutover Command

```bash
cd /var/www/bondhoo.com && git fetch origin && git pull --ff-only origin main && ./deploy/production/cutover-hostvra.sh
```
