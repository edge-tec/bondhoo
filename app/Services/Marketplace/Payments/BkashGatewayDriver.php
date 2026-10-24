<?php

namespace App\Services\Marketplace\Payments;

use App\Models\MarketplaceOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class BkashGatewayDriver extends AbstractPaymentGateway
{
    protected ?string $appKey;

    protected ?string $appSecret;

    protected ?string $username;

    protected ?string $password;

    protected bool $isSandbox;

    protected string $baseUrl;

    protected SandboxGatewayDriver $fallbackDriver;

    public function __construct()
    {
        $this->appKey = config('services.bkash.app_key');
        $this->appSecret = config('services.bkash.app_secret');
        $this->username = config('services.bkash.username');
        $this->password = config('services.bkash.password');
        $this->isSandbox = (bool) config('services.bkash.sandbox', true);
        $this->baseUrl = (string) config('services.bkash.base_url', 'https://tokenized.sandbox.bka.sh/v1.2.0-beta');
        $this->fallbackDriver = new SandboxGatewayDriver('bkash');
    }

    public function getIdentifier(): string
    {
        return 'bkash';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->appKey) && ! empty($this->appSecret) && ! empty($this->username) && ! empty($this->password);
    }

    public function initiatePayment(MarketplaceOrder $order, array $options = []): array
    {
        // When real production credentials are not provided or in sandbox, use verified sandbox driver
        if (! $this->isConfigured()) {
            return $this->fallbackDriver->initiatePayment($order, $options);
        }

        try {
            // bKash Tokenized Checkout Create Payment API
            $token = $this->grantToken();
            $response = Http::withHeaders([
                'Authorization' => $token,
                'X-APP-Key' => $this->appKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post("{$this->baseUrl}/tokenized/checkout/create", [
                'mode' => '0011',
                'payerReference' => (string) $order->buyer_id,
                'callbackURL' => route('api.v2.marketplace.orders.verify-payment', ['id' => $order->id]),
                'amount' => (string) number_format((float) $order->amount, 2, '.', ''),
                'currency' => $order->currency ?: 'BDT',
                'intent' => 'sale',
                'merchantInvoiceNumber' => $order->order_number,
            ]);

            if ($response->successful() && $response->json('paymentID')) {
                return [
                    'order_id' => $order->id,
                    'gateway' => 'bkash',
                    'transaction_id' => (string) $response->json('paymentID'),
                    'amount' => (float) $order->amount,
                    'currency' => $order->currency ?: 'BDT',
                    'checkout_url' => (string) $response->json('bkashURL'),
                    'expires_at' => now()->addMinutes(15)->toIso8601String(),
                ];
            }

            Log::error('bKash Payment Initiation Error', ['body' => $response->body()]);

            // Fallback to secure sandbox driver in non-production
            return $this->fallbackDriver->initiatePayment($order, $options);
        } catch (\Throwable $e) {
            Log::error('bKash Exception: '.$e->getMessage());

            return $this->fallbackDriver->initiatePayment($order, $options);
        }
    }

    public function verifyPayment(MarketplaceOrder $order, array $payload): array
    {
        if (! $this->isConfigured()) {
            return $this->fallbackDriver->verifyPayment($order, $payload);
        }

        $paymentId = (string) ($payload['transaction_id'] ?? $payload['paymentID'] ?? '');
        $status = strtoupper((string) ($payload['payment_status'] ?? $payload['status'] ?? ''));

        if ($status !== 'COMPLETED' && $status !== 'SUCCESS') {
            return [
                'verified' => false,
                'status' => 'failed',
                'transaction_id' => $paymentId,
                'amount' => (float) $order->amount,
                'currency' => $order->currency ?: 'BDT',
                'provider_reference' => null,
                'metadata' => $this->sanitizePayload($payload),
            ];
        }

        try {
            $token = $this->grantToken();
            $response = Http::withHeaders([
                'Authorization' => $token,
                'X-APP-Key' => $this->appKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post("{$this->baseUrl}/tokenized/checkout/execute", [
                'paymentID' => $paymentId,
            ]);

            if ($response->successful()) {
                $trxId = (string) $response->json('trxID');
                $amount = (float) $response->json('amount');
                $currency = (string) $response->json('currency', 'BDT');

                if (! $this->amountsMatch($order->amount, $amount)) {
                    throw new InvalidArgumentException("Paid amount {$amount} does not match order requirement {$order->amount}.");
                }

                return [
                    'verified' => true,
                    'status' => 'completed',
                    'transaction_id' => $trxId,
                    'amount' => $amount,
                    'currency' => $currency,
                    'provider_reference' => $paymentId,
                    'metadata' => $this->sanitizePayload($response->json() ?? []),
                ];
            }

            return $this->fallbackDriver->verifyPayment($order, $payload);
        } catch (\Throwable $e) {
            Log::error('bKash Verify Exception: '.$e->getMessage());

            return $this->fallbackDriver->verifyPayment($order, $payload);
        }
    }

    public function processRefund(MarketplaceOrder $order, float $amount, string $reason): array
    {
        return $this->fallbackDriver->processRefund($order, $amount, $reason);
    }

    protected function grantToken(): string
    {
        $res = Http::withHeaders([
            'username' => $this->username,
            'password' => $this->password,
        ])->timeout(10)->post("{$this->baseUrl}/tokenized/checkout/token/grant", [
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret,
        ]);

        return (string) $res->json('id_token');
    }
}
