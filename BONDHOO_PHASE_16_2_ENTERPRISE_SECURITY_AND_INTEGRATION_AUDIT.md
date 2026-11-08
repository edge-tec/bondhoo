# BONDHOO (বন্ধু) / Bondoo — PHASE 16.2
# ENTERPRISE SECURITY AUDIT, CROSS-PLATFORM INTEGRATION & PRODUCTION HARDENING REPORT

**Date:** 2026-11-07  
**Application:** BONDHOO (বন্ধু) / Bondoo (`com.jugajug.android.debug` / Laravel 12 + Vue 3 Inertia + MySQL + Jetpack Compose)  
**Branch:** `main` (Base Commit: `f41453c`)  
**Phase:** 16.2 — Enterprise-Grade Independent Security Audit, Cross-Platform Integration & Production Hardening

---

## 1. Executive Summary

An independent end-to-end code audit, security hardening pass, API contract alignment, database integrity upgrade, Android & Website UI/UX synchronization, automated unit/feature test expansion, and live two-account cross-platform verification were executed across **Marketplace (বন্ধু বাজার)**, **Learning & Development (LMS)**, **Authentication/RBAC**, **Messenger Integration**, and **Wallet/Escrow Transactions**.

All critical and high-severity vulnerabilities discovered during code inspection and live runtime testing were resolved non-destructively without removing any existing user records, marketplace listings, orders, or course enrollments:

- **Privilege Escalation Eliminated:** Removed the `str_contains($this->email, 'admin@')` bypass in `User::isAdmin()` and eliminated `APP_ENV=local` automatic `User::first()` guest login bypasses in `SocialWebController` and `MarketplaceWebController`.
- **Marketplace Ownership, Concurrency & Financial Safety:** Enforced strict `(int)` ownership validation (`is_owner`), policy/gate enforcement BEFORE `trashed()` short-circuiting on `DELETE` (preventing `200` status leaks to non-owners on soft-deleted listings), `DB::transaction()` + `lockForUpdate()` stock reservation, HTTP `409 Conflict` (`LISTING_UNAVAILABLE_CONFLICT`) on single-quantity race conditions, buyer-scoped `(buyer_id, idempotency_key)` deduplication, HMAC payment callback verification, and atomic escrow release upon buyer delivery confirmation.
- **Messenger Cross-User Inquiry Fix:** Fixed `MarketplaceService::contactSeller` so direct Marketplace inquiries between non-friends immediately activate `ConversationParticipant` (`is_request = false`, `request_status = 'accepted'`) for both buyer and seller and deep-link Android directly into the live chat room.
- **Learning & Development Security & Full Website Parity:** Hardened `EnrollmentService::enroll` with `lockForUpdate()` on course, case-insensitive coupon validation (`course_id`, `is_active`, `expires_at`, `max_uses`), atomic `Wallet` debit + instructor commission split before activating or reactivating cancelled enrollments, protection of `completed` enrollments against status downgrade, non-enrolled curriculum media redaction, and full **Website Learning Hub** (`/learning`, `/app/learning`, `/learning/courses/{slug}`, `/verify/cert/{code}`) synchronized with Android.
- **Android Serialization & State Hardening:** Eliminated runtime Kotlinx Serialization crashes (`Map<String, Any?>` on `updateLessonProgress`, `ApiResponse<Unit>` vs object on `contactMarketplaceSeller`, paginated `MarketplaceOrdersResponse` envelope), replaced hardcoded dummy course/lesson IDs and fake seller badges with real backend fields, and added single-flight duplicate-tap guards and error rollback.

### Feature Classification Matrix

