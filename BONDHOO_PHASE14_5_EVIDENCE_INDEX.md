# BONDHOO PHASE 14.5 — EVIDENCE & VERIFICATION INDEX
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal DevSecOps Lead & Software Architect  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Verified Binary Artifact Checksums

```bash
# File existence and exact byte sizes
$ ls -la android/app/build/outputs/apk/release/app-release.apk android/app/build/outputs/bundle/release/app-release.aab
-rw-r--r--@ 1 mizanurrahman  staff  49697066 Nov  5 13:43 android/app/build/outputs/apk/release/app-release.apk
-rw-r--r--@ 1 mizanurrahman  staff  32842975 Nov  5 13:43 android/app/build/outputs/bundle/release/app-release.aab

# SHA-256 Checksums (Independent Recalculation)
$ shasum -a 256 android/app/build/outputs/apk/release/app-release.apk android/app/build/outputs/bundle/release/app-release.aab
a6e2b91ad3bd5b7c6bfd6a4deb280bbe2ee56ba23d5ec115abe395592b85d354  android/app/build/outputs/apk/release/app-release.apk
2fa62aa65ffe5aabe61363699423fbf4e8253264e7c785aaccd1092f5b3ba794  android/app/build/outputs/bundle/release/app-release.aab
```

---

## 2. Jarsigner AAB Verification Log

```bash
$ "/Applications/Android Studio.app/Contents/jbr/Contents/Home/bin/jarsigner" -verify -certs \
    android/app/build/outputs/bundle/release/app-release.aab

jar verified.

The signer certificate will expire on 2054-03-19.
```

---

## 3. Apksigner Standalone APK Verification Log

```bash
$ /Users/mizanurrahman/Library/Android/sdk/build-tools/36.0.0/apksigner verify --verbose --print-certs \
    android/app/build/outputs/apk/release/app-release.apk

Verifies: true
Verified using v2 scheme (APK Signature Scheme v2): true
Number of signers: 1
Signer #1 certificate DN: CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD
Signer #1 certificate SHA-256 digest: 3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741
Signer #1 certificate SHA-1 digest: d3497eb462ca34961c9f2e96c6e2911ce9e8345c
Signer #1 key algorithm: RSA (2048 bits)
```

---

## 4. Keystore Fingerprints & Attributes

```bash
$ "/Applications/Android Studio.app/Contents/jbr/Contents/Home/bin/keytool" -list -v \
    -keystore android/app/keystore/connectly-release.jks -alias connectly -storepass [PROTECTED]

Alias name: connectly
Valid from: Sun Nov 01 08:26:58 GMT+06:00 2026 until: Thu Mar 19 08:26:58 GMT+06:00 2054
Certificate fingerprints:
	 SHA1: D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C
	 SHA256: 3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41
Signature algorithm name: SHA384withRSA
Subject Public Key Algorithm: 2048-bit RSA key
```

---

## 5. Manifest & Policy Route Verification

```bash
# Verify privacy policy and account deletion routes
$ php artisan route:list --path=privacy
GET|HEAD privacy ................ privacy › Web\SettingsWebController@privacyPolicy
ANY      privacy-policy ......... Illuminate\Routing › RedirectController
GET|HEAD settings/privacy ....... settings.privacy › Web\SettingsWebController@privacy

$ php artisan route:list --path=account/delete
ANY      account/delete ......... Illuminate\Routing › RedirectController
```

---

## 6. Forbidden Parental Permissions Binary Scan

```bash
# Binary manifest scan in app-release.aab
BIND_ACCESSIBILITY_SERVICE in AAB manifest: False
BIND_DEVICE_ADMIN in AAB manifest: False
PACKAGE_USAGE_STATS in AAB manifest: False
ACCESS_BACKGROUND_LOCATION in AAB manifest: False
REQUEST_IGNORE_BATTERY_OPTIMIZATIONS in AAB manifest: False
SYSTEM_ALERT_WINDOW in AAB manifest: False
```

