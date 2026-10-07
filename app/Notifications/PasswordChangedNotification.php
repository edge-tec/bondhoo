<?php

namespace App\Notifications;

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
        return (new MailMessage)
            ->subject('আপনার যুগাজুগ পাসওয়ার্ড সফলভাবে পরিবর্তিত হয়েছে')
            ->greeting('আসসালামু আলাইকুম,')
            ->line('আপনার যুগাজুগ অ্যাকাউন্টের পাসওয়ার্ড এইমাত্র সফলভাবে পরিবর্তন করা হয়েছে।')
            ->line('সময়: '.now()->toDayDateTimeString())
            ->line('আইপি: '.request()->ip())
            ->line('যদি এই পরিবর্তনটি আপনার অজান্তে হয়ে থাকে, তবে অবিলম্বে পাসওয়ার্ড রিসেট করুন।')
            ->action('পাসওয়ার্ড রিসেট', url('/forgot-password'))
            ->salutation('যুগাজুগ সিকিউরিটি সেল');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'পাসওয়ার্ড পরিবর্তন সম্পন্ন হয়েছে',
            'type' => 'password_changed',
        ];
    }
}
