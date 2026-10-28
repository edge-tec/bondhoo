<?php

namespace App\Services\Marketplace\Payments;

use App\Models\MarketplaceOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class NagadGatewayDriver extends AbstractPaymentGateway
{
    protected ?string $merchantId;

    protected ?string $publicKey;

    protected ?string $privateKey;

    protected bool $isSandbox;

    protected string $baseUrl;

    protected SandboxGatewayDriver $fallbackDriver;

    public function __construct()
    {
        $this->merchantId = config('services.nagad.merchant_id');
        $this->publicKey = config('services.nagad.public_key');
        $this->privateKey = config('services.nagad.private_key');
        $this->isSandbox = (bool) config('services.nagad.sandbox', true);
        $this->baseUrl = (string) config('services.nagad.base_url', 'https://api-sandbox.nagad.com.bd');
        $this->fallbackDriver = new SandboxGatewayDriver('nagad');
    }

    public function getIdentifier(): string
    {
        return 'nagad';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->merchantId) && ! empty($this->publicKey) && ! empty($this->privateKey);
    }

    public function initiatePayment(MarketplaceOrder $order, array $options = []): array
    {
        if (! $this->isConfigured()) {
            return $this->fallbackDriver->initiatePayment($order, $options);
        }

        try {
            $dateTime = now()->format('YmdHis');
            $orderId = 'NAGAD_'.$order->id.'_'.bin2hex(random_bytes(6));

            $sensitiveData = [
                'merchantId' => $this->merchantId,
                'datetime' => $dateTime,
                'orderId' => $orderId,
                'challenge' => bin2hex(random_bytes(16)),
            ];

            $response = Http::withHeaders([
                'X-KM-Api-Version' => 'v-0.2.0',
                'X-KM-IP-V4' => request()->ip() ?: '127.0.0.1',
                'X-KM-Client-Type' => 'PC_WEB',
            ])->timeout(15)->post("{$this->baseUrl}/api/dfs/check-out/initialize/{$this->merchantId}/{$orderId}", [
                'dateTime' => $dateTime,
                'sensitiveData' => json_encode($sensitiveData),
                'signature' => hash_hmac('sha256', json_encode($sensitiveData), (string) $this->privateKey),
            ]);

            if ($response->successful() && $response->json('callBackUrl')) {
                return [
                    'order_id' => $order->id,
                    'gateway' => 'nagad',
                    'transaction_id' => $orderId,
                    'amount' => (float) $order->amount,
                    'currency' => $order->currency ?: 'BDT',
                    'checkout_url' => (string) $response->json('callBackUrl'),
                    'expires_at' => now()->addMinutes(20)->toIso8601String(),
                ];
            }

            Log::error('Nagad Init Error', ['body' => $response->body()]);

            return $this->fallbackDriver->initiatePayment($order, $options);
        } catch (\Throwable $e) {
            Log::error('Nagad Exception: '.$e->getMessage());

            return $this->fallbackDriver->initiatePayment($order, $options);
        }
    }

    public function verifyPayment(MarketplaceOrder $order, array $payload): array
    {
        if (! $this->isConfigured()) {
            return $this->fallbackDriver->verifyPayment($order, $payload);
        }

        $paymentRefId = (string) ($payload['payment_ref_id'] ?? $payload['transaction_id'] ?? '');

        if ($paymentRefId === '') {
            throw new InvalidArgumentException('Missing Nagad payment reference ID.');
        }

        try {
            $response = Http::withHeaders([
                'X-KM-Api-Version' => 'v-0.2.0',
            ])->timeout(15)->get("{$this->baseUrl}/api/dfs/verify/payment/{$paymentRefId}");

            if ($response->successful()) {
                $status = strtoupper((string) $response->json('status'));
                $paidAmount = (float) $response->json('amount', 0);

                if (in_array($status, ['SUCCESS', 'COMPLETED', 'PAID'], true)) {
                    if (! $this->amountsMatch($order->amount, $paidAmount)) {
                        throw new InvalidArgumentException("Amount mismatch: expected {$order->amount}, received {$paidAmount}.");
                    }

                    return [
                        'order_id' => $order->id,
                        'gateway' => 'nagad',
                        'transaction_id' => $paymentRefId,
                        'issuer_payment_ref' => (string) $response->json('issuerPaymentRefNo'),
                        'status' => 'COMPLETED',
                        'amount' => $paidAmount,
                        'currency' => 'BDT',
                        'verified_at' => now()->toIso8601String(),
                        'raw_response' => $this->sanitizePayload($response->json()),
                    ];
                }

                return [
                    'order_id' => $order->id,
                    'gateway' => 'nagad',
                    'transaction_id' => $paymentRefId,
                    'status' => 'FAILED',
                    'amount' => $paidAmount,
                    'currency' => 'BDT',
                    'verified_at' => now()->toIso8601String(),
                    'raw_response' => $this->sanitizePayload($response->json()),
                ];
            }

            Log::error('Nagad Verification API Error', ['body' => $response->body()]);

            return $this->fallbackDriver->verifyPayment($order, $payload);
        } catch (\Throwable $e) {
            Log::error('Nagad Verify Exception: '.$e->getMessage());

            return $this->fallbackDriver->verifyPayment($order, $payload);
        }
    }

    public function processRefund(MarketplaceOrder $order, float $amount, string $reason): array
    {
        return $this->fallbackDriver->processRefund($order, $amount, $reason);
    }
}
