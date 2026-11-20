# PHASE 14.4 — Android Production Signing Certification

## 1. Signing Configuration Upgrade (PASS)
- **`android/app/build.gradle.kts`:** Upgraded `signingConfigs.create("release")` to support both:
  1. `android/keystore.properties` (`storeFile`, `storePassword`, `keyAlias`, `keyPassword`)
  2. Environment variables (`BONDHOO_UPLOAD_STORE_FILE`, `BONDHOO_UPLOAD_STORE_PASSWORD`, `BONDHOO_UPLOAD_KEY_ALIAS`, `BONDHOO_UPLOAD_KEY_PASSWORD`)
- **Template & Git Hygiene:** Created `android/keystore.properties.example` and added `keystore.properties` / `android/keystore.properties` to `.gitignore` alongside `*.jks` and `*.keystore`.

## 2. Production Keystore Status (BLOCKED FOR PLAY STORE)
- **Current Signer on `public/bondhoo.apk`:** `CN=Android Debug` (`SHA-256: 4e43989d2180e41b1d83664bac348cc87393af6132904b27ec677e52f4f88c88`)
- **Reason:** No production `.jks` keystore file or credentials were provided in the environment; per Phase 14.4 rules (*"Never generate a fake production certificate"*), no fake self-signed production keystore was fabricated.
- **How to Produce the Final Play-Store-Signed APK:**
  1. Copy `android/keystore.properties.example` to `android/keystore.properties` and point `storeFile` to your official production `.jks` keystore.
  2. Run `./android/gradlew -p android :app:assembleRelease`.
  3. Verify with `apksigner verify --print-certs android/app/build/outputs/apk/release/app-release.apk`.