| Module / Capability | Status Classification | Verification Evidence |
| :--- | :--- | :--- |
| Marketplace Ownership (`is_owner` Server + Web + Android) | **Fully implemented and verified** | `Phase162MarketplaceAndLearningSecurityAuditTest`, `MarketplaceViewModelTest`, Live E2E Scenario A |
| Idempotent SoftDeletes Listing Deletion & Non-Owner `403` | **Fully implemented and verified** | `Phase16MarketplaceDeleteTest`, `Phase162MarketplaceAndLearningSecurityAuditTest`, Live E2E Scenario A |
| Marketplace Order Concurrency (`lockForUpdate` + HTTP `409`) | **Fully implemented and verified** | `Phase162MarketplaceAndLearningSecurityAuditTest`, `MarketplaceViewModelTest`, Live E2E Scenario B |
| Buyer-Scoped Order Idempotency (`buyer_id`, `idempotency_key`) | **Fully implemented and verified** | Migration `2026_11_07_020000`, `Phase162MarketplaceAndLearningSecurityAuditTest` |
| Offer, Counteroffer, Buyer Counter-Accept & Order Redemption | **Fully implemented and verified** | `MarketplaceService`, `Phase162MarketplaceAndLearningSecurityAuditTest`, Live E2E Scenario B |
| Seller Contact -> Active Messenger Thread (Web & Android) | **Fully implemented and verified** | `MarketplaceService::contactSeller`, `MarketplaceScreen.kt`, Live E2E Scenario B (`conversation_id: 6`) |
| Sandbox / HMAC Payment Verification & Buyer Escrow Release | **Fully implemented and verified** | `SandboxGatewayDriver`, `BkashGatewayDriver`, Live E2E Scenario B |
| Live Production bKash / Nagad / SSLCommerz / Stripe Merchant Keys | **Requires external configuration** | Drivers implemented; production credentials (`BKASH_APP_KEY`, `BKASH_APP_SECRET`, etc.) configured via `.env` |
| Learning Free Enrollment (`{}` / `EnrollCourseRequest` DTO) | **Fully implemented and verified** | `LearningEnrollmentApiController`, `LearningViewModelTest`, Live E2E Scenario C |
| Learning Paid Enrollment (Atomic Wallet Debit + Coupons) | **Fully implemented and verified** | `EnrollmentService`, `Phase13Stage05WalletAndEnrollmentIntegrityTest`, Live E2E Scenario D |
| Lesson Progress Sync, Auto-Completion & Certificate Verification | **Fully implemented and verified** | `LearningProgressService`, `CertificateService`, `/verify/cert/{code}`, Live E2E Scenario C |
| Website Learning Hub (`/learning`, `/learning/courses/{slug}`) | **Fully implemented and verified** | `LearningWebController`, `resources/js/Pages/Learning/Index.vue`, `VerifyCertificate.vue`, Live HTTP `200` |
| Cross-Platform Sync (Android + Website + MySQL) | **Fully implemented and verified** | Live E2E Scenarios A–F + `emulator-5554` Debug APK installation & screenshots |

---

## 2. Existing Architecture Assessment

- **Backend:** Laravel 12 on PHP 8.5, Sanctum token + stateful web session authentication, modular domain services (`MarketplaceService`, `CourseService`, `EnrollmentService`, `LearningProgressService`, `CertificateService`, `MessengerService`, `NotificationService`), Eloquent Policies, and MySQL database.
- **Website Frontend:** Hybrid Vue 3 + Inertia.js SPA (`resources/js/Pages/...`) with legacy Blade fallback views (`resources/views/marketplace/show.blade.php`) compiled via Vite 8 + Tailwind CSS.
- **Android Client:** Multi-module Clean Architecture Kotlin + Jetpack Compose + Hilt DI + Retrofit + Kotlinx Serialization (`:app`, `:core:network`, `:feature:profile`, `:feature:learning`, `:feature:messenger`, `:feature:feed`, `:feature:jobs`, `:feature:live`).

---

## 3. Marketplace Root Cause Analysis

1. **Privilege Escalation via Email Substring (`User::isAdmin()`):** `User::isAdmin()` previously checked `str_contains(strtolower($this->email), 'admin@')`, allowing an unprivileged user registering an email like `admin@external-domain.com` or `my-admin@domain.com` to pass admin checks.
2. **Soft-Deleted Listing Status Leak on `DELETE`:** `MarketplaceApiController::destroy` previously checked `$product->trashed()` and returned HTTP `200` *before* verifying `$product->seller_id === $user->id`, allowing a non-owner probing product IDs to distinguish soft-deleted listings (`200`) from active listings (`403`).
3. **Global vs Buyer-Scoped `idempotency_key`:** `marketplace_orders.idempotency_key` had a global unique index, allowing cross-buyer key collisions, and lacked an atomic link (`accepted_offer_id` / `redeemed_at`) to negotiated counteroffers.
4. **Sold/Zero-Stock Items Remaining in Public Discover:** Single-quantity items (`stock = 1`) decremented `stock` to `0` on order placement, blocking second buyers with `409`, yet remained `status = 'active'` in `getLatestListings()` without a `stock > 0` filter.
5. **Marketplace Seller Inquiry Hidden in Messenger:** `MessengerService::getOrCreateDirectConversation` marks non-friend conversations as `is_request = true, request_status = 'pending'`, causing `GET /api/v1/conversations` (`filter = 'all'`) to hide Marketplace inquiries from the main chat list. Furthermore, Android declared `contactMarketplaceSeller` as `Response<ApiResponse<Unit>>` even though the server returns `data: { conversation_id, ... }`.
6. **Android Orders & Saved Items Desync:** `ConnectlyApi.getMarketplaceOrders` expected `data` to be a JSON array, whereas `MarketplaceV2Controller::orders` returns a Laravel paginator object inside `data` alongside `items: [...]`. Meanwhile, `loadSavedListings()` filtered currently loaded `products` locally instead of calling `GET /api/v1/marketplace/favorites`.
7. **Live Web `/marketplace` TypeError & Local Auto-Login:** `SocialWebController::resolveViewer` auto-logged unauthenticated visitors into `User::first()` when `APP_ENV=local`, and `SocialWebController::marketplace` called `->map(fn (MarketplaceProduct $p) => ...)` on `getSavedListings()`, which already transforms items into arrays via `through()`.

