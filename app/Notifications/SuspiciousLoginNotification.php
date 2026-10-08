<?php

namespace App\Notifications;

use App\Services\Email\SmtpConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspiciousLoginNotification extends Notification
{
    use Queueable;

    public function __construct(
        public array $incidentDetails
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Apply active database SMTP configuration
        app(SmtpConfigService::class)->applyToMailer();

        $ip = $this->incidentDetails['ip'] ?? 'Unknown';
        $location = $this->incidentDetails['location'] ?? 'Unknown';
        $reason = $this->incidentDetails['reason'] ?? 'সন্দেহজনক কার্যকলাপ';

        return (new MailMessage)
            ->subject('⚠️ জরুরি সিকিউরিটি অ্যালার্ট: সন্দেহজনক লগইন প্রচেষ্টা!')
            ->view('emails.security-alert', [
                'user' => $notifiable,
                'alertTitle' => 'জরুরি নিরাপত্তা সতর্কতা: সন্দেহজনক লগইন প্রচেষ্টা',
                'alertMessage' => "আপনার অ্যাকাউন্টে একটি অপ্রত্যাশিত বা সন্দেহজনক লগইন প্রচেষ্টা শনাক্ত হয়েছে। কারণ: {$reason}। সুরক্ষার স্বার্থে টু-ফ্যাক্টর অথেন্টিকেশন কোড দাবি করা হয়েছে।",
                'details' => [
                    'কারণ' => $reason,
                    'আইপি এড্রেস' => $ip,
                    'লোকেশন' => $location,
                    'সময়' => now()->timezone('Asia/Dhaka')->format('d M Y, h:i A'),
                ],
                'actionUrl' => url('/forgot-password'),
            ])
            ->text('emails.plain.security-alert', [
                'user' => $notifiable,
                'alertTitle' => 'জরুরি নিরাপত্তা সতর্কতা: সন্দেহজনক লগইন প্রচেষ্টা',
                'alertMessage' => "আপনার অ্যাকাউন্টে একটি অপ্রত্যাশিত বা সন্দেহজনক লগইন প্রচেষ্টা শনাক্ত হয়েছে। কারণ: {$reason}।",
                'details' => [
                    'কারণ' => $reason,
                    'আইপি এড্রেস' => $ip,
                    'লোকেশন' => $location,
                ],
                'actionUrl' => url('/forgot-password'),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'সন্দেহজনক লগইন প্রচেষ্টা প্রতিহত করা হয়েছে',
            'details' => $this->incidentDetails,
            'type' => 'suspicious_login',
        ];
    }
}
