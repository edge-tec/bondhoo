<?php

declare(strict_types=1);

namespace Tests\Feature\Parental;

use App\Models\Parental\Family;
use App\Models\Parental\FamilyMember;
use App\Models\User;
use App\Services\Parental\DevicePairingService;
use App\Services\Parental\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Class ConnectlyParentalControlTest
 *
 * Full verification test suite for Connectly Parental Control & Family Safety Architecture.
 */
class ConnectlyParentalControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $parentA;

    protected User $parentB;

    protected Family $familyA;

    protected FamilyMember $childMemberA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parentA = User::factory()->create([
            'name' => 'Alice Parent',
            'email' => 'alice.parent@example.com',
        ]);

        $this->parentB = User::factory()->create([
            'name' => 'Bob Stranger',
            'email' => 'bob.stranger@example.com',
        ]);

        /** @var FamilyService $familyService */
        $familyService = app(FamilyService::class);
        $this->familyA = $familyService->createFamily($this->parentA, 'Alice Family', 'America/New_York');
        $this->childMemberA = $familyService->addMember($this->familyA, 'Charlie Child', 'child');
    }

    /**
     * Test 1: Parent can create a new family group.
     */
    public function test_parent_can_create_family(): void
    {
        Sanctum::actingAs($this->parentB);

        $response = $this->postJson('/api/v1/parental/families', [
            'name' => 'Bob Family Unit',
            'timezone' => 'Europe/London',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('family.name', 'Bob Family Unit');

        $this->assertDatabaseHas('families', [
            'name' => 'Bob Family Unit',
            'owner_user_id' => $this->parentB->id,
        ]);
    }

    /**
     * Test 2: Parent can view own family details.
     */
    public function test_parent_can_view_own_family(): void
    {
        Sanctum::actingAs($this->parentA);

        $response = $this->getJson("/api/v1/parental/families/{$this->familyA->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('family.id', $this->familyA->id);
    }

    /**
     * Test 3: Cross-family IDOR / BOLA protection rejects unauthorized parents.
     */
    public function test_cross_family_access_is_forbidden(): void
    {
        Sanctum::actingAs($this->parentB); // Stranger trying to view Family A

        $response = $this->getJson("/api/v1/parental/families/{$this->familyA->id}");

        $response->assertForbidden()
            ->assertJsonPath('success', false);
    }

    /**
     * Test 4: Parent can add a new child profile to family.
     */
    public function test_parent_can_add_child_profile(): void
    {
        Sanctum::actingAs($this->parentA);

        $response = $this->postJson("/api/v1/parental/families/{$this->familyA->id}/members", [
            'name' => 'Daisy Child',
            'role' => 'child',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('member.name', 'Daisy Child');

        $this->assertDatabaseHas('family_members', [
            'family_id' => $this->familyA->id,
            'name' => 'Daisy Child',
            'role' => 'child',
        ]);
    }

    /**
     * Test 5: Generate single-use 6-digit pairing code with 10-minute TTL.
     */
    public function test_generate_pairing_code(): void
    {
        Sanctum::actingAs($this->parentA);

        $response = $this->postJson("/api/v1/parental/families/{$this->familyA->id}/pairing-codes", [
            'child_member_id' => $this->childMemberA->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $code = $response->json('pairing_code');
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);

        $this->assertDatabaseHas('device_pairing_codes', [
            'family_id' => $this->familyA->id,
            'child_member_id' => $this->childMemberA->id,
            'code_hash' => hash('sha256', $code),
            'used_at' => null,
        ]);
    }

    /**
     * Test 6: Child device pairing and token generation.
     */
    public function test_child_device_pairing_lifecycle(): void
    {
        /** @var DevicePairingService $pairingService */
        $pairingService = app(DevicePairingService::class);
        $codeData = $pairingService->generatePairingCode($this->familyA, $this->childMemberA, $this->parentA);

        $deviceUuid = (string) Str::uuid();

        // 1. Redeem pairing code
        $pairResponse = $this->postJson('/api/v1/parental/device/pair', [
            'pairing_code' => $codeData['code'],
            'device_uuid' => $deviceUuid,
            'device_name' => "Charlie's Pixel Tablet",
            'platform' => 'android',
            'android_version' => '14.0',
            'app_version' => '1.0.0',
        ]);

        $pairResponse->assertCreated()
            ->assertJsonPath('success', true);

        $deviceToken = $pairResponse->json('device_token');
        $this->assertNotEmpty($deviceToken);

        // 2. Attempting to reuse the exact same pairing code must fail
        $secondAttempt = $this->postJson('/api/v1/parental/device/pair', [
            'pairing_code' => $codeData['code'],
            'device_uuid' => (string) Str::uuid(),
            'device_name' => 'Duplicate Device',
        ]);

        $secondAttempt->assertStatus(422)
            ->assertJsonPath('success', false);

        // 3. Authenticated child device can fetch policies
        $policiesResponse = $this->withHeader('Authorization', "Bearer {$deviceToken}")
            ->getJson('/api/v1/parental/device/policies');

        $policiesResponse->assertOk()
            ->assertJsonPath('success', true);
    }

    /**
     * Test 7: Telemetry ingestion and screen time aggregation.
     */
    public function test_usage_telemetry_sync_and_screen_time_aggregation(): void
    {
        /** @var DevicePairingService $pairingService */
        $pairingService = app(DevicePairingService::class);
        $codeData = $pairingService->generatePairingCode($this->familyA, $this->childMemberA, $this->parentA);
        $pairResult = $pairingService->pairDevice(
            plainCode: $codeData['code'],
            deviceUuid: (string) Str::uuid(),
            deviceName: "Charlie's Galaxy Tab"
        );
        $deviceToken = $pairResult['device_token'];
        $device = $pairResult['device'];

        $today = now()->toIso8601String();

        // Ingest telemetry batch
        $syncResponse = $this->withHeader('Authorization', "Bearer {$deviceToken}")
            ->postJson('/api/v1/parental/device/usage/sync', [
                'events' => [
                    [
                        'application_package' => 'com.google.android.youtube',
                        'duration_seconds' => 1200,
                        'event_started_at' => $today,
                    ],
                    [
                        'application_package' => 'com.mojang.minecraftpe',
                        'duration_seconds' => 1800,
                        'event_started_at' => $today,
                    ],
                ],
            ]);

        $syncResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('records_ingested', 2);

        // Parent queries child screen time
        Sanctum::actingAs($this->parentA);
        $analyticsResponse = $this->getJson("/api/v1/parental/children/{$this->childMemberA->id}/screen-time");

        $analyticsResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('screen_time.total_seconds', 3000)
            ->assertJsonPath('screen_time.total_minutes', 50);
    }

    /**
     * Test 8: Policy restrictions (curfew, screen limit, blacklist).
     */
    public function test_parent_can_upsert_policy_restrictions(): void
    {
        Sanctum::actingAs($this->parentA);

        $response = $this->postJson("/api/v1/parental/families/{$this->familyA->id}/restrictions", [
            'child_member_id' => $this->childMemberA->id,
            'restriction_type' => 'daily_time_limit',
            'configuration' => ['daily_minutes' => 120],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('restriction.policy_version', 1);

        // Update the policy again -> version increments to 2
        $updateResponse = $this->postJson("/api/v1/parental/families/{$this->familyA->id}/restrictions", [
            'child_member_id' => $this->childMemberA->id,
            'restriction_type' => 'daily_time_limit',
            'configuration' => ['daily_minutes' => 90],
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('restriction.policy_version', 2);
    }

    /**
     * Test 9: Remote command lifecycle (queued -> polled -> acknowledged).
     */
    public function test_remote_command_lifecycle(): void
    {
        /** @var DevicePairingService $pairingService */
        $pairingService = app(DevicePairingService::class);
        $codeData = $pairingService->generatePairingCode($this->familyA, $this->childMemberA, $this->parentA);
        $pairResult = $pairingService->pairDevice(
            plainCode: $codeData['code'],
            deviceUuid: (string) Str::uuid(),
            deviceName: "Charlie's Phone"
        );
        $deviceToken = $pairResult['device_token'];
        $device = $pairResult['device'];

        // 1. Parent dispatches lock command
        Sanctum::actingAs($this->parentA);
        $dispatchResponse = $this->postJson("/api/v1/parental/devices/{$device->id}/commands", [
            'command_type' => 'device_lock',
            'payload' => ['message' => 'Dinnertime, please put phone away.'],
        ]);

        $dispatchResponse->assertCreated()
            ->assertJsonPath('success', true);

        $commandUuid = $dispatchResponse->json('command.command_uuid');

        // 2. Child polls pending commands
        $pollResponse = $this->withHeader('Authorization', "Bearer {$deviceToken}")
            ->getJson('/api/v1/parental/device/commands/pending');

        $pollResponse->assertOk()
            ->assertJsonPath('commands.0.command_uuid', $commandUuid);

        // 3. Child acknowledges completion
        $ackResponse = $this->withHeader('Authorization', "Bearer {$deviceToken}")
            ->postJson("/api/v1/parental/device/commands/{$commandUuid}/ack", [
                'status' => 'completed',
            ]);

        $ackResponse->assertOk()
            ->assertJsonPath('status', 'completed');

        $this->assertDatabaseHas('remote_commands', [
            'command_uuid' => $commandUuid,
            'command_status' => 'completed',
        ]);
    }

    /**
     * Test 10: Consent requests flow (child submits -> parent approves).
     */
    public function test_consent_request_flow(): void
    {
        /** @var DevicePairingService $pairingService */
        $pairingService = app(DevicePairingService::class);
        $codeData = $pairingService->generatePairingCode($this->familyA, $this->childMemberA, $this->parentA);
        $pairResult = $pairingService->pairDevice(
            plainCode: $codeData['code'],
            deviceUuid: (string) Str::uuid(),
            deviceName: "Charlie's Device"
        );
        $deviceToken = $pairResult['device_token'];

        // Child submits request for 30 minutes extra screen-time
        $childReq = $this->withHeader('Authorization', "Bearer {$deviceToken}")
            ->postJson('/api/v1/parental/device/consent-requests', [
                'request_type' => 'extra_time',
                'requested_value' => '30',
                'reason' => 'Finishing science project homework',
            ]);

        $childReq->assertCreated()
            ->assertJsonPath('success', true);

        $requestId = $childReq->json('request_id');

        // Parent approves request
        Sanctum::actingAs($this->parentA);
        $parentRes = $this->postJson("/api/v1/parental/consent-requests/{$requestId}/respond", [
            'status' => 'approved',
            'response_note' => 'Approved for homework.',
        ]);

        $parentRes->assertOk()
            ->assertJsonPath('consent_request.request_status', 'approved');
    }

    /**
     * Test 11: Security alert and parental resolution.
     */
    public function test_security_alert_and_resolution(): void
    {
        /** @var DevicePairingService $pairingService */
        $pairingService = app(DevicePairingService::class);
        $codeData = $pairingService->generatePairingCode($this->familyA, $this->childMemberA, $this->parentA);
        $pairResult = $pairingService->pairDevice(
            plainCode: $codeData['code'],
            deviceUuid: (string) Str::uuid(),
            deviceName: "Charlie's Tablet"
        );
        $deviceToken = $pairResult['device_token'];

        // Child agent reports usage access disabled
        $alertReq = $this->withHeader('Authorization', "Bearer {$deviceToken}")
            ->postJson('/api/v1/parental/device/alerts', [
                'alert_type' => 'usage_access_revoked',
                'description' => 'Usage Stats permission was disabled in system settings.',
                'severity' => 'high',
            ]);

        $alertReq->assertCreated()
            ->assertJsonPath('success', true);

        // Parent views alerts
        Sanctum::actingAs($this->parentA);
        $listRes = $this->getJson("/api/v1/parental/families/{$this->familyA->id}/alerts");

        $listRes->assertOk()
            ->assertJsonPath('alerts.0.alert_type', 'usage_access_revoked');
    }

    /**
     * Test 12: Device revocation immediately invalidates credentials.
     */
    public function test_device_revocation_rejects_further_access(): void
    {
        /** @var DevicePairingService $pairingService */
        $pairingService = app(DevicePairingService::class);
        $codeData = $pairingService->generatePairingCode($this->familyA, $this->childMemberA, $this->parentA);
        $pairResult = $pairingService->pairDevice(
            plainCode: $codeData['code'],
            deviceUuid: (string) Str::uuid(),
            deviceName: "Charlie's Old Phone"
        );
        $deviceToken = $pairResult['device_token'];
        $device = $pairResult['device'];

        // Parent revokes device
        Sanctum::actingAs($this->parentA);
        $revokeRes = $this->postJson("/api/v1/parental/devices/{$device->id}/revoke");
        $revokeRes->assertOk();

        // Subsequent child device request fails with 401 Unauthorized
        $unauthRes = $this->withHeader('Authorization', "Bearer {$deviceToken}")
            ->getJson('/api/v1/parental/device/policies');

        $unauthRes->assertUnauthorized();
    }

    /**
     * Test 13: Cross-family IDOR protection rejects unauthorized command dispatch and revocation.
     */
    public function test_cross_family_device_control_is_forbidden(): void
    {
        /** @var DevicePairingService $pairingService */
        $pairingService = app(DevicePairingService::class);
        $codeData = $pairingService->generatePairingCode($this->familyA, $this->childMemberA, $this->parentA);
        $pairResult = $pairingService->pairDevice(
            plainCode: $codeData['code'],
            deviceUuid: (string) Str::uuid(),
            deviceName: "Charlie's Device"
        );
        $device = $pairResult['device'];

        // Parent B (unauthorized stranger) attempts to dispatch command
        Sanctum::actingAs($this->parentB);
        $cmdRes = $this->postJson("/api/v1/parental/devices/{$device->id}/commands", [
            'command_type' => 'device_lock',
        ]);
        $cmdRes->assertForbidden();

        // Parent B attempts to revoke device
        $revokeRes = $this->postJson("/api/v1/parental/devices/{$device->id}/revoke");
        $revokeRes->assertForbidden();
    }

    /**
     * Test 14: Cross-family IDOR protection rejects unauthorized consent response.
     */
    public function test_cross_family_consent_response_is_forbidden(): void
    {
        /** @var DevicePairingService $pairingService */
        $pairingService = app(DevicePairingService::class);
        $codeData = $pairingService->generatePairingCode($this->familyA, $this->childMemberA, $this->parentA);
        $pairResult = $pairingService->pairDevice(
            plainCode: $codeData['code'],
            deviceUuid: (string) Str::uuid(),
            deviceName: "Charlie's Device"
        );
        $deviceToken = $pairResult['device_token'];

        $childReq = $this->withHeader('Authorization', "Bearer {$deviceToken}")
            ->postJson('/api/v1/parental/device/consent-requests', [
                'request_type' => 'extra_time',
                'requested_value' => '15',
            ]);
        $requestId = $childReq->json('request_id');

        // Parent B attempts to respond
        Sanctum::actingAs($this->parentB);
        $response = $this->postJson("/api/v1/parental/consent-requests/{$requestId}/respond", [
            'status' => 'approved',
        ]);
        $response->assertForbidden();
    }

    /**
     * Test 15: Policy Engine consolidates limits, curfews, and app blacklists for child device.
     */
    public function test_policy_engine_consolidates_active_rules(): void
    {
        /** @var DevicePairingService $pairingService */
        $pairingService = app(DevicePairingService::class);
        $codeData = $pairingService->generatePairingCode($this->familyA, $this->childMemberA, $this->parentA);
        $pairResult = $pairingService->pairDevice(
            plainCode: $codeData['code'],
            deviceUuid: (string) Str::uuid(),
            deviceName: "Charlie's Supervised Phone"
        );
        $deviceToken = $pairResult['device_token'];

        Sanctum::actingAs($this->parentA);

        // 1. Bedtime curfew
        $this->postJson("/api/v1/parental/families/{$this->familyA->id}/restrictions", [
            'child_member_id' => $this->childMemberA->id,
            'restriction_type' => 'bedtime_curfew',
            'configuration' => ['start_time' => '20:30', 'end_time' => '06:30'],
        ])->assertOk();

        // 2. Blacklist TikTok
        $this->postJson("/api/v1/parental/families/{$this->familyA->id}/restrictions", [
            'child_member_id' => $this->childMemberA->id,
            'restriction_type' => 'app_blacklist',
            'application_package' => 'com.zhiliaoapp.musically',
            'configuration' => ['reason' => 'Restricted for age compliance'],
        ])->assertOk();

        // Query active policies as child device
        $policyRes = $this->withHeader('Authorization', "Bearer {$deviceToken}")
            ->getJson('/api/v1/parental/device/policies');

        $policyRes->assertOk()
            ->assertJsonPath('policies.bedtime_curfew.start', '20:30')
            ->assertJsonPath('policies.bedtime_curfew.end', '06:30')
            ->assertJsonPath('policies.blacklisted_packages.0', 'com.zhiliaoapp.musically');
    }

    /**
     * Test 16: Parent can update family configuration.
     */
    public function test_parent_can_update_family_details(): void
    {
        Sanctum::actingAs($this->parentA);

        $res = $this->putJson("/api/v1/parental/families/{$this->familyA->id}", [
            'name' => 'Alice Updated Family',
            'timezone' => 'America/Chicago',
        ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('family.name', 'Alice Updated Family');

        $this->assertDatabaseHas('families', [
            'id' => $this->familyA->id,
            'name' => 'Alice Updated Family',
        ]);
    }

    /**
     * Test 17: Parent can update and remove a family member.
     */
    public function test_parent_can_update_and_remove_member(): void
    {
        Sanctum::actingAs($this->parentA);

        // 1. Update member
        $updateRes = $this->putJson("/api/v1/parental/families/{$this->familyA->id}/members/{$this->childMemberA->id}", [
            'name' => 'Charles Child Jr.',
        ]);
        $updateRes->assertOk()
            ->assertJsonPath('member.name', 'Charles Child Jr.');

        // 2. Remove member
        $removeRes = $this->deleteJson("/api/v1/parental/families/{$this->familyA->id}/members/{$this->childMemberA->id}");
        $removeRes->assertOk();

        $this->assertDatabaseHas('family_members', [
            'id' => $this->childMemberA->id,
            'membership_status' => 'left',
        ]);
    }

    /**
     * Test 18: Activity and audit logs are recorded and queryable.
     */
    public function test_parent_can_view_activity_audit_logs(): void
    {
        Sanctum::actingAs($this->parentA);

        $res = $this->getJson("/api/v1/parental/families/{$this->familyA->id}/activity");

        $res->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotEmpty($res->json('activity_logs'));
    }

    /**
     * Test 19: Child device token rotation invalidates old token and validates new one.
     */
    public function test_child_device_token_rotation(): void
    {
        /** @var DevicePairingService $pairingService */
        $pairingService = app(DevicePairingService::class);
        $codeData = $pairingService->generatePairingCode($this->familyA, $this->childMemberA, $this->parentA);
        $pairResult = $pairingService->pairDevice(
            plainCode: $codeData['code'],
            deviceUuid: (string) Str::uuid(),
            deviceName: "Charlie's Rotating Phone"
        );
        $oldToken = $pairResult['device_token'];

        // Rotate token
        $rotateRes = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->postJson('/api/v1/parental/device/rotate-token');

        $rotateRes->assertOk()
            ->assertJsonPath('success', true);

        $newToken = $rotateRes->json('device_token');
        $this->assertNotEquals($oldToken, $newToken);

        // Old token is now rejected
        $oldAttempt = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/v1/parental/device/policies');
        $oldAttempt->assertUnauthorized();

        // New token succeeds
        $newAttempt = $this->withHeader('Authorization', "Bearer {$newToken}")
            ->getJson('/api/v1/parental/device/policies');
        $newAttempt->assertOk();
    }

    /**
     * Test 20: Child device application inventory synchronization.
     */
    public function test_child_device_app_inventory_sync(): void
    {
        /** @var DevicePairingService $pairingService */
        $pairingService = app(DevicePairingService::class);
        $codeData = $pairingService->generatePairingCode($this->familyA, $this->childMemberA, $this->parentA);
        $pairResult = $pairingService->pairDevice(
            plainCode: $codeData['code'],
            deviceUuid: (string) Str::uuid(),
            deviceName: "Charlie's App Inventory Tab"
        );
        $deviceToken = $pairResult['device_token'];

        $res = $this->withHeader('Authorization', "Bearer {$deviceToken}")
            ->postJson('/api/v1/parental/device/apps/inventory', [
                'apps' => [
                    ['package_name' => 'com.google.android.youtube', 'app_name' => 'YouTube', 'is_system_app' => true],
                    ['package_name' => 'com.duolingo', 'app_name' => 'Duolingo', 'is_system_app' => false],
                    ['package_name' => 'com.roblox.client', 'app_name' => 'Roblox', 'is_system_app' => false],
                ],
            ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_apps', 3);
    }

    /**
     * Test 21: Weekly screen-time summary breakdown.
     */
    public function test_parent_can_query_weekly_screen_time_breakdown(): void
    {
        Sanctum::actingAs($this->parentA);

        $res = $this->getJson("/api/v1/parental/children/{$this->childMemberA->id}/screen-time/weekly");

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('weekly_summary.child_id', $this->childMemberA->id);
    }

    /**
     * Test 22: Screen-time quota and remaining minutes calculation.
     */
    public function test_parent_can_query_remaining_quota(): void
    {
        Sanctum::actingAs($this->parentA);

        // Configure a 90-minute daily quota
        $this->postJson("/api/v1/parental/families/{$this->familyA->id}/restrictions", [
            'child_member_id' => $this->childMemberA->id,
            'restriction_type' => 'daily_time_limit',
            'configuration' => ['daily_minutes' => 90],
        ])->assertOk();

        $res = $this->getJson("/api/v1/parental/children/{$this->childMemberA->id}/screen-time/quota");

        $res->assertOk()
            ->assertJsonPath('quota.limit_minutes', 90)
            ->assertJsonPath('quota.is_quota_exceeded', false);
    }

    /**
     * Test 23: Remote command idempotency returns same command on retry.
     */
    public function test_remote_command_idempotency_returns_same_command(): void
    {
        /** @var DevicePairingService $pairingService */
        $pairingService = app(DevicePairingService::class);
        $codeData = $pairingService->generatePairingCode($this->familyA, $this->childMemberA, $this->parentA);
        $pairResult = $pairingService->pairDevice(
            plainCode: $codeData['code'],
            deviceUuid: (string) Str::uuid(),
            deviceName: "Charlie's Idempotent Phone"
        );
        $device = $pairResult['device'];

        $idempotencyKey = (string) Str::uuid();

        Sanctum::actingAs($this->parentA);

        // First attempt
        $res1 = $this->postJson("/api/v1/parental/devices/{$device->id}/commands", [
            'command_type' => 'sound_alarm',
            'idempotency_key' => $idempotencyKey,
        ]);
        $res1->assertCreated();
        $cmdId1 = $res1->json('command.id');

        // Retry with same idempotency key
        $res2 = $this->postJson("/api/v1/parental/devices/{$device->id}/commands", [
            'command_type' => 'sound_alarm',
            'idempotency_key' => $idempotencyKey,
        ]);
        $res2->assertCreated();
        $cmdId2 = $res2->json('command.id');

        $this->assertEquals($cmdId1, $cmdId2);
    }

    /**
     * Test 24: Parent Web Dashboard renders successfully.
     */
    public function test_parent_web_dashboard_renders_successfully(): void
    {
        $this->actingAs($this->parentA);

        $res = $this->get('/parental');

        $res->assertOk();
    }
}
