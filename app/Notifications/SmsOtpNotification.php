<?php

namespace App\Notifications;

use App\Services\Sms\SmsGatewayManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SmsOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $otp,
        public string $purpose = 'যাচাইকরণ'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Directly send SMS using SmsGatewayManager.
     */
    public function sendSms(string $phone, SmsGatewayManager $smsManager): array
    {
        $message = "আপনার যুগাজুগ {$this->purpose} ওটিপি কোড হলো: {$this->otp}। মেয়াদ ১০ মিনিট। কাউকে বলবেন না।";

        return $smsManager->send($phone, $message);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'এসএমএস ওটিপি প্রেরিত হয়েছে',
            'otp' => $this->otp,
            'purpose' => $this->purpose,
            'type' => 'sms_otp',
        ];
    }
}
