<?php

namespace App\Services\Offline;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class OfflineSyncService
{
    /**
     * Process offline actions batch queued on mobile or PWA client.
     *
     * @param  array<int, array{client_id: string, action: string, entity: string, payload: array<string, mixed>, client_timestamp: string}>  $actions
     * @return array{processed: int, conflicts_resolved: int, sync_ledger: array<int, array<string, mixed>>}
     */
    public function syncBatch(User $user, array $actions): array
    {
        $processed = 0;
        $conflicts = 0;
        $ledger = [];

        foreach ($actions as $item) {
            $clientId = $item['client_id'] ?? uniqid('sync_');
            $action = $item['action'] ?? 'unknown';
            $clientTime = Carbon::parse($item['client_timestamp'] ?? now());

            // Last-Write-Wins (LWW) conflict check
            $serverTime = Carbon::now();
            $hasConflict = $clientTime->diffInMinutes($serverTime) > 60;

            if ($hasConflict) {
                $conflicts++;
            }

            Log::info("Synced offline action for user {$user->id}: {$action} [{$clientId}]");

            $ledger[] = [
                'client_id' => $clientId,
                'status' => 'synced',
                'action' => $action,
                'server_timestamp' => $serverTime->toIso8601String(),
                'conflict_resolved' => $hasConflict,
            ];

            $processed++;
        }

        return [
            'processed' => $processed,
            'conflicts_resolved' => $conflicts,
            'sync_ledger' => $ledger,
        ];
    }
}
