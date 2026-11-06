# BONDHOO PHASE 14.5 — OWNER ACTION REPORT
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Release Manager & Google Play Compliance Lead  
**Target:** Project Owner / Play Console Administrator

---

## 1. Overview of Owner Responsibilities

The engineering team has completed all code, asset, configuration, and cryptographic tasks. The remaining actions require direct access to your **Google Play Console** and **Firebase Console** accounts.

---

## 2. Step-by-Step Owner Action Items

### Action 1: Create Application in Google Play Console
1. Navigate to: [https://play.google.com/console](https://play.google.com/console)
2. Click **Create app**.
3. **App name:** `BONDHOO`
4. **Default language:** `English (United States)`
5. **App or game:** `App`
6. **Free or paid:** `Free`
7. Accept the Declarations and click **Create app**.

### Action 2: Enroll in Play App Signing
1. In the left menu, navigate to **Release > Setup > App integrity**.
2. Under **Play App Signing**, select **"Use Google-generated key" (Recommended)**.
3. Accept the Terms of Service.

### Action 3: Upload the Verified AAB to Internal Testing
1. In the left menu, navigate to **Testing > Internal testing**.
2. Click **Create new release**.
3. In the upload area, select:
   ```text
   android/app/build/outputs/bundle/release/app-release.aab
   ```
   *(File size: 32.8 MB, SHA-256: 2fa62aa65ffe5aabe61363699423fbf4e8253264e7c785aaccd1092f5b3ba794)*
4. Verify that Version Code `2` and Version Name `1.0.1` appear without errors.
5. Enter release notes: `BONDHOO v1.0.1 (Build 2) - Internal QA Build`.
6. Click **Next**, review warnings, and click **Save and publish**.

### Action 4: Create Tester List & Share Opt-In Link
1. In **Internal testing**, open the **Testers** tab.
2. Create or select an email list (e.g., `Bondhoo Internal QA Team`) and add tester Gmail addresses.
3. Save changes.
4. Copy the **Join on Android** or **Join on the web** URL and share it with your testers.

### Action 5: Copy App Signing Fingerprints & Update Firebase (CRITICAL)
1. Go back to **Release > Setup > App integrity**.
2. Under **App signing key certificate**, copy both:
   - `App signing key SHA-1 fingerprint`
   - `App signing key SHA-256 fingerprint`
3. Open [Firebase Console](https://console.firebase.google.com) > **Project Settings > General > Your apps > com.jugajug.android**.
4. Click **Add fingerprint** and add both fingerprints. (This enables Google Sign-In and notifications on Play Store installs).

### Action 6: Confirm Developer Account Structure
- Check under **Developer account > Account details**:
  - Is your account registered as an **Organization** or a **Personal** account?
  - If Personal and created after Nov 13, 2023, note that Google requires a 14-day 20-tester Closed Test before Production rollout can be requested.

