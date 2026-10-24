<?php

namespace App\Services\Marketplace\Payments;

use App\Models\MarketplaceOrder;

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
