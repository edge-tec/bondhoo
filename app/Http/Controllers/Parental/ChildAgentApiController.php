<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parental;

use App\Http\Controllers\Controller;
use App\Models\Parental\ChildDevice;
use App\Services\Parental\ConsentRequestService;
use App\Services\Parental\DevicePairingService;
use App\Services\Parental\PolicyEngineService;
use App\Services\Parental\RemoteCommandService;
use App\Services\Parental\SecurityAlertService;
use App\Services\Parental\UsageTelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class ChildAgentApiController
 *
 * Dedicated API endpoints for the Android Child Agent.
 */
class ChildAgentApiController extends Controller
{
    public function __construct(
        protected DevicePairingService $pairingService,
        protected UsageTelemetryService $telemetryService,
        protected PolicyEngineService $policyService,
        protected RemoteCommandService $commandService,
        protected ConsentRequestService $consentService,
        protected SecurityAlertService $alertService
    ) {}

    /**
     * Redeem a 6-digit pairing code to enroll the device.
     */
    public function pair(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pairing_code' => ['required', 'string', 'size:6'],
            'device_uuid' => ['required', 'uuid'],
            'device_name' => ['required', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:32'],
            'android_version' => ['nullable', 'string', 'max:32'],
            'app_version' => ['nullable', 'string', 'max:32'],
        ]);

        try {
            $result = $this->pairingService->pairDevice(
                plainCode: $validated['pairing_code'],
                deviceUuid: $validated['device_uuid'],
                deviceName: $validated['device_name'],
                platform: $validated['platform'] ?? 'android',
                androidVersion: $validated['android_version'] ?? null,
                appVersion: $validated['app_version'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Device enrolled successfully.',
                'device_id' => $result['device']->id,
                'device_uuid' => $result['device']->device_uuid,
                'device_token' => $result['device_token'],
            ], Response::HTTP_CREATED);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Batch ingest foreground application usage telemetry.
     */
    public function syncUsage(Request $request): JsonResponse
    {
        /** @var ChildDevice $device */
        $device = $request->attributes->get('childDevice');

        $validated = $request->validate([
            'events' => ['required', 'array', 'min:1'],
            'events.*.application_package' => ['required', 'string', 'max:191'],
            'events.*.duration_seconds' => ['required', 'integer', 'min:0'],
            'events.*.event_started_at' => ['required', 'date'],
            'events.*.event_ended_at' => ['nullable', 'date'],
        ]);

        $processed = $this->telemetryService->ingestTelemetry($device, $validated['events']);

        return response()->json([
            'success' => true,
            'message' => 'Telemetry batch processed.',
            'records_ingested' => $processed,
        ]);
    }

    /**
     * Retrieve active policies, bedtime curfews, and app blacklists.
     */
    public function getPolicies(Request $request): JsonResponse
    {
        /** @var ChildDevice $device */
        $device = $request->attributes->get('childDevice');

        $policies = $this->policyService->getActivePoliciesForDevice($device);

        return response()->json([
            'success' => true,
            'policies' => $policies,
        ]);
    }

    /**
     * Poll pending remote commands issued to this device.
     */
    public function getPendingCommands(Request $request): JsonResponse
    {
        /** @var ChildDevice $device */
        $device = $request->attributes->get('childDevice');

        $commands = $this->commandService->pollPendingCommands($device);

        return response()->json([
            'success' => true,
            'commands' => $commands,
        ]);
    }

    /**
     * Acknowledge command execution.
     */
    public function acknowledgeCommand(Request $request, string $uuid): JsonResponse
    {
        /** @var ChildDevice $device */
        $device = $request->attributes->get('childDevice');

        $validated = $request->validate([
            'status' => ['required', 'in:acknowledged,completed,rejected,failed'],
            'failure_reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $command = $this->commandService->acknowledgeCommand(
                device: $device,
                commandUuid: $uuid,
                status: $validated['status'],
                failureReason: $validated['failure_reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Command acknowledged.',
                'command_uuid' => $command->command_uuid,
                'status' => $command->command_status,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Child requests additional time or app unblock.
     */
    public function createConsentRequest(Request $request): JsonResponse
    {
        /** @var ChildDevice $device */
        $device = $request->attributes->get('childDevice');

        $validated = $request->validate([
            'request_type' => ['required', 'string', 'in:extra_time,unblock_app,permission_grant'],
            'requested_value' => ['nullable', 'string', 'max:128'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $consent = $this->consentService->createRequest(
            device: $device,
            requestType: $validated['request_type'],
            requestedValue: $validated['requested_value'] ?? null,
            reason: $validated['reason'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Consent request submitted to parents.',
            'request_id' => $consent->id,
            'status' => $consent->request_status,
        ], Response::HTTP_CREATED);
    }

    /**
     * Report tamper detection or permission loss alert.
     */
    public function submitAlert(Request $request): JsonResponse
    {
        /** @var ChildDevice $device */
        $device = $request->attributes->get('childDevice');

        $validated = $request->validate([
            'alert_type' => ['required', 'string', 'max:64'],
            'description' => ['required', 'string', 'max:500'],
            'severity' => ['nullable', 'in:low,medium,high,critical'],
        ]);

        $alert = $this->alertService->recordAlert(
            device: $device,
            alertType: $validated['alert_type'],
            description: $validated['description'],
            severity: $validated['severity'] ?? 'medium'
        );

        return response()->json([
            'success' => true,
            'message' => 'Security alert received.',
            'alert_id' => $alert->id,
        ], Response::HTTP_CREATED);
    }

    /**
     * Synchronize installed applications inventory from child device.
     */
    public function syncAppInventory(Request $request): JsonResponse
    {
        /** @var ChildDevice $device */
        $device = $request->attributes->get('childDevice');

        $validated = $request->validate([
            'apps' => ['required', 'array', 'min:1'],
            'apps.*.package_name' => ['required', 'string', 'max:191'],
            'apps.*.app_name' => ['nullable', 'string', 'max:120'],
            'apps.*.is_system_app' => ['nullable', 'boolean'],
        ]);

        $device->update(['last_seen_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Application inventory synchronized.',
            'total_apps' => count($validated['apps']),
        ]);
    }

    /**
     * Rotate device authentication token.
     */
    public function rotateToken(Request $request): JsonResponse
    {
        /** @var ChildDevice $device */
        $device = $request->attributes->get('childDevice');

        try {
            $newToken = $this->pairingService->rotateDeviceToken($device);

            return response()->json([
                'success' => true,
                'message' => 'Device token rotated successfully.',
                'device_token' => $newToken,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
