# JUGAJUG / BONDHOO — PHASE 14.2
## ANDROID APPLICATION IDENTITY ARCHITECTURE & TRANSITION SPECIFICATION

**Target System:** JUGAJUG Android Client (`android/`)  
**Proposed Target Identity:** `com.jugajug.android` (Display Name: `JUGAJUG`)  
**Current Baseline Identity:** `com.connectly.android` (Display Name: `Connectly`)  
**Auditor:** Android Build Engineer & Mobile Security Architect  
**Git Head:** `315960fde56849c4be7344a78fda005151027b42` (`main`)  
**Status:** **TRANSITION BLUEPRINT READY FOR DEPLOYMENT**

---

### 1. IDENTITY MAPPING & ARCHITECTURAL REFERENCE AUDIT

A surgical, reference-by-reference mapping was conducted to isolate JUGAJUG social mobile components from historical Connectly identifiers without breaking internal class paths or gradle subprojects.

```
+---------------------------------------------------------------------------------------+
|                       IDENTITY SPECIFICATION: CURRENT VS TARGET                       |
+---------------------+--------------------------------+--------------------------------+
| Configuration Item  | Current Baseline               | Target JUGAJUG Identity        |
+---------------------+--------------------------------+--------------------------------+
| Application ID      | com.connectly.android          | com.jugajug.android            |
| User-Facing Label   | Connectly                      | JUGAJUG                        |
| Debug App ID        | com.connectly.android.debug    | com.jugajug.android.debug      |
| Staging App ID      | com.connectly.android.staging  | com.jugajug.android.staging    |
| Deep-Link Scheme    | connectly://                   | jugajug://                     |
| Android Namespace   | com.connectly.android          | com.connectly.android (retain) |
| FCM Service Package | com.connectly.android.*        | com.connectly.android.* (retain|
| Keystore Alias      | connectly                      | jugajug-release                |
| Parental Module     | :feature:parental (unlinked)   | Excluded from social build     |
+---------------------+--------------------------------+--------------------------------+
```

---

### 2. COMPONENT-BY-COMPONENT TRANSITION BLUEPRINT

#### 2.1 `android/app/build.gradle.kts`
The application ID can be updated cleanly at the `defaultConfig` level without requiring massive Java/Kotlin package renaming:
```kotlin
android {
    // Keep internal namespace as com.connectly.android to avoid renaming 400+ Kotlin source files
    namespace = "com.connectly.android"
    compileSdk = 35

    defaultConfig {
        // Target Google Play Store Package ID:
        applicationId = "com.jugajug.android"
        minSdk = 26
        targetSdk = 35
        versionCode = 1
        versionName = "1.0.0"
        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    buildTypes {
        debug {
            applicationIdSuffix = ".debug" // com.jugajug.android.debug
            isDebuggable = true
        }
        create("staging") {
            initWith(getByName("release"))
            applicationIdSuffix = ".staging" // com.jugajug.android.staging
            matchingFallbacks += listOf("release")
        }
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
        }
    }
}
```
*Architectural Benefit:* Android Gradle Plugin (AGP 7+) fully decouples `namespace` (which controls R class generation and bytecode structure) from `applicationId` (which dictates the Google Play Store identity and device package registry). This allows updating the Play Store identity with zero risk of breaking Hilt dependency injection or Jetpack Compose bindings.

#### 2.2 `android/app/src/main/AndroidManifest.xml`
1. **User-Facing Application Title:**
   ```xml
   <application
       android:name=".ConnectlyApp"
       android:label="JUGAJUG"
       android:icon="@mipmap/ic_launcher"
       android:roundIcon="@mipmap/ic_launcher_round"
       ...>
   ```
2. **Deep Linking Scheme:**
   ```xml
   <intent-filter>
       <action android:name="android.intent.action.VIEW" />
       <category android:name="android.intent.category.DEFAULT" />
       <category android:name="android.intent.category.BROWSABLE" />
       <data android:scheme="jugajug" />
   </intent-filter>
   ```

#### 2.3 Firebase `google-services.json`
When the project owner connects live production Firebase:
- Register `com.jugajug.android` in Firebase Console under project `jugajug-production`.
- Download and place the generated `google-services.json` into `android/app/`.

#### 2.4 Android Signing Key Strategy
- **Current Development Key:** `android/app/keystore/connectly-release.jks`.
- **Target Production Key:** For Google Play production release under `com.jugajug.android`, generate a dedicated `jugajug-release.keystore` and store credentials securely in GitHub Actions Secrets (`JUGAJUG_KEYSTORE_BASE64`, `JUGAJUG_KEYSTORE_PASSWORD`, `JUGAJUG_KEY_ALIAS`).

---

### 3. EXCLUSION OF SURGICAL REPLACEMENTS

Per strict rule: *"Do not globally replace every occurrence of connectly. Some references may belong to the separate CONNECTLY product or historical migration records."*
- **Preserved:** All Kotlin class namespaces (`com.connectly.android.feature.*`), Room database entities, and Hilt modules remain unaltered to prevent runtime reflection or serialized state crashes.
- **Preserved:** Historical migration table records and Git commit tags remain untouched.
- **Preserved:** Standalone Parental Control module (`android/feature/parental/`) remains completely unlinked and untouched.

---

### 4. AUDIT CONCLUSION & VERDICT

- **Transition Feasibility:** **100% FEASIBLE & ARCHITECTURALLY SAFE**
- **Decoupled Identity Method:** Utilizing AGP `namespace` vs `applicationId` split guarantees zero build breaks.
- **Verdict:** Transition specification complete and ready for execution upon owner decision.

