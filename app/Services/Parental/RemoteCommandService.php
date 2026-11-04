<?php

declare(strict_types=1);

namespace App\Services\Parental;

use App\Models\Parental\ChildDevice;
use App\Models\Parental\Family;
use App\Models\Parental\RemoteCommand;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class RemoteCommandService
 *
 * Dispatches, tracks, and processes lifecycle acknowledgments for remote child device actions.
 */
class RemoteCommandService
{
    public function __construct(
        protected FamilyService $familyService
    ) {}

    /**
     * Dispatch an actionable remote command to a child device.
     *
     * @param  array<string, mixed>|null  $payload
     */
    public function dispatchCommand(
        Family $family,
        ChildDevice $device,
        string $commandType,
        ?array $payload,
        User $requester,
        ?string $idempotencyKey = null
    ): RemoteCommand {
        if ($device->family_id !== $family->id) {
            throw new InvalidArgumentException('Device does not belong to the target family.');
        }

        if ($device->device_status !== 'active') {
            throw new RuntimeException('Cannot dispatch remote command to an inactive or revoked device.');
        }

        // Check idempotency if client provided a key
        if ($idempotencyKey !== null) {
            /** @var RemoteCommand|null $existing */
            $existing = RemoteCommand::where('child_device_id', $device->id)
                ->where('command_uuid', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $commandUuid = $idempotencyKey ?: (string) Str::uuid();
        $expiresAt = now()->addHours(2);

        $command = RemoteCommand::create([
            'family_id' => $family->id,
            'child_device_id' => $device->id,
            'command_uuid' => $commandUuid,
            'command_type' => $commandType,
            'payload' => $payload,
            'command_status' => 'queued',
            'requested_by' => $requester->id,
            'issued_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        $this->familyService->logAudit(
            family: $family,
            actorUserId: $requester->id,
            role: 'parent',
            action: 'command.dispatched',
            resourceType: 'remote_command',
            resourceId: $commandUuid,
            metadata: [
                'command_type' => $commandType,
                'device_id' => $device->id,
                'device_uuid' => $device->device_uuid,
            ]
        );

        return $command;
    }

    /**
     * Fetch all pending actionable commands for a child device and mark them as sent.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pollPendingCommands(ChildDevice $device): array
    {
        $commands = RemoteCommand::where('child_device_id', $device->id)
            ->whereIn('command_status', ['queued', 'sent'])
            ->where('expires_at', '>', now())
            ->orderBy('id', 'asc')
            ->get();

        $output = [];

        foreach ($commands as $cmd) {
            if ($cmd->command_status === 'queued') {
                $cmd->update(['command_status' => 'sent']);
            }

            $output[] = [
                'command_uuid' => $cmd->command_uuid,
                'command_type' => $cmd->command_type,
                'payload' => $cmd->payload,
                'issued_at' => $cmd->issued_at->toIso8601String(),
            ];
        }

        return $output;
    }

    /**
     * Acknowledge execution status of a command from the child device agent.
     */
    public function acknowledgeCommand(
        ChildDevice $device,
        string $commandUuid,
        string $status,
        ?string $failureReason = null
    ): RemoteCommand {
        /** @var RemoteCommand|null $command */
        $command = RemoteCommand::where('command_uuid', $commandUuid)
            ->where('child_device_id', $device->id)
            ->first();

        if (! $command) {
            throw new RuntimeException("Command with UUID {$commandUuid} not found for this device.");
        }

        $now = now();
        $updates = [
            'command_status' => $status,
            'acknowledged_at' => $now,
            'failure_reason' => $failureReason,
        ];

        if (in_array($status, ['completed', 'rejected', 'failed'], true)) {
            $updates['completed_at'] = $now;
        }

        $command->update($updates);

        return $command;
    }
}