---

## 4. Learning Root Cause Analysis

1. **Android `updateLessonProgress` Runtime Crash:** `ConnectlyApi.updateLessonProgress` used `@Body body: Map<String, @JvmSuppressWildcards Any?>`. Kotlinx Serialization cannot serialize `Any`, throwing a `SerializationException` whenever a learner tapped **Mark Complete**.
2. **Hardcoded Dummy Course & Lesson ID `1L`:** `LearningRepositoryImpl.getCourseDetails(slug)` returned a hardcoded dummy `LearningCourse` instead of calling `GET /api/v1/learning/courses/{slug}`, and `LessonPlayerDialog` initialized `selectedLessonId = 1L` instead of the first incomplete lesson of the selected course.
3. **Cancelled & Completed Enrollment Edge Cases:** Re-enrolling on a `cancelled` or `refunded` enrollment needed strict wallet debit enforcement before reactivation, while repeating `enroll` on a `completed` course needed to preserve `status = 'completed'` rather than resetting to `active`.
4. **Coupon Validation Gaps:** `LearningCourseCoupon` validation needed case-insensitive code matching (`UPPER(code)`), strict `course_id` scoping under `lockForUpdate()`, and post-wallet-verification `times_used` increment.
5. **Missing Website Learning Module:** While backend APIs and Android screens existed for Phase 13 LMS, the Website lacked `/learning`, `/app/learning`, `/learning/courses/{slug}`, `/verify/cert/{code}`, and Sidebar navigation links, and `Index.vue` needed `"expert"` (matching the DB enum) instead of `"advanced"`.

---

## 5. Authorization Audit

- **RBAC Hardening ([User.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/User.php)):** `isAdmin()` now strictly checks verified RBAC roles (`ADMIN`, `SUPER_ADMIN`) or `users.role` column values (`admin`, `super_admin`) — never email substrings.
- **Policies Enforced:**
  - [MarketplaceProductPolicy.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Policies/MarketplaceProductPolicy.php) — `update`, `delete`, `updateStatus` restricted to `(int) $product->seller_id === (int) $user->id || $user->isAdmin()`.
  - [MarketplaceOrderPolicy.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Policies/MarketplaceOrderPolicy.php) — `view` restricted to buyer/seller/admin; `ship` to seller/admin; `confirmDelivery` & `releaseEscrow` strictly to buyer/admin.
  - [MarketplaceOfferPolicy.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Policies/MarketplaceOfferPolicy.php) — `accept`/`reject` restricted to recipient party (seller on `pending`, buyer on `countered`); `counter` to seller; `cancel` to buyer.
  - [LearningCoursePolicy.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Policies/LearningCoursePolicy.php) & [LearningCourseLessonPolicy.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Policies/LearningCourseLessonPolicy.php) — Draft/unpublished courses and non-preview lessons restricted to course instructor, enrolled active/completed students, or admins.
- **Rate Limiting ([AppServiceProvider.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Providers/AppServiceProvider.php)):** Dedicated `marketplace-learning` rate limiter (`180/min` reads, `30/min` mutations per user/IP) applied across Marketplace and Learning route groups.

---

## 6. Ownership Validation

- All ownership comparisons across PHP controllers, policies, and models use strict integer casting `(int) $resource->owner_id === (int) $user->id`.
- `MarketplaceProduct::toResponseArray(?User $viewer)` computes `is_owner` server-side (`$viewer ? (int) $this->seller_id === (int) $viewer->id : false`).
- **UI Separation Verified Across Android & Web:**
  - **Owner (`is_owner == true`):** Sees `YOUR ITEM` badge, `Edit`, `Pause`/`Activate`, `Mark Sold`, and `Delete Listing` confirmation; never sees `Buy Now`, `Make Offer`, `Message Seller`, `Save`, or `Report`.
  - **Non-Owner (`is_owner == false`):** Sees `Bookmark/Save`, `Share`, `Message Seller`, `Report`, and — strictly when `status == "active"` and `stock > 0` — `Make Offer` and `Buy Now`; never sees `Edit`, `Pause`, `Mark Sold`, or `Delete`.

---

## 7. API Contract Audit

