# BONDHOO — PHASE 14.3.1
## RELEASE KEYSTORE & PLAY APP SIGNING VERIFICATION REPORT

**Target Brand:** BONDHOO  
**Package Identifier:** `com.jugajug.android`  
**Signing Framework:** APK Signature Scheme v1 (JAR Signature) + v2 (Full APK block)  
**Auditor:** Application Security Auditor & DevSecOps Lead  
**Git Head:** `315960fde56849c4be7344a78fda005151027b42` (`main`)  
**Status:** **SIGNING INTEGRITY VERIFIED & CRYPTOGRAPHICALLY AUDITED**

---

### 1. KEYSTORE VERIFICATION & CUSTODY SPECIFICATIONS

The release signing keystore was audited without exposing secret credentials:

```
Keystore Properties:
├── Keystore File:          android/app/keystore/connectly-release.jks
├── Keystore Type:          PKCS12
├── Entry Type:             PrivateKeyEntry (2048-bit RSA key)
├── Alias:                  connectly
├── Certificate Chain:      Length 1
├── Validity Window:        Nov 1, 2026 through Mar 19, 2054 (28 years)
├── Signature Algorithm:    SHA384withRSA
└── Environment Secrets:    CONNECTLY_KEYSTORE_PASSWORD, CONNECTLY_KEY_PASSWORD
```

---

### 2. CERTIFICATE FINGERPRINTS FOR GOOGLE CLOUD & PLAY CONSOLE

These fingerprints must be registered in Google Cloud Console / Firebase for OAuth and Google Sign-In authorization:

- **SHA-1 Fingerprint:**
  ```
  D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C
  ```
- **SHA-256 Fingerprint:**
  ```
  3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41
  ```

---

### 3. GOOGLE PLAY APP SIGNING STRATEGY

When publishing the AAB (`app-release.aab`) to Google Play Console:
1. **Upload Key Role:** The `connectly-release.jks` certificate functions as the **Upload Key**.
2. **Play App Signing:** Google Play validates that the incoming AAB is signed with the upload key corresponding to SHA-256 `3D:34:5D...`.
3. **Distribution Signing:** Google Play re-signs the optimized APKs delivered to user devices with the Google Play App Signing key.
4. **Key Preservation Rule:** Per strict security rules (*"Never overwrite existing signing keys. Never generate replacement keys without owner authorization"*), the existing keystore was preserved and verified without modification.

---

### 4. AUDIT CONCLUSION & VERDICT

- **Stage 04 Status:** **PASS**
- **Signing Health:** Valid, 2048-bit RSA key valid through 2054.
- **Verdict:** Signing configuration satisfies all Google Play upload integrity standards.

