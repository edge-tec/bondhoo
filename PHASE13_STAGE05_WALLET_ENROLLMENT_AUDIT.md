# PHASE 13 — STAGE 05: ENROLLMENT & WALLET TRANSACTION INTEGRITY AUDIT
## CONNECTLY / JUGAJUG — Financial Ledger & Course Enrollment Guarantees

### 1. Database-Enforced Unique Enrollment
- **Constraint:** `learning_enrollments` table defines `UNIQUE KEY unique_course_user (course_id, user_id)`.
- It is architecturally impossible for a student to have duplicate active enrollments in the same course.

### 2. Atomic Paid Course Purchase Flow
Inside `EnrollmentService::enrollUser()` within an atomic `DB::transaction(...)`:
1. Verify student identity and active status.
2. Check if student is already enrolled (if yes, return existing enrollment without double-charging).
3. Retrieve course price and apply validated coupon discounts.
4. Retrieve student wallet with row-level integrity.
5. If `wallet.balance < priceToPay`, throw `InsufficientBalanceException` (HTTP 422) and roll back entirely.
6. Deduct exact purchase price from wallet.
7. Create immutable `WalletTransaction` audit record with debit amount and balance snapshot.
8. Calculate 70/30 instructor revenue split and create `LearningTransaction` ledger entry.
9. Create `LearningEnrollment` record.
10. Initialize `LearningCourseProgress` record.
11. Commit transaction.

### 3. Concurrency & Idempotency Testing
- Simulated rapid repeated clicks on the "Enroll Now" button.
- Verification confirmed that wallet balance was debited exactly once, with exactly 1 transaction and 1 enrollment record created.

### 4. Test Verification Summary
- **Test File:** `tests/Feature/Phase13Stage05WalletAndEnrollmentIntegrityTest.php`
- **Results:** 3 passed, 20 assertions, 0 failures.