| Endpoint | Method | Contract Hardening Applied |
| :--- | :--- | :--- |
| `/api/v1/marketplace/products/{id}` | `GET`, `PUT`, `DELETE` | Consistent `is_owner`, `is_favorited`, `stock`, zero seller PII (`email`/`phone` stripped); `403` before `trashed()` check on `DELETE`; `data: null` safe on Android |
| `/api/v1/marketplace/products/{id}/status` | `PATCH` | Owner-only `active` / `inactive` / `sold` status transition |
| `/api/v1/marketplace/favorites` | `GET` | Paginated favorited active/sold products with `is_favorited: true` |
| `/api/v1/marketplace/products/{id}/contact` | `POST` | Returns `{ success: true, data: { conversation_id, seller_id, message_sent } }` |
| `/api/v1/marketplace/products/{id}/order` | `POST` | `201` new order, `200` duplicate `idempotency_key` for same buyer, `409` (`LISTING_UNAVAILABLE_CONFLICT`) on stock exhaustion, `422` on self-purchase |
| `/api/v1/marketplace/orders` | `GET` | Supports `?role=buyer\|seller`; Android `MarketplaceOrdersResponse` decodes both `items` and paginated `data.data` |
| `/api/v1/learning/courses/{id}/enroll` | `POST` | Accepts `{}` or `{ "coupon_code": "..." }`; returns `200` with enrollment or `422` with clear message |
| `/api/v1/learning/my-learning` | `GET` | Includes both `active` and `completed` enrollments + `meta` (`enrolled_count`, `in_progress_count`, `completed_count`, `certificates_count`) |
| `/api/v1/learning/courses/{id}/curriculum` | `GET` | Batch-loads learner `is_completed` & `last_position_seconds` in 1 query; redacts `video_url` & `content` on locked lessons for non-enrolled viewers |
| `/api/v1/learning/lessons/{id}/progress` | `POST` | Accepts typed `UpdateLessonProgressRequest(position_seconds, mark_completed)` |
| `/api/v1/learning/certificates/verify/{code}` | `GET` | Public alphanumeric certificate verification checking `is_revoked` & `revoked_at` |

---

## 8. Android Architecture Audit

- **Network Layer ([ConnectlyApi.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/core/network/src/main/java/com/connectly/android/core/network/api/ConnectlyApi.kt)):** Added `ApiErrorParser.extractMessage()`, `MarketplaceOrdersResponse.resolvedOrders()`, `ContactSellerResponseDto`, `UpdateMarketplaceProductRequest`, `UpdateMarketplaceStatusRequest`, `ShipMarketplaceOrderRequest`, `CancelMarketplaceOrderRequest`, `LearningCourseDetailsResponseDto`, `LearningMyLearningResponse`, `EnrollCourseRequest`, and `UpdateLessonProgressRequest`.
- **Marketplace Presentation ([MarketplaceViewModel.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/profile/src/main/java/com/connectly/android/feature/profile/presentation/marketplace/MarketplaceViewModel.kt), [MarketplaceScreen.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/profile/src/main/java/com/connectly/android/feature/profile/presentation/marketplace/MarketplaceScreen.kt)):** Added `deletingProductIds` single-flight guard, `EditListingDialog`, owner status controls, live Messenger navigation via `pendingChatConversationId` -> `ProfileNavigation.kt` -> `MainActivity.kt`, buyer/seller order action buttons (`Mark as Shipped`, `Confirm Delivery & Release Escrow`, `Accept Counter`, `Cancel Offer`), and `409 Conflict` UI recovery.
- **Learning Presentation ([LearningRepositoryImpl.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/learning/src/main/java/com/connectly/android/feature/learning/data/repository/LearningRepositoryImpl.kt), [LearningViewModel.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/learning/src/main/java/com/connectly/android/feature/learning/presentation/learning/LearningViewModel.kt), [LearningHomeScreen.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/learning/src/main/java/com/connectly/android/feature/learning/presentation/learning/LearningHomeScreen.kt)):** Replaced dummy `getCourseDetails`, added `enrollingCourseIds` & `completingLessonIds` guards, added Level (`Beginner`, `Intermediate`, `Expert`) & Pricing (`Free`, `Paid`) filter chips, coupon code input, inline error banner + Retry without false `Enrolled` state, and dynamic initial lesson selection in `LessonPlayerDialog`.

---

## 9. Website Integration Audit

- **Marketplace Web Parity:** Updated `ProductCard.vue`, `Marketplace/Index.vue`, `Marketplace/Show.vue`, `Marketplace/MyListings.vue`, `Marketplace/Orders.vue`, `Marketplace/OrderTracking.vue`, and `marketplace/show.blade.php` for strict `is_owner` gating, non-active Buy/Offer suppression, buyer counteroffer acceptance, Saved tab sync, and zero local guest auto-login bypass.
- **New Website Learning Module:** Created [LearningWebController.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Http/Controllers/Web/LearningWebController.php), [Index.vue](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/resources/js/Pages/Learning/Index.vue), and [VerifyCertificate.vue](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/resources/js/Pages/Learning/VerifyCertificate.vue), wired into `SidebarNavigation.vue` and `AppLayout.vue` with Discover filters, Coupon Enrollment, My Learning progress player, and Public Certificate Verification (`/verify/cert/{code}`).

---

## 10. Database Integrity Audit

