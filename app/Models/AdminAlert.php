<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class AdminAlert extends Model
{
    use HasFactory;

    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_DANGER = 'danger';

    public const SEVERITY_CRITICAL = 'critical';

    public const STATUS_UNREAD = 'unread';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_SUPPRESSED = 'suppressed';

    protected $table = 'admin_alerts';

    protected $fillable = [
        'type',
        'severity',
        'signature',
        'hit_count',
        'status',
        'title',
        'message',
        'data',
        'link',
        'read_at',
        'read_by',
        'acknowledged_at',
        'acknowledged_by',
        'assigned_to',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'hit_count' => 'integer',
            'read_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(AdminAlertDelivery::class, 'admin_alert_id');
    }

    /**
     * Dispatch an admin alert with deduplication, suppression windows and hit counts.
     */
    public static function trigger(
        string $type,
        string $title,
        string $message,
        string $severity = 'info',
        ?array $data = null,
        ?string $link = null,
        int $suppressionMinutes = 15
    ): static {
        $entityRef = ($data['entity_type'] ?? '').':'.($data['entity_id'] ?? ($data['security_event_id'] ?? ''));
        $signature = hash('sha256', "{$type}|{$title}|{$entityRef}");

        return DB::transaction(function () use ($type, $title, $message, $severity, $data, $link, $suppressionMinutes, $signature) {
            // Check for existing duplicate within suppression window with row lock
            /** @var static|null $recent */
            $recent = static::where('signature', $signature)
                ->where('created_at', '>=', now()->subMinutes($suppressionMinutes))
                ->lockForUpdate()
                ->first();

            if ($recent) {
                $recent->increment('hit_count');
                $recent->update([
                    'updated_at' => now(),
                    'severity' => ($severity === self::SEVERITY_CRITICAL || $recent->severity === self::SEVERITY_CRITICAL)
                        ? self::SEVERITY_CRITICAL
                        : $severity,
                ]);

                return $recent->fresh();
            }

            return static::create([
                'type' => $type,
                'severity' => $severity,
                'signature' => $signature,
                'hit_count' => 1,
                'status' => self::STATUS_UNREAD,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'link' => $link,
            ]);
        });
    }

    /**
     * Acknowledge this alert.
     */
    public function acknowledge(User $admin): void
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'acknowledged_by' => $admin->id,
            'read_at' => $this->read_at ?? now(),
            'read_by' => $this->read_by ?? $admin->id,
        ]);
    }

    /**
     * Resolve this alert.
     */
    public function resolve(User $admin): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'acknowledged_at' => $this->acknowledged_at ?? now(),
            'acknowledged_by' => $this->acknowledged_by ?? $admin->id,
            'read_at' => $this->read_at ?? now(),
            'read_by' => $this->read_by ?? $admin->id,
        ]);
    }

    /**
     * Assign this alert to an admin.
     */
    public function assignTo(User $admin): void
    {
        $this->update([
            'assigned_to' => $admin->id,
        ]);
    }
}
