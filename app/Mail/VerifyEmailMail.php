<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public User $user,
        public string $otp,
        public string $verificationUrl,
        public int $expiresMinutes = 60
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'আপনার Bondhoo অ্যাকাউন্ট ইমেইল যাচাই করুন',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email',
            text: 'emails.plain.verify-email'
        );
    }
}
