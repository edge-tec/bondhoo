<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspiciousLoginNotification extends Notification implements ShouldQueue
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
        $ip = $this->incidentDetails['ip'] ?? 'Unknown';
        $location = $this->incidentDetails['location'] ?? 'Unknown';
        $reason = $this->incidentDetails['reason'] ?? 'সন্দেহজনক কার্যকলাপ';

        return (new MailMessage)
            ->error()
            ->subject('⚠️ জরুরি সিকিউরিটি অ্যালার্ট: সন্দেহজনক লগইন প্রচেষ্টা!')
            ->greeting('জরুরি নিরাপত্তা সতর্কতা!')
            ->line('আপনার যুগাজুগ অ্যাকাউন্টে একটি অপ্রত্যাশিত বা সন্দেহজনক লগইন প্রচেষ্টা শনাক্ত হয়েছে।')
            ->line("**কারণ:** {$reason}")
            ->line("**আইপি:** {$ip}")
            ->line("**লোকেশন:** {$location}")
            ->line('আমরা আপনার অ্যাকাউন্টের সুরক্ষায় টু-ফ্যাক্টর অথেন্টিকেশন কোড দাবি করেছি অথবা সেশন সাময়িক স্থগিত করেছি।')
            ->action('অ্যাকাউন্ট লক করুন ও পাসওয়ার্ড বদলান', url('/forgot-password'))
            ->line('যদি এই প্রচেষ্টাটি আপনার না হয়, তবে অবিলম্বে আমাদের সাথে যোগাযোগ করুন।');
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
