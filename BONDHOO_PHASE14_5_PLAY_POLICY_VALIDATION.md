# BONDHOO PHASE 14.5 — STAGE 07: PLAY POLICY & STORE LISTING VALIDATION REPORT
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Google Play Compliance Engineer & Application Security Lead  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Executive Summary

This report establishes the verified Google Play Store listing declarations, policy questionnaires, and User-Generated Content (UGC) safety compliance for the **BONDHOO** Android application.

All policy selections reflect the actual runtime capabilities and architectural reality of the application.

---

## 2. Store Listing Metadata & Asset Specifications

| Asset / Metadata | Play Store Specification | Verified BONDHOO Value |
|---|---|---|
| **App Title** | Up to 30 characters | `BONDHOO` |
| **Short Description** | Up to 80 characters | `Connect, learn, share, and grow with the BONDHOO professional social network.` (78 chars) |
| **Full Description** | Up to 4000 characters | Comprehensive overview of feed, groups, LMS, real-time messaging, and audio/video calling. |
| **App Icon** | 512 x 512 px PNG, 32-bit color, max 1MB | Official Bondhoo Infinity Icon (`public/images/bondhoo-icon.png`) |
| **Feature Graphic** | 1024 x 500 px JPEG/PNG | Official Bondhoo Wordmark Banner (`public/images/bondhoo-logo.png`) |
| **Contact Email** | Required developer email | `support@bondhoo.com` |
| **Privacy Policy URL** | Publicly accessible HTTPS URL | `https://bondhoo.com/privacy` (alias: `https://bondhoo.com/privacy-policy`) |
| **Account Deletion URL**| Google Play mandatory deletion URL | `https://bondhoo.com/account/delete` (in-app: Settings > Account) |

---

## 3. Target Audience & Content Rating Assessment

### Age Target Analysis (18+ vs 13+):
- **Recommendation:** **18 and older (Adults & Professionals)**.
- **Rationale:** 
  1. BONDHOO includes recruitment/job boards, career progression, unmoderated real-time peer messaging, and professional networking.
  2. Declaring 18+ exempts the application from the Google Play Designed for Families (DFF) requirements and COPPA parental consent mandates.
  3. If the owner ever chooses to expand to university students aged 13–17, Google Play will require declaring "Teens", a neutral age screen, and strict ad/tracking limitations. For the initial launch, 18+ is optimal and policy-safe.

### IARC Content Rating Questionnaire Guidance:
- **Category:** Social Network / Communication.
- **Violence / Gore:** None.
- **Sexuality:** None.
- **Language / Profanity:** User-generated content possibility (select "Users can interact and share content").
- **Expected Rating:** Teen / PEGI 12 / IARC 16+.

---

## 4. User-Generated Content (UGC) Safety Architecture

Google Play strictly monitors social platforms for abusive content. BONDHOO complies with all UGC mandates:

1. **Terms of Service:** User registration requires agreeing to prohibited content policies (`/terms`).
2. **In-App Reporting:** Every post, comment, and message contains an instantaneous "Report" mechanism. Reports dispatch to the admin governance queue (`/api/v2/admin/enterprise/governance/`).
3. **Instant User Blocking:** Users can block abusive members via profile and chat settings, instantly severing visibility and communications.
4. **Moderator Enforcement:** Admin controllers support content takedown, user timeout, and permanent banning within 24 hours of reporting.

---

## 5. Data Safety Form Guidance

| Data Category | Collected? | Shared with 3rd Parties? | Purpose |
|---|---|---|---|
| **Personal Info (Name, Email)** | Yes | No | Account management & authentication |
| **Messages & Chat Content** | Yes | No | In-app messaging & communication |
| **Photos & Videos** | Yes (User uploaded) | No | Post attachments & avatar updates |
| **Location (Coarse/Fine)** | Optional (Check-ins only) | No | User-initiated post tag |
| **Device Identifiers** | Yes (FCM Token) | No | Push notification delivery |
| **Data Encryption in Transit** | **Yes (HTTPS TLS 1.3 enforced)** | — | Security standard |
| **Account Deletion Request** | **Yes** | — | Web & In-app deletion supported |

**Stage 07 Determination:** **PASS (STORE POLICY & LISTING COMPLIANCE VERIFIED)**.

