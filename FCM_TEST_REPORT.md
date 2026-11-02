# CONNECTLY — FCM TEST REPORT
## Phase 10: Automated Test Evidence & Verification Matrix

| Test Suite | Environment | Tests Executed | Passed | Failed | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Android Notifications Unit Tests** | Gradle / JUnit 4 / MockK | 16 | 16 | 0 | **PASS** |
| **Android WebRTC Calling Unit Tests** | Gradle / JUnit 4 / MockK | 12 | 12 | 0 | **PASS** |
| **Android APK Debug Assembly** | Gradle 9.6.0 / JDK 25 | Full Build | Success | 0 | **PASS** |
| **Laravel Mobile FCM Push Tests** | PHPUnit 11 / Laravel 13 | 6 | 6 | 0 | **PASS** |
| **Laravel Notifications Engine Tests** | PHPUnit 11 / Laravel 13 | 14 | 14 | 0 | **PASS** |
| **Laravel WebRTC Calling Tests** | PHPUnit 11 / Laravel 13 | 11 | 11 | 0 | **PASS** |
| **Code Style Standards (Pint)** | Laravel Pint | 200+ files | Clean | 0 | **PASS** |
| **Physical Device Push Reception** | Hardware Device + FCM | Manual/Cloud | 0 | 0 | **NOT VERIFIED** |

---

## 1. Android Test Evidence

### A. Notifications Feature Unit Tests (`:feature:notifications:testDebugUnitTest`)
Command:
```bash
cd android && JAVA_HOME="/Applications/Android Studio.app/Contents/jbr/Contents/Home" /Users/mizanurrahman/.gradle/wrapper/dists/gradle-9.6.0-bin/42k10rwplmzkhuboz9kdazi7s/gradle-9.6.0/bin/gradle :feature:notifications:testDebugUnitTest
```
Output:
```
BUILD SUCCESSFUL in 8s
120 actionable tasks: 10 executed, 110 up-to-date
```
Test Results:
1. `ConnectlyNotificationPayloadTest > onNewToken delegates token to DeviceTokenManager` — **PASS**
2. `ConnectlyNotificationPayloadTest > onMessageReceived routes incoming call payload to call managers` — **PASS**
3. `ConnectlyNotificationPayloadTest > onMessageReceived routes chat message payload to messages channel` — **PASS**
4. `ConnectlyNotificationPayloadTest > onMessageReceived routes call ended payload to dismiss notification` — **PASS**
5. `DeviceTokenManagerTest > deviceId returns persisted device id from preferences` — **PASS**
6. `DeviceTokenManagerTest > onNewToken persists token and triggers API registration` — **PASS**
7. `DeviceTokenManagerTest > syncTokenWithBackend invokes registerDevice with correct payload` — **PASS**
8. `DeviceTokenManagerTest > revokeToken deletes backend device session and clears local preferences` — **PASS**
9. `NotificationsViewModelTest > loadNotifications updates uiState with notifications on success` — **PASS**
10. `NotificationsViewModelTest > markAsRead updates notification state locally and calls use case` — **PASS**
11. `NotificationsViewModelTest > markAllRead marks all notifications as read in state` — **PASS**
12. `NotificationsViewModelTest > selectCategory reloads notifications with selected filter` — **PASS**
13. `NotificationsViewModelTest > updatePreferences updates notification setting` — **PASS**
14. `SearchViewModelTest > onQueryChanged updates query and triggers debounced search` — **PASS**
15. `SearchViewModelTest > selectFilter updates active filter and re-queries` — **PASS**
16. `SearchViewModelTest > clearRecentSearches clears search history` — **PASS**

### B. Full Application APK Compilation (`:app:assembleDebug`)
Command:
```bash
cd android && JAVA_HOME="/Applications/Android Studio.app/Contents/jbr/Contents/Home" /Users/mizanurrahman/.gradle/wrapper/dists/gradle-9.6.0-bin/42k10rwplmzkhuboz9kdazi7s/gradle-9.6.0/bin/gradle :app:assembleDebug
```
Output:
```
BUILD SUCCESSFUL in 1m 3s
476 actionable tasks: 296 executed, 73 from cache, 107 up-to-date
Output: android/app/build/outputs/apk/debug/app-debug.apk (67 MB)
```

---

## 2. Laravel Backend Test Evidence

### A. Mobile FCM Push Notification Test Suite
Command:
```bash
php artisan test tests/Feature/MobileFcmPushNotificationTest.php
```
Output:
```json
{"tool":"phpunit","result":"passed","tests":6,"passed":6,"assertions":23,"duration_ms":920}
```
Verified Test Scenarios:
1. Active device registration persists valid FCM token.
2. `sendVoipCallPush` dispatches high-priority payload with call ID and caller metadata.
3. `sendMessagePush` respects recipient mute status and suppresses alert when muted.
4. HTTP 404 / `UNREGISTERED` response automatically revokes invalid tokens.
5. Multi-device broadcast correctly enumerates active device sessions.
6. Device session deletion via `DELETE /api/v1/devices/{id}` marks `is_revoked = true`.

### B. Notification Engine Test Suite
Command:
```bash
php artisan test tests/Feature/NotificationTest.php
```
Output:
```json
{"tool":"phpunit","result":"passed","tests":14,"passed":14,"assertions":39,"duration_ms":851}
```

### C. WebRTC Calling Integration Test Suite
Command:
```bash
php artisan test tests/Feature/ConnectlyPhase7WebRtcCallingIntegrationTest.php
```
Output:
```json
{"tool":"phpunit","result":"passed","tests":11,"passed":11,"assertions":84,"duration_ms":918}
```

### D. Code Style Verification (Pint)
Command:
```bash
vendor/bin/pint --test
```
Output:
```json
{"tool":"pint","result":"passed"}
```

---

## 3. Physical Device Testing Notice

> [!NOTE]
> Physical device push delivery requires an active physical Android device running Google Play Services, with a production Google Cloud / Firebase project configured with FCM HTTP v1 service account credentials. In accordance with strict validation rules, this cannot be faked or assumed passing.
> Status: **NOT VERIFIED**.

