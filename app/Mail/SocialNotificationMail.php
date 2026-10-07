<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SocialNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public User $recipient,
        public string $notificationTitle,
        public string $notificationMessage,
        public string $actionUrl,
        public string $actionButtonText = 'বিস্তারিত দেখুন',
        public ?string $actorName = null,
        public ?string $actorAvatarUrl = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->notificationTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.social-notification',
            text: 'emails.plain.social-notification'
        );
    }
}
