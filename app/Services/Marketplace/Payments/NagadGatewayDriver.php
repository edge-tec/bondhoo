<?php

namespace App\Services\Marketplace\Payments;

use App\Models\MarketplaceOrder;

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
        return $this->fallbackDriver->initiatePayment($order, $options);
    }

    public function verifyPayment(MarketplaceOrder $order, array $payload): array
    {
        return $this->fallbackDriver->verifyPayment($order, $payload);
    }

    public function processRefund(MarketplaceOrder $order, float $amount, string $reason): array
    {
        return $this->fallbackDriver->processRefund($order, $amount, $reason);
    }
}