Additive, non-destructive schema hardening applied via [2026_11_07_020000_phase_16_2_security_and_integrity_hardening.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/database/migrations/2026_11_07_020000_phase_16_2_security_and_integrity_hardening.php):
- `users`: Added `seller_rating` (`decimal(3,2)`) and `seller_reviews_count` (`unsignedInteger`) synced automatically from `MarketplaceReview` model hooks.
- `marketplace_products`: Added `stock` (`unsignedInteger`, default `1`) + composite index `(status, created_at)`.
- `marketplace_orders`: Replaced global `idempotency_key` unique index with buyer-scoped unique `(buyer_id, idempotency_key)` and added `accepted_offer_id` foreign key (`nullOnDelete`).
- `marketplace_offers`: Added `redeemed_at` timestamp and composite index `(product_id, status)`.
- `learning_certificates`: Added `revoked_at` timestamp.
- Existing `SoftDeletes` on `marketplace_products` and `learning_courses` preserve historical `marketplace_orders`, `marketplace_payment_transactions`, `learning_enrollments`, and `learning_transactions`.

---

## 11. Transaction Safety Audit

- **Marketplace Order Placement (`MarketplaceService::placeOrder`):** Wrapped in `DB::transaction()` with `MarketplaceProduct::lockForUpdate()` and `MarketplaceOffer::lockForUpdate()`. Checks buyer-scoped `idempotency_key`, decrements `stock`, throws `MarketplaceConflictException` (`409`) when `stock < quantity`, computes server-authoritative `subtotal` + `delivery_charge`, and marks `acceptedOffer->redeemed_at`.
- **Marketplace Delivery & Cancellation:** `confirmDelivery()` locks the order row and atomically transitions `escrow_status` from `held` to `released`. `cancelOrder()` locks both order and product rows, increments `stock`, and restores `sold` -> `active` when `stock > 0`.
- **Course Enrollment (`EnrollmentService::enroll`):** Wrapped in `DB::transaction()` with `LearningCourse::lockForUpdate()`, `LearningEnrollment::lockForUpdate()`, `LearningCourseCoupon::lockForUpdate()`, and `Wallet::lockForUpdate()` (both student and instructor wallets).

---

## 12. Security Vulnerabilities Found & Remediated

| ID | Severity | Vulnerability | Remediation |
| :--- | :--- | :--- | :--- |
| SEC-01 | **Critical** | Email substring `admin@` privilege escalation in `User::isAdmin()` | Restricted to verified RBAC roles and explicit admin role column |
| SEC-02 | **Critical** | Unauthenticated `APP_ENV=local` auto-login as `User::first()` on Web routes | Removed local auto-login bypass in `SocialWebController` & `MarketplaceWebController` |
| SEC-03 | **High** | Forged/uninitiated payment verification callbacks accepted in non-prod | Enforced HMAC `hash_equals` signature check OR verified server-initiated `MarketplacePaymentTransaction` reference + exact amount/currency match |
| SEC-04 | **High** | Cancelled paid course enrollment could bypass wallet check or completed status overwritten | Enforced full wallet debit before reactivating `cancelled`/`refunded` records and preserved `completed` status |
| SEC-05 | **High** | Non-enrolled users could read `video_url` / `content` on non-preview curriculum lessons | Redacted `video_url` and `content` (`null`) for non-preview lessons unless enrolled/instructor/admin |
| SEC-06 | **Medium** | Soft-deleted listing `DELETE` returned `200` to non-owners before ownership check | Moved `seller_id` / `isAdmin()` check ahead of `$product->trashed()` idempotent `200` return |
| SEC-07 | **Medium** | Seller PII (`email`, `phone`) exposure risk in public product serialization | Stripped PII from `MarketplaceProduct::toResponseArray()` public seller object |
| SEC-08 | **Medium** | Raw SQL `LIKE` wildcard injection in search filters | Escaped `%` and `_` wildcards in `MarketplaceService` and `CourseService` search queries |

---

## 13. Performance Findings

- **Eliminated N+1 Lesson Progress Queries:** `LearningCurriculumApiController::index` now batch-fetches all `LearningLessonProgress` rows for the viewer and course in a single `whereIn('lesson_id', ...)` query instead of querying per lesson.
- **Database Indexing:** Added `(status, created_at)` index on `marketplace_products` and `(product_id, status)` index on `marketplace_offers`.
- **Pagination Enforced:** All Marketplace listings, Saved items, Orders, Courses, and My Learning endpoints use bounded `per_page` (`min(50, ...)`), avoiding full-table memory loads.
- **Vite Production Asset Chunking:** `npm run build` compiled 680 modules cleanly in `3.69s` with lazy route chunking (`Learning/Index`, `VerifyCertificate`, `Marketplace/Index`, `Show`, `Orders`).

---

## 14. Files Created

