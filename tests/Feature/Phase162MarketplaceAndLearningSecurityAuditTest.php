<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ConversationParticipant;
use App\Models\LearningCategory;
use App\Models\LearningCertificate;
use App\Models\LearningCourse;
use App\Models\LearningCourseCoupon;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseSection;
use App\Models\LearningEnrollment;
use App\Models\LearningTransaction;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceOffer;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase162MarketplaceAndLearningSecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected User $buyer;

    protected User $secondBuyer;

    protected User $instructor;

    protected MarketplaceCategory $category;

    protected LearningCategory $learningCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'name' => 'Rahim Seller',
            'username' => 'rahim_seller',
            'email' => 'rahim.seller@example.com',
            'phone' => '+8801711223344',
        ]);

        $this->buyer = User::factory()->create([
            'name' => 'Karim Buyer',
            'username' => 'karim_buyer',
            'email' => 'karim.buyer@example.com',
        ]);

        $this->secondBuyer = User::factory()->create([
            'name' => 'Nadia Buyer',
            'username' => 'nadia_buyer',
            'email' => 'nadia.buyer@example.com',
        ]);

        $this->instructor = User::factory()->create([
            'name' => 'Dr. Nusrat Instructor',
            'username' => 'nusrat_instructor',
            'email' => 'nusrat.instructor@example.com',
        ]);

        $this->category = MarketplaceCategory::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'icon' => 'devices',
            'display_order' => 1,
            'is_active' => true,
        ]);

        $this->learningCategory = LearningCategory::create([
            'name' => 'Software Engineering',
            'slug' => 'software-engineering',
            'icon' => 'code',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * Ensure email substrings containing "admin@" never grant admin privileges without RBAC role assignment.
     */
    public function test_privilege_escalation_via_admin_substring_email_is_blocked(): void
    {
        $spoofedUser = User::factory()->create([
            'name' => 'Spoofed Attacker',
            'email' => 'not-admin@evil-domain.test',
        ]);

        $spoofedUserTwo = User::factory()->create([
            'name' => 'Spoofed Attacker Two',
            'email' => 'admin@external-attacker.org',
        ]);

        $this->assertFalse($spoofedUser->isAdmin());
        $this->assertFalse($spoofedUserTwo->isAdmin());

        $listing = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Protected DSLR Camera',
            'description' => 'Genuine seller item',
            'price' => 55000,
            'currency' => 'BDT',
            'condition' => 'used',
            'status' => 'active',
        ]);

        $this->actingAs($spoofedUserTwo, 'sanctum')
            ->deleteJson("/api/v1/marketplace/products/{$listing->id}")
            ->assertStatus(403);
    }

    /**
     * Verify strict Marketplace ownership flags, non-owner 403 enforcement (even on trashed items), and SoftDeletes preservation of orders.
     */
    public function test_marketplace_ownership_flags_and_non_owner_mutation_blocks_including_trashed_listings(): void
    {
        $listing = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Apple Studio Display 5K',
            'description' => 'Mint condition studio monitor',
            'price' => 165000,
            'currency' => 'BDT',
            'condition' => 'like_new',
            'location' => 'Gulshan, Dhaka',
            'status' => 'active',
            'stock' => 2,
        ]);

        // Historical order linked to this listing
        $historicalOrder = MarketplaceOrder::create([
            'order_number' => 'ORD-HIST-1001',
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'product_id' => $listing->id,
            'quantity' => 1,
            'unit_price' => 165000,
            'amount' => 165000,
            'currency' => 'BDT',
            'status' => 'completed',
            'escrow_status' => 'released',
        ]);

        // Guest sees is_owner = false
        $this->getJson("/api/v1/marketplace/products/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('data.is_owner', false);

        // Owner sees is_owner = true
        $this->actingAs($this->seller, 'sanctum')
            ->getJson("/api/v1/marketplace/products/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('data.is_owner', true);

        // Non-owner sees is_owner = false
        $this->actingAs($this->buyer, 'sanctum')
            ->getJson("/api/v1/marketplace/products/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('data.is_owner', false);

        // Non-owner cannot edit, change status, or delete
        $this->actingAs($this->buyer, 'sanctum')
            ->putJson("/api/v1/marketplace/products/{$listing->id}", ['title' => 'Hacked Title'])
            ->assertStatus(403);

        $this->actingAs($this->buyer, 'sanctum')
            ->patchJson("/api/v1/marketplace/products/{$listing->id}/status", ['status' => 'sold'])
            ->assertStatus(403);

        $this->actingAs($this->buyer, 'sanctum')
            ->deleteJson("/api/v1/marketplace/products/{$listing->id}")
            ->assertStatus(403);

        // Owner deletes listing -> HTTP 200 SoftDelete
        $this->actingAs($this->seller, 'sanctum')
            ->deleteJson("/api/v1/marketplace/products/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('marketplace_products', ['id' => $listing->id]);

        // Owner repeat delete -> idempotent HTTP 200
        $this->actingAs($this->seller, 'sanctum')
            ->deleteJson("/api/v1/marketplace/products/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        // Non-owner attempting to delete an already soft-deleted listing MUST still receive 403 (no existence/status leak)
        $this->actingAs($this->buyer, 'sanctum')
            ->deleteJson("/api/v1/marketplace/products/{$listing->id}")
            ->assertStatus(403);

        // Historical order record remains intact
        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $historicalOrder->id,
            'product_id' => $listing->id,
            'status' => 'completed',
        ]);
    }

    /**
     * Verify self-purchase block, server-computed totals, buyer-scoped idempotency_key, and HTTP 409 conflict on single-quantity race.
     */
    public function test_marketplace_order_security_self_purchase_price_tampering_idempotency_and_409_conflict(): void
    {
        $product = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'PlayStation 5 Slim Disc Edition',
            'description' => 'Sealed box with 1 controller',
            'price' => 62000.00,
            'delivery_type' => 'delivery',
            'delivery_charge' => 200.00,
            'currency' => 'BDT',
            'condition' => 'new',
            'status' => 'active',
            'stock' => 1,
        ]);

        // 1. Self-purchase blocked
        $this->actingAs($this->seller, 'sanctum')
            ->postJson("/api/v1/marketplace/products/{$product->id}/order", [
                'shipping_name' => 'Rahim Seller',
                'shipping_phone' => '01711223344',
                'shipping_address' => 'Uttara, Dhaka',
                'quantity' => 1,
                'payment_method' => 'sandbox',
            ])
            ->assertStatus(422);

        // 2. Client-tampered price/amount/seller_id ignored; server computes 62000 + 200 = 62200
        $orderPayload = [
            'shipping_name' => 'Karim Buyer',
            'shipping_phone' => '01811223344',
            'shipping_address' => 'Dhanmondi, Dhaka',
            'delivery_type' => 'standard',
            'quantity' => 1,
            'payment_method' => 'sandbox',
            'idempotency_key' => 'shared-idem-key-001',
            'price' => 1.00,
            'amount' => 1.00,
            'seller_id' => $this->secondBuyer->id,
        ];

        $firstOrderRes = $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/marketplace/products/{$product->id}/order", $orderPayload);

        $firstOrderRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $orderId = (int) $firstOrderRes->json('data.id');
        $orderModel = MarketplaceOrder::findOrFail($orderId);
        $this->assertEquals($this->seller->id, (int) $orderModel->seller_id);
        $this->assertEquals(62200.00, (float) $orderModel->amount);

        // 3. Same buyer repeating with identical idempotency_key receives 200 with the exact same order (no duplicate)
        $repeatRes = $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/marketplace/products/{$product->id}/order", $orderPayload);

        $repeatRes->assertStatus(200)
            ->assertJsonPath('data.id', $orderId);
        $this->assertEquals(1, MarketplaceOrder::where('product_id', $product->id)->count());

        // 4. Second buyer attempting to purchase the now-exhausted single-quantity item gets HTTP 409 Conflict
        $conflictRes = $this->actingAs($this->secondBuyer, 'sanctum')
            ->postJson("/api/v1/marketplace/products/{$product->id}/order", [
                'shipping_name' => 'Nadia Buyer',
                'shipping_phone' => '01911223344',
                'shipping_address' => 'Banani, Dhaka',
                'quantity' => 1,
                'payment_method' => 'sandbox',
                'idempotency_key' => 'nadia-idem-key-001',
            ]);

        $conflictRes->assertStatus(409)
            ->assertJsonPath('code', 'LISTING_UNAVAILABLE_CONFLICT');

        // 5. Buyer-scoped idempotency isolation: Second buyer using 'shared-idem-key-001' on another active product creates her own order
        $secondProduct = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Nintendo Switch OLED',
            'description' => 'Neon Red/Blue',
            'price' => 36000.00,
            'currency' => 'BDT',
            'condition' => 'new',
            'status' => 'active',
            'stock' => 1,
        ]);

        $isolatedRes = $this->actingAs($this->secondBuyer, 'sanctum')
            ->postJson("/api/v1/marketplace/products/{$secondProduct->id}/order", [
                'shipping_name' => 'Nadia Buyer',
                'shipping_phone' => '01911223344',
                'shipping_address' => 'Banani, Dhaka',
                'quantity' => 1,
                'payment_method' => 'sandbox',
                'idempotency_key' => 'shared-idem-key-001',
            ]);

        $isolatedRes->assertStatus(201);
        $this->assertNotEquals($orderId, (int) $isolatedRes->json('data.id'));
        $this->assertEquals($this->secondBuyer->id, (int) $isolatedRes->json('data.buyer_id'));
    }

    /**
     * Verify forged payment signature rejection, genuine checkout verification, and buyer-only delivery confirmation / escrow release.
     */
    public function test_marketplace_payment_signature_forgery_and_buyer_only_escrow_delivery_confirmation(): void
    {
        $product = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Garmin Epix Pro Watch',
            'description' => 'Sapphire Titanium',
            'price' => 85000.00,
            'currency' => 'BDT',
            'condition' => 'new',
            'status' => 'active',
            'stock' => 1,
        ]);

        $orderRes = $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/marketplace/products/{$product->id}/order", [
                'shipping_name' => 'Karim Buyer',
                'shipping_phone' => '01811223344',
                'shipping_address' => 'Mirpur DOHS, Dhaka',
                'quantity' => 1,
                'payment_method' => 'bkash',
            ])
            ->assertStatus(201);

        $orderId = (int) $orderRes->json('data.id');

        // 1. Uninitiated / forged transaction callback is rejected with 422
        $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/marketplace/orders/{$orderId}/verify-payment", [
                'transaction_id' => 'TRX_FORGED_999999',
                'payment_status' => 'COMPLETED',
                'signature' => 'deadbeef_forged_signature',
            ])
            ->assertStatus(422);

        // 2. Initiate genuine checkout session and verify with valid server-issued signature
        $checkoutRes = $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/marketplace/orders/{$orderId}/checkout", ['gateway' => 'sandbox'])
            ->assertOk();

        $trxId = (string) $checkoutRes->json('data.transaction_id');
        $signature = (string) $checkoutRes->json('data.signature');

        $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/marketplace/orders/{$orderId}/verify-payment", [
                'transaction_id' => $trxId,
                'payment_status' => 'COMPLETED',
                'signature' => $signature,
            ])
            ->assertOk();

        $this->assertEquals('held', MarketplaceOrder::findOrFail($orderId)->escrow_status);

        // 3. Seller ships order, but seller CANNOT confirm delivery or self-release escrow
        $this->actingAs($this->seller, 'sanctum')
            ->postJson("/api/v1/marketplace/orders/{$orderId}/ship", [
                'carrier' => 'Pathao Courier',
                'tracking_number' => 'PTH-889900',
            ])
            ->assertOk();

        $this->actingAs($this->seller, 'sanctum')
            ->postJson("/api/v1/marketplace/orders/{$orderId}/confirm-delivery")
            ->assertStatus(403);

        $this->actingAs($this->secondBuyer, 'sanctum')
            ->postJson("/api/v1/marketplace/orders/{$orderId}/confirm-delivery")
            ->assertStatus(403);

        // 4. Buyer confirms delivery -> escrow released
        $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/marketplace/orders/{$orderId}/confirm-delivery")
            ->assertOk();

        $completedOrder = MarketplaceOrder::findOrFail($orderId);
        $this->assertEquals('released', $completedOrder->escrow_status);
    }

    /**
     * Verify full offer -> counteroffer -> buyer accepts counteroffer -> negotiated order pricing & expired offer block.
     */
    public function test_marketplace_offer_counteroffer_redemption_and_expiry_lifecycle(): void
    {
        $product = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'DJI Mavic 3 Classic Drone',
            'description' => 'With RC remote controller',
            'price' => 150000.00,
            'is_negotiable' => true,
            'currency' => 'BDT',
            'condition' => 'like_new',
            'status' => 'active',
            'stock' => 1,
        ]);

        // Seller cannot submit offer on own listing
        $this->actingAs($this->seller, 'sanctum')
            ->postJson("/api/v1/marketplace/products/{$product->id}/offers", [
                'offer_amount' => 130000,
            ])
            ->assertStatus(422);

        // Buyer submits offer of 135,000
        $offerRes = $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/marketplace/products/{$product->id}/offers", [
                'offer_amount' => 135000,
                'message' => 'Can pick up today',
            ])
            ->assertStatus(201);

        $offerId = (int) $offerRes->json('data.id');

        // Seller counters with 142,000
        $this->actingAs($this->seller, 'sanctum')
            ->postJson("/api/v1/marketplace/offers/{$offerId}/counter", [
                'counter_amount' => 142000,
                'counter_message' => 'Meet me at 142k including extra battery',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'countered');

        // Buyer accepts seller's counteroffer
        $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/marketplace/offers/{$offerId}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        // Buyer places order -> automatically honors accepted counteroffer price (142,000) and marks offer redeemed
        $orderRes = $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/marketplace/products/{$product->id}/order", [
                'shipping_name' => 'Karim Buyer',
                'shipping_phone' => '01811223344',
                'shipping_address' => 'Banani, Dhaka',
                'delivery_type' => 'pickup',
                'quantity' => 1,
                'payment_method' => 'sandbox',
            ])
            ->assertStatus(201);

        $order = MarketplaceOrder::findOrFail((int) $orderRes->json('data.id'));
        $this->assertEquals(142000.00, (float) $order->amount);
        $this->assertEquals($offerId, (int) $order->accepted_offer_id);
        $this->assertNotNull(MarketplaceOffer::findOrFail($offerId)->redeemed_at);

        // Expired offer cannot be accepted
        $secondProduct = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Sony WH-1000XM5',
            'description' => 'Noise cancelling wireless headphones',
            'price' => 32000.00,
            'is_negotiable' => true,
            'currency' => 'BDT',
            'condition' => 'new',
            'status' => 'active',
            'stock' => 1,
        ]);

        $expiredOffer = MarketplaceOffer::create([
            'product_id' => $secondProduct->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'offer_amount' => 28000.00,
            'original_price' => 32000.00,
            'currency' => 'BDT',
            'status' => 'pending',
            'expires_at' => now()->subHour(),
        ]);

        $this->actingAs($this->seller, 'sanctum')
            ->postJson("/api/v1/marketplace/offers/{$expiredOffer->id}/accept")
            ->assertStatus(422);
    }

    /**
     * Verify contactSeller creates active non-request Messenger thread for both parties and public listing leaks zero PII.
     */
    public function test_marketplace_contact_seller_opens_active_messenger_conversation_and_public_payload_hides_pii(): void
    {
        $product = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Keychron Q1 Pro Mechanical Keyboard',
            'description' => 'Wireless QMK/VIA custom keyboard',
            'price' => 19500.00,
            'currency' => 'BDT',
            'condition' => 'new',
            'status' => 'active',
            'stock' => 1,
        ]);

        // Public payload must NOT leak seller email or phone
        $publicRes = $this->getJson("/api/v1/marketplace/products/{$product->id}")
            ->assertOk();

        $sellerJson = $publicRes->json('data.seller');
        $this->assertArrayNotHasKey('email', $sellerJson ?? []);
        $this->assertArrayNotHasKey('phone', $sellerJson ?? []);
        $this->assertStringNotContainsString('rahim.seller@example.com', $publicRes->getContent());
        $this->assertStringNotContainsString('+8801711223344', $publicRes->getContent());

        // Guest accessing Web /marketplace is NOT auto-logged in as User #1
        $this->get('/marketplace')->assertOk();
        $this->assertGuest();

        // Buyer contacts non-friend Seller -> conversation immediately active for both Buyer & Seller
        $contactRes = $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/marketplace/products/{$product->id}/contact", [
                'message' => 'Hello, is the Keychron Q1 Pro still in stock?',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $conversationId = (int) $contactRes->json('data.conversation_id');
        $this->assertGreaterThan(0, $conversationId);

        $participants = ConversationParticipant::where('conversation_id', $conversationId)->get();
        $this->assertCount(2, $participants);
        foreach ($participants as $participant) {
            $this->assertFalse((bool) $participant->is_request);
            $this->assertEquals('accepted', $participant->request_status);
        }
    }

    /**
     * Verify Learning enrollment with empty body, wallet validation, coupon scoping/expiry/max_uses, and instructor self-enroll block.
     */
    public function test_learning_enrollment_empty_body_free_paid_wallet_and_coupon_security(): void
    {
        $freeCourse = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $this->learningCategory->id,
            'title' => 'Kotlin Coroutines Essentials',
            'slug' => 'kotlin-coroutines-essentials',
            'description' => 'Structured concurrency fundamentals.',
            'level' => 'beginner',
            'language' => 'en',
            'type' => LearningCourse::TYPE_FREE,
            'price' => 0.00,
            'status' => LearningCourse::STATUS_PUBLISHED,
            'is_published' => true,
        ]);

        $paidCourse = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $this->learningCategory->id,
            'title' => 'Production Laravel 12 Security Architecture',
            'slug' => 'production-laravel-12-security',
            'description' => 'Deep security engineering for high-traffic apps.',
            'level' => 'expert',
            'language' => 'en',
            'type' => LearningCourse::TYPE_PAID,
            'price' => 100.00,
            'currency' => 'USD',
            'status' => LearningCourse::STATUS_PUBLISHED,
            'is_published' => true,
        ]);

        $draftCourse = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $this->learningCategory->id,
            'title' => 'Unpublished Draft Course',
            'slug' => 'unpublished-draft-course',
            'description' => 'Still being authored.',
            'level' => 'beginner',
            'language' => 'en',
            'type' => LearningCourse::TYPE_FREE,
            'price' => 0.00,
            'status' => LearningCourse::STATUS_DRAFT,
            'is_published' => false,
        ]);

        // 1. Free course enrollment with empty JSON body {} succeeds
        $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$freeCourse->id}/enroll", [])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');

        // 2. Instructor self-enrollment and draft course enrollment are rejected
        $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$freeCourse->id}/enroll", ['coupon_code' => null])
            ->assertStatus(422);

        $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$draftCourse->id}/enroll", [])
            ->assertStatus(422);

        // 3. Paid course with insufficient wallet fails cleanly with zero enrollment created
        $wallet = Wallet::create([
            'user_id' => $this->buyer->id,
            'balance' => 50.00,
            'currency' => 'USD',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$paidCourse->id}/enroll", ['coupon_code' => null])
            ->assertStatus(422);

        $this->assertEquals(0, LearningEnrollment::where('course_id', $paidCourse->id)->where('user_id', $this->buyer->id)->count());

        // 4. Coupon validation: wrong course_id, expired, and exhausted max_uses all rejected
        $wrongCourseCoupon = LearningCourseCoupon::create([
            'course_id' => $freeCourse->id,
            'code' => 'WRONGCOURSE50',
            'discount_type' => LearningCourseCoupon::TYPE_PERCENTAGE,
            'discount_amount' => 50,
            'is_active' => true,
        ]);

        $expiredCoupon = LearningCourseCoupon::create([
            'course_id' => $paidCourse->id,
            'code' => 'EXPIRED60',
            'discount_type' => LearningCourseCoupon::TYPE_PERCENTAGE,
            'discount_amount' => 60,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $exhaustedCoupon = LearningCourseCoupon::create([
            'course_id' => $paidCourse->id,
            'code' => 'MAXEDOUT60',
            'discount_type' => LearningCourseCoupon::TYPE_PERCENTAGE,
            'discount_amount' => 60,
            'max_uses' => 5,
            'times_used' => 5,
            'is_active' => true,
        ]);

        foreach ([$wrongCourseCoupon->code, $expiredCoupon->code, $exhaustedCoupon->code] as $badCode) {
            $this->actingAs($this->buyer, 'sanctum')
                ->postJson("/api/v1/learning/courses/{$paidCourse->id}/enroll", ['coupon_code' => $badCode])
                ->assertStatus(422);
        }

        // 5. Valid 60% coupon reduces $100 -> $40, fits $50 wallet, debits $40, and increments times_used
        $validCoupon = LearningCourseCoupon::create([
            'course_id' => $paidCourse->id,
            'code' => 'BONDHOO60',
            'discount_type' => LearningCourseCoupon::TYPE_PERCENTAGE,
            'discount_amount' => 60,
            'max_uses' => 10,
            'times_used' => 0,
            'expires_at' => now()->addDays(7),
            'is_active' => true,
        ]);

        $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$paidCourse->id}/enroll", ['coupon_code' => 'bondhoo60'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(10.00, (float) $wallet->fresh()->balance);
        $this->assertEquals(1, (int) $validCoupon->fresh()->times_used);
        $txn = LearningTransaction::where('course_id', $paidCourse->id)->where('user_id', $this->buyer->id)->first();
        $this->assertNotNull($txn);
        $this->assertEquals(40.00, (float) $txn->amount);
    }

    /**
     * Verify cancelled re-enrollment payment enforcement, completed enrollment protection, curriculum redaction, progress -> certificate, and Web LMS routes.
     */
    public function test_learning_cancelled_and_completed_enrollment_transitions_curriculum_redaction_and_certificate_verification(): void
    {
        $paidCourse = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $this->learningCategory->id,
            'title' => 'Cloud Native Microservices',
            'slug' => 'cloud-native-microservices',
            'description' => 'End-to-end distributed architecture.',
            'level' => 'intermediate',
            'language' => 'en',
            'type' => LearningCourse::TYPE_PAID,
            'price' => 30.00,
            'currency' => 'USD',
            'status' => LearningCourse::STATUS_PUBLISHED,
            'is_published' => true,
            'total_sections' => 1,
            'total_lessons' => 2,
        ]);

        $section = LearningCourseSection::create([
            'course_id' => $paidCourse->id,
            'title' => 'Module 1: Service Mesh',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $previewLesson = LearningCourseLesson::create([
            'course_id' => $paidCourse->id,
            'section_id' => $section->id,
            'title' => 'Welcome & Architecture Overview',
            'slug' => 'welcome-architecture-overview',
            'type' => LearningCourseLesson::TYPE_VIDEO,
            'content' => 'Public preview lesson notes.',
            'video_url' => 'https://cdn.bondhoo.test/videos/preview.mp4',
            'video_duration_seconds' => 300,
            'sort_order' => 1,
            'is_preview' => true,
            'is_mandatory' => true,
            'is_published' => true,
        ]);

        $lockedLesson = LearningCourseLesson::create([
            'course_id' => $paidCourse->id,
            'section_id' => $section->id,
            'title' => 'Zero-Trust mTLS Deep Dive',
            'slug' => 'zero-trust-mtls-deep-dive',
            'type' => LearningCourseLesson::TYPE_VIDEO,
            'content' => 'Paid subscriber confidential content.',
            'video_url' => 'https://cdn.bondhoo.test/videos/private-mtls.mp4',
            'video_duration_seconds' => 900,
            'sort_order' => 2,
            'is_preview' => false,
            'is_mandatory' => true,
            'is_published' => true,
        ]);

        // 1. Non-enrolled user receives preview content, while locked lesson video_url & content are redacted
        $curriculumGuestRes = $this->actingAs($this->secondBuyer, 'sanctum')
            ->getJson("/api/v1/learning/courses/{$paidCourse->id}/curriculum")
            ->assertOk();

        $guestLessons = $curriculumGuestRes->json('data.0.lessons');
        $this->assertNotNull($guestLessons[0]['video_url']);
        $this->assertNull($guestLessons[1]['video_url']);
        $this->assertNull($guestLessons[1]['content']);

        // 2. Cancelled enrollment cannot re-enroll for free without paying wallet balance
        LearningEnrollment::create([
            'course_id' => $paidCourse->id,
            'user_id' => $this->buyer->id,
            'status' => LearningEnrollment::STATUS_CANCELLED,
            'price_paid' => 0.00,
            'currency' => 'USD',
            'enrolled_at' => now()->subDays(3),
        ]);

        $wallet = Wallet::create([
            'user_id' => $this->buyer->id,
            'balance' => 10.00,
            'currency' => 'USD',
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$paidCourse->id}/enroll", [])
            ->assertStatus(422);

        // Top up wallet to $50 and re-enroll -> debits $30 and activates enrollment
        $wallet->update(['balance' => 50.00]);

        $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$paidCourse->id}/enroll", [])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertEquals(20.00, (float) $wallet->fresh()->balance);

        // 3. Complete both lessons -> auto-completes course & issues certificate
        $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/learning/lessons/{$previewLesson->id}/progress", [
                'position_seconds' => 300,
                'mark_completed' => true,
            ])
            ->assertOk();

        $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/learning/lessons/{$lockedLesson->id}/progress", [
                'position_seconds' => 900,
                'mark_completed' => true,
            ])
            ->assertOk();

        $enrollment = LearningEnrollment::where('course_id', $paidCourse->id)
            ->where('user_id', $this->buyer->id)
            ->firstOrFail();
        $this->assertEquals(LearningEnrollment::STATUS_COMPLETED, $enrollment->status);

        // 4. Repeat enroll on completed course preserves 'completed' status and does NOT double-charge
        $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$paidCourse->id}/enroll", [])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');
        $this->assertEquals(20.00, (float) $wallet->fresh()->balance);

        // 5. My Learning includes completed courses and accurate meta counters
        $this->actingAs($this->buyer, 'sanctum')
            ->getJson('/api/v1/learning/my-learning')
            ->assertOk()
            ->assertJsonPath('meta.completed_count', 1)
            ->assertJsonCount(1, 'data');

        // 6. Certificate issued and publicly verifiable via API and Web routes
        $cert = LearningCertificate::where('course_id', $paidCourse->id)
            ->where('user_id', $this->buyer->id)
            ->first();
        $this->assertNotNull($cert);

        $this->getJson("/api/v1/learning/certificates/verify/{$cert->certificate_code}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->get('/learning')->assertOk();
        $this->get("/learning/courses/{$paidCourse->slug}")->assertOk();
        $this->get("/verify/cert/{$cert->certificate_code}")->assertOk();
    }
}
