<?php

namespace Tests\Feature;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceOrder;
use App\Models\MarketplacePaymentTransaction;
use App\Models\MarketplaceProduct;
use App\Models\Role;
use App\Models\User;
use App\Services\Marketplace\Payments\BkashGatewayDriver;
use App\Services\Marketplace\Payments\NagadGatewayDriver;
use App\Services\Marketplace\Payments\PaymentGatewayManager;
use App\Services\Marketplace\Payments\SandboxGatewayDriver;
use App\Services\Marketplace\Payments\SslCommerzGatewayDriver;
use App\Services\Marketplace\Payments\StripeGatewayDriver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewaysIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $seller;

    protected MarketplaceProduct $product;

    protected MarketplaceOrder $order;

    protected PaymentGatewayManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        Role::firstOrCreate(['name' => 'USER'], ['label' => 'User', 'description' => 'Regular user']);

        $this->seller = User::factory()->create([
            'name' => 'বিক্রেতা রহিম',
            'username' => 'seller_rahim',
        ]);

        $this->buyer = User::factory()->create([
            'name' => 'ক্রেতা করিম',
            'username' => 'buyer_karim',
        ]);

        $category = MarketplaceCategory::create([
            'name' => 'ইলেকট্রনিক্স',
            'slug' => 'electronics',
            'icon' => 'laptop',
            'display_order' => 1,
        ]);

        $this->product = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $category->id,
            'title' => 'স্মার্টফোন গ্যালাক্সি প্রো',
            'description' => 'একদম ফ্রেশ কন্ডিশন',
            'price' => 25000.00,
            'location' => 'ঢাকা',
            'status' => 'active',
        ]);

        $this->order = MarketplaceOrder::create([
            'order_number' => 'ORD_'.strtoupper(bin2hex(random_bytes(4))),
            'product_id' => $this->product->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'amount' => 25000.00,
            'currency' => 'BDT',
            'quantity' => 1,
            'status' => MarketplaceOrder::STATUS_PENDING,
            'payment_status' => 'unpaid',
            'escrow_status' => MarketplaceOrder::ESCROW_HELD,
            'shipping_address' => 'ধানমন্ডি, ঢাকা',
        ]);

        $this->manager = app(PaymentGatewayManager::class);
    }

    public function test_gateway_manager_resolves_all_supported_drivers(): void
    {
        $this->assertInstanceOf(BkashGatewayDriver::class, $this->manager->driver('bkash'));
        $this->assertInstanceOf(NagadGatewayDriver::class, $this->manager->driver('nagad'));
        $this->assertInstanceOf(SslCommerzGatewayDriver::class, $this->manager->driver('sslcommerz'));
        $this->assertInstanceOf(StripeGatewayDriver::class, $this->manager->driver('stripe'));
        $this->assertInstanceOf(StripeGatewayDriver::class, $this->manager->driver('card'));
        $this->assertInstanceOf(SandboxGatewayDriver::class, $this->manager->driver('sandbox'));
        $this->assertInstanceOf(SandboxGatewayDriver::class, $this->manager->driver('unknown_gateway'));
    }

    public function test_initiate_checkout_across_all_gateways(): void
    {
        $gateways = ['bkash', 'nagad', 'sslcommerz', 'stripe'];

        foreach ($gateways as $gw) {
            $session = $this->manager->initiateCheckout($this->order, $gw);

            $this->assertEquals($this->order->id, $session['order_id']);
            $this->assertEquals($gw, $session['gateway']);
            $this->assertNotEmpty($session['transaction_id']);
            $this->assertNotEmpty($session['checkout_url']);
            $this->assertEquals((float) $this->order->amount, (float) $session['amount']);

            // Assert pending transaction in ledger
            $this->assertDatabaseHas('marketplace_payment_transactions', [
                'order_id' => $this->order->id,
                'user_id' => $this->buyer->id,
                'provider' => $gw,
                'type' => MarketplacePaymentTransaction::TYPE_PAYMENT,
                'status' => MarketplacePaymentTransaction::STATUS_PENDING,
            ]);
        }
    }

    public function test_stripe_live_driver_checkout_and_verification_with_http_mock(): void
    {
        config(['services.stripe.secret' => 'sk_test_mock_stripe_key_12345']);

        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_mock_session_123',
                'url' => 'https://checkout.stripe.com/pay/cs_test_mock_session_123',
                'payment_status' => 'unpaid',
            ], 200),
            'https://api.stripe.com/v1/checkout/sessions/cs_test_mock_session_123' => Http::response([
                'id' => 'cs_test_mock_session_123',
                'payment_status' => 'paid',
                'amount_total' => 2500000, // 25,000 * 100
                'currency' => 'bdt',
                'payment_intent' => 'pi_mock_123456',
            ], 200),
        ]);

        $stripeDriver = new StripeGatewayDriver;
        $this->assertTrue($stripeDriver->isConfigured());

        $session = $stripeDriver->initiatePayment($this->order);
        $this->assertEquals('cs_test_mock_session_123', $session['transaction_id']);
        $this->assertEquals('https://checkout.stripe.com/pay/cs_test_mock_session_123', $session['checkout_url']);

        $verification = $stripeDriver->verifyPayment($this->order, [
            'transaction_id' => 'cs_test_mock_session_123',
        ]);

        $this->assertEquals('COMPLETED', $verification['status']);
        $this->assertEquals('stripe', $verification['gateway']);
        $this->assertEquals(25000.0, $verification['amount']);
    }

    public function test_sslcommerz_live_driver_checkout_and_verification_with_http_mock(): void
    {
        config([
            'services.sslcommerz.store_id' => 'mock_store_id',
            'services.sslcommerz.store_password' => 'mock_store_pass',
            'services.sslcommerz.base_url' => 'https://sandbox.sslcommerz.com',
        ]);

        Http::fake([
            'https://sandbox.sslcommerz.com/gwprocess/v4/api.php' => Http::response([
                'status' => 'SUCCESS',
                'GatewayPageURL' => 'https://sandbox.sslcommerz.com/gwprocess/v4/gw.php?session=mock_session_key',
                'sessionkey' => 'mock_session_key',
            ], 200),
            'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => Http::response([
                'status' => 'VALID',
                'amount' => '25000.00',
                'currency' => 'BDT',
                'tran_id' => 'SSL_TRX_12345',
                'bank_tran_id' => 'BANK_TRX_67890',
                'card_type' => 'VISA-CityBank',
            ], 200),
        ]);

        $sslDriver = new SslCommerzGatewayDriver;
        $this->assertTrue($sslDriver->isConfigured());

        $session = $sslDriver->initiatePayment($this->order);
        $this->assertEquals('sslcommerz', $session['gateway']);
        $this->assertStringContainsString('sslcommerz.com', $session['checkout_url']);

        $verification = $sslDriver->verifyPayment($this->order, [
            'val_id' => 'VAL_12345678',
        ]);

        $this->assertEquals('COMPLETED', $verification['status']);
        $this->assertEquals('sslcommerz', $verification['gateway']);
        $this->assertEquals(25000.0, $verification['amount']);
    }

    public function test_checkout_api_endpoint_via_sanctum_supports_stripe(): void
    {
        $response = $this->actingAs($this->buyer, 'sanctum')->postJson("/api/v2/marketplace/orders/{$this->order->id}/checkout", [
            'gateway' => 'stripe',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.gateway', 'stripe')
            ->assertJsonPath('data.order_id', $this->order->id)
            ->assertJsonStructure([
                'data' => [
                    'order_id',
                    'gateway',
                    'transaction_id',
                    'amount',
                    'checkout_url',
                ],
            ]);
    }

    public function test_verify_payment_callback_updates_order_status_to_paid(): void
    {
        $session = $this->manager->initiateCheckout($this->order, 'sandbox');

        $response = $this->actingAs($this->buyer, 'sanctum')->postJson("/api/v2/marketplace/orders/{$this->order->id}/verify-payment", [
            'transaction_id' => $session['transaction_id'],
            'payment_status' => 'COMPLETED',
            'amount' => $this->order->amount,
            'currency' => 'BDT',
            'signature' => $session['signature'] ?? null,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.payment_status', 'paid');

        $fresh = $this->order->fresh();
        $this->assertEquals('paid', $fresh->payment_status);
        $this->assertEquals(MarketplaceOrder::STATUS_PROCESSING, $fresh->status);

        // Verify transaction ledger updated to successful
        $this->assertDatabaseHas('marketplace_payment_transactions', [
            'order_id' => $this->order->id,
            'status' => MarketplacePaymentTransaction::STATUS_SUCCESSFUL,
        ]);
    }
}
