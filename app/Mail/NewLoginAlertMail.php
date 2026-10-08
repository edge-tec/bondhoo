<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewLoginAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public User $user,
        public string $device,
        public string $ip,
        public ?string $location = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'নিরাপত্তা সতর্কতা: নতুন ডিভাইস থেকে Bondhoo-তে লগইন শনাক্ত হয়েছে',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-login-alert',
            text: 'emails.plain.new-login-alert'
        );
    }
}
