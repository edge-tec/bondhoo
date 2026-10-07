<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $meta
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $time = now()->toDayDateTimeString();
        $ip = $this->meta['ip'] ?? '127.0.0.1';
        $browser = $this->meta['browser'] ?? 'Unknown';
        $device = $this->meta['device'] ?? 'Admin Workstation';

        return (new MailMessage)
            ->subject('নিরাপত্তা সতর্কবার্তা: সুপার অ্যাডমিন লগইন সম্পন্ন হয়েছে')
            ->greeting("প্রিয় {$notifiable->name},")
            ->line('আপনার অ্যাডমিনিস্ট্রেটর অ্যাকাউন্টে এইমাত্র সফলভাবে লগইন করা হয়েছে।')
            ->line("**লগইন সময়:** {$time}")
            ->line("**আইপি ঠিকানা:** {$ip}")
            ->line("**ব্রাউজার/ডিভাইস:** {$browser} ({$device})")
            ->line('আপনি যদি এই লগইন না করে থাকেন, তবে অবিলম্বে আপনার পাসওয়ার্ড পরিবর্তন করুন এবং সিস্টেম সিকিউরিটি টিমকে অবহিত করুন।')
            ->action('সিকিউরিটি কনসোলে প্রবেশ করুন', url('/admin/auth-management'))
            ->salutation('যুগাজুগ প্ল্যাটফর্ম সিকিউরিটি টিম');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'admin_login_alert',
            'meta' => $this->meta,
            'time' => now()->toIso8601String(),
        ];
    }
}
