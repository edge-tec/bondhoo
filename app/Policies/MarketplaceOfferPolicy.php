<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MarketplaceOffer;
use App\Models\User;

class MarketplaceOfferPolicy
{
    /**
     * Determine whether the user can view the offer.
     */
    public function view(User $user, MarketplaceOffer $offer): bool
    {
        return (int) $offer->buyer_id === (int) $user->id
            || (int) $offer->seller_id === (int) $user->id
            || $user->isAdmin();
    }

    /**
     * Determine whether the user can accept the offer.
     * - Pending offer: Seller (or Admin) can accept.
     * - Countered offer: Buyer (accepting seller's counteroffer) or Seller (reverting to buyer's original offer) or Admin can accept.
     */
    public function accept(User $user, MarketplaceOffer $offer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($offer->status === MarketplaceOffer::STATUS_PENDING) {
            return (int) $offer->seller_id === (int) $user->id;
        }

        if ($offer->status === MarketplaceOffer::STATUS_COUNTERED) {
            return (int) $offer->buyer_id === (int) $user->id
                || (int) $offer->seller_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can reject the offer.
     */
    public function reject(User $user, MarketplaceOffer $offer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($offer->status === MarketplaceOffer::STATUS_PENDING) {
            return (int) $offer->seller_id === (int) $user->id;
        }

        if ($offer->status === MarketplaceOffer::STATUS_COUNTERED) {
            return (int) $offer->buyer_id === (int) $user->id
                || (int) $offer->seller_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can send a counteroffer.
     */
    public function counter(User $user, MarketplaceOffer $offer): bool
    {
        return (int) $offer->seller_id === (int) $user->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can cancel their offer.
     */
    public function cancel(User $user, MarketplaceOffer $offer): bool
    {
        return (int) $offer->buyer_id === (int) $user->id || $user->isAdmin();
    }
}
