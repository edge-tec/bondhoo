<?php

namespace App\Notifications;

use App\Services\Email\SmtpConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public array $deviceInfo
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
            ->subject('Bondhoo — নতুন ডিভাইস থেকে লগইন সতর্কতা')
            ->view('emails.new-login-alert', [
                'user' => $notifiable,
                'deviceInfo' => $this->deviceInfo,
            ])
            ->text('emails.plain.new-login-alert', [
                'user' => $notifiable,
                'deviceInfo' => $this->deviceInfo,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'নতুন ডিভাইস থেকে লগইন শনাক্ত হয়েছে',
            'device' => $this->deviceInfo,
            'type' => 'login_alert',
        ];
    }
}
