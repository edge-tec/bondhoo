<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SecurityAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public User $user,
        public string $actionTitle,
        public string $actionDescription,
        public ?string $ip = null,
        public ?string $device = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'নিরাপত্তা বিজ্ঞপ্তি: '.$this->actionTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.security-alert',
            text: 'emails.plain.security-alert'
        );
    }
}
