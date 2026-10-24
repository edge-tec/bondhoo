<?php

namespace App\Policies;

use App\Models\MarketplaceOrder;
use App\Models\User;

class MarketplaceOrderPolicy
{
    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, MarketplaceOrder $order): bool
    {
        return $order->buyer_id === $user->id
            || $order->seller_id === $user->id
            || (bool) ($user->is_admin ?? false);
    }

    /**
     * Determine whether the user can update the order (e.g. status updates).
     */
    public function update(User $user, MarketplaceOrder $order): bool
    {
        return $order->seller_id === $user->id || (bool) ($user->is_admin ?? false);
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(User $user, MarketplaceOrder $order): bool
    {
        return $order->canBeCancelledBy($user);
    }

    /**
     * Determine whether the user can fulfill / ship the order.
     */
    public function ship(User $user, MarketplaceOrder $order): bool
    {
        return $order->canBeShippedBy($user);
    }

    /**
     * Determine whether the user can confirm delivery of the order.
     */
    public function confirmDelivery(User $user, MarketplaceOrder $order): bool
    {
        return ($order->buyer_id === $user->id && in_array($order->status, [MarketplaceOrder::STATUS_SHIPPED, MarketplaceOrder::STATUS_PROCESSING]))
            || (bool) ($user->is_admin ?? false);
    }

    /**
     * Determine whether the user can release payment / escrow to seller.
     */
    public function releaseEscrow(User $user, MarketplaceOrder $order): bool
    {
        return $order->canReleaseEscrow($user);
    }

    /**
     * Determine whether the user can raise a dispute on the order.
     */
    public function dispute(User $user, MarketplaceOrder $order): bool
    {
        return $order->canBeDisputedBy($user);
    }

    /**
     * Determine whether the user can review the order.
     */
    public function review(User $user, MarketplaceOrder $order): bool
    {
        return $order->canBeReviewedBy($user);
    }

    /**
     * Determine whether the user can process an admin refund.
     */
    public function refund(User $user, MarketplaceOrder $order): bool
    {
        return (bool) ($user->is_admin ?? false);
    }
}
