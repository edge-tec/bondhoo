<?php

namespace App\Services\Admin;

use App\Models\AdminAlert;
use App\Models\AdminAlertDelivery;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class OperationsAlertService
{
    /**
     * Get Filtered and Paginated Operations Alerts.
     */
    public function getAlerts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AdminAlert::with(['reader:id,name', 'acknowledger:id,name', 'assignee:id,name', 'deliveries']);

        if (! empty($filters['type']) && $filters['type'] !== 'all') {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['severity']) && $filters['severity'] !== 'all') {
            $query->where('severity', $filters['severity']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['unread_only']) && filter_var($filters['unread_only'], FILTER_VALIDATE_BOOLEAN)) {
            $query->whereNull('read_at');
        }

        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('message', 'like', "%{$term}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Dispatch an Alert with Multi-Channel Notification Delivery.
     */
    public function dispatchAlert(
        string $type,
        string $title,
        string $message,
        string $severity = 'info',
        ?array $data = null,
        ?string $link = null,
        array $channels = ['in_app']
    ): AdminAlert {
        $alert = AdminAlert::trigger(
            type: $type,
            title: $title,
            message: $message,
            severity: $severity,
            data: $data,
            link: $link
        );

        // Record Delivery attempts
        foreach ($channels as $channel) {
            AdminAlertDelivery::create([
                'admin_alert_id' => $alert->id,
                'channel' => $channel,
                'status' => AdminAlertDelivery::STATUS_SENT,
                'attempts' => 1,
                'sent_at' => now(),
            ]);
        }

        return $alert;
    }

    /**
     * Acknowledge an alert.
     */
    public function acknowledgeAlert(int $alertId, User $admin): AdminAlert
    {
        $alert = AdminAlert::findOrFail($alertId);
        $alert->acknowledge($admin);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'admin_alert_acknowledged',
            'entity_type' => AdminAlert::class,
            'entity_id' => $alert->id,
            'new_values' => ['status' => AdminAlert::STATUS_ACKNOWLEDGED],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $alert;
    }

    /**
     * Resolve an alert.
     */
    public function resolveAlert(int $alertId, User $admin): AdminAlert
    {
        $alert = AdminAlert::findOrFail($alertId);
        $alert->resolve($admin);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'admin_alert_resolved',
            'entity_type' => AdminAlert::class,
            'entity_id' => $alert->id,
            'new_values' => ['status' => AdminAlert::STATUS_RESOLVED],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $alert;
    }

    /**
     * Assign alert to admin.
     */
    public function assignAlert(int $alertId, User $assignee, User $admin): AdminAlert
    {
        $alert = AdminAlert::findOrFail($alertId);
        $alert->assignTo($assignee);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'admin_alert_assigned',
            'entity_type' => AdminAlert::class,
            'entity_id' => $alert->id,
            'new_values' => ['assigned_to' => $assignee->id, 'assignee_name' => $assignee->name],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $alert;
    }

    /**
     * Get Alert Summary KPI metrics.
     */
    public function getSummary(): array
    {
        return [
            'total_alerts' => AdminAlert::count(),
            'unread_count' => AdminAlert::whereNull('read_at')->count(),
            'critical_unread_count' => AdminAlert::whereNull('read_at')->where('severity', AdminAlert::SEVERITY_CRITICAL)->count(),
            'acknowledged_count' => AdminAlert::where('status', AdminAlert::STATUS_ACKNOWLEDGED)->count(),
            'resolved_count' => AdminAlert::where('status', AdminAlert::STATUS_RESOLVED)->count(),
        ];
    }
}
