<?php

declare(strict_types=1);

namespace App\Services\Parental;

use App\Models\Parental\ChildDevice;
use App\Models\Parental\ConsentRequest;
use App\Models\User;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class ConsentRequestService
 *
 * Handles child-initiated permission escalation requests and parental decisions.
 */
class ConsentRequestService
{
    public function __construct(
        protected FamilyService $familyService
    ) {}

    /**
     * Submit a consent request from a child device.
     */
    public function createRequest(
        ChildDevice $device,
        string $requestType,
        ?string $requestedValue = null,
        ?string $reason = null
    ): ConsentRequest {
        if ($device->device_status !== 'active') {
            throw new RuntimeException('Cannot create consent request from an inactive or revoked device.');
        }

        $request = ConsentRequest::create([
            'family_id' => $device->family_id,
            'child_member_id' => $device->child_member_id,
            'child_device_id' => $device->id,
            'request_type' => $requestType,
            'request_reason' => $reason,
            'requested_value' => $requestedValue,
            'request_status' => 'pending',
            'expires_at' => now()->addHours(24),
        ]);

        $this->familyService->logAudit(
            family: $device->family,
            actorUserId: null,
            role: 'child_device',
            action: 'consent.requested',
            resourceType: 'consent_request',
            resourceId: (string) $request->id,
            metadata: [
                'request_type' => $requestType,
                'requested_value' => $requestedValue,
            ]
        );

        return $request;
    }

    /**
     * Parent approves or rejects a pending consent request.
     */
    public function respondToRequest(
        ConsentRequest $request,
        User $parent,
        string $status,
        ?string $responseNote = null
    ): ConsentRequest {
        if (! in_array($status, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Invalid response status. Must be approved or rejected.');
        }

        if ($request->request_status !== 'pending') {
            throw new RuntimeException('This consent request has already been processed or expired.');
        }

        $request->update([
            'request_status' => $status,
            'parent_response' => $responseNote,
            'responded_by' => $parent->id,
            'responded_at' => now(),
        ]);

        $this->familyService->logAudit(
            family: $request->family,
            actorUserId: $parent->id,
            role: 'parent',
            action: "consent.{$status}",
            resourceType: 'consent_request',
            resourceId: (string) $request->id,
            metadata: [
                'decision' => $status,
                'note' => $responseNote,
            ]
        );

        return $request;
    }
}
