<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
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
        $from = $this->fromAddress ? new Address($this->fromAddress, $this->fromName ?: 'Bondhoo') : null;

        return new Envelope(
            from: $from,
            replyTo: $from ? [$from] : [],
            subject: 'Bondhoo Enterprise SMTP কনফিগারেশন টেস্ট',
        );
    }

    public function headers(): Headers
    {
        $domain = ($this->fromAddress && str_contains($this->fromAddress, '@'))
            ? substr(strrchr($this->fromAddress, '@'), 1)
            : 'bondhoo.com';

        return new Headers(
            messageId: bin2hex(random_bytes(16)).'@'.$domain,
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
