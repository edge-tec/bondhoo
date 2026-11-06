# BONDHOO PHASE 14.4 — STAGE 02: PLAY APP SIGNING & UPLOAD KEY ARCHITECTURE REPORT
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Application Security Auditor & DevSecOps Lead  
**Product:** BONDHOO Professional Social Networking Platform  
**Target Platform:** Google Play Store

---

## 1. Executive Summary

This report defines the cryptographic architecture and key management workflow for enrolling the **BONDHOO** Android application in **Google Play App Signing**. 

As this is a **NEW** Google Play application registration, the industry-standard and Google-recommended dual-key architecture is deployed:
1. **Upload Key (Owner-Controlled):** Used exclusively by the release pipeline to cryptographically sign the `.aab` file before uploading to Google Play Console.
2. **App Signing Key (Google-Managed):** Generated and protected inside Google's secure infrastructure (Google Cloud KMS) to sign the final distribution APKs delivered to end-user devices.

---

## 2. Keystore Inspection & Suitability Assessment

The existing release keystore was inspected using JDK Keytool:

| Cryptographic Attribute | Required Google Play Standard | Verified Keystore Value | Verdict |
|---|---|---|---|
| **Keystore File** | PKCS12 / JKS format | `android/app/keystore/connectly-release.jks` | **PASS** |
| **Key Alias** | Unique alias string | `connectly` | **PASS** |
| **Key Algorithm** | RSA | `2048-bit RSA key` | **PASS** |
| **Signature Algorithm** | SHA256withRSA / SHA384withRSA | `SHA384withRSA` | **PASS** |
| **Validity Duration** | At least 25 years | Valid until March 19, 2054 (28 years) | **PASS** |
| **Subject / Issuer** | Valid X.509 Distinguished Name | `CN=Connectly Release, OU=Mobile, O=Connectly Inc, C=BD` | **PASS** |
| **SHA-1 Fingerprint** | 40-character hex string | `D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C` | **PASS** |
| **SHA-256 Fingerprint** | 64-character hex string | `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41` | **PASS** |

### Suitability Determination:
The existing keystore **fully complies with all Google Play standards**. Per the project safety rules, the file and alias names are retained without unnecessary disruption or renaming. No replacement key is required.

---

## 3. Upload Key vs App Signing Key Architecture

```
+-----------------------------------------------------------------------------------+
|                            BONDHOO DUAL KEY ARCHITECTURE                          |
+-----------------------------------------------------------------------------------+

     [ Local Build / CI/CD Pipeline ]
                  |
                  | (Signs .aab with UPLOAD KEY)
                  v
       +--------------------+
       |  app-release.aab   |
       +--------------------+
                  |
                  | (Owner uploads to Google Play Console)
                  v
+----------------------------------------------------+
|                GOOGLE PLAY CONSOLE                 |
|                                                    |
|  1. Verifies Upload Key Signature                  |
|     (Matches SHA-1: D3:49:7E:B4:62:CA...)          |
|  2. Strips Upload Signature                        |
|  3. Generates Device-Optimized APKs                |
|  4. Signs Distribution APKs with:                  |
|     GOOGLE APP SIGNING KEY (Google Cloud KMS)      |
+----------------------------------------------------+
                  |
                  | (Downloads via Play Store)
                  v
          [ End-User Device ]
```

### Critical Architectural Distinctions:
1. **The Upload Key:**
   - Managed locally by the owner/pipeline.
   - If ever compromised or lost, Google Play Console allows the owner to reset the Upload Key without affecting existing users or requiring a new package name.
2. **The App Signing Key:**
   - Generated and held in Google's cloud infrastructure.
   - Signs the APKs that end users actually install.
   - **Crucial Warning:** The SHA-1 and SHA-256 fingerprints of the App Signing Key **will differ** from the Upload Key. These Google-generated fingerprints must be copied from Play Console and added to Firebase & Google Cloud OAuth after enrollment (Stage 05).

---

## 4. Play App Signing Enrollment Instructions for Owner

When setting up the new app in Play Console:
1. Navigate to **Release > Setup > App integrity**.
2. Under **Play App Signing**, select:
   - **"Use Google-generated key" (Recommended)**.
3. When prompted for the upload key, Google Play will automatically register the certificate from your first uploaded AAB, or you can export the public certificate:
   ```bash
   "/Applications/Android Studio.app/Contents/jbr/Contents/Home/bin/keytool" \
     -export -rfc \
     -keystore android/app/keystore/connectly-release.jks \
     -alias connectly \
     -file upload_certificate.pem
   ```
4. Review and accept the Play App Signing Terms of Service.
5. Click **Save**.

*Note: Enrollment is verified only after the Play Console displays "Play App Signing is active".*

**Stage 02 Determination:** **PASS (UPLOAD KEY VALIDATED & ENROLLMENT ARCHITECTURE PREPARED)**.