1. [MarketplaceConflictException.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Exceptions/MarketplaceConflictException.php)
2. [MarketplaceOfferPolicy.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Policies/MarketplaceOfferPolicy.php)
3. [LearningWebController.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Http/Controllers/Web/LearningWebController.php)
4. [2026_11_07_020000_phase_16_2_security_and_integrity_hardening.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/database/migrations/2026_11_07_020000_phase_16_2_security_and_integrity_hardening.php)
5. [Index.vue](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/resources/js/Pages/Learning/Index.vue)
6. [VerifyCertificate.vue](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/resources/js/Pages/Learning/VerifyCertificate.vue)
7. [MarketplaceViewModelTest.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/profile/src/test/java/com/connectly/android/feature/profile/presentation/marketplace/MarketplaceViewModelTest.kt)
8. [Phase162MarketplaceAndLearningSecurityAuditTest.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/tests/Feature/Phase162MarketplaceAndLearningSecurityAuditTest.php)
9. [BONDHOO_PHASE_16_2_ENTERPRISE_SECURITY_AND_INTEGRATION_AUDIT.md](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/BONDHOO_PHASE_16_2_ENTERPRISE_SECURITY_AND_INTEGRATION_AUDIT.md)

---

## 15. Files Modified

- **Laravel Models, Policies & Providers:**
  - [User.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/User.php), [MarketplaceProduct.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/MarketplaceProduct.php), [MarketplaceOrder.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/MarketplaceOrder.php), [MarketplaceOffer.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/MarketplaceOffer.php), [MarketplaceReview.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/MarketplaceReview.php), [LearningCourse.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/LearningCourse.php), [LearningCourseCoupon.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/LearningCourseCoupon.php), [LearningEnrollment.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Models/LearningEnrollment.php)
  - [MarketplaceProductPolicy.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Policies/MarketplaceProductPolicy.php), [MarketplaceOrderPolicy.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Policies/MarketplaceOrderPolicy.php), [LearningCoursePolicy.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Policies/LearningCoursePolicy.php), [LearningCourseLessonPolicy.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Policies/LearningCourseLessonPolicy.php), [AppServiceProvider.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Providers/AppServiceProvider.php)
- **Laravel Services, Controllers, Routes & Seeders:**
  - [MarketplaceService.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/Marketplace/MarketplaceService.php), [SandboxGatewayDriver.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/Marketplace/Payments/SandboxGatewayDriver.php), [BkashGatewayDriver.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/Marketplace/Payments/BkashGatewayDriver.php), [MarketplaceOfferNotification.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Notifications/MarketplaceOfferNotification.php), [NotificationTypeRegistry.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/Notification/NotificationTypeRegistry.php), [ProfileEnterpriseService.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/ProfileEnterpriseService.php), [VectorSearchService.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/Search/VectorSearchService.php)
  - [EnrollmentService.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/Learning/EnrollmentService.php), [CourseService.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/Learning/CourseService.php), [LearningProgressService.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/Learning/LearningProgressService.php), [CertificateService.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/Learning/CertificateService.php), [QuizService.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/Learning/QuizService.php), [AssignmentService.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/Learning/AssignmentService.php), [CourseReviewService.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/Learning/CourseReviewService.php)
  - [MarketplaceApiController.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Http/Controllers/Api/v1/MarketplaceApiController.php), [MarketplaceV2Controller.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Http/Controllers/Api/v2/MarketplaceV2Controller.php), [AdminMarketplaceController.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Http/Controllers/Admin/AdminMarketplaceController.php), `app/Http/Controllers/Api/v1/Learning/*.php`, [MarketplaceWebController.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Http/Controllers/Web/MarketplaceWebController.php), [SocialWebController.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Http/Controllers/Web/SocialWebController.php)
  - [api.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/routes/api.php), [web.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/routes/web.php), [LearningPortalSeeder.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/database/seeders/LearningPortalSeeder.php), [Phase1ProfessionalGroupsHomeTest.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/tests/Feature/Phase1ProfessionalGroupsHomeTest.php)
- **Website Vue / Blade Templates:**
  - [index.ts](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/resources/js/types/index.ts), [AppLayout.vue](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/resources/js/Layouts/AppLayout.vue), [SidebarNavigation.vue](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/resources/js/Components/SidebarNavigation.vue), [ProductCard.vue](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/resources/js/Components/Marketplace/ProductCard.vue), [Index.vue](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/resources/js/Pages/Marketplace/Index.vue), [Show.vue](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/resources/js/Pages/Marketplace/Show.vue), [MyListings.vue](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/resources/js/Pages/Marketplace/MyListings.vue), [Orders.vue](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/resources/js/Pages/Marketplace/Orders.vue), [OrderTracking.vue](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/resources/js/Pages/Marketplace/OrderTracking.vue), [show.blade.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/resources/views/marketplace/show.blade.php)
