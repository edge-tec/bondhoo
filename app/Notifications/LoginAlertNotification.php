<?php

namespace App\Notifications;

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
        $device = $this->deviceInfo['browser'] ?? 'Unknown Browser';
        $os = $this->deviceInfo['os'] ?? 'Unknown OS';
        $ip = $this->deviceInfo['ip'] ?? 'Unknown IP';
        $country = $this->deviceInfo['country'] ?? 'Unknown Location';
        $time = now()->toDayDateTimeString();

        return (new MailMessage)
            ->subject('নতুন ডিভাইস থেকে যুগাজুগ লগইন সতর্কতা')
            ->greeting('নিরাপত্তা সতর্কতা:')
            ->line('একটি নতুন ডিভাইস থেকে আপনার অ্যাকাউন্টে সফলভাবে লগইন করা হয়েছে।')
            ->line("**ডিভাইস ও ব্রাউজার:** {$device} on {$os}")
            ->line("**আইপি এড্রেস:** {$ip}")
            ->line("**স্থান:** {$country}")
            ->line("**সময়:** {$time}")
            ->line('এটি আপনি না হলে অবিলম্বে পাসওয়ার্ড পরিবর্তন করুন এবং সব সেশন থেকে লগআউট করুন।')
            ->action('সেশন ম্যানেজমেন্ট দেখুন', url('/dashboard'))
            ->salutation('যুগাজুগ সাইবার সিকিউরিটি সেল');
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
