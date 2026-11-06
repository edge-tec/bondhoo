# BONDHOO PHASE 14.5.2 — OWNER DECISION REQUIRED: UPLOAD SIGNING KEY REMEDIATION
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Application Security Engineer & Release Manager  
**Target:** Project Owner

---

## 1. Context of the Decision

During our deep security audit, it was verified that the Android release signing keystore:
```text
android/app/keystore/connectly-release.jks
```
was committed into the Git index on November 1, 2026 in commit `9b52f37`. 

Although the repository is **Private** on GitHub and Google Play uses **Play App Signing** (meaning this key is only an **Upload Key**, not the master key that signs user APKs), keeping a signing key in version control violates strict DevSecOps standards.

Before uploading to Google Play Console, **your explicit decision is required** on how to proceed.

---

## 2. Option A vs Option B Comparison

| Factor | Option A: Proceed with Current Key (Temporary) | Option B: Generate Fresh Upload Key (Clean Baseline) |
|---|---|---|
| **Description** | Use the currently compiled and verified `app-release.aab` (`2fa62aa6...`) for immediate Internal Testing. Untrack the keystore from Git subsequently. | Generate a brand new, uncommitted Upload Key in a secure location outside Git, recompile `app-release.aab`, and register the new key in Play Console. |
| **Immediate Schedule Impact** | **ZERO DELAY:** Can upload `app-release.aab` to Internal Testing immediately today. | **1–2 HOURS:** Requires key generation, Gradle build recompilation, and artifact re-verification. |
| **Play App Signing Protection** | **ACTIVE:** Google Play protects the user distribution key in Cloud KMS; this key is only verified when uploading AAB drafts. | **ACTIVE:** Google Play protects the user distribution key in Cloud KMS; fresh key used for uploads. |
| **History Rewriting Needed?** | **NO:** We simply run `git rm --cached` post-release so future commits don't track it. | **NO:** New key is created outside Git; old key is ignored. |
| **Security Posture** | Acceptable for **Internal Testing with trusted team**; must be resolved before public production. | **Industry Best Practice (100% Cryptographically Clean)**. |

---

## 3. How to Respond

Please reply to the assistant with one of the two choices:

- **"Proceed with Option A"**  
  *(We will keep the current verified AAB and guide you through uploading it to Google Play Console Internal Testing right now).*

- **"Authorize Option B"**  
  *(We will generate a fresh, secure Upload Key outside Git, recompile and re-verify `app-release.aab`, and deliver the updated bundle for Google Play Console upload).*

---

## 4. Current Artifact Constants (Ready for Option A)

If you choose **Option A**, the verified artifact on disk is ready:
- **File:** `android/app/build/outputs/bundle/release/app-release.aab`
- **Size:** `32,842,975 bytes` (~32.8 MB)
- **SHA-256:** `2fa62aa65ffe5aabe61363699423fbf4e8253264e7c785aaccd1092f5b3ba794`
- **Upload Key SHA-1:** `D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C`
- **Upload Key SHA-256:** `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41`

