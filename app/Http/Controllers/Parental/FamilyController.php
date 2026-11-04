<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parental;

use App\Http\Controllers\Controller;
use App\Models\Parental\ChildDevice;
use App\Models\Parental\ConsentRequest;
use App\Models\Parental\Family;
use App\Models\Parental\FamilyMember;
use App\Models\User;
use App\Services\Parental\ConsentRequestService;
use App\Services\Parental\DevicePairingService;
use App\Services\Parental\FamilyService;
use App\Services\Parental\PolicyEngineService;
use App\Services\Parental\RemoteCommandService;
use App\Services\Parental\SecurityAlertService;
use App\Services\Parental\UsageTelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FamilyController
 *
 * REST API Controller for parents managing their family unit and child controls.
 */
class FamilyController extends Controller
{
    public function __construct(
        protected FamilyService $familyService,
        protected DevicePairingService $pairingService,
        protected UsageTelemetryService $telemetryService,
        protected PolicyEngineService $policyService,
        protected RemoteCommandService $commandService,
        protected ConsentRequestService $consentService,
        protected SecurityAlertService $alertService
    ) {}

    /**
     * Create a new family group.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $family = $this->familyService->createFamily(
            owner: $user,
            name: $validated['name'],
            timezone: $validated['timezone'] ?? 'UTC'
        );

        return response()->json([
            'success' => true,
            'message' => 'Family group created successfully.',
            'family' => $family->load('members'),
        ], Response::HTTP_CREATED);
    }

    /**
     * Show family details, members, and enrolled devices.
     */
    public function show(Request $request, Family $family): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $family->hasGuardianAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have guardian access to this family.',
            ], Response::HTTP_FORBIDDEN);
        }

        $family->load([
            'members.devices',
            'restrictions',
            'securityAlerts' => fn ($q) => $q->where('alert_status', 'active'),
        ]);

        return response()->json([
            'success' => true,
            'family' => $family,
        ]);
    }

    /**
     * Update family configuration details.
     */
    public function update(Request $request, Family $family): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $family->hasGuardianAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to update this family.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $updated = $this->familyService->updateFamily($family, $validated, $user);

        return response()->json([
            'success' => true,
            'message' => 'Family details updated.',
            'family' => $updated,
        ]);
    }

    /**
     * Add a child profile or secondary guardian.
     */
    public function addMember(Request $request, Family $family): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $family->hasGuardianAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to add members.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'role' => ['required', 'in:parent,guardian,child'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $invitedUser = ! empty($validated['user_id']) ? User::find($validated['user_id']) : null;

        $member = $this->familyService->addMember(
            family: $family,
            name: $validated['name'],
            role: $validated['role'],
            user: $invitedUser,
            actorUserId: $user->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Family member added successfully.',
            'member' => $member,
        ], Response::HTTP_CREATED);
    }

    /**
     * Update member attributes (name, role).
     */
    public function updateMember(Request $request, Family $family, FamilyMember $member): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $family->hasGuardianAccess($user->id) || $member->family_id !== $family->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to modify this member.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:parent,guardian,child'],
        ]);

        $updated = $this->familyService->updateMember($member, $validated, $user);

        return response()->json([
            'success' => true,
            'message' => 'Family member updated.',
            'member' => $updated,
        ]);
    }

    /**
     * Revoke or remove a family member.
     */
    public function removeMember(Request $request, Family $family, FamilyMember $member): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($family->owner_user_id !== $user->id || $member->family_id !== $family->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only the family owner can remove members.',
            ], Response::HTTP_FORBIDDEN);
        }

        $this->familyService->removeMember($member, $user);

        return response()->json([
            'success' => true,
            'message' => 'Family member removed successfully.',
        ]);
    }

    /**
     * Retrieve immutable activity and audit logs for the family.
     */
    public function activityHistory(Request $request, Family $family): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $family->hasGuardianAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], Response::HTTP_FORBIDDEN);
        }

        $logs = $this->familyService->getActivityHistory($family, 50);

        return response()->json([
            'success' => true,
            'activity_logs' => $logs,
        ]);
    }

    /**
     * Generate a 6-digit pairing code for enrolling a child's device.
     */
    public function generatePairingCode(Request $request, Family $family): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $family->hasGuardianAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have guardian access to this family.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'child_member_id' => ['required', 'integer', 'exists:family_members,id'],
        ]);

        /** @var FamilyMember $childMember */
        $childMember = FamilyMember::where('id', $validated['child_member_id'])
            ->where('family_id', $family->id)
            ->firstOrFail();

        $codeData = $this->pairingService->generatePairingCode(
            family: $family,
            childMember: $childMember,
            createdBy: $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Single-use pairing code generated.',
            'pairing_code' => $codeData['code'],
            'expires_at' => $codeData['expires_at'],
        ], Response::HTTP_CREATED);
    }

    /**
     * Get daily screen time summary for a child.
     */
    public function getScreenTime(Request $request, FamilyMember $childMember): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $childMember->family->hasGuardianAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You cannot view screen time for another family.',
            ], Response::HTTP_FORBIDDEN);
        }

        $date = $request->query('date', now()->toDateString());
        $summary = $this->telemetryService->getChildDailySummary($childMember, (string) $date);

        return response()->json([
            'success' => true,
            'screen_time' => $summary,
        ]);
    }

    /**
     * Get weekly screen time analytics for a child.
     */
    public function getWeeklyScreenTime(Request $request, FamilyMember $childMember): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $childMember->family->hasGuardianAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], Response::HTTP_FORBIDDEN);
        }

        $date = $request->query('date', now()->toDateString());
        $summary = $this->telemetryService->getChildWeeklySummary($childMember, (string) $date);

        return response()->json([
            'success' => true,
            'weekly_summary' => $summary,
        ]);
    }

    /**
     * Get daily screen time quota and remaining minutes for a child.
     */
    public function getScreenTimeQuota(Request $request, FamilyMember $childMember): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $childMember->family->hasGuardianAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], Response::HTTP_FORBIDDEN);
        }

        $date = $request->query('date', now()->toDateString());
        $quota = $this->telemetryService->calculateRemainingQuota($childMember, (string) $date);

        return response()->json([
            'success' => true,
            'quota' => $quota,
        ]);
    }

    /**
     * Upsert a device restriction (curfew, screen limit, blacklist).
     */
    public function upsertRestriction(Request $request, Family $family): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $family->hasGuardianAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to modify restrictions.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'child_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'restriction_type' => ['required', 'string', 'in:daily_time_limit,bedtime_curfew,app_blacklist,app_whitelist'],
            'application_package' => ['nullable', 'string', 'max:191'],
            'configuration' => ['required', 'array'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        /** @var FamilyMember $childMember */
        $childMember = FamilyMember::where('id', $validated['child_member_id'])
            ->where('family_id', $family->id)
            ->firstOrFail();

        $restriction = $this->policyService->upsertRestriction(
            family: $family,
            childMember: $childMember,
            restrictionType: $validated['restriction_type'],
            configuration: $validated['configuration'],
            creator: $user,
            applicationPackage: $validated['application_package'] ?? null,
            enabled: $validated['enabled'] ?? true
        );

        return response()->json([
            'success' => true,
            'message' => 'Device restriction updated.',
            'restriction' => $restriction,
        ]);
    }

    /**
     * Dispatch an immediate remote command to a child's device (lock, sync, alarm).
     */
    public function dispatchCommand(Request $request, ChildDevice $device): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $device->family->hasGuardianAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to control this device.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'command_type' => ['required', 'string', 'in:device_lock,device_unlock,policy_sync,sound_alarm,telemetry_flush'],
            'payload' => ['nullable', 'array'],
            'idempotency_key' => ['nullable', 'uuid'],
        ]);

        $command = $this->commandService->dispatchCommand(
            family: $device->family,
            device: $device,
            commandType: $validated['command_type'],
            payload: $validated['payload'] ?? null,
            requester: $user,
            idempotencyKey: $validated['idempotency_key'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Remote command queued for delivery.',
            'command' => $command,
        ], Response::HTTP_CREATED);
    }

    /**
     * Revoke child device authorization.
     */
    public function revokeDevice(Request $request, ChildDevice $device): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $device->family->hasGuardianAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to revoke this device.',
            ], Response::HTTP_FORBIDDEN);
        }

        $device->revoke();

        $this->familyService->logAudit(
            family: $device->family,
            actorUserId: $user->id,
            role: 'parent',
            action: 'device.revoked',
            resourceType: 'child_device',
            resourceId: (string) $device->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Device credentials revoked successfully.',
        ]);
    }

    /**
     * List consent requests for the family.
     */
    public function listConsentRequests(Request $request, Family $family): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $family->hasGuardianAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to this family.',
            ], Response::HTTP_FORBIDDEN);
        }

        $requests = $family->consentRequests()
            ->with(['childMember', 'device'])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'consent_requests' => $requests,
        ]);
    }

    /**
     * Respond to a child consent request.
     */
    public function respondConsentRequest(Request $request, ConsentRequest $consentRequest): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $consentRequest->family->hasGuardianAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You cannot respond to another family request.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'response_note' => ['nullable', 'string', 'max:255'],
        ]);

        $updated = $this->consentService->respondToRequest(
            request: $consentRequest,
            parent: $user,
            status: $validated['status'],
            responseNote: $validated['response_note'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => "Request has been {$validated['status']}.",
            'consent_request' => $updated,
        ]);
    }

    /**
     * List active security alerts for the family.
     */
    public function listAlerts(Request $request, Family $family): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $family->hasGuardianAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], Response::HTTP_FORBIDDEN);
        }

        $alerts = $family->securityAlerts()
            ->with('device')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'alerts' => $alerts,
        ]);
    }
}
