<?php

namespace App\Jobs;

use App\Mail\SystemNotificationMail;
use App\Services\Contracts\QueueServiceInterface;
use App\Services\Email\SmtpConfigService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * এই জবটি ব্যাকগ্রাউন্ডে ইউজারদের ইমেইল নোটিফিকেশন পাঠায়।
 * এটি সিস্টেমের 'default' কিউতে এক্সিকিউট হয়।
 */
class SendEmailNotificationJob implements ShouldQueue
{
    use Queueable;

    // যদি কোনো কারণে ইমেইল সার্ভার রেসপন্স না দেয়, তাহলে সর্বোচ্চ ৩ বার চেষ্টা করবে।
    public int $tries = 3;

    // ব্যর্থ হলে যথাক্রমে ১০, ৩০ ও ৬০ সেকেন্ড বিরতি দিয়ে পুনরায় চেষ্টা করবে।
    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $toEmail,
        public string $subject,
        public string $messageBody
    ) {
        $this->onQueue(QueueServiceInterface::QUEUE_DEFAULT);
    }

    public function handle(): void
    {
        try {
            app(SmtpConfigService::class)->applyToMailer();

            Mail::to($this->toEmail)->send(new SystemNotificationMail(
                notificationSubject: $this->subject,
                notificationMessage: $this->messageBody
            ));

            Log::info("Email successfully sent to: {$this->toEmail}");
        } catch (Exception $e) {
            Log::error("Failed to send email to {$this->toEmail}: ".$e->getMessage());
            throw $e; // রিট্রাই ট্রিগার করার জন্য এক্সেপশন থ্রো করা হচ্ছে
        }
    }
}
