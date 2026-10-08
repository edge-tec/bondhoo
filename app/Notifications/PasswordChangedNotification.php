<?php

namespace App\Notifications;

use App\Services\Email\SmtpConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
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
