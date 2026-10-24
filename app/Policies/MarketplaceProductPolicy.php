<?php

namespace App\Policies;

use App\Models\MarketplaceProduct;
use App\Models\User;

class MarketplaceProductPolicy
{
    /**
     * Determine whether the user can view any products.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the specific product.
     */
    public function view(?User $user, MarketplaceProduct $product): bool
    {
        if ($product->status === MarketplaceProduct::STATUS_ACTIVE) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $user->id === $product->seller_id || (bool) ($user->is_admin ?? false);
    }

    /**
     * Determine whether the user can create a product listing.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the product listing.
     */
    public function update(User $user, MarketplaceProduct $product): bool
    {
        return $user->id === $product->seller_id || (bool) ($user->is_admin ?? false);
    }

    /**
     * Determine whether the user can delete the product listing.
     */
    public function delete(User $user, MarketplaceProduct $product): bool
    {
        return $user->id === $product->seller_id || (bool) ($user->is_admin ?? false);
    }

    /**
     * Determine whether the user can change status of the product.
     */
    public function changeStatus(User $user, MarketplaceProduct $product): bool
    {
        return $user->id === $product->seller_id || (bool) ($user->is_admin ?? false);
    }
}
