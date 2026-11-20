# PHASE 17 — GIT BASELINE & SECRET AUDIT REPORT

**Project:** Bondhoo Social Platform  
**Timestamp:** 2026-11-20T20:17:00+06:00  
**Starting Phase 16 Commit:** `4aa8f72cb28c291d7bbcef0ae830345394611cff`  
**Parent Commit:** `dcff63a83bbde1b69c092faac00da7a96b9717ec`  
**Previous Phase 15 Baseline:** `ebb853c`  
**Branch:** `main` (`origin/main`)  
**Final Status:** **PASS**

---

## 1. Git Baseline & Remote Synchronization Verification

**Commands Executed:**
```bash
git fetch origin
git status --short --branch
git rev-parse HEAD
git rev-parse origin/main
git log --oneline -n 6
git ls-files "*.jks" "*.keystore" "keystore.properties" ".env*"
```

| Verification Item | Expected | Actual Verified Output | Status |
| :--- | :--- | :--- | :---: |
| **Current Branch** | `main` | `## main...origin/main` | **PASS** |
| **Local `HEAD` Commit** | `4aa8f72cb28c291d7bbcef0ae830345394611cff` | `4aa8f72cb28c291d7bbcef0ae830345394611cff` | **PASS** |
| **Remote `origin/main` Commit** | `4aa8f72cb28c291d7bbcef0ae830345394611cff` | `4aa8f72cb28c291d7bbcef0ae830345394611cff` | **PASS** |
| **Parent Commit (`HEAD~1`)** | `dcff63a83bbde1b69c092faac00da7a96b9717ec` | `dcff63a83bbde1b69c092faac00da7a96b9717ec` | **PASS** |
| **Tracked `.env` Files** | Only `.env.example` / `.env.production.example` | `.env.example`, `.env.production.example` (`0` real `.env` files tracked) | **PASS** |
| **Tracked `.jks` / `.keystore` / `keystore.properties` in `HEAD`** | `0` files | `0` files (`android/app/keystore/connectly-release.jks` untracked in `4aa8f72`) | **PASS** |
| **`.gitignore` Coverage** | Excludes `.env*`, `*.jks`, `*.keystore`, `keystore.properties`, `*.apk`, `/public/bondhoo.apk` | Verified in `.gitignore` lines 3–5, 35–40, 45–46 | **PASS** |

---

## 2. Historical Keystore Exposure Assessment & Safe Key-Rotation Plan (Phase 17.1 Item 9–10)

### 2.1 Historical Audit Finding
- Commit `9b52f37d07d242621b3d7eb4a4c38ec43e43e58a` (Nov 1, 2026 — Phase 11) historically committed `android/app/keystore/connectly-release.jks` and fallback password strings in `android/app/build.gradle.kts`.
- In Phase 14 (`19ffd53`), the hardcoded password strings were removed from `android/app/build.gradle.kts` in favor of `android/keystore.properties` / `BONDHOO_UPLOAD_*` environment variables.
- In Phase 16 (`dcff63a` and `4aa8f72`), the debug keystore fallback was removed from `android/app/build.gradle.kts`, and `android/app/keystore/connectly-release.jks` was removed from the Git index (`git rm --cached`) while remaining on local disk.

### 2.2 Risk Assessment & Play Store Continuity Key-Rotation Plan
Per Phase 17.1 instruction (*"Do not rewrite shared Git history or rotate production signing keys without explicit authorization. If the production keystore was exposed, assess the risk and prepare a safe key-rotation/revocation plan without destroying the existing Play Store signing continuity"*):
1. **Repository Access Scope:** The GitHub repository (`git@github.com:edge-tec/bondhoo.git`) is private to the organization, limiting external exposure.
2. **Preserving Play Store Signing Continuity (Google Play App Signing Key Upgrade):**
   - Because changing the signing key directly on a standalone APK breaks in-place upgrades (`INSTALL_FAILED_UPDATE_INCOMPATIBLE`), any future key rotation must use **Google Play App Signing**:
     1. Enroll `com.jugajug.android` in Play App Signing using the current `connectly-release.jks` (`SHA-256: 3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741`).
     2. Generate a new **Upload Keystore** stored exclusively outside the repository (`~/.android/bondhoo-upload.jks`) with a strong passphrase.
     3. Export the new upload certificate (`keytool -export -rfc -keystore ~/.android/bondhoo-upload.jks -alias upload -file upload_cert.pem`) and request an **Upload Key Reset** in Google Play Console (`Setup -> App integrity -> App signing -> Request upload key reset`).
     4. For direct website APK downloads (`https://bondhoo.com/bondhoo.apk`), if rotating keys outside Play Store, use APK Signature Scheme v3 key rotation (`apksigner rotate --out rotation.lineage --old-signer ... --new-signer ...`) so existing devices trust the new key via the cryptographic proof-of-rotation lineage.
