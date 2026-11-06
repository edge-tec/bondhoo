# BONDHOO PHASE 14.5 — STAGE 06: GOOGLE PLAY ACCOUNT TYPE & PRODUCTION ACCESS REQUIREMENTS
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Google Play Compliance Engineer & Release Manager  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Executive Summary

Google Play enforces significantly different testing prerequisites depending on whether the registered developer account is an **Organization (Company)** account or a **Personal** account.

This report outlines the regulatory criteria and identifies the mandatory questions the owner must confirm regarding their Google Play Developer account structure.

---

## 2. Google Play Account Type Matrix & Production Requirements

| Policy Dimension | Organization (Business) Account | Personal Developer Account (Created on/after Nov 13, 2023) | Personal Developer Account (Created before Nov 13, 2023) |
|---|---|---|---|
| **D-U-N-S Number Required** | Yes (Verified legal entity) | No | No |
| **Mandatory Closed Testing Period**| **None** (Can proceed from Internal Testing to Production directly upon review) | **Mandatory 14-Day Closed Test** (Must maintain at least 12 or 20 opted-in testers for 14 continuous days) | None |
| **Production Access Application** | Direct production rollout | Must apply for production access in Play Console after closed testing completes | Direct production rollout |
| **Internal Testing Impact** | Rapid staging and QA | Useful for immediate QA, but **DOES NOT count towards the 14-day closed testing requirement** | Rapid staging and QA |

---

## 3. Mandatory Questions for the Owner

The owner must inspect their Google Play Console account details and confirm:

1. **What is your Developer Account Type?**
   - [ ] **Organization / Company Account**
   - [ ] **Personal Account**
2. **What was the Account Creation Date?**
   - Date: `YYYY-MM-DD` (Check under **Developer account > Account details**).
3. **Is the Account Subject to the 14-Day Closed Testing Rule?**
   - In Google Play Console, if you see a task in the dashboard stating:
     *"Test your app with 12 or 20 testers for at least 14 days"*, your account is subject to this mandatory requirement.

---

## 4. Operational Strategy for BONDHOO

1. **Phase 14.4 & 14.5 Target:** **Internal Testing Track**.
   - Regardless of account type, Internal Testing is the correct, immediate first milestone.
   - It permits up to 100 testers immediately with zero review latency.
2. **Path to Production:**
   - If **Organization Account:** Can request Production release as soon as Internal QA passes.
   - If **Personal Account:** The owner must promote the build to **Closed Testing (Alpha)** and recruit 12–20 testers who keep the app installed for 14 continuous days before requesting Production access.

**Stage 06 Determination:** **PASS (COMPLIANCE FRAMEWORK & ACCOUNT QUESTIONNAIRE ESTABLISHED)**.

