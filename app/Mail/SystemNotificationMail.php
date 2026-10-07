<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * সাধারণ সিস্টেম ইমেইল নোটিফিকেশন মেইলেবল ক্লাস।
 */
class SystemNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $notificationSubject,
        public string $notificationMessage
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->notificationSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "<div style='font-family: Arial, sans-serif; padding: 20px; line-height: 1.6;'>"
                ."<h2 style='color: #4f46e5;'>Jugajug Platform</h2>"
                .'<p>'.nl2br(e($this->notificationMessage)).'</p>'
                ."<hr style='border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;'>"
                ."<small style='color: #6b7280;'>© ".date('Y').' Jugajug Social Network</small>'
                .'</div>'
        );
    }
}
