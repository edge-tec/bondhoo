# PHASE 21 — PRODUCTION SMOKE TEST REPORT

- **Date & Time:** 2026-11-20T21:08:00+06:00
- **Production Domain:** `https://bondhoo.com`
- **Status:** **BLOCKED (Edge Health PASS; Post-Cutover Endpoints Pending HostVra Deployment)**

## 1. Live Smoke Test Matrix
| Probe | Expected | Actual | Latency | Status |
| :--- | :---: | :---: | :---: | :---: |
| `GET https://bondhoo.com` | `200` | `200` | `1.104s` | **PASS** |
| `GET https://bondhoo.com/.env` | `403/404` | `403` | `1.228s` | **PASS** |
| `GET https://bondhoo.com/robots.txt` | `200` | `200` | `0.707s` | **PASS** |
| `GET https://bondhoo.com/build/manifest.json` | `200` | `200` | `0.874s` | **PASS** |
| `GET https://bondhoo.com/api/v1/search?q=bondhoo` | `200` | `401` | `0.749s` | **BLOCKED** |
| `GET https://bondhoo.com/app/{key}` (WS Upgrade) | `101` | `404` | — | **BLOCKED** |
| `HEAD https://bondhoo.com/bondhoo.apk` | `200` | `404` | `0.833s` | **BLOCKED** |
