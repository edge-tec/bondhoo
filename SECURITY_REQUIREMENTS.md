# Connectly Android — Security Requirements & Hardening Standards

**Document Version:** 1.0.0  
**Security Standard:** OWASP Mobile Application Security Verification Standard (MASVS Level 2)  
**Compliance Target:** Android Enterprise Security & Data Privacy Guidelines

---

## 1. Secure Authentication & Credential Storage

### 1.1. Android KeyStore & Encrypted DataStore
- **Requirement:** Access tokens, refresh tokens, and session secrets MUST NEVER be stored in plain text, standard `SharedPreferences`, or SQLite databases.
- **Implementation:**
  - Leverage `androidx.security.crypto.EncryptedSharedPreferences` backed by the hardware-backed **Android KeyStore** (`MasterKey.DEFAULT_MASTER_KEY_ALIAS`).
  - Symmetric encryption with **AES-256 GCM** for payload encryption.
  - Key material generated inside the Trusted Execution Environment (TEE) or StrongBox Keymaster.

```kotlin
// TokenStorageManager.kt
class TokenStorageManager @Inject constructor(
    @ApplicationContext context: Context
) {
    private val masterKey = MasterKey.Builder(context)
        .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
        .build()

    private val sharedPreferences = EncryptedSharedPreferences.create(
        context,
        "connectly_secure_prefs",
        masterKey,
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
    )

    fun saveAccessToken(token: String) {
        sharedPreferences.edit().putString("auth_token", token).apply()
    }

    fun getAccessToken(): String? = sharedPreferences.getString("auth_token", null)

    fun clear() {
        sharedPreferences.edit().clear().apply()
    }
}
```

### 1.2. Automatic Session Revocation & Inactivity Timeout
- **Token Invalidation:** When any API request returns an HTTP `401 Unauthorized` with an invalid token payload, the `TokenAuthenticator` immediately clears secure storage, halts background sync workers, and dispatches a navigation event to `LoginRoute`.
- **Remote Revocation:** When a user revokes a session from Screen 10 (Settings > Account > Device Sessions), the local token is invalidated.

---

## 2. Network Transport Security & Certificate Pinning

### 2.1. Strict HTTPS & Cleartext Prohibition
- Cleartext traffic (`http://`) is strictly prohibited in all release builds via `network_security_config.xml`.

```xml
<!-- res/xml/network_security_config.xml -->
<?xml version="1.0" encoding="utf-8"?>
<network-security-config>
    <base-config cleartextTrafficPermitted="false">
        <trust-anchors>
            <certificates src="system" />
        </trust-anchors>
    </base-config>
    <domain-config>
        <domain includeSubdomains="true">api.connectly.com</domain>
        <domain includeSubdomains="true">media.connectly.com</domain>
        <pin-set expiration="2027-12-31">
            <!-- Primary Certificate Pin -->
            <pin digest="SHA-256">47DEQpj8HBSa+/TImW+5JCeuQeRkm5NMpJWZG3hSuFU=</pin>
            <!-- Backup Certificate Pin -->
            <pin digest="SHA-256">k2ErWTSpWKuh1zgFQK11qQOrLyfNcNPiumFlPdUtwZZ=</pin>
        </pin-set>
    </domain-config>
</network-security-config>
```

### 2.2. TLS 1.3 Enforcement
- The OkHttp client explicitly requires TLS 1.3 / TLS 1.2 modern cipher suites, rejecting obsolete SSL/TLS protocols:

```kotlin
val connectionSpec = ConnectionSpec.Builder(ConnectionSpec.MODERN_TLS)
    .tlsVersions(TlsVersion.TLS_1_3, TlsVersion.TLS_1_2)
    .cipherSuites(
        CipherSuite.TLS_AES_128_GCM_SHA256,
        CipherSuite.TLS_AES_256_GCM_SHA384,
        CipherSuite.TLS_CHACHA20_POLY1305_SHA256,
        CipherSuite.TLS_ECDHE_ECDSA_WITH_AES_128_GCM_SHA256,
        CipherSuite.TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256
    )
    .build()
```

---

## 3. Biometric & Re-Authentication Guard

- **Target Actions:** Modifying security settings, exporting recovery codes, transferring group ownership, or deleting an account.
- **Requirement:** Class 3 (Strong) Biometric authentication with device credential fallback (`BIOMETRIC_STRONG or DEVICE_CREDENTIAL`):

```kotlin
val biometricPrompt = BiometricPrompt(
    activity,
    executor,
    object : BiometricPrompt.AuthenticationCallback() {
        override fun onAuthenticationSucceeded(result: BiometricPrompt.AuthenticationResult) {
            // Proceed with sensitive action
        }
    }
)

val promptInfo = BiometricPrompt.PromptInfo.Builder()
    .setTitle("Confirm Identity")
    .setSubtitle("Authenticate to perform sensitive action")
    .setAllowedAuthenticators(BiometricManager.Authenticators.BIOMETRIC_STRONG or BiometricManager.Authenticators.DEVICE_CREDENTIAL)
    .build()
```

---

## 4. Runtime Permissions Architecture (Android 13 - 15)

The app adheres strictly to the **Principle of Least Privilege**, requesting permissions only just-in-time when the user activates the corresponding capability:

| Capability | Manifest Permission | Rationale & Granular Fallback |
| :--- | :--- | :--- |
| **Notifications** | `POST_NOTIFICATIONS` (API 33+) | Prompted during onboarding or when enabling chat alerts |
| **Post / Story Media** | `READ_MEDIA_IMAGES`, `READ_MEDIA_VIDEO` | Modern Android 13+ granular media access; prefer Photo Picker without permission where possible |
| **Stories & Reels Camera** | `CAMERA`, `RECORD_AUDIO` | Just-in-time request when tapping "Camera" or "Record Reel" |
| **WebRTC Audio/Video Calls** | `RECORD_AUDIO`, `CAMERA` | Prompted when placing or answering an audio/video call |
| **Calling Background Service** | `FOREGROUND_SERVICE_MICROPHONE`, `FOREGROUND_SERVICE_CAMERA` | Required for continuous calling when app is minimized (Android 14+) |
| **Live Streaming Broadcaster** | `FOREGROUND_SERVICE_MEDIA_PROJECTION`, `FOREGROUND_SERVICE_CAMERA` | Required for streaming screen or camera video continuously |

---

## 5. Screen Security & Data Leakage Prevention

1. **`FLAG_SECURE` on Sensitive Screens:**
   - Activities/Screens displaying 2FA QR codes, OTP recovery phrases, or private billing details apply `WindowManager.LayoutParams.FLAG_SECURE` to prevent screen recording, screenshots, and task switcher snapshots.
2. **ProGuard / R8 Obfuscation & Resource Shrinking:**
   - Release builds enable minification and resource shrinking:

```groovy
buildTypes {
    release {
        minifyEnabled true
        shrinkResources true
        proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
    }
}
```

3. **Log Sanitization:**
   - Production builds strip all `Log.v`, `Log.d`, and `Log.i` calls.
   - PII (Personally Identifiable Information), passwords, auth tokens, and full message bodies are strictly excluded from crash reporting (Firebase Crashlytics).

---

## 6. Device Integrity & Anti-Tampering

- **Google Play Integrity API:** Used during user registration and financial transactions (virtual gifts) to verify that the binary is genuinely signed by Google Play and running on an untampered Android device.
- **Root & Emulation Heuristics:** Basic heuristic checks detect debugger attachments in release builds and unauthorized runtime hooking frameworks.
