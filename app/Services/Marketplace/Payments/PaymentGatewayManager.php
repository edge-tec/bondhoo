<?php

namespace App\Services\Marketplace\Payments;

use App\Models\MarketplaceOrder;
use App\Models\MarketplacePaymentTransaction;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * Map of supported drivers.
     *
     * @var array<string, class-string<PaymentGatewayInterface>>
     */
    protected array $drivers = [
        'bkash' => BkashGatewayDriver::class,
        'nagad' => NagadGatewayDriver::class,
        'sslcommerz' => SslCommerzGatewayDriver::class,
        'stripe' => StripeGatewayDriver::class,
        'card' => StripeGatewayDriver::class,
        'sandbox' => SandboxGatewayDriver::class,
    ];

    /**
     * Resolve a gateway driver instance.
     */
    public function driver(?string $gateway = null): PaymentGatewayInterface
    {
        $name = strtolower(trim((string) $gateway));
        if (empty($name) || ! isset($this->drivers[$name])) {
            if (app()->environment('production')) {
                throw new InvalidArgumentException("Unsupported payment gateway '{$gateway}' in production.");
            }
            $name = 'sandbox';
        }

        if ($name === 'sandbox' && app()->environment('production')) {
            throw new InvalidArgumentException('Sandbox payment gateway is not permitted in production environment.');
        }

        $class = $this->drivers[$name];

        return new $class;
    }

    /**
     * Initiate payment session and log pending ledger transaction.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function initiateCheckout(MarketplaceOrder $order, string $gateway = 'bkash', array $options = [], ?string $ip = null): array
    {
        if ($order->isPaid()) {
            throw new InvalidArgumentException('Cannot initiate payment for an already paid order.');
        }

        if (in_array($order->status, [MarketplaceOrder::STATUS_CANCELLED, MarketplaceOrder::STATUS_REFUNDED], true)) {
            throw new InvalidArgumentException('Cannot initiate payment for a cancelled or refunded order.');
        }

        $driver = $this->driver($gateway);
        $session = $driver->initiatePayment($order, $options);

        $idempotencyKey = 'INIT_'.hash('sha256', "{$order->id}_{$session['transaction_id']}_{$order->amount}");

        // Save initial transaction record into immutable audit ledger
        MarketplacePaymentTransaction::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'order_id' => $order->id,
                'user_id' => $order->buyer_id,
                'transaction_reference' => $session['transaction_id'],
                'provider' => $session['gateway'],
                'type' => MarketplacePaymentTransaction::TYPE_PAYMENT,
                'amount' => $order->amount,
                'currency' => $order->currency ?: 'BDT',
                'status' => MarketplacePaymentTransaction::STATUS_PENDING,
                'gateway_payload' => [
                    'order_number' => $order->order_number,
                    'amount' => $order->amount,
                    'gateway' => $session['gateway'],
                ],
                'ip_address' => $ip,
            ]
        );

        return $session;
    }

    /**
     * Process payment gateway callback / webhook idempotently with audit ledger persistence.
     *
     * @param  array<string, mixed>  $payload
     * @return array{order: MarketplaceOrder, verified: bool, transaction: MarketplacePaymentTransaction}
     */
    public function processCallback(MarketplaceOrder $order, array $payload, ?string $ip = null): array
    {
        if (in_array($order->status, [MarketplaceOrder::STATUS_CANCELLED, MarketplaceOrder::STATUS_REFUNDED], true)) {
            throw new InvalidArgumentException("Cannot process payment callback for a {$order->status} order.");
        }

        $trxId = (string) ($payload['transaction_id'] ?? $payload['session_id'] ?? $payload['paymentID'] ?? '');
        if ($trxId !== '') {
            $existingWithTrx = MarketplacePaymentTransaction::where('transaction_reference', $trxId)->first();
            if ($existingWithTrx && (int) $existingWithTrx->order_id !== (int) $order->id) {
                throw new InvalidArgumentException("Transaction reference '{$trxId}' belongs to another order.");
            }
        }

        $gatewayName = strtolower((string) ($payload['gateway'] ?? $order->payment_method ?? 'bkash'));
        $driver = $this->driver($gatewayName);

        // Verification call via driver
        $verification = $driver->verifyPayment($order, $payload);
        $trxId = $verification['transaction_id'];

        return DB::transaction(function () use ($order, $gatewayName, $verification, $trxId, $ip) {
            $lockedOrder = MarketplaceOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if (in_array($lockedOrder->status, [MarketplaceOrder::STATUS_CANCELLED, MarketplaceOrder::STATUS_REFUNDED], true)) {
                throw new InvalidArgumentException("Cannot process payment callback for a {$lockedOrder->status} order.");
            }

            // Cross-order replay prevention: if transaction reference already exists for another order, reject immediately
            $existingWithTrx = MarketplacePaymentTransaction::where('transaction_reference', $trxId)->first();
            if ($existingWithTrx && (int) $existingWithTrx->order_id !== (int) $lockedOrder->id) {
                throw new InvalidArgumentException("Transaction reference '{$trxId}' belongs to another order.");
            }

            // If order is already paid, do not allow subsequent callback to downgrade or alter payment state
            if ($lockedOrder->isPaid()) {
                return [
                    'order' => $lockedOrder->fresh(['buyer', 'seller', 'product']),
                    'verified' => true,
                    'transaction' => $existingWithTrx ?? MarketplacePaymentTransaction::where('order_id', $lockedOrder->id)->latest('id')->firstOrFail(),
                ];
            }

            // Amount and currency validation on verified callbacks
            $verified = (bool) ($verification['verified'] ?? false);
            if ($verified) {
                $paidAmount = (float) ($verification['amount'] ?? 0.0);
                $expectedAmount = (float) $lockedOrder->amount;
                if (abs($paidAmount - $expectedAmount) > 0.01) {
                    $verified = false;
                    $verification['status'] = 'failed';
                    $verification['metadata']['amount_mismatch'] = "Expected {$expectedAmount}, got {$paidAmount}";
                }

                $paidCurrency = strtoupper(trim((string) ($verification['currency'] ?? 'BDT')));
                $expectedCurrency = strtoupper(trim((string) ($lockedOrder->currency ?: 'BDT')));
                if ($paidCurrency !== $expectedCurrency) {
                    $verified = false;
                    $verification['status'] = 'failed';
                    $verification['metadata']['currency_mismatch'] = "Expected {$expectedCurrency}, got {$paidCurrency}";
                }
            }

            $idempotencyKey = 'VERIFY_'.hash('sha256', "{$lockedOrder->id}_{$trxId}_{$verification['status']}");

            $trxStatus = match (strtolower((string) $verification['status'])) {
                'completed', 'success', 'paid' => MarketplacePaymentTransaction::STATUS_SUCCESSFUL,
                'failed' => MarketplacePaymentTransaction::STATUS_FAILED,
                'cancelled' => MarketplacePaymentTransaction::STATUS_CANCELLED,
                default => MarketplacePaymentTransaction::STATUS_REVERSED,
            };

            $transaction = $existingWithTrx;
            if ($transaction) {
                $transaction->update([
                    'status' => $trxStatus,
                    'gateway_payload' => $verification['metadata'] ?? [],
                    'gateway_response' => [
                        'status' => $verification['status'],
                        'verified' => $verified,
                    ],
                    'ip_address' => $ip,
                    'verified_at' => $verified ? now() : null,
                ]);
            } else {
                $transaction = MarketplacePaymentTransaction::create([
                    'order_id' => $lockedOrder->id,
                    'user_id' => $lockedOrder->buyer_id,
                    'transaction_reference' => $trxId,
                    'provider' => $gatewayName,
                    'type' => MarketplacePaymentTransaction::TYPE_PAYMENT,
                    'amount' => (float) ($verification['amount'] ?? $lockedOrder->amount),
                    'currency' => $verification['currency'] ?? ($lockedOrder->currency ?: 'BDT'),
                    'status' => $trxStatus,
                    'idempotency_key' => $idempotencyKey,
                    'gateway_payload' => $verification['metadata'] ?? [],
                    'gateway_response' => [
                        'status' => $verification['status'],
                        'verified' => $verified,
                    ],
                    'ip_address' => $ip,
                    'verified_at' => $verified ? now() : null,
                ]);
            }

            if ($verified) {
                $lockedOrder->update([
                    'status' => MarketplaceOrder::STATUS_PROCESSING,
                    'payment_status' => MarketplaceOrder::PAYMENT_PAID,
                    'transaction_id' => $trxId,
                    'paid_at' => $lockedOrder->paid_at ?: now(),
                    'escrow_status' => MarketplaceOrder::ESCROW_HELD,
                ]);
            } elseif ($trxStatus === MarketplacePaymentTransaction::STATUS_FAILED) {
                $lockedOrder->update([
                    'payment_status' => MarketplaceOrder::PAYMENT_FAILED,
                ]);
            }

            return [
                'order' => $lockedOrder->fresh(['buyer', 'seller', 'product']),
                'verified' => $verified,
                'transaction' => $transaction,
            ];
        });
    }

    /**
     * Process order refund and record in audit ledger.
     *
     * @return array{order: MarketplaceOrder, transaction: MarketplacePaymentTransaction, success: bool}
     */
    public function processRefund(MarketplaceOrder $order, float $amount, string $reason, ?User $admin = null): array
    {
        return DB::transaction(function () use ($order, $amount, $reason, $admin) {
            $lockedOrder = MarketplaceOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if (! $lockedOrder->isPaid()) {
                throw new InvalidArgumentException('Cannot refund an unpaid order.');
            }

            if ($lockedOrder->payment_status === MarketplaceOrder::PAYMENT_REFUNDED || $lockedOrder->status === MarketplaceOrder::STATUS_REFUNDED) {
                throw new InvalidArgumentException('Order is already refunded.');
            }

            $maxRefund = (float) $lockedOrder->amount - (float) ($lockedOrder->refund_amount ?? 0);
            if ($amount <= 0 || $amount > ($maxRefund + 0.01)) {
                throw new InvalidArgumentException("Invalid refund amount {$amount}. Maximum refundable is {$maxRefund}.");
            }

            if (! in_array($lockedOrder->status, [
                MarketplaceOrder::STATUS_PROCESSING,
                MarketplaceOrder::STATUS_CONFIRMED,
                MarketplaceOrder::STATUS_SHIPPED,
                MarketplaceOrder::STATUS_DISPUTED,
                MarketplaceOrder::STATUS_REFUND_REQUESTED,
                MarketplaceOrder::STATUS_DELIVERED,
                MarketplaceOrder::STATUS_CANCELLED,
            ], true)) {
                throw new InvalidArgumentException("Order in status '{$lockedOrder->status}' is not eligible for refund.");
            }

            $driver = $this->driver($lockedOrder->payment_method);
            $gatewayResult = $driver->processRefund($lockedOrder, $amount, $reason);

            if (empty($gatewayResult['success']) && ($gatewayResult['status'] ?? '') !== 'REFUNDED') {
                throw new \RuntimeException('Payment gateway refund failed: '.($gatewayResult['error'] ?? 'Unknown error'));
            }

            $idempotencyKey = 'REFUND_'.hash('sha256', "{$lockedOrder->id}_{$amount}_".time());

            $transaction = MarketplacePaymentTransaction::create([
                'order_id' => $lockedOrder->id,
                'user_id' => $lockedOrder->buyer_id,
                'transaction_reference' => $gatewayResult['refund_id'] ?? ('REF_'.bin2hex(random_bytes(6))),
                'provider' => $lockedOrder->payment_method ?: 'manual',
                'type' => MarketplacePaymentTransaction::TYPE_REFUND,
                'amount' => $amount,
                'currency' => $lockedOrder->currency ?: 'BDT',
                'status' => MarketplacePaymentTransaction::STATUS_SUCCESSFUL,
                'idempotency_key' => $idempotencyKey,
                'gateway_payload' => [
                    'reason' => $reason,
                    'authorized_by_admin' => $admin?->id,
                ],
                'gateway_response' => $gatewayResult,
                'verified_at' => now(),
            ]);

            $newRefundAmount = (float) ($lockedOrder->refund_amount ?? 0) + $amount;
            $isFullyRefunded = $newRefundAmount >= ((float) $lockedOrder->amount - 0.01);

            $lockedOrder->update([
                'status' => $isFullyRefunded ? MarketplaceOrder::STATUS_REFUNDED : $lockedOrder->status,
                'payment_status' => $isFullyRefunded ? MarketplaceOrder::PAYMENT_REFUNDED : $lockedOrder->payment_status,
                'escrow_status' => $isFullyRefunded ? MarketplaceOrder::ESCROW_REFUNDED : $lockedOrder->escrow_status,
                'refunded_at' => now(),
                'refund_amount' => $newRefundAmount,
                'refund_reason' => $reason,
            ]);

            // If order was cancelled/refunded before shipment, restore stock if not already restored
            if (in_array($lockedOrder->status, [MarketplaceOrder::STATUS_CANCELLED, MarketplaceOrder::STATUS_REFUNDED], true) && $lockedOrder->shipped_at === null) {
                $lockedProduct = MarketplaceProduct::withTrashed()->whereKey($lockedOrder->product_id)->lockForUpdate()->first();
                if ($lockedProduct && $lockedProduct->stock !== null) {
                    $lockedProduct->increment('stock', max(1, (int) $lockedOrder->quantity));
                    if (! $lockedProduct->trashed() && $lockedProduct->status === MarketplaceProduct::STATUS_SOLD && (int) $lockedProduct->fresh()->stock > 0) {
                        $lockedProduct->update(['status' => MarketplaceProduct::STATUS_ACTIVE]);
                    }
                }
            }

            return [
                'order' => $lockedOrder->fresh(['buyer', 'seller', 'product']),
                'transaction' => $transaction,
                'success' => true,
            ];
        });
    }
}
