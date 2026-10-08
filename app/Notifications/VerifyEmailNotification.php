<?php

namespace App\Notifications;

use App\Services\Email\SmtpConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $otp,
        public string $token,
        public ?string $ip = null,
        public ?string $device = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Apply active database SMTP configuration
        app(SmtpConfigService::class)->applyToMailer();

        // Generate secure temporary signed URL valid for 60 minutes
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->id,
                'hash' => sha1($notifiable->getEmailForVerification()),
                'token' => $this->token,
            ]
        );

        return (new MailMessage)
            ->subject('আপনার Bondhoo অ্যাকাউন্ট ইমেইল যাচাইকরণ')
            ->view('emails.verify-email', [
                'user' => $notifiable,
                'otp' => $this->otp,
                'verificationUrl' => $verificationUrl,
                'expiresMinutes' => 60,
            ])
            ->text('emails.plain.verify-email', [
                'user' => $notifiable,
                'otp' => $this->otp,
                'verificationUrl' => $verificationUrl,
                'expiresMinutes' => 60,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'ইমেইল ভেরিফিকেশন কোড',
            'otp' => $this->otp,
            'token' => $this->token,
            'type' => 'email_verification',
        ];
    }
}
