<?php

namespace App\Services\Marketplace\Payments;

use App\Models\MarketplaceOrder;
use InvalidArgumentException;

class SandboxGatewayDriver extends AbstractPaymentGateway
{
    protected string $gatewayName;

    protected string $secretKey;

    public function __construct(string $gatewayName = 'sandbox', ?string $secretKey = null)
    {
        $this->gatewayName = $gatewayName;
        $this->secretKey = $secretKey ?: (string) config('services.marketplace_payments.webhook_secret', 'bondhoo_marketplace_secret_key_2026');
    }

    public function getIdentifier(): string
    {
        return $this->gatewayName;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function initiatePayment(MarketplaceOrder $order, array $options = []): array
    {
        $trxId = 'TRX_'.strtoupper($this->gatewayName).'_'.bin2hex(random_bytes(8));
        $timestamp = time();

        $signaturePayload = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'trx_id' => $trxId,
            'amount' => (string) number_format((float) $order->amount, 2, '.', ''),
            'currency' => $order->currency ?: 'BDT',
        ];
        $signature = $this->generateSignature($signaturePayload, $this->secretKey);

        $checkoutUrl = match ($this->gatewayName) {
            'bkash' => "https://checkout.sandbox.bka.sh/v1.2.0-beta/checkout?trx={$trxId}&amount={$order->amount}&order={$order->order_number}&sig={$signature}",
            'nagad' => "https://api-sandbox.nagad.com.bd/checkout?trx={$trxId}&amount={$order->amount}&order={$order->order_number}&sig={$signature}",
            'sslcommerz' => "https://sandbox.sslcommerz.com/gwprocess/v4/api.php?trx={$trxId}&amount={$order->amount}&order={$order->order_number}&sig={$signature}",
            default => "/marketplace/orders/{$order->id}/checkout-view?trx={$trxId}&amount={$order->amount}&sig={$signature}",
        };

        return [
            'order_id' => $order->id,
            'gateway' => $this->gatewayName,
            'transaction_id' => $trxId,
            'amount' => (float) $order->amount,
            'currency' => $order->currency ?: 'BDT',
            'checkout_url' => $checkoutUrl,
            'signature' => $signature,
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
        ];
    }

    public function verifyPayment(MarketplaceOrder $order, array $payload): array
    {
        $status = strtoupper((string) ($payload['payment_status'] ?? $payload['status'] ?? ''));
        $trxId = (string) ($payload['transaction_id'] ?? $payload['trx_id'] ?? '');

        if ($trxId === '') {
            throw new InvalidArgumentException('Missing transaction ID.');
        }

        if (isset($payload['amount'])) {
            $paidAmount = (float) $payload['amount'];
            if (! $this->amountsMatch($order->amount, $paidAmount)) {
                throw new InvalidArgumentException("Amount mismatch. Order requires {$order->amount}, received {$paidAmount}.");
            }
        }

        if (isset($payload['currency']) && ! empty($order->currency)) {
            if (strtoupper((string) $payload['currency']) !== strtoupper((string) $order->currency)) {
                throw new InvalidArgumentException("Currency mismatch: expected {$order->currency}, received {$payload['currency']}.");
            }
        }

        // Validate signature if provided
        if (isset($payload['signature']) && ! empty($payload['signature'])) {
            $signatureCheckData = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'trx_id' => $trxId,
                'amount' => (string) number_format((float) $order->amount, 2, '.', ''),
                'currency' => $order->currency ?: 'BDT',
            ];
            if (isset($payload['timestamp'])) {
                $signatureCheckData['timestamp'] = (int) $payload['timestamp'];
            }
            $expectedSignature = $this->generateSignature($signatureCheckData, $this->secretKey);
            // Verify HMAC timing attack safe
            if (! hash_equals($expectedSignature, (string) $payload['signature']) && ! hash_equals($this->generateSignature(['trx_id' => $trxId], $this->secretKey), (string) $payload['signature'])) {
                // If signature was provided but invalid, reject callback
                throw new InvalidArgumentException('Invalid cryptographic transaction signature.');
            }
        }

        $isVerified = ($status === 'COMPLETED' || $status === 'SUCCESS' || $status === 'PAID');

        return [
            'verified' => $isVerified,
            'status' => $isVerified ? 'completed' : (in_array($status, ['FAILED', 'FAILURE']) ? 'failed' : 'cancelled'),
            'transaction_id' => $trxId,
            'amount' => (float) $order->amount,
            'currency' => $order->currency ?: 'BDT',
            'provider_reference' => 'SANDBOX_REF_'.strtoupper(bin2hex(random_bytes(6))),
            'metadata' => $this->sanitizePayload($payload),
        ];
    }

    public function processRefund(MarketplaceOrder $order, float $amount, string $reason): array
    {
        if ($amount <= 0 || $amount > (float) $order->amount) {
            throw new InvalidArgumentException('Invalid refund amount specified.');
        }

        $refundId = 'REF_'.strtoupper($this->gatewayName).'_'.bin2hex(random_bytes(6));

        return [
            'success' => true,
            'refund_id' => $refundId,
            'message' => "Sandbox refund of {$amount} {$order->currency} processed successfully.",
        ];
    }
}
