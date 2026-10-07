<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FriendRequestAcceptedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public User $friend;

    public User $recipient;

    public string $profileUrl;

    public ?string $friendAvatarUrl;

    public function __construct(
        ?User $friend = null,
        ?User $recipient = null,
        string $profileUrl = '',
        ?string $friendAvatarUrl = null,
        ?User $accepter = null,
        ?string $accepterAvatarUrl = null
    ) {
        $this->friend = $friend ?? $accepter ?? new User;
        $this->recipient = $recipient ?? new User;
        $this->profileUrl = $profileUrl;
        $this->friendAvatarUrl = $friendAvatarUrl ?? $accepterAvatarUrl;
    }

    public function envelope(): Envelope
    {
        $friendName = $this->friend->name ?: $this->friend->username;

        return new Envelope(
            subject: "{$friendName} আপনার ফ্রেন্ড রিকোয়েস্ট গ্রহণ করেছেন",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.friend-request-accepted',
            text: 'emails.plain.friend-request-accepted'
        );
    }
}