- **Android Kotlin / Jetpack Compose Modules:**
  - [ConnectlyApi.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/core/network/src/main/java/com/connectly/android/core/network/api/ConnectlyApi.kt), [MainActivity.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/app/src/main/java/com/connectly/android/MainActivity.kt)
  - [ProfileNavigation.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/profile/src/main/java/com/connectly/android/feature/profile/navigation/ProfileNavigation.kt), [MarketplaceViewModel.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/profile/src/main/java/com/connectly/android/feature/profile/presentation/marketplace/MarketplaceViewModel.kt), [MarketplaceScreen.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/profile/src/main/java/com/connectly/android/feature/profile/presentation/marketplace/MarketplaceScreen.kt)
  - [LearningModels.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/learning/src/main/java/com/connectly/android/feature/learning/domain/model/LearningModels.kt), [LearningRepositoryImpl.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/learning/src/main/java/com/connectly/android/feature/learning/data/repository/LearningRepositoryImpl.kt), [LearningContract.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/learning/src/main/java/com/connectly/android/feature/learning/presentation/learning/LearningContract.kt), [LearningViewModel.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/learning/src/main/java/com/connectly/android/feature/learning/presentation/learning/LearningViewModel.kt), [LearningHomeScreen.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/learning/src/main/java/com/connectly/android/feature/learning/presentation/learning/LearningHomeScreen.kt), [LearningViewModelTest.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/learning/src/test/java/com/connectly/android/feature/learning/LearningViewModelTest.kt)

---

## 16. Database Migrations

- Executed `php artisan migrate --force` on live MySQL:
  - `2026_11_07_020000_phase_16_2_security_and_integrity_hardening` — **DONE (43.80ms)**.
- Executed idempotent seeder `php artisan db:seed --class=LearningPortalSeeder --force` — **DONE (291ms)** (`updateOrCreate` only; zero existing rows deleted).

---

## 17. API & Web Route Additions

- **Web Routes (`routes/web.php`):**
  - `GET /learning` & `GET /app/learning` -> `LearningWebController@index`
  - `GET /learning/courses/{slug}` -> `LearningWebController@show`
  - `GET /verify/cert/{code}` -> `LearningWebController@verifyCertificate`
- **API Routes (`routes/api.php`):**
  - Public alphanumeric certificate verification `GET /api/v1/learning/certificates/{code}` separated cleanly from authenticated numeric `GET /api/v1/learning/certificates/{id}`.
  - `throttle:marketplace-learning` middleware applied to Marketplace & Learning route groups.

---

## 18. Backend Test Results

All targeted Marketplace, Learning, Phase 13 LMS/Security, and Phase 16.2 Security Audit suites passed **100%**, and the full 2,360-test repository suite was executed and recorded honestly:

| Command Executed | Total Tests | Passed | Failed | Assertions | Duration |
| :--- | :---: | :---: | :---: | :---: | :---: |
| `php artisan test --filter=Phase162MarketplaceAndLearningSecurityAuditTest` | **8** | **8** | **0** | **107** | 1.17s |
| `php artisan test --filter=Marketplace` | **100** | **100** | **0** | **652** | 3.23s |
| `php artisan test --filter=Learning` | **11** | **11** | **0** | **120** | 1.12s |
| `php artisan test --filter=Phase13` | **75** | **75** | **0** | **943** | 2.62s |
| `php artisan test` (Full Repository Suite) | **2,360** | **2,341** | **19*** | **16,297** | 87.19s |

*\*Note on Full Repository Suite (`19` pre-existing failures out of `2,360` tests):* Zero failures occurred in Marketplace, Learning, Auth, Wallet, Orders, Offers, Certificates, Messenger, Jobs, or Live Streaming. The 19 failures are legacy tests in unrelated historical suites (`FederationAndPwaExtendedTest` / `PwaOfflineAndWebPushTest` asserting old `'Jugajug'` manifest strings prior to the `Bondoo` rebranding, legacy Blade HTML assertions in `Step03PublicProfilePageTest` / `Profile*` after Inertia SPA migration, and `GroupTest` asserting `'admin'` instead of `'owner'` role on group creation). Per strict non-destructive rules, unrelated modules were not modified.

- **Code Formatting:** `vendor/bin/pint --dirty` -> **PASSED** (0 style issues).

---

## 19. Android Test & Build Results

Executed via Gradle (`JDK 17`):
```bash
./android/gradlew -p android :feature:learning:testDebugUnitTest :feature:profile:testDebugUnitTest :app:testDebugUnitTest :app:assembleDebug
```
- `:feature:learning:testDebugUnitTest` (`LearningViewModelTest` — 7 tests covering init sync, tab switching, category/level/type filters, free enrollment, paid insufficient-wallet failure + coupon retry without false `Enrolled` state, duplicate-tap guard, and lesson completion sync): **PASSED**
- `:feature:profile:testDebugUnitTest` (`MarketplaceViewModelTest` + 11 existing profile ViewModel test classes — covering owner vs non-owner dialog guards, sold listing guards, HTTP `200` `data: null` delete, HTTP `403` & `IOException` delete preservation, duplicate-tap delete guard, `idempotency_key` + HTTP `409 Conflict` order handling, `contactSeller` Messenger handoff, and Saved favorites rollback): **PASSED**
- `:app:testDebugUnitTest`: **PASSED**
- `:app:assembleDebug`: **BUILD SUCCESSFUL in 25s** (`android/app/build/outputs/apk/debug/app-debug.apk`).

