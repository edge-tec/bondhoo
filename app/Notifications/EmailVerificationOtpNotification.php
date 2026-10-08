<?php

namespace App\Notifications;

use App\Services\Email\SmtpConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $otp,
        public string $purpose = 'ইমেইল যাচাইকরণ'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Apply active database SMTP configuration
        app(SmtpConfigService::class)->applyToMailer();

        return (new MailMessage)
            ->subject("আপনার যুগাজুগ ওটিপি (OTP) কোড: {$this->otp}")
            ->greeting('আসসালামু আলাইকুম,')
            ->line("আপনার {$this->purpose}-এর জন্য নিচের ৬ সংখ্যার গোপন ওটিপি (OTP) কোডটি ব্যবহার করুন:")
            ->line("**{$this->otp}**")
            ->line('এই কোডটির মেয়াদ আগামী ১০ মিনিট পর্যন্ত কার্যকর থাকবে।')
            ->line('নিরাপত্তার স্বার্থে এই কোডটি কারো সাথে শেয়ার করবেন না।')
            ->salutation('ধন্যবাদান্তে, যুগাজুগ সিকিউরিটি টিম');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "ওটিপি (OTP) প্রেরিত হয়েছে: {$this->purpose}",
            'otp' => $this->otp,
            'purpose' => $this->purpose,
            'type' => 'otp_verification',
        ];
    }
}
