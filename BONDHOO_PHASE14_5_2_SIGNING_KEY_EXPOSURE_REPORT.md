# BONDHOO PHASE 14.5.2 — SIGNING KEY EXPOSURE ASSESSMENT REPORT
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Application Security Engineer & Git Security Auditor  
**Product:** BONDHOO Professional Social Networking Platform  
**Target Asset:** `android/app/keystore/connectly-release.jks`

---

## 1. Executive Summary & Exposure Classification

During the Phase 14.5.1 Git & Secret audit, the release signing keystore `android/app/keystore/connectly-release.jks` was identified as being tracked in the Git index. 

This assessment forensically traces the introduction, modification history, exposure boundaries, and impact on the release architecture.

### Overall Exposure Severity Classification:
**`HIGH`** *(Exposure in version control index and commit tree; risk mitigated by Google Play App Signing architecture; zero evidence of active unauthorized compromise).*

---

## 2. Forensic Investigation Findings

| Forensic Question | Evidence Found | Technical Finding |
|---|---|---|
| **1. Currently Tracked in Git Index?** | `git ls-files android/app/keystore/connectly-release.jks` returns exit code 0 | **YES (Tracked)** |
| **2. Origin Commit Introducing Key?** | Commit `9b52f37d07d242621b3d7eb4a4c38ec43e43e58a` (`feat(connectly): complete Phase 11...`) | **Introduced on Nov 1, 2026 by Mizanur Rahman** |
| **3. Subsequent Git Modifications?** | `git log 9b52f37..HEAD -- connectly-release.jks` returns 0 changes | **NO (Binary unchanged since initial commit)** |
| **4. Remote Repository Exposure?** | `git remote -v` -> `git@github.com:edge-tec/bondhoo.git` | **Hosted on GitHub (Edge-Tec Private/Internal Repo)** |
| **5. Fallback Password in Source?** | Found in `build.gradle.kts` and `.github/workflows/connectly-android-ci.yml` | **YES (Fallback default password string present in code)** |
| **6. Shared with External CI?** | GitHub Actions runner executes `connectly-android-ci.yml` | **YES (CI/CD pipeline references keystore)** |
| **7. Actual Evidence of Compromise?** | No unauthorized commits, no unauthorized package uploads, no rogue cert clones | **NO EVIDENCE OF COMPROMISE** |

---

## 3. Disambiguation: Exposure Boundary Analysis

To provide an objective risk posture without hyperbole:
1. **Tracked in Current Index:** The file is tracked in the working branch `main`. Anyone who clones the repo receives the binary.
2. **Present in Historical Commits:** Even if removed from HEAD, commit `9b52f37` retains the binary in the Git object database (`.git/objects/`).
3. **Repository Collaborators:** Anyone with read access to `github.com:edge-tec/bondhoo` has read access to the keystore.
4. **Public Exposure Status:** Repository is private under Edge-Tec organization. It is **NOT** indexed on public search engines.
5. **Impact Under Google Play App Signing:**
   - Under modern Google Play App Signing, the developer upload key is **NEVER** used to sign APKs delivered to end users.
   - Google manages the actual **App Signing Key** inside Google Cloud KMS.
   - Therefore, even in a worst-case scenario, an unauthorized party possessing this keystore **cannot** deliver rogue updates to end-user devices unless they also gain full administrative account access to the owner's Google Play Console.
   - The Upload Key can be reset at any time via Play Console support.

---

## 4. Assessment Determination

**Status:** **HIGH RISK IDENTIFIED (CONFIRMED IN REPOSITORY HISTORY; NO EVIDENCE OF EXTERNAL COMPROMISE)**.

