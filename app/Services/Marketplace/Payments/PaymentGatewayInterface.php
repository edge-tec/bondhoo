<?php

namespace App\Services\Marketplace\Payments;

use App\Models\MarketplaceOrder;

interface PaymentGatewayInterface
{
    /**
     * Get gateway identifier (e.g. bkash, nagad, sslcommerz, sandbox).
     */
    public function getIdentifier(): string;

    /**
     * Check whether real credentials are configured for this gateway.
     */
    public function isConfigured(): bool;

    /**
     * Initiate payment session for an order.
     *
     * @param  array<string, mixed>  $options
     * @return array{gateway: string, transaction_id: string, checkout_url: string, amount: float, currency: string, expires_at: string}
     */
    public function initiatePayment(MarketplaceOrder $order, array $options = []): array;

    /**
     * Verify payment status and payload authenticity.
     *
     * @param  array<string, mixed>  $payload
     * @return array{verified: bool, status: string, transaction_id: string, amount: float, currency: string, provider_reference: ?string, metadata: array}
     */
    public function verifyPayment(MarketplaceOrder $order, array $payload): array;

    /**
     * Process refund via gateway.
     *
     * @return array{success: bool, refund_id: ?string, message: string}
     */
    public function processRefund(MarketplaceOrder $order, float $amount, string $reason): array;
}
