<?php

declare(strict_types=1);

namespace App\Events\Parental;

use App\Models\Parental\ConsentRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class ConsentRequestCreated
 *
 * Broadcast to parents when a child requests extra screen-time or app unblock.
 */
class ConsentRequestCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ConsentRequest $consentRequest
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("family.{$this->consentRequest->family_id}.guardians"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'consent.request.created';
    }
}

