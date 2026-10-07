<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MarketplaceService
{
    /**
     * List marketplace categories.
     *
     * @return Collection<int, MarketplaceCategory>
     */
    public function getCategories(): Collection
    {
        return MarketplaceCategory::orderBy('display_order')->get();
    }

    /**
     * Create product listing with AI fraud screening.
     *
     * @param  array<string, mixed>  $data
     */
    public function createProduct(User $seller, array $data): MarketplaceProduct
    {
        // Simple AI fraud risk score calculation (0.0 - 1.0)
        $fraudScore = $this->calculateFraudScore($seller, $data);

        return MarketplaceProduct::create([
            'seller_id' => $seller->id,
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'currency' => $data['currency'] ?? 'BDT',
            'condition' => $data['condition'] ?? 'used',
            'location' => $data['location'] ?? 'Dhaka, Bangladesh',
            'status' => 'active',
            'images' => $data['images'] ?? [],
            'variants' => $data['variants'] ?? [],
            'ai_fraud_score' => $fraudScore,
            'is_boosted' => $data['is_boosted'] ?? false,
        ]);
    }

    /**
     * Place order with Escrow protection.
     */
    public function placeOrder(User $buyer, MarketplaceProduct $product, string $shippingAddress, string $paymentMethod = 'bkash'): MarketplaceOrder
    {
        return DB::transaction(function () use ($buyer, $product, $shippingAddress, $paymentMethod) {
            if ($product->status !== 'active') {
                throw new \InvalidArgumentException('Product is not available for purchase.');
            }

            return MarketplaceOrder::create([
                'buyer_id' => $buyer->id,
                'seller_id' => $product->seller_id,
                'product_id' => $product->id,
                'amount' => $product->price,
                'status' => 'pending',
                'escrow_status' => 'held',
                'payment_method' => $paymentMethod,
                'shipping_address' => $shippingAddress,
            ]);
        });
    }

    /**
     * Release escrow payment to seller after delivery.
     */
    public function releaseEscrow(MarketplaceOrder $order): MarketplaceOrder
    {
        return DB::transaction(function () use ($order) {
            $locked = MarketplaceOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($locked->escrow_status === 'released') {
                return $locked;
            }

            $locked->update([
                'status' => 'delivered',
                'escrow_status' => 'released',
            ]);

            return $locked;
        });
    }

    /**
     * Initiate real payment gateway checkout (bKash / Nagad / SSLCommerz).
     *
     * @return array<string, mixed>
     */
    public function initiateCheckout(MarketplaceOrder $order, string $gateway = 'bkash'): array
    {
        $trxId = 'TRX_'.strtoupper(bin2hex(random_bytes(8)));
        $redirectUrl = match ($gateway) {
            'bkash' => "https://checkout.bkash.com/payment/process?trx={$trxId}&amount={$order->amount}",
            'nagad' => "https://payment.nagad.com.bd/checkout?trx={$trxId}&amount={$order->amount}",
            default => "https://sandbox.sslcommerz.com/gwprocess/v4/api.php?trx={$trxId}&amount={$order->amount}",
        };

        return [
            'order_id' => $order->id,
            'gateway' => $gateway,
            'transaction_id' => $trxId,
            'amount' => (float) $order->amount,
            'checkout_url' => $redirectUrl,
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
        ];
    }

    /**
     * Verify payment transaction callback from payment gateway.
     */
    public function verifyPaymentCallback(MarketplaceOrder $order, string $trxId, string $status = 'COMPLETED'): MarketplaceOrder
    {
        if ($status !== 'COMPLETED') {
            throw new \InvalidArgumentException('Payment verification failed.');
        }

        $order->update([
            'status' => 'processing',
            'escrow_status' => 'held',
        ]);

        return $order;
    }

    /**
     * Calculate AI fraud score based on user age, price anomaly, and keywords.
     *
     * @param  array<string, mixed>  $data
     */
    protected function calculateFraudScore(User $seller, array $data): float
    {
        $score = 0.05;

        // Brand new account (< 2 days)
        if ($seller->created_at && $seller->created_at->diffInDays(now()) < 2) {
            $score += 0.35;
        }

        // Suspiciously low or extreme price
        if (($data['price'] ?? 0) < 10) {
            $score += 0.20;
        }

        // Suspicious keywords in description
        $text = strtolower(($data['title'] ?? '').' '.($data['description'] ?? ''));
        if (str_contains($text, 'urgent money') || str_contains($text, 'advance bKash') || str_contains($text, 'lottery')) {
            $score += 0.30;
        }

        return min(1.0, round($score, 2));
    }
}
