<?php

namespace App\Notifications;

use App\Models\EmailLog;
use App\Services\Email\SmtpConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginAlertNotification extends Notification implements ShouldQueue
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

        try {
            EmailLog::create([
                'user_id' => $notifiable->id ?? null,
                'recipient' => $notifiable->email,
                'email_type' => 'new_login',
                'subject' => 'Bondhoo — নতুন ডিভাইস থেকে লগইন সতর্কতা',
                'mail_class' => self::class,
                'ip_address' => $this->deviceInfo['ip'] ?? request()->ip(),
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Ignore logging error in test or disconnected environment
        }

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
