# BONDHOO PHASE 14.4 — OWNER ACTION REPORT
**Generated:** November 5, 2026  
**Target:** Project Owner / Administrator  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Overview of Owner Responsibilities

The software engineering, compilation, cryptographic signing, and local validation phases are complete. The remaining steps require administrative access to the **Google Play Console** and **Firebase Console**, which must be executed by the project owner.

---

## 2. Step-by-Step Action Plan for Owner

### Step 1: Open Google Play Console & Create New App
- URL: [https://play.google.com/console](https://play.google.com/console)
- Click **Create app**.
- Name: **`BONDHOO`**
- App or Game: **App**
- Free or Paid: **Free**
- Confirm the legal declarations and click **Create app**.

### Step 2: Enroll in Play App Signing
- Navigate to **Release > Setup > App integrity**.
- Under **Play App Signing**, choose: **"Use Google-generated key" (Recommended)**.
- Accept the Terms of Service.

### Step 3: Create Internal Testing Release & Upload AAB
- Navigate to **Testing > Internal testing**.
- Click **Create new release**.
- Upload the signed Android App Bundle located at:
  ```text
  android/app/build/outputs/bundle/release/app-release.aab
  ```
  *(Size: 31 MB, SHA-256: 2fa62aa65ffe5aabe61363699423fbf4e8253264e7c785aaccd1092f5b3ba794)*
- Enter release notes: `BONDHOO v1.0.1 (Build 2) Internal QA Release`.
- Click **Next**, review warnings (ensure 0 errors), and click **Save and publish**.

### Step 4: Configure Internal Testers
- In **Internal testing > Testers** tab, create an email list (e.g., `Bondhoo QA Team`).
- Add tester Gmail addresses.
- Copy the **Join on Android** / **Join on the Web** link and share it with your testers.

### Step 5: Sync Play App Signing Key with Firebase (CRITICAL)
- Return to **Release > Setup > App integrity**.
- Copy the **App signing key certificate**:
  - `SHA-1 fingerprint`
  - `SHA-256 fingerprint`
- Open [Firebase Console](https://console.firebase.google.com).
- Go to **Project Settings > General > Your apps > com.jugajug.android**.
- Click **Add fingerprint** and paste both fingerprints.
- This ensures Google Sign-In and notifications work seamlessly on Play Store installed devices.

---

## 3. Communication Channel with Engineering Team

Once you have completed the upload and copied the Play App Signing fingerprints:
1. Share the confirmation that the AAB was accepted without errors.
2. Provide the App Signing SHA-1 and SHA-256 fingerprints so the engineering team can update the `google-services.json` repository record and verify Android App Links.

