<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\MarketplaceConflictException;
use App\Models\LearningCategory;
use App\Models\LearningCertificate;
use App\Models\LearningCourse;
use App\Models\LearningCourseCoupon;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseSection;
use App\Models\LearningEnrollment;
use App\Models\LearningLessonProgress;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceOrder;
use App\Models\MarketplacePaymentTransaction;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Learning\CertificateService;
use App\Services\Learning\EnrollmentService;
use App\Services\Marketplace\MarketplaceService;
use App\Services\Marketplace\Payments\PaymentGatewayManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class Phase163ProductionReadinessAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected User $buyer;

    protected User $secondBuyer;

    protected User $admin;

    protected User $instructor;

    protected MarketplaceCategory $marketplaceCategory;

    protected LearningCategory $learningCategory;

    protected MarketplaceService $marketplaceService;

    protected PaymentGatewayManager $paymentManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'name' => 'Bondhoo Seller',
            'username' => 'bondhoo_seller',
            'email' => 'seller@bondhoo.com',
            'phone' => '+8801711000001',
        ]);

        $this->buyer = User::factory()->create([
            'name' => 'Bondhoo Buyer',
            'username' => 'bondhoo_buyer',
            'email' => 'buyer@bondhoo.com',
            'phone' => '+8801711000002',
        ]);

        $this->secondBuyer = User::factory()->create([
            'name' => 'Second Buyer',
            'username' => 'second_buyer',
            'email' => 'buyer2@bondhoo.com',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'System Admin',
            'username' => 'system_admin',
            'email' => 'admin@bondhoo.com',
            'status' => 'admin',
        ]);

        $this->instructor = User::factory()->create([
            'name' => 'Lead Instructor',
            'username' => 'lead_instructor',
            'email' => 'instructor@bondhoo.com',
        ]);

        $this->marketplaceCategory = MarketplaceCategory::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'icon' => 'laptop',
            'is_active' => true,
        ]);

        $this->learningCategory = LearningCategory::create([
            'name' => 'Software Development',
            'slug' => 'software-development',
            'is_active' => true,
        ]);

        $this->marketplaceService = app(MarketplaceService::class);
        $this->paymentManager = app(PaymentGatewayManager::class);
    }

    protected function createProduct(array $overrides = []): MarketplaceProduct
    {
        return MarketplaceProduct::create(array_merge([
            'seller_id' => $this->seller->id,
            'category_id' => $this->marketplaceCategory->id,
            'title' => 'Test Smartphone Pro',
            'description' => 'Original phone for audit testing',
            'price' => 15000.00,
            'currency' => 'BDT',
            'condition' => 'new',
            'status' => MarketplaceProduct::STATUS_ACTIVE,
            'delivery_type' => MarketplaceOrder::DELIVERY_STANDARD,
            'delivery_charge' => 100.00,
            'stock' => 5,
        ], $overrides));
    }

    protected function initiateAndPay(MarketplaceOrder $order, array $callbackOverrides = []): array
    {
        $session = $this->paymentManager->initiateCheckout($order, 'sandbox');

        $payload = array_merge([
            'gateway' => 'sandbox',
            'transaction_id' => $session['transaction_id'],
            'signature' => $session['signature'],
            'status' => 'completed',
            'payment_status' => 'COMPLETED',
            'amount' => $order->amount,
            'currency' => $order->currency ?: 'BDT',
        ], $callbackOverrides);

        return $this->paymentManager->processCallback($order, $payload);
    }

    // =========================================================================
    // SECTION 1: PAYMENT GATEWAY VERIFICATION & WEBHOOK AUDIT (17 SCENARIOS)
    // =========================================================================

    /** Scenario 1: Successful payment callback marks order paid, processing, held escrow, records transaction */
    public function test_payment_scenario_1_successful_callback(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, [
            'shipping_address' => 'Banani, Dhaka',
            'payment_method' => 'bkash',
        ]);

        $this->assertSame(MarketplaceOrder::PAYMENT_UNPAID, $order->payment_status);
        $this->assertSame(MarketplaceOrder::ESCROW_HELD, $order->escrow_status);

        $result = $this->initiateAndPay($order);

        $this->assertTrue($result['verified']);
        $this->assertSame(MarketplaceOrder::PAYMENT_PAID, $result['order']->payment_status);
        $this->assertSame(MarketplaceOrder::STATUS_PROCESSING, $result['order']->status);
        $this->assertSame(MarketplaceOrder::ESCROW_HELD, $result['order']->escrow_status);
        $this->assertSame(MarketplacePaymentTransaction::STATUS_SUCCESSFUL, $result['transaction']->status);
    }

    /** Scenario 2: Failed payment callback marks payment failed and records failed ledger entry */
    public function test_payment_scenario_2_failed_callback(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $session = $this->paymentManager->initiateCheckout($order, 'sandbox');

        $result = $this->paymentManager->processCallback($order, [
            'gateway' => 'sandbox',
            'transaction_id' => $session['transaction_id'],
            'signature' => $session['signature'],
            'payment_status' => 'FAILED',
            'status' => 'failed',
            'amount' => 15100.00,
        ]);

        $this->assertFalse($result['verified']);
        $this->assertSame(MarketplaceOrder::PAYMENT_FAILED, $result['order']->payment_status);
        $this->assertSame(MarketplacePaymentTransaction::STATUS_FAILED, $result['transaction']->status);
    }

    /** Scenario 3: Cancelled callback updates transaction status to cancelled */
    public function test_payment_scenario_3_cancelled_callback(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $session = $this->paymentManager->initiateCheckout($order, 'sandbox');

        $result = $this->paymentManager->processCallback($order, [
            'gateway' => 'sandbox',
            'transaction_id' => $session['transaction_id'],
            'signature' => $session['signature'],
            'status' => 'cancelled',
            'payment_status' => 'CANCELLED',
        ]);

        $this->assertFalse($result['verified']);
        $this->assertSame(MarketplacePaymentTransaction::STATUS_CANCELLED, $result['transaction']->status);
    }

    /** Scenario 4: Expired / unknown status callback transitions to reversed */
    public function test_payment_scenario_4_expired_callback(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $session = $this->paymentManager->initiateCheckout($order, 'sandbox');

        $result = $this->paymentManager->processCallback($order, [
            'gateway' => 'sandbox',
            'transaction_id' => $session['transaction_id'],
            'signature' => $session['signature'],
            'status' => 'expired',
        ]);

        $this->assertFalse($result['verified']);
        $this->assertSame(MarketplacePaymentTransaction::STATUS_REVERSED, $result['transaction']->status);
    }

    /** Scenario 5: Gateway timeout / empty transaction ID fails closed */
    public function test_payment_scenario_5_timeout_or_missing_trx(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing transaction ID.');

        $this->paymentManager->processCallback($order, [
            'gateway' => 'sandbox',
            'status' => 'timeout',
        ]);
    }

    /** Scenario 6: Duplicate callback is idempotent and does not create duplicate ledger records */
    public function test_payment_scenario_6_idempotent_duplicate_callback(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $session = $this->paymentManager->initiateCheckout($order, 'sandbox');

        $payload = [
            'gateway' => 'sandbox',
            'transaction_id' => $session['transaction_id'],
            'signature' => $session['signature'],
            'status' => 'completed',
            'payment_status' => 'COMPLETED',
            'amount' => 15100.00,
        ];

        $first = $this->paymentManager->processCallback($order, $payload);
        $this->assertTrue($first['verified']);

        $countBefore = MarketplacePaymentTransaction::where('order_id', $order->id)->count();

        // Second duplicate callback
        $second = $this->paymentManager->processCallback($order, $payload);
        $this->assertTrue($second['verified']);
        $this->assertSame($first['transaction']->id, $second['transaction']->id);

        $countAfter = MarketplacePaymentTransaction::where('order_id', $order->id)->count();
        $this->assertSame($countBefore, $countAfter);
    }

    /** Scenario 7: Late failed callback cannot downgrade an already paid order */
    public function test_payment_scenario_7_late_failed_callback_does_not_downgrade_paid_order(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $session = $this->paymentManager->initiateCheckout($order, 'sandbox');

        // First successful payment
        $this->paymentManager->processCallback($order, [
            'gateway' => 'sandbox',
            'transaction_id' => $session['transaction_id'],
            'signature' => $session['signature'],
            'status' => 'completed',
            'payment_status' => 'COMPLETED',
            'amount' => 15100.00,
        ]);

        $this->assertTrue($order->fresh()->isPaid());

        // Subsequent delayed failed callback arrives
        $retry = $this->paymentManager->processCallback($order->fresh(), [
            'gateway' => 'sandbox',
            'transaction_id' => $session['transaction_id'],
            'signature' => $session['signature'],
            'status' => 'failed',
            'payment_status' => 'FAILED',
        ]);

        $this->assertTrue($retry['verified']);
        $this->assertSame(MarketplaceOrder::PAYMENT_PAID, $order->fresh()->payment_status);
        $this->assertSame(MarketplaceOrder::STATUS_PROCESSING, $order->fresh()->status);
    }

    /** Scenario 8: Cross-order transaction replay is rejected */
    public function test_payment_scenario_8_cross_order_replay_rejected(): void
    {
        $product = $this->createProduct();
        $orderA = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $orderB = $this->marketplaceService->placeOrder($this->secondBuyer, $product, 'Gulshan, Dhaka');

        $sessionA = $this->paymentManager->initiateCheckout($orderA, 'sandbox');

        // Order A completes
        $this->paymentManager->processCallback($orderA, [
            'gateway' => 'sandbox',
            'transaction_id' => $sessionA['transaction_id'],
            'signature' => $sessionA['signature'],
            'status' => 'completed',
            'payment_status' => 'COMPLETED',
            'amount' => 15100.00,
        ]);

        // Attacker attempts to replay Order A's transaction on Order B
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Transaction reference '{$sessionA['transaction_id']}' belongs to another order.");

        $this->paymentManager->processCallback($orderB, [
            'gateway' => 'sandbox',
            'transaction_id' => $sessionA['transaction_id'],
            'signature' => $sessionA['signature'],
            'status' => 'completed',
            'payment_status' => 'COMPLETED',
            'amount' => 15100.00,
        ]);
    }

    /** Scenario 9: Amount mismatch in callback fails verification */
    public function test_payment_scenario_9_amount_mismatch_fails_verification(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $session = $this->paymentManager->initiateCheckout($order, 'sandbox');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount mismatch. Order requires 15100.00, received 100.');

        $this->paymentManager->processCallback($order, [
            'gateway' => 'sandbox',
            'transaction_id' => $session['transaction_id'],
            'signature' => $session['signature'],
            'status' => 'completed',
            'payment_status' => 'COMPLETED',
            'amount' => 100.00, // Expected 15100.00
        ]);
    }

    /** Scenario 10: Currency mismatch in callback fails verification */
    public function test_payment_scenario_10_currency_mismatch_fails_verification(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $session = $this->paymentManager->initiateCheckout($order, 'sandbox');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch: expected BDT, received EUR.');

        $this->paymentManager->processCallback($order, [
            'gateway' => 'sandbox',
            'transaction_id' => $session['transaction_id'],
            'signature' => $session['signature'],
            'status' => 'completed',
            'payment_status' => 'COMPLETED',
            'amount' => 15100.00,
            'currency' => 'EUR', // Expected BDT
        ]);
    }

    /** Scenario 11: Callback on cancelled order is rejected */
    public function test_payment_scenario_11_callback_on_cancelled_order_rejected(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $session = $this->paymentManager->initiateCheckout($order, 'sandbox');
        $this->marketplaceService->cancelOrder($order, $this->buyer, 'Decided not to buy');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot process payment callback for a cancelled order.');

        $this->paymentManager->processCallback($order->fresh(), [
            'gateway' => 'sandbox',
            'transaction_id' => $session['transaction_id'],
            'signature' => $session['signature'],
            'status' => 'completed',
        ]);
    }

    /** Scenario 12: Cannot initiate checkout on already paid order */
    public function test_payment_scenario_12_cannot_initiate_checkout_on_paid_order(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $this->initiateAndPay($order);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot initiate payment for an already paid order.');

        $this->paymentManager->initiateCheckout($order->fresh(), 'sandbox');
    }

    /** Scenario 13: Refund initiation succeeds on paid order and transitions status */
    public function test_payment_scenario_13_refund_success_on_paid_order(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $this->initiateAndPay($order);

        $refundResult = $this->paymentManager->processRefund($order->fresh(), 15100.00, 'Customer return', $this->admin);

        $this->assertTrue($refundResult['success']);
        $this->assertSame(MarketplaceOrder::STATUS_REFUNDED, $refundResult['order']->status);
        $this->assertSame(MarketplaceOrder::PAYMENT_REFUNDED, $refundResult['order']->payment_status);
        $this->assertSame(MarketplaceOrder::ESCROW_REFUNDED, $refundResult['order']->escrow_status);
        $this->assertSame(MarketplacePaymentTransaction::TYPE_REFUND, $refundResult['transaction']->type);
    }

    /** Scenario 14: Unpaid order cannot be refunded */
    public function test_payment_scenario_14_cannot_refund_unpaid_order(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot refund an unpaid order.');

        $this->paymentManager->processRefund($order, 15100.00, 'Invalid refund');
    }

    /** Scenario 15: Duplicate refund or over-refund is rejected */
    public function test_payment_scenario_15_duplicate_or_over_refund_rejected(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $this->initiateAndPay($order);

        // Full refund
        $this->paymentManager->processRefund($order->fresh(), 15100.00, 'First full refund', $this->admin);

        // Attempt second refund
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Order is already refunded.');

        $this->paymentManager->processRefund($order->fresh(), 100.00, 'Second attempt', $this->admin);
    }

    /** Scenario 16: Refund amount exceeding order amount is rejected */
    public function test_payment_scenario_16_excessive_refund_amount_rejected(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $this->initiateAndPay($order);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid refund amount 20000. Maximum refundable is 15100.');

        $this->paymentManager->processRefund($order->fresh(), 20000.00, 'Too much money', $this->admin);
    }

    /** Scenario 17: Production sandbox guard blocks sandbox gateway in production */
    public function test_payment_scenario_17_production_sandbox_guard(): void
    {
        $originalEnv = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Sandbox payment gateway is not permitted in production environment.');

            $this->paymentManager->driver('sandbox');
        } finally {
            app()->detectEnvironment(fn () => $originalEnv);
        }
    }

    // =========================================================================
    // SECTION 2: MARKETPLACE ESCROW, STOCK CONCURRENCY & FULFILLMENT AUDIT
    // =========================================================================

    /** Unpaid order cannot be marked shipped by seller */
    public function test_unpaid_order_cannot_be_shipped(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');

        $this->assertFalse($order->canBeShippedBy($this->seller));

        $this->expectException(AuthorizationException::class);
        $this->marketplaceService->shipOrder($order, $this->seller, ['carrier' => 'Pathao']);
    }

    /** Pending order cannot be marked delivered */
    public function test_pending_order_cannot_be_marked_delivered(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Order in status 'pending' cannot be marked as delivered.");

        $this->marketplaceService->confirmDelivery($order, $this->buyer);
    }

    /** Cancelled order cannot release escrow */
    public function test_cancelled_order_cannot_release_escrow(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $this->marketplaceService->cancelOrder($order, $this->buyer, 'Cancelled');

        $this->assertFalse($order->fresh()->canReleaseEscrow($this->buyer));

        $this->expectException(AuthorizationException::class);
        $this->marketplaceService->releaseEscrow($order->fresh(), $this->buyer);
    }

    /** Stock concurrency: Item with stock = 1 cannot be double-sold */
    public function test_stock_concurrency_prevents_double_purchase(): void
    {
        $product = $this->createProduct(['stock' => 1]);

        $orderA = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $this->assertSame(0, (int) $product->fresh()->stock);

        $this->expectException(MarketplaceConflictException::class);
        $this->expectExceptionMessage('This listing no longer has sufficient stock available.');

        $this->marketplaceService->placeOrder($this->secondBuyer, $product, 'Gulshan, Dhaka');
    }

    /** Cancellation of paid unshipped order refunds buyer and restores product stock */
    public function test_cancellation_of_paid_unshipped_order_refunds_and_restores_stock(): void
    {
        $product = $this->createProduct(['stock' => 2]);
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');
        $this->assertSame(1, (int) $product->fresh()->stock);

        // Mark paid
        $this->initiateAndPay($order);
        $this->assertTrue($order->fresh()->isPaid());

        // Buyer cancels before shipment
        $cancelledOrder = $this->marketplaceService->cancelOrder($order->fresh(), $this->buyer, 'Changed my mind');

        $this->assertSame(MarketplaceOrder::STATUS_CANCELLED, $cancelledOrder->status);
        $this->assertSame(MarketplaceOrder::PAYMENT_REFUNDED, $cancelledOrder->payment_status);
        $this->assertSame(MarketplaceOrder::ESCROW_REFUNDED, $cancelledOrder->escrow_status);
        $this->assertSame(15100.00, (float) $cancelledOrder->refund_amount);

        // Stock restored back to 2
        $this->assertSame(2, (int) $product->fresh()->stock);

        // Refund ledger record exists
        $refundTx = MarketplacePaymentTransaction::where('order_id', $order->id)
            ->where('type', MarketplacePaymentTransaction::TYPE_REFUND)
            ->first();
        $this->assertNotNull($refundTx);
        $this->assertSame(15100.00, (float) $refundTx->amount);
    }

    /** Delivery confirmation records seller payout ledger entry idempotently */
    public function test_delivery_confirmation_records_seller_payout_transaction(): void
    {
        $product = $this->createProduct();
        $order = $this->marketplaceService->placeOrder($this->buyer, $product, 'Banani, Dhaka');

        $this->initiateAndPay($order);

        // Seller ships
        $this->marketplaceService->shipOrder($order->fresh(), $this->seller, ['carrier' => 'Steadfast', 'tracking_number' => 'ST12345']);

        // Buyer confirms delivery
        $deliveredOrder = $this->marketplaceService->confirmDelivery($order->fresh(), $this->buyer);

        $this->assertSame(MarketplaceOrder::STATUS_DELIVERED, $deliveredOrder->status);
        $this->assertSame(MarketplaceOrder::ESCROW_RELEASED, $deliveredOrder->escrow_status);

        $payoutTx = MarketplacePaymentTransaction::where('order_id', $order->id)
            ->where('type', MarketplacePaymentTransaction::TYPE_PAYOUT)
            ->first();
        $this->assertNotNull($payoutTx);
        $this->assertSame($this->seller->id, $payoutTx->user_id);
        $this->assertSame(15100.00, (float) $payoutTx->amount);

        // Seller sales summary verifies net earnings
        $summary = $this->marketplaceService->getSellerSalesSummary($this->seller);
        $this->assertSame(15100.00, (float) $summary['net_earnings']);
        $this->assertSame(0.00, (float) $summary['pending_settlements']);
    }

    // =========================================================================
    // SECTION 3: LMS ACCESS CONTROL, COUPON INTEGRITY & CERTIFICATE AUDIT
    // =========================================================================

    protected function createCourse(): array
    {
        $course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $this->learningCategory->id,
            'title' => 'Laravel 12 & Compose Mastery',
            'slug' => 'laravel-12-compose-mastery',
            'description' => 'Comprehensive masterclass',
            'price' => 5000.00,
            'type' => LearningCourse::TYPE_PAID,
            'status' => LearningCourse::STATUS_PUBLISHED,
            'published_at' => now(),
            'total_lessons' => 2,
        ]);

        $section = LearningCourseSection::create([
            'course_id' => $course->id,
            'title' => 'Getting Started',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $lessonFree = LearningCourseLesson::create([
            'section_id' => $section->id,
            'course_id' => $course->id,
            'title' => 'Introduction (Free Preview)',
            'slug' => 'intro-preview',
            'sort_order' => 1,
            'is_preview' => true,
            'is_published' => true,
            'duration_minutes' => 10,
        ]);

        $lessonPaid = LearningCourseLesson::create([
            'section_id' => $section->id,
            'course_id' => $course->id,
            'title' => 'Advanced Architecture (Locked)',
            'slug' => 'adv-architecture',
            'sort_order' => 2,
            'is_preview' => false,
            'is_published' => true,
            'duration_minutes' => 45,
        ]);

        return [$course, $section, $lessonFree, $lessonPaid];
    }

    /** Unenrolled user cannot access paid lesson content */
    public function test_unenrolled_user_cannot_access_paid_lesson(): void
    {
        [$course, $section, $lessonFree, $lessonPaid] = $this->createCourse();

        $response = $this->actingAs($this->buyer)
            ->getJson("/api/v1/learning/lessons/{$lessonPaid->id}");

        $response->assertStatus(403);
    }

    /** Free preview lesson is accessible by non-enrolled user */
    public function test_free_preview_lesson_is_accessible(): void
    {
        [$course, $section, $lessonFree, $lessonPaid] = $this->createCourse();

        $response = $this->actingAs($this->buyer)
            ->getJson("/api/v1/learning/lessons/{$lessonFree->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.lesson.is_preview', true);
    }

    /** Coupon validation enforces valid discount calculation */
    public function test_coupon_validation_and_discount(): void
    {
        [$course] = $this->createCourse();

        Wallet::create([
            'user_id' => $this->buyer->id,
            'balance' => 10000.00,
            'currency' => 'USD',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        $coupon = LearningCourseCoupon::create([
            'course_id' => $course->id,
            'code' => 'BONDHOO50',
            'discount_type' => 'percentage',
            'discount_amount' => 50.00,
            'expires_at' => now()->addDays(7),
            'max_uses' => 10,
            'times_used' => 0,
            'is_active' => true,
        ]);

        $enrollmentService = app(EnrollmentService::class);
        $enrollment = $enrollmentService->enrollUser($this->buyer, $course, 'BONDHOO50');

        $this->assertSame(2500.00, (float) $enrollment->price_paid);
        $this->assertSame(1, (int) $coupon->fresh()->times_used);
    }

    /** Certificate cannot be issued before 100% course completion */
    public function test_certificate_cannot_be_claimed_before_full_completion(): void
    {
        [$course, $section, $lessonFree, $lessonPaid] = $this->createCourse();

        $enrollment = LearningEnrollment::create([
            'user_id' => $this->buyer->id,
            'course_id' => $course->id,
            'amount_paid' => 5000.00,
            'status' => LearningEnrollment::STATUS_ACTIVE,
            'progress_percent' => 50,
        ]);

        $certificateService = app(CertificateService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Course requirements not satisfied: All lessons must be completed.');

        $certificateService->issueCertificate($this->buyer, $course);
    }

    /** Certificate issuance succeeds at 100% completion and can be verified by code */
    public function test_certificate_issued_at_100_percent_and_verified(): void
    {
        [$course, $section, $lessonFree, $lessonPaid] = $this->createCourse();

        $enrollment = LearningEnrollment::create([
            'user_id' => $this->buyer->id,
            'course_id' => $course->id,
            'amount_paid' => 5000.00,
            'status' => LearningEnrollment::STATUS_COMPLETED,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);

        foreach ([$lessonFree, $lessonPaid] as $lesson) {
            LearningLessonProgress::create([
                'user_id' => $this->buyer->id,
                'course_id' => $course->id,
                'lesson_id' => $lesson->id,
                'is_completed' => true,
            ]);
        }

        $certificateService = app(CertificateService::class);
        $certificate = $certificateService->issueCertificate($this->buyer, $course);

        $this->assertNotNull($certificate);
        $this->assertNotNull($certificate->certificate_code);

        // Verify publicly via API
        $response = $this->getJson("/api/v1/learning/certificates/{$certificate->certificate_code}");
        $response->assertStatus(200);
        $response->assertJsonPath('data.is_valid', true);
    }

    /** Revoked certificate is rejected upon verification */
    public function test_revoked_certificate_is_invalid(): void
    {
        [$course] = $this->createCourse();

        $certificate = LearningCertificate::create([
            'user_id' => $this->buyer->id,
            'course_id' => $course->id,
            'certificate_code' => 'CERT-REVOKED-123',
            'verification_token' => 'TOK-REVOKED-123',
            'student_name' => $this->buyer->name,
            'course_title' => $course->title,
            'instructor_name' => $this->instructor->name,
            'issued_at' => now(),
            'is_revoked' => true,
            'revoked_reason' => 'Honor code violation',
        ]);

        $response = $this->getJson("/api/v1/learning/certificates/{$certificate->certificate_code}");
        $response->assertStatus(200);
        $response->assertJsonPath('data.is_valid', false);
        $response->assertJsonPath('data.is_revoked', true);
    }
}
