# PHASE 16 — SIGNED ANDROID ARTIFACT REPORT (APK & AAB)

**Project:** Bondhoo Android Application  
**Application ID:** `com.jugajug.android` (`versionCode = 2`, `versionName = "1.0.1"`)  
**Timestamp:** 2026-11-20T19:15:00+06:00  
**Final Status:** **PASS**

---

## 1. Build Execution

**Command Executed:**
```bash
JAVA_HOME="/Applications/Android Studio.app/Contents/jbr/Contents/Home" ./android/gradlew -p android assembleRelease bundleRelease --no-daemon
```
**Build Output:**
- `BUILD SUCCESSFUL in 1m 52s` (`842 actionable tasks: 61 executed, 1 from cache, 780 up-to-date`)
- Tasks `:app:minifyReleaseWithR8`, `:app:shrinkReleaseRes`, `:app:packageRelease`, `:app:assembleRelease`, `:app:signReleaseBundle`, `:app:bundleRelease` all succeeded.

---

## 2. Generated Artifact Checksums & Metadata

| Artifact | File Path | File Size | SHA-256 Checksum | MD5 Checksum |
| :--- | :--- | :--- | :--- | :--- |
| **Signed Release APK** | `android/app/build/outputs/apk/release/app-release.apk` (and staged to `public/bondhoo.apk`) | `48 MB` | `51fc4673c2d3f6361315574b72714eea566057df2804985ff8ecbdd03d17204e` | `156b3bada05573301efc88c5b6dca4a8` |
| **Signed Release AAB** | `android/app/build/outputs/bundle/release/app-release.aab` | `34 MB` | `1c817afde6455a073b79d3ab84617d0477207a54b4f092ba76234a215031be9f` | `7e0ca03335d4d75db379c75c7e542f9a` |

---

## 3. Cryptographic Signature Verification (`apksigner` & `jarsigner`)

**Command Executed:**
```bash
/Users/mizanurrahman/Library/Android/sdk/build-tools/35.0.0/apksigner verify --verbose --print-certs android/app/build/outputs/apk/release/app-release.apk
```

**Exact `apksigner` Output:**
```text
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
Signer #1 certificate MD5 digest: 26b166a11b37c77bd5776ec2333c5ec4
Signer #1 key algorithm: RSA
Signer #1 key size (bits): 2048
Signer #1 public key SHA-256 digest: d1c062976705075a25cf7b56a31836048dd1e4e1c40d70f69c9a7b45db6729fe
Signer #1 public key SHA-1 digest: bf4aa3cabfc35609b7d9947b2700a683c7a7b682
Signer #1 public key MD5 digest: 6d06421856f7824bb44a94d1888ff2bf
```

**AAB `jarsigner` Verification:**
- Verified signed with `CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD` (`The signer certificate will expire on 2054-03-19`).
