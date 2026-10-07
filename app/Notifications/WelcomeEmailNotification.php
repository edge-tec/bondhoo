<?php

namespace App\Notifications;

use App\Models\EmailLog;
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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        try {
            EmailLog::create([
                'user_id' => $notifiable->id ?? null,
                'recipient' => $notifiable->email,
                'subject' => 'যুগাজুগ প্ল্যাটফর্মে আপনাকে স্বাগতম!',
                'message_id' => 'welcome-'.uniqid(),
                'status' => 'sent',
            ]);
        } catch (Throwable $e) {
            // Ignore logging error in test or disconnected environment
        }

        return (new MailMessage)
            ->subject('যুগাজুগ প্ল্যাটফর্মে আপনাকে স্বাগতম!')
            ->greeting("প্রিয় {$this->name},")
            ->line('বাংলাদেশের নিজস্ব সোশ্যাল নেটওয়ার্ক "যুগাজুগ"-এ যোগদানের জন্য আপনাকে ধন্যবাদ।')
            ->line('আপনার বন্ধু ও পরিবারের সাথে যুক্ত থাকুন, নতুন কমিউনিটি আবিষ্কার করুন এবং নিরাপদভাবে ভাব বিনিময় করুন।')
            ->action('আপনার প্রোফাইল দেখুন', url('/dashboard'))
            ->line('যেকোনো প্রয়োজনে আমাদের সাপোর্ট টিম আপনার পাশে আছে।')
            ->salutation('আন্তরিক শুভেচ্ছাসহ, যুগাজুগ টিম');
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
