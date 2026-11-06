# BONDHOO PHASE 14.5.2 — GOOGLE PLAY PRIVACY & WEB DELETION COMPLIANCE AUDIT
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Google Play Compliance Engineer & Application Security Lead  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Google Play Account Deletion Policy Mandate

Under the **Google Play User Data Policy (Understanding user data deletion requirements)**:
1. **In-App Deletion:** The app must provide an easy-to-find option in the app to initiate account deletion.
2. **Web-Based Deletion Requirement:** The developer must provide a **web resource URL** where users can request account and data deletion without needing to reinstall the app.
3. **Guest / Unauthenticated User Path:** If a user uninstalled the app and forgot their password, or only used biometric/mobile SSO, the web link **cannot** lead solely to a login wall with no alternative. The web page must provide a functional way to request deletion (e.g., a deletion request form or clearly stated steps to submit a deletion request to a dedicated privacy officer).

---

## 2. Evaluation of Current Web-Based Deletion Path (`/account/delete`)

### Current State Analysis:
- In `routes/web.php`:
  ```php
  Route::redirect('/account/delete', '/settings/account');
  ```
- **Scenario A (Logged-In User):**
  - If the user has an active web browser session, navigating to `https://bondhoo.com/account/delete` redirects them to `https://bondhoo.com/settings/account`, where the account settings UI provides the in-app deletion button.
  - **Verdict:** **COMPLIANT for authenticated web users.**

- **Scenario B (Unauthenticated User / User Who Uninstalled Mobile App):**
  - If the user does not have an active session, `/settings/account` redirects to `/login`.
  - A user who uninstalled the app and forgot their password cannot log in, creating a friction point that Google Play policy auditors flag during app reviews.
  - **Verdict:** **GAP IDENTIFIED (Advisory for Production Access).**

---

## 3. Play Store Compliance Remediation Blueprint

To achieve 100% flawless compliance before moving from Internal Testing to Production:

### Step 1: Create a Dedicated Web Deletion Request View (`/account/delete`)
Instead of an immediate redirect to `/settings/account`, serve a dedicated hybrid view:
1. If the user is logged in: provide a direct "Delete My Account Now" button.
2. If the user is a guest (not logged in):
   - Provide a "Log in to delete immediately" button.
   - **PLUS:** Provide an **"Unauthenticated Deletion Request Form"** where the user can enter their registered email address, username, and reason. The backend dispatches an email with a secure, one-time verification link (`/account/delete/verify/{token}`) that authorizes deletion upon clicking.
   - Alternatively: Display clear instructions: *"To request deletion of your account and data without logging in, send an email to privacy@bondhoo.com from your registered email address with subject 'Account Deletion Request'. Deletion requests are processed within 48 hours."*

### Step 2: Play Console URL Declaration
- In Google Play Console under **App content > Data safety > Account deletion URL**:
  - Enter: `https://bondhoo.com/account/delete`
- In the description field, specify that users can delete their account in-app via Settings > Account, or via web request.

---

## 4. Compliance Status for Internal Testing Track

- For the immediate **Internal Testing Track**, internal testers are known team members with active accounts. The existing in-app API and `/settings/account` redirect are sufficient for internal QA.
- Prior to public Production rollout, the dedicated hybrid deletion request page should be deployed to eliminate any Google Play review rejection risk.

**Stage 04 Determination:** **PASS WITH ADVISORY (INTERNAL TESTING READY; PRODUCTION HARDENING BLUEPRINT DELIVERED)**.

