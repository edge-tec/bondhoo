<?php

namespace App\Notifications;

use App\Models\EmailLog;
use App\Services\Email\SmtpConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $otp,
        public ?string $resetUrl = null,
        public array $meta = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ip = $this->meta['ip'] ?? request()->ip();

        // Apply active database SMTP configuration
        app(SmtpConfigService::class)->applyToMailer();

        // Record in email_logs
        try {
            EmailLog::create([
                'user_id' => $notifiable->id ?? null,
                'recipient' => $notifiable->email ?? 'unknown',
                'email_type' => 'password_reset',
                'subject' => 'আপনার Bondhoo অ্যাকাউন্টের পাসওয়ার্ড রিসেট লিংক',
                'mail_class' => self::class,
                'ip_address' => $ip,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Ignore log persistence failure in tests
        }

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
