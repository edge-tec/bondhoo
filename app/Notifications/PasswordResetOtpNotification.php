<?php

namespace App\Notifications;

use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $otp,
        public ?string $resetUrl = null,
        public array $meta = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $recipientName = $notifiable->name ?? 'সম্মানিত যুগাজুগ ব্যবহারকারী';
        $ip = $this->meta['ip'] ?? request()->ip();
        $device = $this->meta['device'] ?? request()->userAgent() ?? 'Unknown Device';
        $time = now()->timezone('Asia/Dhaka')->format('d M Y, h:i A');

        // Record in email_logs
        try {
            EmailLog::create([
                'user_id' => $notifiable->id ?? null,
                'recipient' => $notifiable->email ?? 'unknown',
                'subject' => 'যুগাজুগ — পাসওয়ার্ড রিসেট ওটিপি কোড',
                'mail_class' => self::class,
                'ip_address' => $ip,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Ignore log persistence failure in tests
        }

        $mail = (new MailMessage)
            ->subject('যুগাজুগ — পাসওয়ার্ড রিসেট ওটিপি কোড')
            ->greeting("আসসালামু আলাইকুম {$recipientName},")
            ->line('আপনার যুগাজুগ অ্যাকাউন্টের পাসওয়ার্ড রিসেট করার জন্য একটি অনুরোধ পাওয়া গেছে।')
            ->line('নিরাপত্তা যাচাইয়ের জন্য আপনার ওটিপি (OTP) কোডটি নিচে দেওয়া হলো:')
            ->line("# **{$this->otp}**")
            ->line('⏱️ এই ওটিপি কোড ও রিসেট লিঙ্কের মেয়াদ **১৫ মিনিট**।')
            ->line('📍 **অনুরোধের তথ্য:**')
            ->line("• আইপি ঠিকানা: {$ip}")
            ->line("• ডিভাইস / ব্রাউজার: {$device}")
            ->line("• সময়: {$time} (বাংলাদেশ সময়)");

        if ($this->resetUrl) {
            $mail->action('পাসওয়ার্ড রিসেট করুন', $this->resetUrl);
        }

        return $mail->line('⚠️ আপনি যদি এই অনুরোধটি না করে থাকেন, তবে এটি উপেক্ষা করুন অথবা অবিলম্বে আপনার অ্যাকাউন্ট সুরক্ষিত করুন।');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'পাসওয়ার্ড রিসেট কোড প্রেরিত হয়েছে',
            'otp' => $this->otp,
            'type' => 'password_reset',
            'ip' => $this->meta['ip'] ?? request()->ip(),
            'requested_at' => now()->toIso8601String(),
        ];
    }
}
