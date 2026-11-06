# BONDHOO PHASE 14.5.3 — OLD UPLOAD KEY EXPOSURE CONTAINMENT REPORT
**Execution Timestamp:** 2026-11-05T16:13:30+06:00  
**Auditor / Engineer:** Principal DevSecOps Lead & Git Security Auditor  
**Product:** BONDHOO Professional Social Networking Platform  
**Target Asset:** `android/app/keystore/connectly-release.jks`

---

## 1. Executive Summary & Forensic Verification

The historical release keystore `android/app/keystore/connectly-release.jks` was identified as having been tracked in the Git repository since commit `9b52f37d07d242621b3d7eb4a4c38ec43e43e58a` (Nov 1, 2026).

Per owner instructions:
- The old keystore has **NOT** been deleted from disk or Git history.
- Git history has **NOT** been rewritten.
- All references in active build scripts and CI pipelines have been permanently contained and decoupled.

---

## 2. Hardcoded Credentials Discovery & Containment Matrix

| File Path | Previous Insecure State | Remediated Secure State | Containment Status |
|---|---|---|---|
| `android/app/build.gradle.kts` (Lines 24–47) | Fallback plaintext password string `?: "connectly2026"` | Insecure fallback **REMOVED**. Requires `BONDHOO_UPLOAD_*` environment variables. Fails closed with `GradleException`. | **CONTAINED (PASS)** |
| `.github/workflows/connectly-android-ci.yml` (Lines 49–56)| Fallback string `|| 'connectly2026'` in environment block | Insecure fallback **REMOVED**. Dynamically injected via GitHub Actions Secrets (`BONDHOO_UPLOAD_*`). | **CONTAINED (PASS)** |
| `android/app/keystore/connectly-release.jks` | Used for active release signing in build variants | Decoupled from active release builds. Keystore preserved as read-only historical archive. | **CONTAINED (PASS)** |

---

## 3. Residual Risk & Historical Tracking Note

- **Current Working State:** Active Gradle compilation no longer references `connectly-release.jks`.
- **Git Historical Object:** Commit `9b52f37` retains the binary in historical packfiles.
- **Risk Mitigation:** Because **Option B** generates a new Upload Key (`bondhoo-upload.jks`), Google Play App Signing will enroll the **new** certificate (`26:0B:64:E1...`). Even if an unauthorized party extracts the old keystore from Git commit `9b52f37`, Google Play will reject any upload signed with the old key.

---

## 4. Owner Recommendation for Future Cleanup

When the owner authorizes Git history cleanup post-Phase 14.5:
1. Untrack the file from index: `git rm --cached android/app/keystore/connectly-release.jks`
2. If total historical purge is desired across git history, execute an authorized BFG Repo-Cleaner or git-filter-repo maintenance window.

**Stage 01 Determination:** **PASS (HARDCODED PASSWORDS REMOVED & OLD KEY SAFELY CONTAINED)**.

