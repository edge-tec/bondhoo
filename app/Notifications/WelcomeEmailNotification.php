<?php

namespace App\Notifications;

use App\Models\EmailLog;
use App\Services\Email\SmtpConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class WelcomeEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $name
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Apply active database SMTP configuration
        app(SmtpConfigService::class)->applyToMailer();

        try {
            EmailLog::create([
                'user_id' => $notifiable->id ?? null,
                'recipient' => $notifiable->email,
                'email_type' => 'welcome',
                'subject' => 'Bondhoo প্ল্যাটফর্মে আপনাকে স্বাগতম!',
                'mail_class' => self::class,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Ignore logging error in test or disconnected environment
        }

        return (new MailMessage)
            ->subject('Bondhoo প্ল্যাটফর্মে আপনাকে স্বাগতম!')
            ->view('emails.welcome', ['user' => $notifiable])
            ->text('emails.plain.welcome', ['user' => $notifiable]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'যুগাজুগ-এ স্বাগতম!',
            'message' => 'আপনার অ্যাকাউন্টটি সফলভাবে চালু হয়েছে। প্রোফাইল সম্পূর্ণ করুন।',
            'type' => 'welcome',
        ];
    }
}
