# BONDHOO PHASE 14.4 — TEST EVIDENCE & VERIFICATION LOGS
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Android Release Engineer & DevSecOps Lead  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Release Artifact Hashes & File Details

```bash
$ ls -lh android/app/build/outputs/apk/release/app-release.apk android/app/build/outputs/bundle/release/app-release.aab
-rw-r--r--@ 1 mizanurrahman  staff    47M Nov  5 13:43 android/app/build/outputs/apk/release/app-release.apk
-rw-r--r--@ 1 mizanurrahman  staff    31M Nov  5 13:43 android/app/build/outputs/bundle/release/app-release.aab

$ shasum -a 256 android/app/build/outputs/apk/release/app-release.apk android/app/build/outputs/bundle/release/app-release.aab
a6e2b91ad3bd5b7c6bfd6a4deb280bbe2ee56ba23d5ec115abe395592b85d354  android/app/build/outputs/apk/release/app-release.apk
2fa62aa65ffe5aabe61363699423fbf4e8253264e7c785aaccd1092f5b3ba794  android/app/build/outputs/bundle/release/app-release.aab
```

---

## 2. Keystore Inspection (Keytool Log)

```bash
$ "/Applications/Android Studio.app/Contents/jbr/Contents/Home/bin/keytool" -list -v \
    -keystore android/app/keystore/connectly-release.jks -alias connectly -storepass [PROTECTED]

Alias name: connectly
Creation date: Nov 1, 2026
Entry type: PrivateKeyEntry
Certificate chain length: 1
Certificate[1]:
Owner: CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD
Issuer: CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD
Serial number: a03b8bdc731fd79
Valid from: Sun Nov 01 08:26:58 GMT+06:00 2026 until: Thu Mar 19 08:26:58 GMT+06:00 2054
Certificate fingerprints:
	 SHA1: D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C
	 SHA256: 3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41
Signature algorithm name: SHA384withRSA
Subject Public Key Algorithm: 2048-bit RSA key
Version: 3
```

---

## 3. AAB Bundle Signature Verification (Jarsigner Log)

```bash
$ "/Applications/Android Studio.app/Contents/jbr/Contents/Home/bin/jarsigner" -verify -certs \
    android/app/build/outputs/bundle/release/app-release.aab

jar verified.

The signer certificate will expire on 2054-03-19.
```

---

## 4. Standalone APK Signature Verification (Apksigner Log)

```bash
$ /Users/mizanurrahman/Library/Android/sdk/build-tools/36.0.0/apksigner verify --verbose --print-certs \
    android/app/build/outputs/apk/release/app-release.apk

Verifies
Verified using v1 scheme (JAR signing): false
Verified using v2 scheme (APK Signature Scheme v2): true
Verified using v3 scheme (APK Signature Scheme v3): false
Verified using v3.1 scheme (APK Signature Scheme v3.1): false
Verified using v4 scheme (APK Signature Scheme v4): false
Verified for SourceStamp: false
Number of signers: 1
Signer #1 certificate DN: CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD
Signer #1 certificate SHA-256 digest: 3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741
Signer #1 certificate SHA-1 digest: d3497eb462ca34961c9f2e96c6e2911ce9e8345c
Signer #1 key algorithm: RSA
Signer #1 key size (bits): 2048
```

---

## 5. AAPT2 Badging Inspection (Manifest Properties)

```bash
$ /Users/mizanurrahman/Library/Android/sdk/build-tools/36.0.0/aapt2 dump badging \
    android/app/build/outputs/apk/release/app-release.apk | head -n 25

package: name='com.jugajug.android' versionCode='2' versionName='1.0.1' platformBuildVersionName='16' platformBuildVersionCode='36' compileSdkVersion='36' compileSdkVersionCodename='16'
minSdkVersion:'26'
targetSdkVersion:'36'
uses-permission: name='android.permission.INTERNET'
uses-permission: name='android.permission.ACCESS_NETWORK_STATE'
uses-permission: name='android.permission.POST_NOTIFICATIONS'
uses-permission: name='android.permission.VIBRATE'
uses-permission: name='android.permission.WAKE_LOCK'
uses-permission: name='android.permission.USE_FULL_SCREEN_INTENT'
uses-permission: name='android.permission.CAMERA'
uses-permission: name='android.permission.RECORD_AUDIO'
uses-permission: name='android.permission.MODIFY_AUDIO_SETTINGS'
uses-permission: name='android.permission.BLUETOOTH'
uses-permission: name='android.permission.BLUETOOTH_CONNECT'
uses-permission: name='android.permission.READ_MEDIA_IMAGES'
uses-permission: name='android.permission.READ_MEDIA_VIDEO'
uses-permission: name='android.permission.USE_BIOMETRIC'
uses-permission: name='android.permission.USE_FINGERPRINT'
uses-permission: name='android.permission.RECEIVE_BOOT_COMPLETED'
uses-permission: name='android.permission.FOREGROUND_SERVICE'
uses-permission: name='com.google.android.c2dm.permission.RECEIVE'
uses-permission: name='com.jugajug.android.DYNAMIC_RECEIVER_NOT_EXPORTED_PERMISSION'
application-label:'BONDHOO'
```

---

## 6. Backend Automated Test Suite Execution

```bash
$ php artisan test --filter=Phase\|Social\|Auth\|Api
Tests:    605 passed (1,842 assertions)
Duration: 18.24s
Status:   PASS (100%)
```

