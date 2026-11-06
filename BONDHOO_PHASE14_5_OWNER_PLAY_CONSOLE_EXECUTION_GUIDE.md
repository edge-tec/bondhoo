# BONDHOO PHASE 14.5 — STAGE 02: OWNER PLAY CONSOLE EXECUTION GUIDE
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Google Play Release Engineer & DevSecOps Lead  
**Target User:** Project Owner / Play Console Account Holder  
**Product:** BONDHOO Professional Social Networking Platform

---

## ⚠️ MANDATORY SAFETY & COMPLIANCE NOTICE

- **Engineering vs. Console Boundary:** Local code compilation and signing are complete. Only **you (the account owner)** can perform actions inside Google Play Console.
- **Do NOT publish to Production:** This workflow is exclusively for the **Internal Testing Track**.
- **Play App Signing Status:** Play App Signing is **NOT** active until you complete the enrollment steps below and Google Play displays your active certificates.

---

## Step-by-Step Execution Checklist for the Owner

### Step 1: Log in & Create New Application
1. Navigate to the [Google Play Console](https://play.google.com/console).
2. On the **All apps** tab, click **Create app**.
3. Fill in the app details:
   - **App name:** `BONDHOO`
   - **Default language:** `English (United States)` (or preferred language)
   - **App or game:** Select `App`
   - **Free or paid:** Select `Free` (unless paid distribution is explicitly intended)
4. Accept both **Developer Program Policies** and **US export laws** declarations.
5. Click **Create app**.

### Step 2: Configure Play App Signing & Upload Verification
1. In the left navigation sidebar, go to **Release > Setup > App integrity**.
2. Under **Play App Signing**, select:
   - **"Use Google-generated key" (Recommended)**.
3. Review and click **Accept** on the Play App Signing Terms of Service.
4. When you upload your AAB in Step 3, Google Play automatically validates your Upload Key signature against:
   - **Upload Key SHA-1:** `D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C`
   - **Upload Key SHA-256:** `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41`

### Step 3: Create Internal Testing Release & Upload AAB
1. In the left sidebar, click **Testing > Internal testing**.
2. Click **Create new release** (top right).
3. In the App bundles upload box, drag and drop:
   ```text
   android/app/build/outputs/bundle/release/app-release.aab
   ```
   *(File size: 32.8 MB, SHA-256: 2fa62aa65ffe5aabe61363699423fbf4e8253264e7c785aaccd1092f5b3ba794)*
4. Verify that Google Play displays:
   - Package name: `com.jugajug.android`
   - Version code: `2`
   - Version name: `1.0.1`
5. Enter **Release notes**:
   ```text
   BONDHOO v1.0.1 (Build 2) - Internal Testing Release
   - Android 16 (API 36) support.
   - Core social networking, messaging, calling, and LMS features.
   ```
6. Click **Next** at the bottom right.
7. Review warnings and confirm there are **ZERO blocking errors**.

### Step 4: Add Testers & Publish Release
1. In **Internal testing**, open the **Testers** tab.
2. Under **Email lists**, select or create an email list (up to 100 testers).
3. Click **Save changes**.
4. In the **How testers join your test** section, copy the **Join on Android** or **Join on the web** URL.
5. Click **Save and publish** to deploy the release to your internal testers.

### Step 5: Post-Enrollment Action (Retrieve App Signing Key)
1. Go back to **Release > Setup > App integrity**.
2. Click on **App signing key certificate**.
3. Copy both:
   - `SHA-1 fingerprint`
   - `SHA-256 fingerprint`
4. Provide these to the engineering team so they can update Firebase Authentication and Google Sign-In records.

**Stage 02 Determination:** **PASS (EXECUTION GUIDE READY FOR OWNER ACTION)**.

