<?php

namespace App\Notifications;

use App\Models\MarketplaceOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MarketplaceOfferNotification extends Notification
{
    use Queueable;

    public function __construct(
        public MarketplaceOffer $offer,
        public string $actionType // created, accepted, rejected, countered, cancelled
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $product = $this->offer->product;
        $title = match ($this->actionType) {
            'created' => 'নতুন অফার পেয়েছেন (New Offer Received)',
            'accepted' => 'আপনার অফার গ্রহণ করা হয়েছে (Offer Accepted)',
            'rejected' => 'আপনার অফার প্রত্যাখ্যাত হয়েছে (Offer Declined)',
            'countered' => 'কাউন্টার অফার পেয়েছেন (Counteroffer Received)',
            'cancelled' => 'অফার বাতিল করা হয়েছে (Offer Cancelled)',
            default => 'Marketplace Notification',
        };

        $body = match ($this->actionType) {
            'created' => "{$this->offer->buyer->name} '{$product?->title}'-এর জন্য ৳{$this->offer->offer_amount} অফার করেছেন।",
            'accepted' => "বিক্রেতা '{$product?->title}'-এর জন্য আপনার ৳{$this->offer->offer_amount} অফার গ্রহণ করেছেন।",
            'rejected' => "বিক্রেতা '{$product?->title}'-এর জন্য আপনার অফার গ্রহণ করেননি।",
            'countered' => "বিক্রেতা '{$product?->title}'-এর জন্য ৳{$this->offer->counter_amount} কাউন্টার অফার দিয়েছেন।",
            'cancelled' => "ক্রেতা অফারটি প্রত্যাহার করেছেন।",
            default => 'You have an update on your marketplace offer.',
        };

        return [
            'type' => 'marketplace_offer',
            'title' => $title,
            'body' => $body,
            'offer_id' => $this->offer->id,
            'product_id' => $this->offer->product_id,
            'action_type' => $this->actionType,
            'amount' => (float) $this->offer->offer_amount,
            'counter_amount' => $this->offer->counter_amount !== null ? (float) $this->offer->counter_amount : null,
            'created_at' => now()->toIso8601String(),
        ];
    }
}

