<?php

declare(strict_types=1);

namespace App\Events\Parental;

use App\Models\Parental\ChildSecurityAlert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class ChildSecurityAlertRaised
 *
 * Broadcast to family guardian channel when a child device triggers a security alert.
 */
class ChildSecurityAlertRaised implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChildSecurityAlert $alert
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("family.{$this->alert->family_id}.guardians"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'child.security.alert';
    }
}
