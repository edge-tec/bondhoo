<?php

namespace App\Notifications;

use App\Services\Email\SmtpConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $otp,
        public ?string $resetUrl = null,
        public array $meta = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Apply active database SMTP configuration
        app(SmtpConfigService::class)->applyToMailer();

        return (new MailMessage)
            ->subject('আপনার Bondhoo অ্যাকাউন্টের পাসওয়ার্ড রিসেট লিংক')
            ->view('emails.password-reset', [
                'user' => $notifiable,
                'resetUrl' => $this->resetUrl ?: url('/forgot-password'),
                'otp' => $this->otp,
                'expiresMinutes' => 15,
            ])
            ->text('emails.plain.password-reset', [
                'user' => $notifiable,
                'resetUrl' => $this->resetUrl ?: url('/forgot-password'),
                'otp' => $this->otp,
                'expiresMinutes' => 15,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'পাসওয়ার্ড রিসেট কোড প্রেরিত হয়েছে',
            'otp' => $this->otp,
            'type' => 'password_reset',
            'ip' => $this->meta['ip'] ?? request()->ip(),
            'requested_at' => now()->toIso8601String(),
        ];
    }
}
