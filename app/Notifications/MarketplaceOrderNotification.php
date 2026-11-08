<?php

namespace App\Notifications;

use App\Models\MarketplaceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MarketplaceOrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public MarketplaceOrder $order,
        public string $event // created, confirmed, shipped, delivered, completed, cancelled, disputed, refunded
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $order = $this->order;
        $title = match ($this->event) {
            'created' => 'নতুন অর্ডার পেয়েছেন! (New Order)',
            'confirmed' => 'অর্ডার নিশ্চিত হয়েছে (Order Confirmed)',
            'shipped' => 'পণ্য পাঠানো হয়েছে (Order Shipped)',
            'delivered' => 'পণ্য ডেলিভারি হয়েছে (Order Delivered)',
            'completed' => 'অর্ডার সম্পন্ন হয়েছে (Order Completed)',
            'cancelled' => 'অর্ডার বাতিল হয়েছে (Order Cancelled)',
            'disputed' => 'অর্ডারে বিরোধ তৈরি হয়েছে (Order Disputed)',
            'refunded' => 'টাকা রিফান্ড করা হয়েছে (Refund Processed)',
            default => 'Order Update',
        };

        return [
            'type' => 'marketplace_order',
            'title' => $title,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'product_id' => $order->product_id,
            'amount' => (float) $order->amount,
            'status' => $order->status,
            'escrow_status' => $order->escrow_status,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
