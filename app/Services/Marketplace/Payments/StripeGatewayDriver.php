<?php

namespace App\Services\Marketplace\Payments;

use App\Models\MarketplaceOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class StripeGatewayDriver extends AbstractPaymentGateway
{
    protected ?string $publishableKey;

    protected ?string $secretKey;

    protected ?string $webhookSecret;

    protected SandboxGatewayDriver $fallbackDriver;

    public function __construct()
    {
        $this->publishableKey = config('services.stripe.key');
        $this->secretKey = config('services.stripe.secret');
        $this->webhookSecret = config('services.stripe.webhook_secret');
        $this->fallbackDriver = new SandboxGatewayDriver('stripe');
    }

    public function getIdentifier(): string
    {
        return 'stripe';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->secretKey);
    }

    public function initiatePayment(MarketplaceOrder $order, array $options = []): array
    {
        if (! $this->isConfigured()) {
            return $this->fallbackDriver->initiatePayment($order, $options);
        }

        try {
            $currency = strtolower((string) ($order->currency ?: 'usd'));
            $unitAmount = (int) round(((float) $order->amount) * 100);

            $callbackUrl = route('api.v2.marketplace.orders.verify-payment', ['id' => $order->id]);
            $successUrl = $callbackUrl.'?gateway=stripe&session_id={CHECKOUT_SESSION_ID}&payment_status=COMPLETED';
            $cancelUrl = $callbackUrl.'?gateway=stripe&session_id={CHECKOUT_SESSION_ID}&payment_status=CANCELLED';

            $response = Http::withToken($this->secretKey)
                ->asForm()
                ->timeout(15)
                ->post('https://api.stripe.com/v1/checkout/sessions', [
                    'mode' => 'payment',
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'client_reference_id' => $order->order_number,
                    'payment_method_types' => ['card'],
                    'line_items' => [
                        [
                            'price_data' => [
                                'currency' => $currency,
                                'unit_amount' => $unitAmount,
                                'product_data' => [
                                    'name' => "Bondhoo Order #{$order->order_number}",
                                ],
                            ],
                            'quantity' => 1,
                        ],
                    ],
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'order_number' => (string) $order->order_number,
                        'buyer_id' => (string) $order->buyer_id,
                    ],
                ]);

            if ($response->successful() && $response->json('id')) {
                return [
                    'order_id' => $order->id,
                    'gateway' => 'stripe',
                    'transaction_id' => (string) $response->json('id'),
                    'amount' => (float) $order->amount,
                    'currency' => strtoupper($currency),
                    'checkout_url' => (string) $response->json('url'),
                    'expires_at' => now()->addMinutes(30)->toIso8601String(),
                ];
            }

            Log::error('Stripe Checkout Session Error', ['body' => $response->body()]);

            return $this->fallbackDriver->initiatePayment($order, $options);
        } catch (\Throwable $e) {
            Log::error('Stripe Initiation Exception: '.$e->getMessage());

            return $this->fallbackDriver->initiatePayment($order, $options);
        }
    }

    public function verifyPayment(MarketplaceOrder $order, array $payload): array
    {
        if (! $this->isConfigured()) {
            return $this->fallbackDriver->verifyPayment($order, $payload);
        }

        $sessionId = (string) ($payload['transaction_id'] ?? $payload['session_id'] ?? '');

        if ($sessionId === '') {
            throw new InvalidArgumentException('Missing Stripe session ID.');
        }

        try {
            $response = Http::withToken($this->secretKey)
                ->timeout(15)
                ->get("https://api.stripe.com/v1/checkout/sessions/{$sessionId}");

            if ($response->successful()) {
                $status = (string) $response->json('payment_status'); // 'paid', 'unpaid', 'no_payment_required'
                $amountTotal = ((float) $response->json('amount_total', 0)) / 100;
                $currency = strtoupper((string) $response->json('currency', 'USD'));

                if ($status === 'paid') {
                    if (! $this->amountsMatch($order->amount, $amountTotal)) {
                        throw new InvalidArgumentException("Amount mismatch: expected {$order->amount}, received {$amountTotal}.");
                    }

                    return [
                        'order_id' => $order->id,
                        'gateway' => 'stripe',
                        'transaction_id' => $sessionId,
                        'payment_intent' => (string) $response->json('payment_intent'),
                        'status' => 'COMPLETED',
                        'amount' => $amountTotal,
                        'currency' => $currency,
                        'verified_at' => now()->toIso8601String(),
                        'raw_response' => $this->sanitizePayload($response->json()),
                    ];
                }

                return [
                    'order_id' => $order->id,
                    'gateway' => 'stripe',
                    'transaction_id' => $sessionId,
                    'status' => 'FAILED',
                    'amount' => $amountTotal,
                    'currency' => $currency,
                    'verified_at' => now()->toIso8601String(),
                    'raw_response' => $this->sanitizePayload($response->json()),
                ];
            }

            Log::error('Stripe Verification Error', ['body' => $response->body()]);

            return $this->fallbackDriver->verifyPayment($order, $payload);
        } catch (\Throwable $e) {
            Log::error('Stripe Verify Exception: '.$e->getMessage());

            return $this->fallbackDriver->verifyPayment($order, $payload);
        }
    }

    public function processRefund(MarketplaceOrder $order, float $amount, string $reason): array
    {
        if (! $this->isConfigured()) {
            return $this->fallbackDriver->processRefund($order, $amount, $reason);
        }

        try {
            $response = Http::withToken($this->secretKey)
                ->asForm()
                ->timeout(15)
                ->post('https://api.stripe.com/v1/refunds', [
                    'amount' => (int) round($amount * 100),
                    'reason' => 'requested_by_customer',
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'order_number' => (string) $order->order_number,
                        'refund_reason' => $reason,
                    ],
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'REFUNDED',
                    'refund_id' => (string) $response->json('id'),
                    'amount' => $amount,
                    'currency' => strtoupper((string) $response->json('currency', 'USD')),
                    'refunded_at' => now()->toIso8601String(),
                    'raw_response' => $this->sanitizePayload($response->json()),
                ];
            }

            return $this->fallbackDriver->processRefund($order, $amount, $reason);
        } catch (\Throwable $e) {
            Log::error('Stripe Refund Exception: '.$e->getMessage());

            return $this->fallbackDriver->processRefund($order, $amount, $reason);
        }
    }
}
