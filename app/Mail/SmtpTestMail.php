<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SmtpTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $toEmail,
        public string $smtpHost,
        public int $smtpPort,
        public string $encryption,
        public ?string $fromAddress = null,
        public ?string $fromName = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bondhoo Enterprise SMTP কনফিগারেশন টেস্ট',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.smtp-test',
            text: 'emails.plain.smtp-test'
        );
    }
}
