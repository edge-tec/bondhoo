<?php

namespace App\Services\Audit;

use App\Models\AuditCheckpoint;
use App\Models\AuditIntegrityRun;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TamperEvidentAuditService
{
    public const ERROR_NONE = 'NONE';

    public const ERROR_BROKEN_CHAIN = 'BROKEN_CHAIN';

    public const ERROR_PAYLOAD_TAMPERED = 'PAYLOAD_TAMPERED';

    public const ERROR_GAP_DETECTED = 'GAP_DETECTED';

    /**
     * Verify the cryptographic chain integrity of audit logs across a range or limit.
     *
     * @return array{verified: bool, total_checked: int, broken_at_id: ?int, gap_ids: array<int>, error_code: string, message: string, root_hash: ?string, duration_ms: int}
     */
    public function verifyIntegrity(
        int $limit = 100,
        ?int $startId = null,
        ?int $endId = null,
        bool $recordRun = false,
        ?User $actor = null
    ): array {
        $startTime = microtime(true);
        $query = AuditLog::orderBy('id', 'asc');

        if ($startId !== null) {
            $query->where('id', '>=', $startId);
        }

        if ($endId !== null) {
            $query->where('id', '<=', $endId);
        } else {
            $query->take($limit);
        }

        $logs = $query->get();

        if ($logs->isEmpty()) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $result = [
                'verified' => true,
                'total_checked' => 0,
                'broken_at_id' => null,
                'gap_ids' => [],
                'error_code' => self::ERROR_NONE,
                'message' => 'অডিট লগে কোনো এন্ট্রি নেই, যাচাইকরণ সম্পন্ন।',
                'root_hash' => null,
                'duration_ms' => $durationMs,
            ];

            if ($recordRun) {
                $this->recordIntegrityRun($result, $startId, $endId, $actor);
            }

            return $result;
        }

        $firstLog = $logs->first();
        $predecessor = AuditLog::where('id', '<', $firstLog->id)->latest('id')->first();
        $previousHash = $predecessor ? $predecessor->checksum : hash('sha256', 'GENESIS_BLOCK_JUGAJUG_ADMIN');

        $checked = 0;
        $lastLogId = $predecessor?->id;
        $gapIds = [];

        foreach ($logs as $log) {
            $checked++;

            // 1. Detect missing sequence IDs (gaps caused by out-of-band row deletion)
            if ($lastLogId !== null && $log->id > ($lastLogId + 1)) {
                for ($missingId = $lastLogId + 1; $missingId < $log->id; $missingId++) {
                    $gapIds[] = $missingId;
                }
            }

            // 2. Verify previous hash link (chain consistency)
            if ($log->previous_hash !== null && $log->previous_hash !== $previousHash) {
                $durationMs = (int) round((microtime(true) - $startTime) * 1000);
                $result = [
                    'verified' => false,
                    'total_checked' => $checked,
                    'broken_at_id' => $log->id,
                    'gap_ids' => $gapIds,
                    'error_code' => ! empty($gapIds) ? self::ERROR_GAP_DETECTED : self::ERROR_BROKEN_CHAIN,
                    'message' => "অডিট ট্রেইল লিংক ত্রুটি সনাক্ত হয়েছে (লগ আইডি: #{$log->id})। প্রিভিয়াস হ্যাশ মিলছে না।",
                    'root_hash' => null,
                    'duration_ms' => $durationMs,
                ];

                if ($recordRun) {
                    $this->recordIntegrityRun($result, $firstLog->id, $logs->last()->id, $actor);
                }

                return $result;
            }

            // 3. Verify individual HMAC checksum
            if ($log->checksum !== null && ! $log->verifyChecksum()) {
                $durationMs = (int) round((microtime(true) - $startTime) * 1000);
                $result = [
                    'verified' => false,
                    'total_checked' => $checked,
                    'broken_at_id' => $log->id,
                    'gap_ids' => $gapIds,
                    'error_code' => self::ERROR_PAYLOAD_TAMPERED,
                    'message' => "অডিট এন্ট্রি টেম্পারিং বা ডাটা বিকৃতি সনাক্ত হয়েছে (লগ আইডি: #{$log->id})। পেলোড পরিবর্তন করা হয়েছে।",
                    'root_hash' => null,
                    'duration_ms' => $durationMs,
                ];

                if ($recordRun) {
                    $this->recordIntegrityRun($result, $firstLog->id, $logs->last()->id, $actor);
                }

                return $result;
            }

            $previousHash = $log->checksum ?? $previousHash;
            $lastLogId = $log->id;
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);
        $result = [
            'verified' => true,
            'total_checked' => $checked,
            'broken_at_id' => null,
            'gap_ids' => $gapIds,
            'error_code' => self::ERROR_NONE,
            'message' => "ক্রিপ্টোগ্রাফিক যাচাই সফল: সর্বশেষ {$checked} টি অডিট লগ অপরিবর্তিত ও সুরক্ষিত আছে।",
            'root_hash' => $previousHash,
            'duration_ms' => $durationMs,
            'storage_architecture_note' => 'Application-level Eloquent immutability + HMAC-SHA256 hash chain active; external WORM/S3 Object Lock storage required for independent hardware-level tamper resistance.',
        ];

        if ($recordRun) {
            $this->recordIntegrityRun($result, $firstLog->id, $logs->last()->id, $actor);
        }

        return $result;
    }

    /**
     * Persist an audit integrity verification run in execution history.
     */
    protected function recordIntegrityRun(array $result, ?int $startId, ?int $endId, ?User $actor): void
    {
        try {
            if (! Schema::hasTable('audit_integrity_runs')) {
                return;
            }

            AuditIntegrityRun::create([
                'run_uid' => 'AIR-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
                'records_checked' => (int) ($result['total_checked'] ?? 0),
                'start_id' => $startId,
                'end_id' => $endId,
                'verified' => (bool) ($result['verified'] ?? false),
                'error_code' => (string) ($result['error_code'] ?? self::ERROR_NONE),
                'broken_at_id' => $result['broken_at_id'] ?? null,
                'gap_ids' => $result['gap_ids'] ?? [],
                'root_hash' => $result['root_hash'] ?? null,
                'duration_ms' => (int) ($result['duration_ms'] ?? 0),
                'triggered_by' => $actor?->id,
                'executed_at' => now(),
            ]);
        } catch (Throwable) {
        }
    }

    /**
     * Create an immutable signed checkpoint snapshot for the audit chain and export it to isolated storage.
     */
    public function createCheckpoint(?User $admin = null): AuditCheckpoint
    {
        return DB::transaction(function () use ($admin) {
            $lastCheckpoint = AuditCheckpoint::latest('checkpoint_number')->lockForUpdate()->first();
            $startId = $lastCheckpoint ? ($lastCheckpoint->record_end_id + 1) : 1;

            $latestLog = AuditLog::latest('id')->lockForUpdate()->first();
            $endId = $latestLog?->id ?? 0;

            $count = $endId >= $startId ? AuditLog::whereBetween('id', [$startId, $endId])->count() : 0;
            $verification = $this->verifyIntegrity($count > 0 ? $count : 100, $startId, max($startId, $endId), true, $admin);

            if (! $verification['verified']) {
                throw new \InvalidArgumentException($verification['message'] ?? 'Audit log chain integrity verification failed; cannot seal checkpoint.');
            }

            $rootHash = $verification['root_hash'] ?? ($latestLog?->checksum ?? hash('sha256', 'GENESIS_CHECKPOINT'));
            $checkpointNumber = ($lastCheckpoint?->checkpoint_number ?? 0) + 1;
            $now = now();

            $payload = json_encode([
                'checkpoint_number' => $checkpointNumber,
                'record_start_id' => $startId,
                'record_end_id' => $endId,
                'record_count' => $count,
                'root_hash' => $rootHash,
                'checkpoint_at' => $now->toIso8601String(),
            ], JSON_UNESCAPED_SLASHES);

            $secret = config('app.key', 'audit_integrity_secret_key');
            $signature = hash_hmac('sha256', (string) $payload, $secret);

            $checkpoint = AuditCheckpoint::create([
                'checkpoint_number' => $checkpointNumber,
                'record_start_id' => $startId,
                'record_end_id' => $endId,
                'record_count' => $count,
                'root_hash' => $rootHash,
                'signature' => $signature,
                'key_version' => 'v1',
                'created_by' => $admin?->id,
                'checkpoint_at' => $now,
            ]);

            // Export signed checkpoint artifact to isolated storage path
            try {
                $exportPayload = (string) json_encode([
                    'checkpoint' => $checkpoint->toArray(),
                    'payload' => json_decode((string) $payload, true),
                    'hmac_sha256_signature' => $signature,
                    'exported_at' => $now->toIso8601String(),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                Storage::disk('local')->put("audit-checkpoints/checkpoint_{$checkpointNumber}.json", $exportPayload);
                if ($checkpoint->record_end_id) {
                    Storage::disk('local')->put("audit-checkpoints/checkpoint_{$checkpoint->record_end_id}.json", $exportPayload);
                }
            } catch (Throwable) {
            }

            return $checkpoint;
        });
    }

    /**
     * Alias for external checkpoint creation.
     */
    public function createExternalCheckpoint(int|User|null $admin = null): AuditCheckpoint
    {
        $user = is_int($admin) ? User::find($admin) : $admin;

        return $this->createCheckpoint($user);
    }

    /**
     * Verify chain integrity with sequence gap detection.
     */
    public function verifyChainIntegrity(int $limit = 500, ?int $userId = null): array
    {
        $actor = $userId ? User::find($userId) : null;
        $res = $this->verifyIntegrity($limit, null, null, true, $actor);

        return [
            'valid' => $res['verified'] && empty($res['gap_ids']),
            'gaps_count' => count($res['gap_ids']),
            'details' => $res,
        ];
    }

    /**
     * Filterable and searchable paginated audit log queries.
     */
    public function getAuditLogs(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = AuditLog::with('user:id,name,email,username');

        if (! empty($filters['action'])) {
            $query->where('action', 'like', "%{$filters['action']}%");
        }

        if (! empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('action', 'like', "%{$term}%")
                    ->orWhere('ip_address', 'like', "%{$term}%")
                    ->orWhere('entity_type', 'like', "%{$term}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Streamed CSV export of cryptographic audit logs with integrity proof header.
     */
    public function exportAuditLogsCsv(array $filters = []): StreamedResponse
    {
        $filename = 'audit-logs-export-'.now()->format('Y-m-d').'.csv';
        $verification = $this->verifyIntegrity(500);

        return response()->stream(function () use ($verification) {
            $handle = fopen('php://output', 'w');

            // BOM for UTF-8 compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Integrity verification header
            fputcsv($handle, ['# CRYPTOGRAPHIC AUDIT VERIFICATION REPORT']);
            fputcsv($handle, ['# Status', $verification['verified'] ? 'VERIFIED_VALID' : 'INTEGRITY_TAMPERED']);
            fputcsv($handle, ['# Checked Records', $verification['total_checked']]);
            fputcsv($handle, ['# Root Hash', $verification['root_hash'] ?? 'N/A']);
            fputcsv($handle, ['# Export Timestamp', now()->toIso8601String()]);
            fputcsv($handle, []);

            // CSV Column Headers
            fputcsv($handle, ['ID', 'Timestamp', 'Admin User', 'Action', 'Target Entity', 'Entity ID', 'IP Address', 'HMAC Checksum', 'Previous Hash']);

            AuditLog::with('user:id,name,email')
                ->latest('id')
                ->chunk(200, function ($logs) use ($handle) {
                    foreach ($logs as $log) {
                        fputcsv($handle, [
                            $log->id,
                            $log->created_at?->toIso8601String(),
                            $log->user?->name ?? 'System/Guest',
                            $log->action,
                            $log->entity_type,
                            $log->entity_id,
                            $log->ip_address,
                            $log->checksum,
                            $log->previous_hash,
                        ]);
                    }
                });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
