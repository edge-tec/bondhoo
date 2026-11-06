# BONDHOO PHASE 14.4 — STAGE 04: OWNER PLAY CONSOLE STEP-BY-STEP WORKFLOW GUIDE
**Generated:** November 5, 2026  
**Target User:** Project Owner / Play Console Account Administrator  
**Product:** BONDHOO Professional Social Networking Platform  
**Target Action:** Internal Testing Track Deployment

---

## ⚠️ CRITICAL NOTICE FOR THE OWNER

- **Do NOT publish to Production:** This procedure is strictly for the **Internal Testing Track**.
- **Distribution Model:** Select **Free** (do not change pricing without business review).
- **Upload File:** Only upload `android/app/build/outputs/bundle/release/app-release.aab`. Never upload an `.apk` file to Google Play Console for a new application.

---

## Part 1: Create the Application Listing

1. Log in to [Google Play Console](https://play.google.com/console).
2. On the **All apps** dashboard, click the blue button: **Create app**.
3. Fill in the **App details**:
   - **App name:** `BONDHOO`
   - **Default language:** `English (United States) - en-US` (or `Bengali - bn-BD`)
   - **App or game:** Select `App`
   - **Free or paid:** Select `Free`
4. In the **Declarations** section:
   - Check ☑️ *"Accept Developer Program Policies"*
   - Check ☑️ *"Accept US export laws"*
5. Click **Create app** at the bottom right.

---

## Part 2: Enroll in Google Play App Signing

1. In the left navigation menu of your new app, go to **Release > Setup > App integrity**.
2. Under the **Play App Signing** section, you will see the enrollment choices:
   - Select: **"Use Google-generated key" (Recommended)**.
3. Google Play will prepare the cloud KMS infrastructure to manage the public App Signing Key.
4. When you upload your first AAB in Part 3, Google Play Console automatically detects and registers your local Upload Key certificate:
   - Certificate SHA-1: `D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C`
   - Certificate SHA-256: `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41`
5. Accept the **Play App Signing Terms of Service** and click **Save**.

---

## Part 3: Create Internal Testing Release & Upload AAB

1. In the left sidebar, navigate to **Testing > Internal testing**.
2. Click the **Testers** tab:
   - Click **Create email list**.
   - List name: `Bondhoo Core QA Team`.
   - Add tester Gmail / Google Workspace email addresses (up to 100 testers).
   - Click **Save changes**.
   - Check the box next to your new list to activate it.
   - Copy the **Join on the web** or **Join on Android** opt-in URL for distribution to your testers.
3. Click the **Releases** tab in Internal testing.
4. Click **Create new release** at the top right.
5. In the **App bundles** upload area:
   - Drag and drop or browse to select:  
     `android/app/build/outputs/bundle/release/app-release.aab`
   - File size: ~31 MB
   - Checksum: `2fa62aa65ffe5aabe61363699423fbf4e8253264e7c785aaccd1092f5b3ba794`
6. Wait for the upload and validation to complete.
   - Google Play will parse: `com.jugajug.android`, Version Code `2`, Version Name `1.0.1`.
7. Enter **Release notes**:
   ```text
   BONDHOO v1.0.1 (Build 2) - Internal Testing Release
   - Initial internal QA build targeting Android 16 (API 36).
   - High-performance social feed, networking, messaging, and LMS modules.
   - Enhanced security, biometrics, and push notification support.
   ```
8. Click **Next** at the bottom right.
9. Review any warnings or advisory notices. (Ensure there are **zero blocking errors**).
10. Click **Save and publish** to make the build available to internal testers.

---

## Part 4: Post-Upload Critical Action (Retrieve App Signing Fingerprints)

Immediately after the release is uploaded:
1. Return to **Release > Setup > App integrity**.
2. Under **Play App Signing**, click **App signing key certificate**.
3. Locate and copy the two Google-generated fingerprints:
   - **App signing key SHA-1 fingerprint**
   - **App signing key SHA-256 fingerprint**
4. Paste these fingerprints into your Firebase Console and Google Cloud Credentials (detailed in Stage 05) so Google Sign-In and notifications function on Play-installed devices.

---

## 5. Verification Checklist for Owner

- [ ] App created in Play Console as `BONDHOO` (Free).
- [ ] Enrolled in Play App Signing.
- [ ] Upload Key verified matching SHA-1 `D3:49:7E:B4...`.
- [ ] `app-release.aab` uploaded to Internal Testing.
- [ ] Release published to internal testers.
- [ ] App Signing Key SHA-1 and SHA-256 copied for Firebase configuration.

