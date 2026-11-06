# BONDHOO PHASE 14.5.4 — REMEDIATION CHANGELOG

**Date:** November 5, 2026  
**Auditor:** Production Release Manager & Senior Engineer  
**Scope:** Complete list of additive, non-destructive changes applied during Phase 14.5.4  
**Status:** **COMMITTED TO WORKSPACE**

---

## 1. Modified & Created Files Changelog

### Backend (Laravel 11 / PHP 8.5)
1. **`app/Models/Page.php`:**
   - Fixed `getSocialLinksAttribute` to preserve associative dictionary keys (`github`, `facebook`, etc.) instead of forcing numeric indexing via `array_values`.
   - Formatted with Pint.
2. **`app/Http/Controllers/Web/SettingsWebController.php`:**
   - Added `accountDeletion()` to render public account deletion guidance and form.
   - Added `submitAccountDeletionRequest()` with validation, rate limiting, and audit logging.
3. **`resources/views/legal/account-deletion.blade.php`:**
   - Created public legal compliance page fulfilling Google Play User Data policy.
4. **`routes/web.php`:**
   - Replaced internal redirect `/account/delete` with dedicated controller route and request submission endpoint.
5. **`tests/Feature/PublicAccountDeletionPolicyTest.php`:**
   - Added automated feature tests verifying public account deletion page loading and request submission.

### Frontend Web (Vue 3 / Vite 8)
1. **`resources/js/Components/Navbar.vue`:**
   - Updated logo and icon branding to use official BONDHOO identity with `:src="'/images/bondhoo-icon.png'"`.
   - Verified clean production compilation with `npm run build`.

### Android Mobile (Kotlin / Jetpack Compose)
1. **`android/app/src/main/res/values-bn/strings.xml`:**
   - Corrected Bengali application label from `কানেক্টলি` to `বন্ধু`.
2. **`android/core/network/src/main/java/.../ConnectlyApi.kt`:**
   - Added `type` and `likes_count` convenience getters to `PostDto`.
   - Added `last_message_preview` and `last_message_at` convenience getters to `PageInboxMessageDto`.
   - Added `is_public` property to `PageSettingsDto`.
3. **`android/feature/groups/src/main/java/.../PageProfileScreen.kt`:**
   - Aligned tab chip indices (0: Home, 1: About, 2: Reviews, 3: Products, 4: Services, 5: Media) with content branches.
   - Removed duplicate `else` block.
4. **`android/feature/groups/src/main/java/.../GroupsUseCases.kt`:**
   - Implemented `DeletePageUseCase` injected into `PageSettingsViewModel`.

