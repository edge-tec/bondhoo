<?php

declare(strict_types=1);

namespace App\Events\Parental;

use App\Models\Parental\RemoteCommand;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class RemoteCommandQueued
 *
 * Broadcast to child device channel when parent issues an actionable command.
 */
class RemoteCommandQueued implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public RemoteCommand $command
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("device.{$this->command->child_device_id}.commands"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'remote.command.queued';
    }
}
