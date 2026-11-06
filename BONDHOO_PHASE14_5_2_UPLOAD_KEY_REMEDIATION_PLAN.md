# BONDHOO PHASE 14.5.2 — UPLOAD KEY REMEDIATION PLAN
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Android Release Security Engineer & DevSecOps Lead  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Executive Summary & Policy Rules

This document outlines two explicit, non-destructive remediation strategies for handling the Git-tracked signing keystore (`connectly-release.jks`).

> **CRITICAL DIRECTIVE:** Neither Option A nor Option B will be executed automatically. The decision rests exclusively with the project owner. In this phase, **NO Git history rewriting** (`git filter-branch`, BFG, or `git rebase`) will be performed.

---

## 2. Option A: Preserve Current Key Temporarily (Staged Internal Testing)

Under this option, the owner proceeds with the existing signed AAB (`2fa62aa6...`) for the immediate **Internal Testing** track milestone, while applying immediate defensive access controls.

### Implementation Protocol:
1. **Repository Access Review:**
   - Audit all collaborator permissions on GitHub repository `git@github.com:edge-tec/bondhoo.git`.
   - Ensure the repository remains strictly **Private**.
   - Restrict write and clone access to trusted core developers.
2. **Secure Keystore Backup:**
   - Back up `android/app/keystore/connectly-release.jks` to an encrypted offline storage or cloud vault (e.g. 1Password / Google Cloud Secret Manager).
3. **Untrack from Working Index (Post-Approval):**
   - Once authorized, run `git rm --cached android/app/keystore/connectly-release.jks`.
   - The file remains on disk for local builds but will never be pushed in future commits.
4. **Remove Hardcoded Passwords in Gradle:**
   - Remove fallback default passwords (`connectly2026`) from `build.gradle.kts` and GitHub Actions workflow. Enforce environment variable injection only.
5. **Residual Risk Analysis:**
   - Historical commit `9b52f37` will still contain the original keystore in the Git packfiles unless history is later purged.
   - However, because the app is enrolled in **Google Play App Signing**, this key functions only as an **Upload Key**. Google Play rejects any AAB that does not pass developer console authentication, limiting risk to unauthorized draft uploads.

---

## 3. Option B: Replace & Reset Upload Key (Fresh Cryptographic Baseline)

Under this option, the owner decides to establish a completely fresh, unexposed Upload Key before or immediately after initial Play Console registration.

### Implementation Protocol:
1. **Owner Explicit Authorization:**
   - Owner issues written approval: *"Authorize Generation of New Upload Key"*.
2. **Generate New Cryptographic Key (Outside Git):**
   - Generate a fresh RSA 4096-bit or 2048-bit key in a dedicated, Git-ignored folder (e.g., `~/.android/keys/bondhoo-upload-key.jks`):
     ```bash
     keytool -genkeypair -v -keystore bondhoo-upload-key.jks -alias bondhoo-upload \
       -keyalg RSA -keysize 2048 -validity 10000 \
       -dname "CN=Bondhoo Upload Key, O=Bondhoo Social, C=BD"
     ```
3. **Export Public PEM Certificate:**
   - Export public certificate for Google Play:
     ```bash
     keytool -export -rfc -keystore bondhoo-upload-key.jks -alias bondhoo-upload -file upload_certificate.pem
     ```
4. **Google Play Upload Key Registration:**
   - **If app not yet published:** Upload the newly generated AAB signed with the new key directly during initial setup. Google Play will register the new key as the official Upload Key.
   - **If app already enrolled:** Go to Play Console > **Release > Setup > App integrity**, click **Request upload key reset**, and submit `upload_certificate.pem`. Google Play activates the new key within 24–48 hours.
5. **App Signing Key Independence:**
   - End users are unaffected because Google's master **App Signing Key** remains securely in Google Cloud KMS.
6. **Recompile AAB:**
   - Update `signingConfigs` in `build.gradle.kts` to point to the new key, recompile `app-release.aab`, and verify checksums.

---

## 4. Comparative Strategic Summary

| Evaluation Attribute | Option A (Preserve & Restrict) | Option B (Generate Fresh Key) |
|---|---|---|
| **Immediate Schedule Impact** | **Zero delay:** Can upload current verified AAB immediately to Internal Testing. | **Minor delay (1–2 days):** Requires key generation, AAB recompilation, and possible Play Support ticket. |
| **History Rewriting Required?**| **No.** | **No.** |
| **Cryptographic Cleanliness** | Residual exposure in commit `9b52f37` until history purge. | **100% Clean:** New key never committed to Git. |
| **Recommended Path** | **Recommended for Internal Testing Track**; transition to Option B prior to public production release. | **Recommended before Public Production Release**. |

---

## 5. Decision Requirement

The engineering team pauses all key generation activities awaiting the owner's formal selection between **Option A** and **Option B**.

**Stage 02 Determination:** **PASS (DUAL-OPTION REMEDIATION BLUEPRINT COMPLETED)**.

