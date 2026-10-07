<?php

namespace App\Notifications;

use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class VerifyEmailNotification extends Notification implements ShouldQueue
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

        $name = $notifiable->name ?: $notifiable->username;

        // Auto-record in email_logs
        try {
            EmailLog::create([
                'user_id' => $notifiable->id,
                'recipient' => $notifiable->email,
                'subject' => 'আপনার যুগাজুগ অ্যাকাউন্ট ইমেইল নিশ্চিতকরণ',
                'message_id' => 'verify-'.uniqid(),
                'status' => 'sent',
            ]);
        } catch (\Throwable $e) {
            // Ignore logging error in test or disconnected environment
        }

        return (new MailMessage)
            ->subject('আপনার যুগাজুগ অ্যাকাউন্ট ইমেইল নিশ্চিতকরণ')
            ->greeting("আসসালামু আলাইকুম, {$name}!")
            ->line('যুগাজুগ প্ল্যাটফর্মে আপনাকে স্বাগতম। আপনার অ্যাকাউন্টের সম্পূর্ণ নিরাপত্তা ও কার্যকারিতা নিশ্চিত করতে অনুগ্রহ করে নিচের যেকোনো একটি পদ্ধতিতে আপনার ইমেইলটি ভেরিফাই করুন:')
            ->line(new HtmlString("
                <div style='text-align: center; margin: 25px 0;'>
                    <div style='display: inline-block; background: #e7f3ff; border: 2px dashed #1877f2; border-radius: 8px; padding: 12px 28px;'>
                        <div style='font-size: 12px; color: #65676b; font-weight: 600; text-transform: uppercase;'>আপনার ৬-সংখ্যার ওটিপি (OTP)</div>
                        <div style='font-size: 32px; font-weight: 800; color: #1877f2; letter-spacing: 6px; margin-top: 4px;'>{$this->otp}</div>
                    </div>
                </div>
            "))
            ->action('১-ক্লিক ইমেইল ভেরিফাই করুন', $verificationUrl)
            ->line('এই ভেরিফিকেশন লিঙ্ক ও ওটিপি কোডটির মেয়াদ আগামী **৬০ মিনিট** পর্যন্ত কার্যকর থাকবে।')
            ->line($this->ip ? "অনুরোধের আইপি: {$this->ip}" : '')
            ->line('আপনি যদি এই অনুরোধ না করে থাকেন, তবে অবিলম্বে আমাদের সাপোর্ট টিমের সাথে যোগাযোগ করুন।')
            ->salutation('ধন্যবাদান্তে, যুগাজুগ সিকিউরিটি টিম');
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
