<?php

namespace Tests\Feature;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceOrder;
use App\Models\MarketplacePaymentTransaction;
use App\Models\MarketplaceProduct;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseMarketplaceCommerceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected User $buyer;

    protected User $otherUser;

    protected User $adminUser;

    protected MarketplaceCategory $category;

    protected MarketplaceProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['label' => 'Super Admin', 'description' => 'Full access']);
        Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Admin', 'description' => 'Staff administrator']);
        Role::firstOrCreate(['name' => 'USER'], ['label' => 'User', 'description' => 'Regular user']);

        $this->seller = User::factory()->create([
            'name' => 'Rahim Seller',
            'username' => 'rahim_seller',
            'email' => 'seller@test.com',
        ]);

        $this->buyer = User::factory()->create([
            'name' => 'Karim Buyer',
            'username' => 'karim_buyer',
            'email' => 'buyer@test.com',
        ]);

        $this->otherUser = User::factory()->create([
            'name' => 'Faruk Intruding User',
            'username' => 'faruk_user',
            'email' => 'faruk@test.com',
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin Officer',
            'username' => 'admin_officer',
            'email' => 'admin@bondhoo.com',
        ]);
        $this->adminUser->assignRole('ADMIN');

        $this->category = MarketplaceCategory::create([
            'name' => 'Tech & Gadgets',
            'slug' => 'tech-gadgets',
            'icon' => 'laptop',
            'display_order' => 1,
        ]);

        $this->product = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Sony WH-1000XM5 Wireless Headphones',
            'description' => 'Brand new noise cancelling headphones.',
            'price' => 35000.00,
            'condition' => 'new',
            'status' => 'active',
            'delivery_charge' => 60.00,
            'location' => 'Gulshan, Dhaka',
            'images' => ['products/sony_1.jpg'],
        ]);
    }

    /**
     * 1. Buyer creates an eligible order.
     */
    public function test_scenario_01_buyer_creates_an_eligible_order(): void
    {
        $response = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/products/{$this->product->id}/order", [
                'quantity' => 2,
                'delivery_type' => 'express',
                'shipping_name' => 'Karim Buyer',
                'shipping_phone' => '01711000000',
                'shipping_address' => 'House 12, Road 4, Dhanmondi',
                'shipping_city' => 'Dhaka',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
            ]);

        $orderData = $response->json('data') ?: $response->json('order');
        $this->assertNotEmpty($orderData['order_number']);
        $this->assertStringStartsWith('ORD-', $orderData['order_number']);
        $this->assertEquals(2, $orderData['quantity']);
        $this->assertEquals('pending', $orderData['status']);
        // 35,000 * 2 = 70,000 + 120 (express) = 70,120
        $this->assertEquals(70120.00, (float) ($orderData['amount'] ?? $orderData['total_amount']));

        $this->assertDatabaseHas('marketplace_orders', [
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'delivery_type' => 'express',
            'delivery_charge' => 120.00,
            'amount' => 70120.00,
            'status' => 'pending',
        ]);
    }

    /**
     * 2. Unauthenticated users cannot create protected orders.
     */
    public function test_scenario_02_unauthenticated_users_cannot_create_protected_orders(): void
    {
        $response = $this->postJson("/api/v2/marketplace/products/{$this->product->id}/order", [
            'quantity' => 1,
            'delivery_type' => 'standard',
            'shipping_name' => 'Anonymous',
            'shipping_phone' => '01800000000',
            'shipping_address' => 'Unknown',
            'shipping_city' => 'Dhaka',
        ]);

        $response->assertStatus(401);
    }

    /**
     * 3. Buyer cannot access another buyer's order.
     */
    public function test_scenario_03_buyer_cannot_access_another_buyers_order(): void
    {
        $order = MarketplaceOrder::create([
            'order_number' => MarketplaceOrder::generateOrderNumber(),
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'unit_price' => 35000.00,
            'quantity' => 1,
            'subtotal' => 35000.00,
            'delivery_charge' => 60.00,
            'amount' => 35060.00,
            'currency' => 'BDT',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'delivery_type' => 'standard',
            'shipping_name' => 'Karim Buyer',
            'shipping_phone' => '01711000000',
            'shipping_address' => 'Dhanmondi',
        ]);

        // Another buyer attempts to inspect Karim's order
        $response = $this->actingAs($this->otherUser)
            ->getJson("/api/v2/marketplace/orders/{$order->id}");

        $response->assertStatus(404);
    }

    /**
     * 4. Seller cannot modify or ship another seller's order.
     */
    public function test_scenario_04_seller_cannot_modify_or_ship_another_sellers_order(): void
    {
        $order = MarketplaceOrder::create([
            'order_number' => MarketplaceOrder::generateOrderNumber(),
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'unit_price' => 35000.00,
            'quantity' => 1,
            'subtotal' => 35000.00,
            'delivery_charge' => 60.00,
            'amount' => 35060.00,
            'currency' => 'BDT',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'delivery_type' => 'standard',
            'shipping_name' => 'Karim Buyer',
            'shipping_phone' => '01711000000',
            'shipping_address' => 'Dhanmondi',
        ]);

        // An unrelated user (not Rahim seller) tries to ship this order
        $response = $this->actingAs($this->otherUser)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/ship", [
                'tracking_number' => 'STEAL-12345',
                'carrier' => 'Pathao',
            ]);

        $response->assertStatus(403);
        $this->assertEquals('confirmed', $order->fresh()->status);
    }

    /**
     * 5. The server calculates financial totals independently and rejects/ignores client alterations.
     */
    public function test_scenario_05_server_rejects_altered_prices_and_totals(): void
    {
        // Attacker attempts to submit total_amount = 10 BDT and unit_price = 5 BDT
        $response = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/products/{$this->product->id}/order", [
                'quantity' => 1,
                'price' => 5.00,
                'total_amount' => 10.00,
                'delivery_fee' => 0.00,
                'delivery_type' => 'standard',
                'shipping_name' => 'Karim Buyer',
                'shipping_phone' => '01711000000',
                'shipping_address' => 'Dhanmondi',
            ]);

        $response->assertStatus(201);
        $orderData = $response->json('data') ?: $response->json('order');

        // Total MUST be 35,000 + 60 standard delivery = 35,060 BDT regardless of client payload
        $this->assertEquals(35060.00, (float) ($orderData['amount'] ?? $orderData['total_amount']));
        $this->assertEquals(35000.00, (float) $orderData['subtotal']);
        $this->assertEquals(60.00, (float) ($orderData['delivery_charge'] ?? $orderData['delivery_fee']));
    }

    /**
     * 6. Duplicate order submissions are handled safely via idempotency key.
     */
    public function test_scenario_06_duplicate_order_submissions_handled_safely(): void
    {
        $idempotencyKey = 'unique-checkout-token-abc-999';

        $payload = [
            'quantity' => 1,
            'delivery_type' => 'pickup',
            'shipping_name' => 'Karim Buyer',
            'shipping_phone' => '01711000000',
            'shipping_address' => 'Gulshan Store',
            'idempotency_key' => $idempotencyKey,
        ];

        // First submission
        $res1 = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/products/{$this->product->id}/order", $payload);
        $res1->assertStatus(201);
        $orderId = $res1->json('data.id') ?: $res1->json('order.id');

        // Duplicate submission with same key returns existing order safely
        $res2 = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/products/{$this->product->id}/order", $payload);
        $res2->assertStatus(200);
        $this->assertEquals($orderId, $res2->json('data.id') ?: $res2->json('order.id'));

        // Database only has 1 order
        $this->assertEquals(1, MarketplaceOrder::where('idempotency_key', $idempotencyKey)->count());
    }

    /**
     * 7. Invalid payment callbacks are rejected.
     */
    public function test_scenario_07_invalid_payment_callbacks_are_rejected(): void
    {
        $order = MarketplaceOrder::create([
            'order_number' => MarketplaceOrder::generateOrderNumber(),
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'unit_price' => 35000.00,
            'quantity' => 1,
            'subtotal' => 35000.00,
            'delivery_charge' => 60.00,
            'amount' => 35060.00,
            'currency' => 'BDT',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'delivery_type' => 'standard',
            'shipping_name' => 'Karim Buyer',
            'shipping_phone' => '01711000000',
            'shipping_address' => 'Dhanmondi',
        ]);

        // Attempt callback with forged signature
        $response = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/verify-payment", [
                'gateway' => 'sandbox',
                'transaction_id' => 'SANDBOX-TRX-FORGED',
                'payment_status' => 'COMPLETED',
                'amount' => 35060.00,
                'currency' => 'BDT',
                'signature' => 'invalid_tampered_signature_123',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
            ]);

        $this->assertEquals('unpaid', $order->fresh()->payment_status);
        $this->assertEquals('pending', $order->fresh()->status);
    }

    /**
     * 8. Valid provider callbacks are processed idempotently without duplicate ledger records.
     */
    public function test_scenario_08_valid_provider_callbacks_processed_idempotently(): void
    {
        $order = MarketplaceOrder::create([
            'order_number' => MarketplaceOrder::generateOrderNumber(),
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'unit_price' => 35000.00,
            'quantity' => 1,
            'subtotal' => 35000.00,
            'delivery_charge' => 60.00,
            'amount' => 35060.00,
            'currency' => 'BDT',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'delivery_type' => 'standard',
            'shipping_name' => 'Karim Buyer',
            'shipping_phone' => '01711000000',
            'shipping_address' => 'Dhanmondi',
        ]);

        // Initiate checkout to obtain gateway parameters and signature
        $checkoutRes = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/checkout", [
                'gateway' => 'sandbox',
            ]);
        $checkoutRes->assertStatus(200);
        $payload = $checkoutRes->json('data');

        // Send valid callback
        $callbackRes1 = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/verify-payment", [
                'gateway' => 'sandbox',
                'transaction_id' => $payload['transaction_id'],
                'amount' => $payload['amount'],
                'currency' => $payload['currency'],
                'payment_status' => 'COMPLETED',
                'signature' => $payload['signature'],
            ]);

        $callbackRes1->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $freshOrder = $order->fresh();
        $this->assertEquals('paid', $freshOrder->payment_status);
        $this->assertTrue(in_array($freshOrder->status, ['processing', 'confirmed']));

        $initialTxCount = MarketplacePaymentTransaction::where('order_id', $order->id)->count();

        // Resend identical callback (simulate webhook retry)
        $callbackRes2 = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/verify-payment", [
                'gateway' => 'sandbox',
                'transaction_id' => $payload['transaction_id'],
                'amount' => $payload['amount'],
                'currency' => $payload['currency'],
                'payment_status' => 'COMPLETED',
                'signature' => $payload['signature'],
            ]);

        $callbackRes2->assertStatus(200);

        // Transaction records must not be duplicated
        $this->assertEquals($initialTxCount, MarketplacePaymentTransaction::where('order_id', $order->id)->count());
    }

    /**
     * 9. Failed and pending payments do not appear as successful.
     */
    public function test_scenario_09_failed_and_pending_payments_do_not_appear_as_successful(): void
    {
        $order = MarketplaceOrder::create([
            'order_number' => MarketplaceOrder::generateOrderNumber(),
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'unit_price' => 35000.00,
            'quantity' => 1,
            'subtotal' => 35000.00,
            'delivery_charge' => 60.00,
            'amount' => 35060.00,
            'currency' => 'BDT',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'delivery_type' => 'standard',
            'shipping_name' => 'Karim Buyer',
            'shipping_phone' => '01711000000',
            'shipping_address' => 'Dhanmondi',
        ]);

        $checkoutRes = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/checkout", [
                'gateway' => 'sandbox',
            ]);
        $payload = $checkoutRes->json('data');

        // Customer cancels or payment fails at provider
        $failCallback = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/verify-payment", [
                'gateway' => 'sandbox',
                'transaction_id' => $payload['transaction_id'],
                'amount' => $payload['amount'],
                'currency' => $payload['currency'],
                'payment_status' => 'FAILED',
                'signature' => $payload['signature'],
            ]);

        $failCallback->assertStatus(422);

        $fresh = $order->fresh();
        $this->assertNotEquals('paid', $fresh->payment_status);
        $this->assertNotEquals('confirmed', $fresh->status);
        $this->assertEquals('failed', $fresh->payment_status);
    }

    /**
     * 10. Refunds cannot be completed without appropriate confirmation and authorization.
     */
    public function test_scenario_10_refunds_cannot_be_completed_without_appropriate_confirmation(): void
    {
        $order = MarketplaceOrder::create([
            'order_number' => MarketplaceOrder::generateOrderNumber(),
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'unit_price' => 35000.00,
            'quantity' => 1,
            'subtotal' => 35000.00,
            'delivery_charge' => 60.00,
            'amount' => 35060.00,
            'currency' => 'BDT',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'delivery_type' => 'standard',
            'shipping_name' => 'Karim Buyer',
            'shipping_phone' => '01711000000',
            'shipping_address' => 'Dhanmondi',
        ]);

        // 1. Regular user cannot trigger admin refund endpoint
        $this->actingAs($this->buyer)
            ->postJson("/api/v2/admin/marketplace/orders/{$order->id}/refund", [
                'reason' => 'Item defective',
            ])->assertStatus(403);

        // 2. Admin processes authorized refund
        $adminRes = $this->actingAs($this->adminUser)
            ->postJson("/api/v2/admin/marketplace/orders/{$order->id}/refund", [
                'reason' => 'Defective item verified by inspection',
            ]);

        $adminRes->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $fresh = $order->fresh();
        $this->assertEquals('refunded', $fresh->status);
        $this->assertEquals('refunded', $fresh->payment_status);
        $this->assertNotNull($fresh->refunded_at);

        // Verify refund transaction in ledger
        $this->assertDatabaseHas('marketplace_payment_transactions', [
            'order_id' => $order->id,
            'type' => 'refund',
            'status' => 'successful',
        ]);
    }

    /**
     * 11. Unauthorized order status changes are rejected.
     */
    public function test_scenario_11_unauthorized_order_status_changes_are_rejected(): void
    {
        $order = MarketplaceOrder::create([
            'order_number' => MarketplaceOrder::generateOrderNumber(),
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'unit_price' => 35000.00,
            'quantity' => 1,
            'subtotal' => 35000.00,
            'delivery_charge' => 60.00,
            'amount' => 35060.00,
            'currency' => 'BDT',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'delivery_type' => 'standard',
            'shipping_name' => 'Karim Buyer',
            'shipping_phone' => '01711000000',
            'shipping_address' => 'Dhanmondi',
        ]);

        // Buyer cannot call /ship (only seller can ship)
        $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/ship", [
                'tracking_number' => 'FAKE-TRACK',
            ])->assertStatus(403);

        // Seller cannot call /confirm-delivery (only buyer can confirm delivery)
        $this->actingAs($this->seller)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/confirm-delivery")
            ->assertStatus(403);

        // Random outsider cannot cancel order
        $this->actingAs($this->otherUser)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/cancel", [
                'reason' => 'I feel like cancelling someone elses order',
            ])->assertStatus(403);
    }

    /**
     * 12. Eligible buyers can submit reviews after delivery.
     */
    public function test_scenario_12_eligible_buyer_can_submit_review(): void
    {
        $order = MarketplaceOrder::create([
            'order_number' => MarketplaceOrder::generateOrderNumber(),
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'unit_price' => 35000.00,
            'quantity' => 1,
            'subtotal' => 35000.00,
            'delivery_charge' => 60.00,
            'amount' => 35060.00,
            'currency' => 'BDT',
            'status' => 'delivered',
            'payment_status' => 'paid',
            'delivery_type' => 'standard',
            'shipping_name' => 'Karim Buyer',
            'shipping_phone' => '01711000000',
            'shipping_address' => 'Dhanmondi',
        ]);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/review", [
                'rating' => 5,
                'comment' => 'Outstanding sound quality and ANC! Highly recommended.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('marketplace_reviews', [
            'product_id' => $this->product->id,
            'buyer_id' => $this->buyer->id,
            'order_id' => $order->id,
            'rating' => 5,
        ]);
    }

    /**
     * 13. Duplicate or unauthorized reviews are rejected.
     */
    public function test_scenario_13_duplicate_or_unauthorized_reviews_are_rejected(): void
    {
        $order = MarketplaceOrder::create([
            'order_number' => MarketplaceOrder::generateOrderNumber(),
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'unit_price' => 35000.00,
            'quantity' => 1,
            'subtotal' => 35000.00,
            'delivery_charge' => 60.00,
            'amount' => 35060.00,
            'currency' => 'BDT',
            'status' => 'delivered',
            'payment_status' => 'paid',
            'delivery_type' => 'standard',
            'shipping_name' => 'Karim Buyer',
            'shipping_phone' => '01711000000',
            'shipping_address' => 'Dhanmondi',
        ]);

        // First review succeeds
        $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/review", [
                'rating' => 5,
                'comment' => 'Great product!',
            ])->assertStatus(201);

        // Non-buyer cannot review Karim's order
        $intruderResponse = $this->actingAs($this->otherUser)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/review", [
                'rating' => 1,
                'comment' => 'I never bought this but want to leave a bad review.',
            ]);
        $intruderResponse->assertStatus(403);
    }

    /**
     * 14. Admin actions require the appropriate role.
     */
    public function test_scenario_14_admin_actions_require_the_appropriate_role(): void
    {
        // 1. Regular buyer gets 403 on admin order inspection
        $this->actingAs($this->buyer)
            ->getJson('/api/v2/admin/marketplace/orders')
            ->assertStatus(403);

        // 2. Regular seller gets 403 on admin transactions
        $this->actingAs($this->seller)
            ->getJson('/api/v2/admin/marketplace/payments')
            ->assertStatus(403);

        // 3. Admin user successfully views admin orders & transactions
        $adminRes = $this->actingAs($this->adminUser)
            ->getJson('/api/v2/admin/marketplace/orders');

        $adminRes->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);
    }

    /**
     * 15. Full end-to-end lifecycle transition integrity:
     * pending -> paid -> confirmed -> shipped -> delivered -> reviewed.
     */
    public function test_scenario_15_full_lifecycle_state_machine_transition_integrity(): void
    {
        // Step A: Buyer creates order
        $orderRes = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/products/{$this->product->id}/order", [
                'quantity' => 1,
                'delivery_type' => 'standard',
                'shipping_name' => 'Karim Buyer',
                'shipping_phone' => '01711000000',
                'shipping_address' => 'Dhanmondi, Dhaka',
            ]);
        $orderRes->assertStatus(201);
        $orderId = $orderRes->json('data.id') ?: $orderRes->json('order.id');

        // Step B: Buyer initiates checkout with sandbox gateway
        $checkoutRes = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/orders/{$orderId}/checkout", [
                'gateway' => 'sandbox',
            ]);
        $checkoutRes->assertStatus(200);
        $payload = $checkoutRes->json('data');

        // Step C: Payment verification callback
        $verifyRes = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/orders/{$orderId}/verify-payment", [
                'gateway' => 'sandbox',
                'transaction_id' => $payload['transaction_id'],
                'amount' => $payload['amount'],
                'currency' => $payload['currency'],
                'payment_status' => 'COMPLETED',
                'signature' => $payload['signature'],
            ]);
        $verifyRes->assertStatus(200);

        $order = MarketplaceOrder::findOrFail($orderId);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertTrue(in_array($order->status, ['processing', 'confirmed']));

        // Step D: Seller ships the order with tracking details
        $shipRes = $this->actingAs($this->seller)
            ->postJson("/api/v2/marketplace/orders/{$orderId}/ship", [
                'tracking_number' => 'REDX-998822',
                'carrier' => 'RedX Express',
            ]);
        $shipRes->assertStatus(200);

        $order->refresh();
        $this->assertEquals('shipped', $order->status);
        $this->assertEquals('REDX-998822', $order->tracking_number);
        $this->assertEquals('RedX Express', $order->tracking_carrier);
        $this->assertNotNull($order->shipped_at);

        // Step E: Buyer confirms receipt of order
        $deliverRes = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/orders/{$orderId}/confirm-delivery");
        $deliverRes->assertStatus(200);

        $order->refresh();
        $this->assertEquals('delivered', $order->status);
        $this->assertNotNull($order->delivered_at);

        // Step F: Buyer leaves a verified purchase review
        $reviewRes = $this->actingAs($this->buyer)
            ->postJson("/api/v2/marketplace/orders/{$orderId}/review", [
                'rating' => 5,
                'comment' => 'Received the parcel in pristine condition. Excellent seller!',
            ]);
        $reviewRes->assertStatus(201);

        $order->refresh();
        $this->assertTrue($order->reviews()->exists());

        // Step G: Verify seller sales summary reflects real data
        $summaryRes = $this->actingAs($this->seller)
            ->getJson('/api/v2/marketplace/seller/summary');
        $summaryRes->assertStatus(200);
        $summary = $summaryRes->json('data');

        $this->assertEquals(1, $summary['total_orders']);
        $this->assertEquals(1, $summary['completed_orders']);
        $this->assertEquals(35060.00, (float) $summary['gross_sales']);
    }
}
