<?php

namespace App\Services\Marketplace\Payments;

use App\Models\MarketplaceOrder;
use App\Models\MarketplacePaymentTransaction;
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
        'sandbox' => SandboxGatewayDriver::class,
    ];

    /**
     * Resolve a gateway driver instance.
     */
    public function driver(?string $gateway = null): PaymentGatewayInterface
    {
        $name = strtolower(trim((string) $gateway));
        if (empty($name) || ! isset($this->drivers[$name])) {
            $name = 'sandbox';
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
        $gatewayName = strtolower((string) ($payload['gateway'] ?? $order->payment_method ?? 'bkash'));
        $driver = $this->driver($gatewayName);

        // Verification call via driver
        $verification = $driver->verifyPayment($order, $payload);
        $trxId = $verification['transaction_id'];

        return DB::transaction(function () use ($order, $gatewayName, $verification, $trxId, $ip) {
            $lockedOrder = MarketplaceOrder::where('id', $order->id)->lockForUpdate()->firstOrFail();

            $idempotencyKey = 'VERIFY_'.hash('sha256', "{$lockedOrder->id}_{$trxId}_{$verification['status']}");

            // Check if already completed to prevent duplicate ledger inserts
            $existingTrx = MarketplacePaymentTransaction::where('transaction_reference', $trxId)
                ->where('status', MarketplacePaymentTransaction::STATUS_SUCCESSFUL)
                ->first();

            if ($existingTrx && $lockedOrder->isPaid()) {
                // Idempotent return
                return [
                    'order' => $lockedOrder,
                    'verified' => true,
                    'transaction' => $existingTrx,
                ];
            }

            $trxStatus = match ($verification['status']) {
                'completed' => MarketplacePaymentTransaction::STATUS_SUCCESSFUL,
                'failed' => MarketplacePaymentTransaction::STATUS_FAILED,
                default => MarketplacePaymentTransaction::STATUS_REVERSED,
            };

            $transaction = MarketplacePaymentTransaction::where('transaction_reference', $trxId)->first();
            if ($transaction) {
                $transaction->update([
                    'status' => $trxStatus,
                    'gateway_payload' => $verification['metadata'],
                    'gateway_response' => [
                        'status' => $verification['status'],
                        'verified' => $verification['verified'],
                    ],
                    'ip_address' => $ip,
                    'verified_at' => $verification['verified'] ? now() : null,
                ]);
            } else {
                $transaction = MarketplacePaymentTransaction::create([
                    'order_id' => $lockedOrder->id,
                    'user_id' => $lockedOrder->buyer_id,
                    'transaction_reference' => $trxId,
                    'provider' => $gatewayName,
                    'type' => MarketplacePaymentTransaction::TYPE_PAYMENT,
                    'amount' => $verification['amount'],
                    'currency' => $verification['currency'],
                    'status' => $trxStatus,
                    'idempotency_key' => $idempotencyKey,
                    'gateway_payload' => $verification['metadata'],
                    'gateway_response' => [
                        'status' => $verification['status'],
                        'verified' => $verification['verified'],
                    ],
                    'ip_address' => $ip,
                    'verified_at' => $verification['verified'] ? now() : null,
                ]);
            }

            if ($verification['verified']) {
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
                'verified' => $verification['verified'],
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

            if (! in_array($lockedOrder->status, [
                MarketplaceOrder::STATUS_PROCESSING,
                MarketplaceOrder::STATUS_CONFIRMED,
                MarketplaceOrder::STATUS_SHIPPED,
                MarketplaceOrder::STATUS_DISPUTED,
                MarketplaceOrder::STATUS_REFUND_REQUESTED,
                MarketplaceOrder::STATUS_DELIVERED,
                MarketplaceOrder::STATUS_PENDING,
            ], true)) {
                throw new InvalidArgumentException("Order in status '{$lockedOrder->status}' is not eligible for refund.");
            }

            $driver = $this->driver($lockedOrder->payment_method);
            $gatewayResult = $driver->processRefund($lockedOrder, $amount, $reason);

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

            $lockedOrder->update([
                'status' => MarketplaceOrder::STATUS_REFUNDED,
                'payment_status' => MarketplaceOrder::PAYMENT_REFUNDED,
                'escrow_status' => MarketplaceOrder::ESCROW_REFUNDED,
                'refunded_at' => now(),
                'refund_amount' => $amount,
                'refund_reason' => $reason,
            ]);

            return [
                'order' => $lockedOrder->fresh(['buyer', 'seller', 'product']),
                'transaction' => $transaction,
                'success' => true,
            ];
        });
    }
}
