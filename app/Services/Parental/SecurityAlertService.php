<?php

declare(strict_types=1);

namespace App\Services\Parental;

use App\Models\Parental\ChildDevice;
use App\Models\Parental\ChildSecurityAlert;
use App\Models\User;

/**
 * Class SecurityAlertService
 *
 * Records and processes tamper detection and security health alerts from child devices.
 */
class SecurityAlertService
{
    public function __construct(
        protected FamilyService $familyService
    ) {}

    /**
     * Record a new security or health alert emitted by the child device agent.
     */
    public function recordAlert(
        ChildDevice $device,
        string $alertType,
        string $description,
        string $severity = 'medium'
    ): ChildSecurityAlert {
        $alert = ChildSecurityAlert::create([
            'family_id' => $device->family_id,
            'child_device_id' => $device->id,
            'alert_type' => $alertType,
            'severity' => $severity,
            'description' => $description,
            'alert_status' => 'active',
            'detected_at' => now(),
        ]);

        $this->familyService->logAudit(
            family: $device->family,
            actorUserId: null,
            role: 'child_device',
            action: 'security.alert_raised',
            resourceType: 'child_security_alert',
            resourceId: (string) $alert->id,
            metadata: [
                'alert_type' => $alertType,
                'severity' => $severity,
                'description' => $description,
            ]
        );

        return $alert;
    }

    /**
     * Acknowledge or resolve an active security alert by a parent.
     */
    public function resolveAlert(ChildSecurityAlert $alert, User $parent, string $status = 'resolved'): ChildSecurityAlert
    {
        $alert->update([
            'alert_status' => $status,
            'acknowledged_at' => now(),
        ]);

        $this->familyService->logAudit(
            family: $alert->family,
            actorUserId: $parent->id,
            role: 'parent',
            action: 'security.alert_resolved',
            resourceType: 'child_security_alert',
            resourceId: (string) $alert->id,
            metadata: ['status' => $status]
        );

        return $alert;
    }
}