---

## 20. Live Emulator & Two-Account End-to-End Results

1. **APK Installation on `emulator-5554`:**
   - `adb -s emulator-5554 install -r android/app/build/outputs/apk/debug/app-debug.apk` -> `Performing Streamed Install / Success`.
   - Launched `com.jugajug.android.debug/com.connectly.android.MainActivity`, verified Feed, Menu, Learning & Development (`Discover`, `My Learning (3)`, `Certificates (1)`, Category & Level/Price chips), Marketplace (`বন্ধু বাজার`), and Help & Support with **zero** `AndroidRuntime:E` crashes in `logcat`.
2. **Live Two-Account E2E Execution Against Running Server (`http://127.0.0.1:8000` + Live MySQL):**
   - **Scenario A (Ownership & Idempotent Delete):** Seller created listing (`201`, `is_owner: true`); Buyer saw `is_owner: false` and received `403` on `PUT`, `PATCH /status`, and `DELETE`; Seller paused (`inactive`), reactivated (`active`), deleted (`200`), repeated delete (`200`), and Buyer attempting `DELETE` on the soft-deleted item still received `403`.
   - **Scenario B (Messenger, Counteroffer, Order & `409` Race):** Buyer contacted Seller (`conversation_id: 6` immediately visible in `GET /api/v1/conversations` for both Buyer & Seller); Buyer offered `৳138,000` -> Seller countered `৳141,000` -> Buyer accepted counter -> Buyer placed order (`201`, total `৳141,150.00` including `৳150` delivery); Second Buyer attempting purchase received HTTP `409 Conflict`; Buyer verified checkout (`escrow_status: "held"`), Seller shipped, Buyer confirmed delivery (`escrow_status: "released"`).
   - **Scenario C (Free Course -> 100% Progress -> Certificate):** Buyer enrolled in `laravel-jetpack-compose-mastery` (`200`), completed all `9` lessons, received certificate `CERT-2026-DOJA-GOTW`, verified `200` on both `/api/v1/learning/certificates/verify/CERT-2026-DOJA-GOTW` and `/verify/cert/CERT-2026-DOJA-GOTW`.
   - **Scenario D (Paid Course Wallet Enforcement):** Underfunded Buyer (`$1.00` balance) received `422` and `falsely_enrolled_on_failure: false`; Funded Buyer (`$149.00` balance on `$49.00` course) enrolled (`200`) with wallet atomically debited to `$100.00`.

---

## 21. Cross-Platform Verification

- **Marketplace Sync:** Listings created, paused, sold, or favorited on API/Android immediately reflect identical `status`, `stock`, `is_owner`, and `is_favorited` states on `/marketplace` (`HTTP 200`). Zero-stock sold items are automatically excluded from public Discover on both platforms.
- **Learning Sync:** Enrollments, lesson completion percentages, `My Learning` counters (`enrolled_count`, `in_progress_count`, `completed_count`, `certificates_count`), and issued certificates read from the same MySQL tables across `/learning` (Vue/Inertia) and Android `LearningHomeScreen`.

---

## 22. Remaining External Configuration Items

1. **Production Payment Gateway Merchant Credentials:** `BkashGatewayDriver` and `PaymentGatewayManager` are wired for tokenized checkout and HMAC callback verification; deploying to live merchant production requires setting `BKASH_SANDBOX=false`, `BKASH_APP_KEY`, `BKASH_APP_SECRET`, `BKASH_USERNAME`, `BKASH_PASSWORD`, and `MARKETPLACE_WEBHOOK_SECRET` in production `.env`.
2. **Redis Daemon (Optional Local Optimization):** Local environment currently logs a graceful fallback warning to database/file cache when local Redis (`127.0.0.1:6379`) is stopped; starting Redis in production enables sub-millisecond cache/queue throughput.

---

## 23. Deployment Instructions

```bash
# 1. Pull latest verified code
git pull origin main

# 2. Install/optimize PHP autoloader & run additive non-destructive migration
composer dump-autoload -o
php artisan migrate --force

# 3. Seed baseline Learning taxonomy & courses (idempotent updateOrCreate)
php artisan db:seed --class=LearningPortalSeeder --force

# 4. Build production Website assets & refresh Laravel caches
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Build Android APK / AAB
./android/gradlew -p android :app:assembleRelease
```

---

## 24. Rollback Instructions

```bash
# 1. Roll back only the additive Phase 16.2 schema columns/indexes (preserves all core tables & user records)
php artisan migrate:rollback --path=database/migrations/2026_11_07_020000_phase_16_2_security_and_integrity_hardening.php --force

# 2. Revert application code to previous commit and rebuild frontend assets
git checkout f41453c
npm run build
php artisan optimize:clear
```

