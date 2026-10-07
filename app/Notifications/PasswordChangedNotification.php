<?php

namespace App\Notifications;

use App\Models\EmailLog;
use App\Services\Email\SmtpConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
                'email_type' => 'password_changed',
                'subject' => 'আপনার Bondhoo পাসওয়ার্ড সফলভাবে পরিবর্তিত হয়েছে',
                'mail_class' => self::class,
                'ip_address' => request()->ip(),
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Ignore logging error in test or disconnected environment
        }

        return (new MailMessage)
            ->subject('আপনার Bondhoo পাসওয়ার্ড সফলভাবে পরিবর্তিত হয়েছে')
            ->view('emails.password-changed', ['user' => $notifiable])
            ->text('emails.plain.password-changed', ['user' => $notifiable]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'পাসওয়ার্ড পরিবর্তন সম্পন্ন হয়েছে',
            'type' => 'password_changed',
        ];
    }
}
