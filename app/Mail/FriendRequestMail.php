<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FriendRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public User $sender,
        public User $recipient,
        public string $requestUrl,
        public ?string $senderAvatarUrl = null
    ) {}

    public function envelope(): Envelope
    {
        $senderName = $this->sender->name ?: $this->sender->username;

        return new Envelope(
            subject: "{$senderName} আপনাকে Bondhoo-তে ফ্রেন্ড রিকোয়েস্ট পাঠিয়েছেন",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.friend-request',
            text: 'emails.plain.friend-request'
        );
    }
}
