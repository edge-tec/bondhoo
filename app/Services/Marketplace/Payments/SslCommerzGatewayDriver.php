<?php

namespace App\Services\Marketplace\Payments;

use App\Models\MarketplaceOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SslCommerzGatewayDriver extends AbstractPaymentGateway
{
    protected ?string $storeId;

    protected ?string $storePassword;

    protected bool $isSandbox;

    protected string $baseUrl;

    protected SandboxGatewayDriver $fallbackDriver;

    public function __construct()
    {
        $this->storeId = config('services.sslcommerz.store_id');
        $this->storePassword = config('services.sslcommerz.store_password');
        $this->isSandbox = (bool) config('services.sslcommerz.sandbox', true);
        $this->baseUrl = (string) config('services.sslcommerz.base_url', 'https://sandbox.sslcommerz.com');
        $this->fallbackDriver = new SandboxGatewayDriver('sslcommerz');
    }

    public function getIdentifier(): string
    {
        return 'sslcommerz';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->storeId) && ! empty($this->storePassword);
    }

    public function initiatePayment(MarketplaceOrder $order, array $options = []): array
    {
        if (! $this->isConfigured()) {
            return $this->fallbackDriver->initiatePayment($order, $options);
        }

        try {
            $callbackUrl = route('api.v2.marketplace.orders.verify-payment', ['id' => $order->id]);
            $tranId = 'SSL_'.$order->id.'_'.bin2hex(random_bytes(6));

            $buyer = $order->buyer;
            $cusName = $buyer?->name ?: 'Customer';
            $cusEmail = $buyer?->email ?: 'customer@bondhoo.com';
            $cusPhone = $buyer?->phone ?: '01700000000';

            $postData = [
                'store_id' => $this->storeId,
                'store_passwd' => $this->storePassword,
                'total_amount' => (string) number_format((float) $order->amount, 2, '.', ''),
                'currency' => $order->currency ?: 'BDT',
                'tran_id' => $tranId,
                'success_url' => $callbackUrl.'?gateway=sslcommerz&payment_status=COMPLETED',
                'fail_url' => $callbackUrl.'?gateway=sslcommerz&payment_status=FAILED',
                'cancel_url' => $callbackUrl.'?gateway=sslcommerz&payment_status=CANCELLED',
                'ipn_url' => $callbackUrl.'?gateway=sslcommerz&mode=ipn',
                'cus_name' => $cusName,
                'cus_email' => $cusEmail,
                'cus_add1' => 'Dhaka',
                'cus_city' => 'Dhaka',
                'cus_country' => 'Bangladesh',
                'cus_phone' => $cusPhone,
                'shipping_method' => 'NO',
                'product_name' => "Order #{$order->order_number}",
                'product_category' => 'General',
                'product_profile' => 'general',
            ];

            $response = Http::asForm()
                ->timeout(15)
                ->post("{$this->baseUrl}/gwprocess/v4/api.php", $postData);

            if ($response->successful() && $response->json('status') === 'SUCCESS' && $response->json('GatewayPageURL')) {
                return [
                    'order_id' => $order->id,
                    'gateway' => 'sslcommerz',
                    'transaction_id' => $tranId,
                    'session_key' => (string) $response->json('sessionkey'),
                    'amount' => (float) $order->amount,
                    'currency' => $order->currency ?: 'BDT',
                    'checkout_url' => (string) $response->json('GatewayPageURL'),
                    'expires_at' => now()->addMinutes(20)->toIso8601String(),
                ];
            }

            Log::error('SSLCommerz Session Init Error', ['body' => $response->body()]);

            return $this->fallbackDriver->initiatePayment($order, $options);
        } catch (\Throwable $e) {
            Log::error('SSLCommerz Exception: '.$e->getMessage());

            return $this->fallbackDriver->initiatePayment($order, $options);
        }
    }

    public function verifyPayment(MarketplaceOrder $order, array $payload): array
    {
        if (! $this->isConfigured()) {
            return $this->fallbackDriver->verifyPayment($order, $payload);
        }

        $valId = (string) ($payload['val_id'] ?? $payload['transaction_id'] ?? '');

        if ($valId === '') {
            throw new InvalidArgumentException('Missing SSLCommerz validation ID (val_id).');
        }

        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/validator/api/validationserverAPI.php", [
                'val_id' => $valId,
                'store_id' => $this->storeId,
                'store_passwd' => $this->storePassword,
                'v' => 1,
                'format' => 'json',
            ]);

            if ($response->successful()) {
                $status = strtoupper((string) $response->json('status'));
                $paidAmount = (float) $response->json('amount', 0);
                $currency = strtoupper((string) $response->json('currency', 'BDT'));
                $tranId = (string) $response->json('tran_id', $valId);

                if (in_array($status, ['VALID', 'VALIDATED'], true)) {
                    if (! $this->amountsMatch($order->amount, $paidAmount)) {
                        throw new InvalidArgumentException("Amount mismatch: expected {$order->amount}, received {$paidAmount}.");
                    }

                    return [
                        'order_id' => $order->id,
                        'gateway' => 'sslcommerz',
                        'transaction_id' => $tranId,
                        'bank_tran_id' => (string) $response->json('bank_tran_id'),
                        'card_type' => (string) $response->json('card_type'),
                        'status' => 'COMPLETED',
                        'amount' => $paidAmount,
                        'currency' => $currency,
                        'verified_at' => now()->toIso8601String(),
                        'raw_response' => $this->sanitizePayload($response->json()),
                    ];
                }

                return [
                    'order_id' => $order->id,
                    'gateway' => 'sslcommerz',
                    'transaction_id' => $tranId,
                    'status' => 'FAILED',
                    'amount' => $paidAmount,
                    'currency' => $currency,
                    'verified_at' => now()->toIso8601String(),
                    'raw_response' => $this->sanitizePayload($response->json()),
                ];
            }

            Log::error('SSLCommerz Validator API Error', ['body' => $response->body()]);

            return $this->fallbackDriver->verifyPayment($order, $payload);
        } catch (\Throwable $e) {
            Log::error('SSLCommerz Verify Exception: '.$e->getMessage());

            return $this->fallbackDriver->verifyPayment($order, $payload);
        }
    }

    public function processRefund(MarketplaceOrder $order, float $amount, string $reason): array
    {
        if (! $this->isConfigured()) {
            return $this->fallbackDriver->processRefund($order, $amount, $reason);
        }

        try {
            $bankTranId = $order->paymentTransaction?->transaction_reference ?? '';

            $response = Http::asForm()
                ->timeout(15)
                ->post("{$this->baseUrl}/validator/api/merchantTransIDvalidationAPI.php", [
                    'store_id' => $this->storeId,
                    'store_passwd' => $this->storePassword,
                    'bank_tran_id' => $bankTranId,
                    'refund_amount' => (string) number_format($amount, 2, '.', ''),
                    'refund_remarks' => $reason,
                    'v' => 1,
                    'format' => 'json',
                ]);

            if ($response->successful() && strtoupper((string) $response->json('status')) === 'SUCCESS') {
                return [
                    'status' => 'REFUNDED',
                    'refund_id' => (string) ($response->json('refund_ref_id') ?? 'REF_'.bin2hex(random_bytes(6))),
                    'amount' => $amount,
                    'currency' => $order->currency ?: 'BDT',
                    'refunded_at' => now()->toIso8601String(),
                    'raw_response' => $this->sanitizePayload($response->json()),
                ];
            }

            return $this->fallbackDriver->processRefund($order, $amount, $reason);
        } catch (\Throwable $e) {
            Log::error('SSLCommerz Refund Exception: '.$e->getMessage());

            return $this->fallbackDriver->processRefund($order, $amount, $reason);
        }
    }
}
